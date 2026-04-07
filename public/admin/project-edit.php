<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/project-model.php';
require_once __DIR__ . '/../../src/media.php';

require_login();

$id      = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$project = $id ? get_project_by_id($id) : false;

if (!$project) {
    http_response_code(404);
    echo '<p>Project not found. <a href="/admin/dashboard.php?tab=projects">Back to dashboard</a></p>';
    exit;
}

// ── Helpers ───────────────────────────────────────────────────
function load_sections(array $project): array {
    if (!empty($project['sections'])) {
        $decoded = json_decode($project['sections'], true);
        if (is_array($decoded) && !empty($decoded)) {
            // Ensure every section has media_url key
            return array_map(function ($s) {
                return ['label' => $s['label'] ?? '', 'body' => $s['body'] ?? '', 'media_url' => $s['media_url'] ?? ''];
            }, $decoded);
        }
    }
    $legacy = [
        'Concept'      => $project['immersion']        ?? '',
        'Context'      => $project['context']          ?? '',
        'Process'      => $project['system_text']      ?? '',
        'Interaction'  => $project['interaction_text'] ?? '',
        'Presentation' => $project['reflection']       ?? '',
    ];
    $out = [];
    foreach ($legacy as $label => $body) {
        if (trim((string)$body) !== '') {
            $out[] = ['label' => $label, 'body' => (string) $body, 'media_url' => ''];
        }
    }
    return $out;
}

function section_key(string $label): string {
    return strtolower(preg_replace('/[^a-z0-9]/i', '', $label));
}

function hv(string $v): string { return htmlspecialchars($v, ENT_QUOTES); }
function iv(string $key, array $arr): string { return hv((string)($arr[$key] ?? '')); }

$allowed_exts = ['jpg','jpeg','png','webp','gif','mp4','mov','webm','pdf','txt','doc','docx'];

// ── Upload error helper ───────────────────────────────────────
function upload_error_msg(int $code): string {
    return match($code) {
        UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize in php.ini',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE in form',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION  => 'Upload blocked by PHP extension',
        default               => 'Unknown upload error (code ' . $code . ')',
    };
}

// Returns true if this is an AJAX upload request
function is_ajax(): bool {
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
}

function json_response(array $data): never {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Upload one file from a normalized file array entry, return ['url','name','ext'] or throw string error
function upload_one(string $slug, string $dest_name, string $tmp, string $orig_name, int $err_code, bool $replace_prefix = false): array {
    global $allowed_exts;
    if ($err_code !== UPLOAD_ERR_OK) throw new RuntimeException(upload_error_msg($err_code));
    $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_exts)) throw new RuntimeException('File type .' . $ext . ' not allowed');

    $dir = get_project_media_path($slug);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0775, true)) {
            $parent = dirname($dir);
            throw new RuntimeException('Cannot create folder — chmod 775 ' . $parent);
        }
    }
    if (!is_writable($dir)) throw new RuntimeException('Folder not writable: ' . $dir);

    if ($replace_prefix) {
        $prefix = explode('-', $dest_name)[0];
        foreach (scandir($dir) ?: [] as $f) {
            if (stripos($f, $prefix . '-') === 0 && is_file("$dir/$f")) unlink("$dir/$f");
        }
    }

    $dest = $dir . '/' . $dest_name . '-' . uniqid('', true) . '.' . $ext;
    if (!move_uploaded_file($tmp, $dest)) throw new RuntimeException('move_uploaded_file failed');

    $rel = '/uploads/projects/' . $slug . '/' . basename($dest);
    return ['url' => $rel, 'name' => basename($dest), 'ext' => $ext];
}


// Normalize $_FILES['media'] into a flat array of entries
function normalize_files(): array {
    $f = $_FILES['media'] ?? [];
    if (empty($f['name'])) return [];
    // Multiple files: name is array
    if (is_array($f['name'])) {
        $out = [];
        foreach ($f['name'] as $i => $name) {
            $out[] = ['name' => $name, 'tmp' => $f['tmp_name'][$i], 'error' => $f['error'][$i]];
        }
        return $out;
    }
    // Single file
    return [['name' => $f['name'], 'tmp' => $f['tmp_name'], 'error' => $f['error']]];
}

// ── Handle media upload (section) ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_section') {
    $sec_key = preg_replace('/[^a-z0-9]/', '', strtolower($_POST['sec_key'] ?? ''));
    if ($sec_key === '') {
        if (is_ajax()) json_response(['ok' => false, 'error' => 'Section key empty — save project first']);
        header('Location: /admin/project-edit.php?id=' . $id . '&tab=content&upload_error=' . urlencode('Section key empty'));
        exit;
    }
    $files = normalize_files(); $uploaded = []; $errors = [];
    foreach ($files as $entry) {
        try { $uploaded[] = upload_one($project['slug'], $sec_key, $entry['tmp'], $entry['name'], $entry['error']); }
        catch (RuntimeException $e) { $errors[] = $e->getMessage(); }
    }
    if (is_ajax()) json_response(['ok' => empty($errors), 'files' => $uploaded, 'errors' => $errors]);
    $loc = '/admin/project-edit.php?id=' . $id . '&tab=content';
    if ($errors) $loc .= '&upload_error=' . urlencode(implode('; ', $errors));
    header('Location: ' . $loc); exit;
}

// ── Handle media upload (thumbnail) ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_thumbnail') {
    $files = normalize_files(); $uploaded = []; $errors = []; $first = true;
    foreach ($files as $entry) {
        try { $uploaded[] = upload_one($project['slug'], 'thumb', $entry['tmp'], $entry['name'], $entry['error'], $first); $first = false; }
        catch (RuntimeException $e) { $errors[] = $e->getMessage(); }
    }
    if (is_ajax()) json_response(['ok' => empty($errors), 'files' => $uploaded, 'errors' => $errors]);
    $loc = '/admin/project-edit.php?id=' . $id . '&tab=content';
    if ($errors) $loc .= '&upload_error=' . urlencode(implode('; ', $errors));
    header('Location: ' . $loc); exit;
}

// ── Handle media upload (hero) ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_hero') {
    $files = normalize_files(); $uploaded = []; $errors = []; $first = true;
    foreach ($files as $entry) {
        try { $uploaded[] = upload_one($project['slug'], 'hero', $entry['tmp'], $entry['name'], $entry['error'], $first); $first = false; }
        catch (RuntimeException $e) { $errors[] = $e->getMessage(); }
    }
    if (is_ajax()) json_response(['ok' => empty($errors), 'files' => $uploaded, 'errors' => $errors]);
    $loc = '/admin/project-edit.php?id=' . $id . '&tab=content';
    if ($errors) $loc .= '&upload_error=' . urlencode(implode('; ', $errors));
    header('Location: ' . $loc); exit;
}

// ── Handle media upload (gallery) ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_gallery') {
    $files = normalize_files(); $uploaded = []; $errors = [];
    foreach ($files as $entry) {
        try { $uploaded[] = upload_one($project['slug'], 'gallery', $entry['tmp'], $entry['name'], $entry['error']); }
        catch (RuntimeException $e) { $errors[] = $e->getMessage(); }
    }
    if (is_ajax()) json_response(['ok' => empty($errors), 'files' => $uploaded, 'errors' => $errors]);
    $loc = '/admin/project-edit.php?id=' . $id . '&tab=content';
    if ($errors) $loc .= '&upload_error=' . urlencode(implode('; ', $errors));
    header('Location: ' . $loc); exit;
}

// ── Handle media delete ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_file') {
    $fname = basename($_POST['filename'] ?? '');
    if ($fname !== '') {
        $path = get_project_media_path($project['slug']) . '/' . $fname;
        if (is_file($path)) unlink($path);
    }
    if (is_ajax()) json_response(['ok' => true]);
    header('Location: /admin/project-edit.php?id=' . $id . '&tab=content');
    exit;
}

// ── Handle save ───────────────────────────────────────────────
$errors        = [];
$info = [
    'title'        => $project['title']        ?? '',
    'subtitle'     => $project['subtitle']     ?? '',
    'year'         => $project['year']         ?? '',
    'category'     => $project['category']     ?? '',
    'skillset'     => $project['skillset']     ?? '',
    'material'     => $project['material']     ?? '',
    'exhibition'   => $project['exhibition']   ?? '',
    'location'     => $project['location']     ?? '',
    'video_url'    => $project['video_url']    ?? '',
    'is_published' => $project['is_published'] ?? 1,
    'title_cn'     => $project['title_cn']     ?? '',
    'subtitle_cn'  => $project['subtitle_cn']  ?? '',
];
$sections_data = load_sections($project);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    foreach ($info as $key => $v) {
        $info[$key] = trim($_POST[$key] ?? $v);
    }
    $info['is_published'] = isset($_POST['is_published']) ? 1 : 0;
    $info['slug']         = slugify($info['title']);

    if ($info['title'] === '') $errors['title'] = 'Title is required.';

    $raw_sections  = $_POST['sections_json'] ?? '[]';
    $decoded       = json_decode($raw_sections, true);
    $sections_data = is_array($decoded) ? $decoded : load_sections($project);

    if (empty($errors)) {
        $data               = $info;
        $data['sections']   = json_encode($sections_data, JSON_UNESCAPED_UNICODE);
        // sections_cn: rebuild from posted CN bodies (labels stay same as EN)
        $cn_bodies = $_POST['section_cn_body'] ?? [];
        if (!empty($sections_data) && !empty(array_filter($cn_bodies, fn($v) => trim($v) !== ''))) {
            $cn_secs = [];
            foreach ($sections_data as $i => $sec) {
                $cn_secs[] = ['label' => $sec['label'], 'body' => trim($cn_bodies[$i] ?? '')];
            }
            $data['sections_cn'] = json_encode($cn_secs, JSON_UNESCAPED_UNICODE);
        } else {
            $data['sections_cn'] = $project['sections_cn'] ?? null;
        }
        update_project($id, $data);
        header('Location: /admin/dashboard.php?tab=projects');
        exit;
    }
}

// ── Build media buckets ───────────────────────────────────────
$all_media    = list_project_media($project['slug']);
$media_exts   = ['jpg','jpeg','png','webp','gif','mp4','webm','mov','pdf','txt','doc','docx'];
$img_exts     = ['jpg','jpeg','png','webp','gif'];

$section_keys  = array_map(fn($s) => section_key($s['label']), $sections_data);
$section_media = array_fill(0, count($sections_data), []);
$thumb_files   = [];
$hero_files    = [];
$gallery_files = [];

foreach ($all_media as $f) {
    if (!in_array($f['ext'], $media_exts)) continue;
    if (preg_match('/^thumb[\-_.]/i', $f['name'])) { $thumb_files[] = $f; continue; }
    if (preg_match('/^hero[\-_.]/i', $f['name'])) { $hero_files[] = $f; continue; }
    if (preg_match('/^gallery[\-_.]/i', $f['name'])) { $gallery_files[] = $f; continue; }
    $matched = false;
    foreach ($section_keys as $idx => $key) {
        if ($key === '') continue;
        if (preg_match('/^' . preg_quote($key, '/') . '[\-_.\d]/i', $f['name']) ||
            strcasecmp(pathinfo($f['name'], PATHINFO_FILENAME), $key) === 0) {
            $section_media[$idx][] = $f;
            $matched = true;
            break;
        }
    }
    if (!$matched) $gallery_files[] = $f;
}

$active_tab = ($_GET['tab'] ?? 'info') === 'content' ? 'content' : 'info';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit — <?= hv($project['title']) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; background: #f4f4f4; color: #222; padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; }
        h1 { font-size: 1.3rem; }

        /* tabs */
        .page-tabs { display: flex; gap: 0; margin-bottom: 1.5rem; border-bottom: 2px solid #ddd; }
        .page-tab { padding: 0.5rem 1.2rem; font-size: 0.85rem; cursor: pointer; color: #888; border: none;
                    background: none; border-bottom: 2px solid transparent; margin-bottom: -2px;
                    font-family: inherit; }
        .page-tab.active { color: #222; font-weight: 600; border-bottom-color: #222; }
        .page-tab:hover { color: #222; }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* groups */
        .group { background: #fff; border: 1px solid #ddd; padding: 1.5rem; max-width: 800px; margin-bottom: 1.5rem; }
        .group-title { font-size: 0.75rem; text-transform: uppercase; letter-spacing: .1em; color: #888;
                       margin-bottom: 1.25rem; padding-bottom: 0.5rem; border-bottom: 1px solid #eee; font-weight: 600; }
        .field { margin-bottom: 1.1rem; }
        .field:last-child { margin-bottom: 0; }
        label { display: block; font-size: 0.83rem; font-weight: 600; margin-bottom: 0.3rem; color: #333; }
        input[type="text"], input[type="url"], input[type="file"], textarea {
            width: 100%; padding: 0.45rem 0.6rem; border: 1px solid #ccc; font-size: 0.9rem; font-family: inherit; }
        input[type="file"] { padding: 0.3rem; }
        input:focus, textarea:focus { outline: 2px solid #222; border-color: transparent; }
        textarea { resize: vertical; }
        .hint { font-size: 0.75rem; color: #888; margin-top: 0.25rem; }
        .error { font-size: 0.78rem; color: #c00; margin-top: 0.25rem; }
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1rem; }
        .checkbox-row { display: flex; align-items: center; gap: 0.5rem; }
        .checkbox-row label { margin: 0; font-weight: normal; }

        /* actions */
        .actions { display: flex; gap: 0.75rem; margin-top: 0.5rem; align-items: center; max-width: 800px; }
        button[type="submit"] { padding: 0.5rem 1.4rem; background: #222; color: #fff; border: none;
                                font-size: 0.9rem; cursor: pointer; }
        button[type="submit"]:hover { background: #444; }
        a.btn, button.btn { display: inline-block; padding: 0.4rem 0.9rem; font-size: 0.85rem;
                            text-decoration: none; border: 1px solid #aaa; color: #555; background: #fff;
                            cursor: pointer; font-family: inherit; }
        a.btn:hover, button.btn:hover { background: #222; color: #fff; border-color: #222; }

        /* ── Content blocks ── */
        .content-block { background: #fff; border: 1px solid #ddd; max-width: 800px; margin-bottom: 1.25rem; }
        .content-block-head { padding: 0.65rem 1rem; background: #f5f5f5; border-bottom: 1px solid #ddd;
                              font-size: 0.8rem; font-weight: 600; color: #333; }
        .content-block-body { padding: 1rem; }

        /* upload widgets */
        .media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 0.6rem; margin-bottom: 0.75rem; }
        .media-item { position: relative; border: 1px solid #e0e0e0; background: #fafafa; }
        .media-item img, .media-item video { width: 100%; aspect-ratio: 4/3; object-fit: cover; display: block; }
        .media-item-ext { width: 100%; aspect-ratio: 4/3; display: flex; align-items: center;
                          justify-content: center; font-size: 0.7rem; color: #999; text-transform: uppercase;
                          background: #f0f0f0; }
        .media-item-name { padding: 0.25rem 0.4rem; font-size: 0.65rem; color: #888; overflow: hidden;
                           text-overflow: ellipsis; white-space: nowrap; }
        .media-del { position: absolute; top: 3px; right: 3px; background: rgba(180,0,0,.85); color: #fff;
                     border: none; font-size: 0.65rem; padding: 0.15rem 0.35rem; cursor: pointer; line-height: 1; }
        .media-del:hover { background: #c00; }
        .upload-error { background: #fee; border: 1px solid #f99; color: #c00; padding: 0.6rem 1rem;
                        font-size: 0.82rem; max-width: 800px; margin-bottom: 1rem; word-break: break-all; }
        .upload-error strong { display: block; margin-bottom: 0.25rem; font-size: 0.75rem;
                               text-transform: uppercase; letter-spacing: .08em; }
        .upload-row { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
        .upload-row input[type="file"] { flex: 1; min-width: 0; }
        .upload-btn { padding: 0.4rem 0.9rem; background: #fff; border: 1px solid #999; font-size: 0.82rem;
                      cursor: pointer; white-space: nowrap; font-family: inherit; color: #333; }
        .upload-btn:hover { background: #222; color: #fff; border-color: #222; }
        .upload-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .upload-status { font-size: 0.78rem; }
        .media-empty { font-size: 0.8rem; color: #bbb; font-style: italic; margin-bottom: 0.75rem; }
        .media-hint { font-size: 0.72rem; color: #aaa; margin-top: 0.4rem; }

        /* or separator */
        .or-sep { display: flex; align-items: center; gap: 0.75rem; margin: 0.9rem 0; color: #bbb; font-size: 0.75rem; }
        .or-sep::before, .or-sep::after { content: ''; flex: 1; height: 1px; background: #e8e8e8; }

        /* section cards */
        #sections-list { display: flex; flex-direction: column; gap: 1rem; }
        .section-row { border: 1px solid #ddd; background: #fafafa; }
        .section-row-head { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 0.75rem;
                            background: #f0f0f0; border-bottom: 1px solid #ddd; }
        .section-row-head input { flex: 1; padding: 0.3rem 0.5rem; border: 1px solid #ccc; font-size: 0.85rem;
                                  font-weight: 600; background: #fff; }
        .section-row-head input:focus { outline: 2px solid #222; border-color: transparent; }
        .sec-body-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
        .sec-col-left { padding: 0.75rem; border-right: 1px solid #e8e8e8; display: flex; flex-direction: column; gap: 0.6rem; }
        .sec-col-right { padding: 0.75rem; }
        .sec-col-left textarea { width: 100%; padding: 0.5rem; border: 1px solid #ccc; font-size: 0.875rem;
                                  font-family: inherit; resize: vertical; }
        .sec-col-left textarea:focus { outline: 2px solid #222; border-color: transparent; }
        .sec-col-left input[type="url"] { width: 100%; padding: 0.4rem 0.5rem; border: 1px solid #ccc;
                                           font-size: 0.82rem; font-family: inherit; }
        .sec-col-left input[type="url"]:focus { outline: 2px solid #222; border-color: transparent; }
        .sec-col-right-label { font-size: 0.72rem; font-weight: 600; color: #888; text-transform: uppercase;
                               letter-spacing: 0.08em; margin-bottom: 0.5rem; }
        .btn-remove { padding: 0.25rem 0.6rem; background: none; border: 1px solid #ccc; color: #999;
                      font-size: 0.75rem; cursor: pointer; flex-shrink: 0; }
        .btn-remove:hover { background: #fee; border-color: #f99; color: #c00; }
        .btn-add { display: inline-block; margin-top: 0.75rem; padding: 0.45rem 1rem; background: #fff;
                   border: 1px dashed #aaa; color: #666; font-size: 0.85rem; cursor: pointer; }
        .btn-add:hover { background: #f0f0f0; }
    </style>
</head>
<body>

<!-- The single save form. All metadata inputs reference it via form="f-save". -->
<form id="f-save" method="POST">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="sections_json" id="sections-json-input">
</form>

<div class="header">
    <h1>Edit <span style="color:#aaa;font-weight:normal">#<?= $id ?> — <?= hv($project['title']) ?></span></h1>
    <a href="/admin/dashboard.php?tab=projects" class="btn">&larr; Projects</a>
</div>

<?php if (!empty($_GET['upload_error'])): ?>
<div class="upload-error">
    <strong>Upload failed</strong>
    <?= htmlspecialchars($_GET['upload_error']) ?>
</div>
<?php endif; ?>

<div class="page-tabs">
    <button class="page-tab <?= $active_tab === 'info' ? 'active' : '' ?>"
            onclick="switchTab('info')">Info</button>
    <button class="page-tab <?= $active_tab === 'content' ? 'active' : '' ?>"
            onclick="switchTab('content')">Content</button>
</div>

<!-- ════════════════ TAB: CONTENT ════════════════════════════ -->
<div class="tab-panel <?= $active_tab === 'content' ? 'active' : '' ?>" id="tab-content">

<!-- ── Hero ── -->
<div class="content-block">
    <div class="content-block-head">Hero</div>
    <div class="content-block-body">
        <?php if (!empty($hero_files)): ?>
        <div class="media-grid" id="hero-grid">
            <?php foreach ($hero_files as $f): ?>
            <div class="media-item">
                <?php if (in_array($f['ext'], $img_exts)): ?>
                    <img src="<?= hv($f['url']) ?>" alt="">
                <?php elseif (in_array($f['ext'], ['mp4','webm','mov'])): ?>
                    <video src="<?= hv($f['url']) ?>"></video>
                <?php else: ?>
                    <div class="media-item-ext"><?= hv($f['ext']) ?></div>
                <?php endif; ?>
                <div class="media-item-name"><?= hv($f['name']) ?></div>
                <form method="POST">
                    <input type="hidden" name="action" value="delete_file">
                    <input type="hidden" name="filename" value="<?= hv($f['name']) ?>">
                    <button type="submit" class="media-del">✕</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="media-empty">No hero media yet.</p>
        <?php endif; ?>

        <form class="ajax-upload" data-action="upload_hero" data-grid="hero-grid" data-replace="true" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_hero">
            <div class="upload-row">
                <input type="file" name="media[]" accept="image/*,video/*">
                <button type="submit" class="upload-btn">Upload hero</button>
                <span class="upload-status"></span>
            </div>
            <p class="media-hint">Image or video. Uploading replaces the existing hero.</p>
        </form>

        <div class="or-sep">or embed URL</div>

        <div class="field">
            <label>Video / Embed URL</label>
            <input type="url" name="video_url" form="f-save"
                   value="<?= iv('video_url', $info) ?>"
                   placeholder="https://vimeo.com/... or https://youtube.com/watch?v=...">
            <p class="hint">YouTube or Vimeo URL. If set, overrides the uploaded hero file.</p>
        </div>
    </div>
</div>

<!-- ── Thumbnail ── -->
<div class="content-block">
    <div class="content-block-head">Thumbnail <span style="font-weight:400;color:#aaa;font-size:0.75rem">— shown on the Work list page</span></div>
    <div class="content-block-body">
        <?php if (!empty($thumb_files)): ?>
        <div class="media-grid" id="thumb-grid">
            <?php foreach ($thumb_files as $f): ?>
            <div class="media-item">
                <?php if (in_array($f['ext'], $img_exts)): ?>
                    <img src="<?= hv($f['url']) ?>" alt="">
                <?php elseif (in_array($f['ext'], ['mp4','mov','webm'])): ?>
                    <video src="<?= hv($f['url']) ?>" muted playsinline preload="metadata" style="width:100%;aspect-ratio:4/3;object-fit:cover;display:block"></video>
                <?php else: ?>
                    <div class="media-item-ext"><?= hv($f['ext']) ?></div>
                <?php endif; ?>
                <div class="media-item-name"><?= hv($f['name']) ?></div>
                <form method="POST">
                    <input type="hidden" name="action" value="delete_file">
                    <input type="hidden" name="filename" value="<?= hv($f['name']) ?>">
                    <button type="submit" class="media-del">✕</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="media-empty">No thumbnail yet. Falls back to first image in the folder.</p>
        <?php endif; ?>

        <form class="ajax-upload" data-action="upload_thumbnail" data-grid="thumb-grid" data-replace="true" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_thumbnail">
            <div class="upload-row">
                <input type="file" name="media[]" accept="image/*,video/*">
                <button type="submit" class="upload-btn">Upload thumbnail</button>
                <span class="upload-status"></span>
            </div>
            <p class="media-hint">Uploading replaces the existing thumbnail.</p>
        </form>
    </div>
</div>

<!-- ── Sections ── -->
<div class="content-block">
    <div class="content-block-head">Sections</div>
    <div class="content-block-body">
        <div id="sections-list">
            <?php
            $cn_sections_data = [];
            if (!empty($project['sections_cn'])) {
                $decoded_cn = json_decode($project['sections_cn'], true);
                if (is_array($decoded_cn)) $cn_sections_data = $decoded_cn;
            }
            foreach ($sections_data as $i => $sec):
                $key    = $section_keys[$i];
                $files  = $section_media[$i];
                $cn_body = $cn_sections_data[$i]['body'] ?? '';
            ?>
            <div class="section-row">
                <div class="section-row-head">
                    <input type="text" class="sec-label" value="<?= hv($sec['label']) ?>" placeholder="Section title">
                    <button type="button" class="btn-remove" onclick="removeSection(this)">Remove</button>
                </div>
                <div class="sec-body-cols">
                    <div class="sec-col-left">
                        <textarea class="sec-body" rows="5" placeholder="Write content here…"><?= hv($sec['body']) ?></textarea>
                        <textarea class="sec-body-cn" name="section_cn_body[<?= $i ?>]" rows="3"
                                  placeholder="中文内容（留空则显示英文）"
                                  form="f-save"
                                  style="margin-top:6px;font-size:0.8rem;color:#666;border-color:#e0e0e0"><?= hv($cn_body) ?></textarea>
                        <input type="url" class="sec-media-url"
                               value="<?= hv($sec['media_url'] ?? '') ?>"
                               placeholder="Embed URL (YouTube / Vimeo) — overrides uploaded files">
                    </div>
                    <div class="sec-col-right">
                        <div class="sec-col-right-label">Media</div>
                        <?php if (!empty($files)): ?>
                        <div class="media-grid" id="sec-grid-<?= hv($key) ?>">
                            <?php foreach ($files as $f): ?>
                            <div class="media-item">
                                <?php if (in_array($f['ext'], $img_exts)): ?>
                                    <img src="<?= hv($f['url']) ?>" alt="">
                                <?php elseif (in_array($f['ext'], ['mp4','webm','mov'])): ?>
                                    <video src="<?= hv($f['url']) ?>"></video>
                                <?php else: ?>
                                    <div class="media-item-ext"><?= hv($f['ext']) ?></div>
                                <?php endif; ?>
                                <div class="media-item-name"><?= hv($f['name']) ?></div>
                                <form method="POST" onsubmit="return confirm('Delete?')">
                                    <input type="hidden" name="action" value="delete_file">
                                    <input type="hidden" name="filename" value="<?= hv($f['name']) ?>">
                                    <button type="submit" class="media-del">✕</button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="media-empty">No files yet.</p>
                        <?php endif; ?>

                        <?php if ($key): ?>
                        <form class="ajax-upload" data-action="upload_section" data-grid="sec-grid-<?= hv($key) ?>" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload_section">
                            <input type="hidden" name="sec_key" value="<?= hv($key) ?>">
                            <div class="upload-row">
                                <input type="file" name="media[]" accept="image/*,video/*" multiple>
                                <button type="submit" class="upload-btn">Upload</button>
                                <span class="upload-status"></span>
                            </div>
                            <p class="media-hint">Saved as <code><?= hv($key) ?>-{ts}.ext</code></p>
                        </form>
                        <?php else: ?>
                        <p class="media-hint">Save the project first to enable uploads for this section.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <button type="button" class="btn-add" onclick="addSection()">+ Add section</button>
        <p class="hint" style="margin-top:0.6rem">Section title is used as the file prefix on upload. Renaming a section does not rename already-uploaded files.</p>
    </div>
</div>

<!-- ── Gallery ── -->
<div class="content-block">
    <div class="content-block-head">Gallery <span style="font-weight:400;color:#aaa;font-size:0.75rem">— peek carousel at the bottom of the project page</span></div>
    <div class="content-block-body">
        <?php if (!empty($gallery_files)): ?>
        <div class="media-grid" id="gallery-grid">
            <?php foreach ($gallery_files as $f): ?>
            <div class="media-item">
                <?php if (in_array($f['ext'], $img_exts)): ?>
                    <img src="<?= hv($f['url']) ?>" alt="">
                <?php elseif (in_array($f['ext'], ['mp4','webm','mov'])): ?>
                    <video src="<?= hv($f['url']) ?>"></video>
                <?php else: ?>
                    <div class="media-item-ext"><?= hv($f['ext']) ?></div>
                <?php endif; ?>
                <div class="media-item-name"><?= hv($f['name']) ?></div>
                <form method="POST">
                    <input type="hidden" name="action" value="delete_file">
                    <input type="hidden" name="filename" value="<?= hv($f['name']) ?>">
                    <button type="submit" class="media-del">✕</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="media-empty">No gallery files yet.</p>
        <?php endif; ?>

        <form class="ajax-upload" data-action="upload_gallery" data-grid="gallery-grid" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_gallery">
            <div class="upload-row">
                <input type="file" name="media[]" accept="image/*,video/*" multiple>
                <button type="submit" class="upload-btn">Upload to gallery</button>
                <span class="upload-status"></span>
            </div>
        </form>
    </div>
</div>

<div class="actions">
    <button type="submit" form="f-save">Save Changes</button>
    <a href="/admin/dashboard.php?tab=projects" class="btn">Cancel</a>
</div>

</div><!-- #tab-content -->

<!-- ════════════════ TAB: INFO ═══════════════════════════════ -->
<div class="tab-panel <?= $active_tab === 'info' ? 'active' : '' ?>" id="tab-info">

<div class="group">
    <div class="group-title">Info</div>

    <div class="field">
        <label>Title <span style="color:#c00">*</span></label>
        <input type="text" name="title" form="f-save" value="<?= iv('title', $info) ?>">
        <?php if (isset($errors['title'])): ?><p class="error"><?= hv($errors['title']) ?></p><?php endif; ?>
    </div>

    <div class="field">
        <label>Title 中文</label>
        <input type="text" name="title_cn" form="f-save" value="<?= iv('title_cn', $info) ?>" placeholder="中文标题（留空则显示英文）">
    </div>

    <div class="field">
        <label>Subtitle</label>
        <input type="text" name="subtitle" form="f-save" value="<?= iv('subtitle', $info) ?>"
               placeholder="e.g. An interactive installation for two voices">
    </div>

    <div class="field">
        <label>Subtitle 中文</label>
        <input type="text" name="subtitle_cn" form="f-save" value="<?= iv('subtitle_cn', $info) ?>" placeholder="中文副标题（留空则显示英文）">
    </div>

    <div class="two-col">
        <div class="field">
            <label>Year</label>
            <input type="text" name="year" form="f-save" value="<?= iv('year', $info) ?>" placeholder="e.g. 2024">
        </div>
        <div class="field">
            <label>Category</label>
            <input type="text" name="category" form="f-save" value="<?= iv('category', $info) ?>"
                   placeholder="e.g. Installation">
        </div>
    </div>

    <div class="field">
        <label>Skillset</label>
        <input type="text" name="skillset" form="f-save" value="<?= iv('skillset', $info) ?>"
               placeholder="e.g. TouchDesigner, Arduino, Python">
    </div>

    <div class="field">
        <label>Materials</label>
        <input type="text" name="material" form="f-save" value="<?= iv('material', $info) ?>"
               placeholder="e.g. Projection, sensor, custom hardware">
    </div>

    <div class="two-col">
        <div class="field">
            <label>Exhibition</label>
            <input type="text" name="exhibition" form="f-save" value="<?= iv('exhibition', $info) ?>"
                   placeholder="e.g. Thesis Show 2025">
        </div>
        <div class="field">
            <label>Location</label>
            <input type="text" name="location" form="f-save" value="<?= iv('location', $info) ?>"
                   placeholder="e.g. Brooklyn, NY">
        </div>
    </div>

    <div class="field">
        <div class="checkbox-row">
            <input type="checkbox" id="is_published" name="is_published" value="1"
                   form="f-save" <?= $info['is_published'] ? 'checked' : '' ?>>
            <label for="is_published">Published</label>
        </div>
    </div>
</div>

<div class="actions">
    <button type="submit" form="f-save">Save Changes</button>
    <a href="/admin/dashboard.php?tab=projects" class="btn">Cancel</a>
</div>

</div><!-- #tab-info -->

<script>
function addSection() {
    const list = document.getElementById('sections-list');
    const row  = document.createElement('div');
    row.className = 'section-row';
    row.innerHTML = `
        <div class="section-row-head">
            <input type="text" class="sec-label" placeholder="Section title">
            <button type="button" class="btn-remove" onclick="removeSection(this)">Remove</button>
        </div>
        <div class="sec-body-cols">
            <div class="sec-col-left">
                <textarea class="sec-body" rows="5" placeholder="Write content here…"></textarea>
                <input type="url" class="sec-media-url" placeholder="Embed URL (YouTube / Vimeo) — overrides uploaded files">
            </div>
            <div class="sec-col-right">
                <div class="sec-col-right-label">Media</div>
                <p class="media-hint">Save the project first to enable uploads for this section.</p>
            </div>
        </div>`;
    list.appendChild(row);
    row.querySelector('.sec-label').focus();
}

function removeSection(btn) {
    if (document.querySelectorAll('.section-row').length <= 1) return;
    btn.closest('.section-row').remove();
}

document.getElementById('f-save').addEventListener('submit', function () {
    const rows = document.querySelectorAll('.section-row');
    const sections = [...rows].map(row => ({
        label:     row.querySelector('.sec-label').value.trim(),
        body:      row.querySelector('.sec-body').value.trim(),
        media_url: row.querySelector('.sec-media-url') ? row.querySelector('.sec-media-url').value.trim() : '',
    })).filter(s => s.label !== '');
    document.getElementById('sections-json-input').value = JSON.stringify(sections);
});

function switchTab(name) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.page-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    event.currentTarget.classList.add('active');
    const url = new URL(window.location);
    url.searchParams.set('tab', name);
    history.replaceState(null, '', url);
}
</script>

<script>
// ── AJAX Delete ──────────────────────────────────────────────
document.addEventListener('submit', async function (e) {
    const form = e.target;
    if (form.querySelector('input[name="action"][value="delete_file"]') === null) return;
    e.preventDefault();
    if (!confirm('Delete?')) return;
    const fd = new FormData(form);
    try {
        await fetch(window.location.href, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        });
        // Remove the parent .media-item from the DOM
        const item = form.closest('.media-item');
        if (item) item.remove();
    } catch (err) {
        alert('Delete failed');
    }
});

// ── AJAX Upload ───────────────────────────────────────────────
(function () {
    const IMG_EXTS = ['jpg','jpeg','png','webp','gif'];
    const VID_EXTS = ['mp4','mov','webm'];

    function makeMediaItem(f) {
        const item = document.createElement('div');
        item.className = 'media-item';
        if (IMG_EXTS.includes(f.ext)) {
            const img = document.createElement('img');
            img.src = f.url; img.alt = '';
            item.appendChild(img);
        } else if (VID_EXTS.includes(f.ext)) {
            const v = document.createElement('video');
            v.src = f.url; v.muted = true; v.setAttribute('playsinline',''); v.setAttribute('preload','metadata');
            v.style.cssText = 'width:100%;aspect-ratio:4/3;object-fit:cover;display:block';
            item.appendChild(v);
        } else {
            const d = document.createElement('div');
            d.className = 'media-item-ext'; d.textContent = f.ext;
            item.appendChild(d);
        }
        const name = document.createElement('div');
        name.className = 'media-item-name'; name.textContent = f.name;
        item.appendChild(name);
        // Delete button (handled by AJAX delete listener above)
        const delForm = document.createElement('form');
        delForm.method = 'POST';
        delForm.innerHTML = '<input type="hidden" name="action" value="delete_file">'
            + '<input type="hidden" name="filename" value="' + f.name.replace(/"/g,'&quot;') + '">'
            + '<button type="submit" class="media-del">✕</button>';
        item.appendChild(delForm);
        return item;
    }

    function getOrCreateGrid(gridId, form) {
        let grid = document.getElementById(gridId);
        if (!grid) {
            // Remove "no files" placeholder if present
            const empty = form.closest('.content-block-body, .sec-col-right')
                             ?.querySelector('.media-empty');
            if (empty) empty.remove();
            grid = document.createElement('div');
            grid.className = 'media-grid';
            grid.id = gridId;
            form.before(grid);
        }
        return grid;
    }

    document.querySelectorAll('form.ajax-upload').forEach(function (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const fileInput = form.querySelector('input[type="file"]');
            if (!fileInput.files.length) return;

            const btn    = form.querySelector('button[type="submit"]');
            const status = form.querySelector('.upload-status');
            const gridId = form.dataset.grid;

            btn.disabled = true;
            status.textContent = 'Uploading…';
            status.style.color = '#888';

            // Build FormData manually to ensure all files are included
            const fd = new FormData();
            new FormData(form).forEach((val, key) => { if (key !== 'media[]') fd.append(key, val); });
            for (const file of fileInput.files) fd.append('media[]', file);

            try {
                const res = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd
                });
                const data = await res.json();

                if (data.files && data.files.length) {
                    const grid = getOrCreateGrid(gridId, form);
                    // For hero/thumbnail: clear old items before inserting replacement
                    if (form.dataset.replace === 'true') grid.innerHTML = '';
                    data.files.forEach(f => grid.appendChild(makeMediaItem(f)));
                }

                if (data.errors && data.errors.length) {
                    status.textContent = '✗ ' + data.errors.join('; ');
                    status.style.color = '#c00';
                } else {
                    status.textContent = '✓ Uploaded ' + (data.files?.length || 0) + ' file(s)';
                    status.style.color = '#155724';
                    fileInput.value = '';
                    setTimeout(() => { status.textContent = ''; }, 3000);
                }
            } catch (err) {
                status.textContent = '✗ Network error';
                status.style.color = '#c00';
            }

            btn.disabled = false;
        });
    });
}());
</script>
</body>
</html>
