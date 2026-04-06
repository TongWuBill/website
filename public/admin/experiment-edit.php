<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/experiment-model.php';

require_login();

$id  = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$exp = $id ? get_experiment_by_id($id) : false;

if (!$exp) {
    http_response_code(404);
    echo '<p>Experiment not found. <a href="/admin/experiments.php">Back</a></p>';
    exit;
}

$errors = [];
$fields = [
    'title'       => $exp['title']       ?? '',
    'category'    => $exp['category']    ?? '',
    'date'        => $exp['date']        ?? '',
    'description' => $exp['description'] ?? '',
];

// ── Handle media upload ───────────────────────────────────────
$upload_error = '';
$upload_ok    = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    if (empty($_FILES['media']['name'])) {
        $cl = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
        $upload_error = $cl > 0
            ? 'post_max_size exceeded — PHP silently dropped the upload. Current post_max_size: ' . ini_get('post_max_size')
            : 'No file received';
    } else {
        $allowed = ['jpg','jpeg','png','webp','gif','mp4','mov','webm','pdf','txt','doc','docx'];
        $file    = $_FILES['media'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $upload_error = 'File type .' . $ext . ' not allowed';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $codes = [
                UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize (' . ini_get('upload_max_filesize') . ')',
                UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE in form',
                UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION  => 'Upload blocked by PHP extension',
            ];
            $upload_error = $codes[$file['error']] ?? 'Unknown upload error (code ' . $file['error'] . ')';
        } else {
            $dir = get_experiment_media_path((string) $id);
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0775, true)) {
                    $parent = dirname($dir);
                    $upload_error = 'Cannot create upload folder: ' . $dir
                        . ' — parent writable: ' . (is_writable($parent) ? 'yes' : 'NO — chmod 775 ' . $parent);
                }
            }
            if ($upload_error === '' && !is_writable($dir)) {
                $upload_error = 'Upload folder not writable: ' . $dir . ' — run: chmod 775 ' . $dir;
            }
            if ($upload_error === '') {
                $dest = $dir . '/' . time() . '_' . preg_replace('/[^a-z0-9._-]/i', '_', $file['name']);
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $upload_ok = true;
                } else {
                    $upload_error = 'move_uploaded_file failed — dest: ' . $dest;
                }
            }
        }
    }
    header('Location: /admin/experiment-edit.php?id=' . $id
        . ($upload_ok ? '&uploaded=1' : '')
        . ($upload_error ? '&upload_error=' . urlencode($upload_error) : ''));
    exit;
}

// ── Handle media delete ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_file') {
    $fname = basename($_POST['filename'] ?? '');
    if ($fname !== '') {
        $path = get_experiment_media_path((string) $id) . '/' . $fname;
        if (is_file($path)) unlink($path);
    }
    header('Location: /admin/experiment-edit.php?id=' . $id);
    exit;
}

// ── Handle info save ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    foreach ($fields as $key => $_) {
        $fields[$key] = trim($_POST[$key] ?? '');
    }
    if ($fields['title'] === '') $errors['title'] = 'Title is required.';

    if (empty($errors)) {
        update_experiment($id, $fields);
        header('Location: /admin/dashboard.php?tab=experiments');
        exit;
    }
}

$media = list_experiment_media($id);

function hv(string $v): string { return htmlspecialchars($v, ENT_QUOTES); }
function iv(string $key, array $arr): string { return hv((string)($arr[$key] ?? '')); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Experiment — Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; background: #f4f4f4; color: #222; padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        h1 { font-size: 1.3rem; }
        .group { background: #fff; border: 1px solid #ddd; padding: 1.5rem; max-width: 700px; margin-bottom: 1.5rem; }
        .group-title { font-size: 0.75rem; text-transform: uppercase; letter-spacing: .1em; color: #888; margin-bottom: 1.25rem; padding-bottom: 0.5rem; border-bottom: 1px solid #eee; font-weight: 600; }
        .field { margin-bottom: 1.1rem; }
        .field:last-child { margin-bottom: 0; }
        label { display: block; font-size: 0.83rem; font-weight: 600; margin-bottom: 0.3rem; color: #333; }
        input[type="text"], input[type="file"] { width: 100%; padding: 0.45rem 0.6rem; border: 1px solid #ccc; font-size: 0.9rem; font-family: inherit; }
        input[type="file"] { padding: 0.3rem; }
        input:focus { outline: 2px solid #222; border-color: transparent; }
        .hint { font-size: 0.75rem; color: #888; margin-top: 0.25rem; }
        .error { font-size: 0.78rem; color: #c00; margin-top: 0.25rem; }
        .actions { display: flex; gap: 0.75rem; align-items: center; }
        button[type="submit"] { padding: 0.5rem 1.4rem; background: #222; color: #fff; border: none; font-size: 0.9rem; cursor: pointer; }
        button[type="submit"]:hover { background: #444; }
        a.btn { padding: 0.4rem 0.9rem; font-size: 0.85rem; text-decoration: none; border: 1px solid #aaa; color: #555; background: #fff; }
        a.btn:hover { background: #222; color: #fff; border-color: #222; }

        /* Media grid */
        .media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 0.75rem; margin-top: 1rem; }
        .media-item { position: relative; border: 1px solid #ddd; background: #fafafa; }
        .media-item img { width: 100%; aspect-ratio: 4/3; object-fit: cover; display: block; }
        .media-item .media-ext { width: 100%; aspect-ratio: 4/3; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; color: #888; text-transform: uppercase; background: #f0f0f0; }
        .media-item-name { padding: 0.3rem 0.5rem; font-size: 0.7rem; color: #666; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .media-delete { position: absolute; top: 4px; right: 4px; background: rgba(200,0,0,0.8); color: #fff; border: none; font-size: 0.7rem; padding: 0.15rem 0.4rem; cursor: pointer; }
        .media-delete:hover { background: #c00; }
        .upload-ok { color: #155724; font-size: 0.85rem; margin-bottom: 0.75rem; }
        .upload-err { color: #c00; font-size: 0.85rem; margin-bottom: 0.75rem; }
    </style>
</head>
<body>

<div class="header">
    <h1>Edit <span style="color:#aaa;font-weight:normal">#<?= $id ?> — <?= hv($exp['title']) ?></span></h1>
    <a href="/admin/dashboard.php?tab=experiments" class="btn">&larr; Experiments</a>
</div>

<!-- ── INFO ─────────────────────────────────────────────── -->
<form method="POST">
<input type="hidden" name="action" value="save">
<div class="group">
    <div class="group-title">Info</div>

    <div class="field">
        <label>Title <span style="color:#c00">*</span></label>
        <input type="text" name="title" value="<?= iv('title', $fields) ?>">
        <?php if (isset($errors['title'])): ?><p class="error"><?= hv($errors['title']) ?></p><?php endif; ?>
    </div>

    <div class="field">
        <label>Category</label>
        <input type="text" name="category" value="<?= iv('category', $fields) ?>" placeholder="e.g. Sound Studies">
    </div>

    <div class="field">
        <label>Date</label>
        <input type="text" name="date" value="<?= iv('date', $fields) ?>" placeholder="e.g. 2024-11">
        <p class="hint">Format: YYYY-MM</p>
    </div>

    <div class="field">
        <label>Description</label>
        <textarea name="description" rows="3" placeholder="Short description shown in the modal…" style="width:100%;padding:0.45rem 0.6rem;border:1px solid #ccc;font-size:0.9rem;font-family:inherit;resize:vertical"><?= hv($fields['description']) ?></textarea>
    </div>
</div>

<div class="actions" style="margin-bottom:2rem">
    <button type="submit">Save Changes</button>
    <a href="/admin/dashboard.php?tab=experiments" class="btn">Cancel</a>
</div>
</form>

<!-- ── MEDIA ─────────────────────────────────────────────── -->
<div class="group">
    <div class="group-title">Media (<?= count($media) ?> file<?= count($media) !== 1 ? 's' : '' ?>)</div>

    <?php if (isset($_GET['uploaded'])): ?>
        <p class="upload-ok">File uploaded successfully.</p>
    <?php endif; ?>
    <?php $display_err = $upload_error ?: ($_GET['upload_error'] ?? ''); ?>
    <?php if ($display_err): ?>
        <p class="upload-err"><?= hv($display_err) ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload">
        <div class="field" style="margin-bottom:0">
            <label>Upload file</label>
            <input type="file" name="media" accept="image/*,video/*,.pdf">
            <p class="hint">Allowed: jpg, jpeg, png, webp, gif, mp4, mov, pdf</p>
        </div>
        <div class="actions" style="margin-top:0.75rem">
            <button type="submit">Upload</button>
        </div>
    </form>

    <?php if (!empty($media)): ?>
    <div class="media-grid">
        <?php foreach ($media as $f): ?>
        <div class="media-item">
            <?php if (in_array($f['ext'], ['jpg','jpeg','png','webp','gif'])): ?>
                <img src="<?= hv($f['url']) ?>" alt="<?= hv($f['name']) ?>">
            <?php else: ?>
                <div class="media-ext"><?= hv($f['ext']) ?></div>
            <?php endif; ?>
            <div class="media-item-name"><?= hv($f['name']) ?></div>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this file?')">
                <input type="hidden" name="action" value="delete_file">
                <input type="hidden" name="filename" value="<?= hv($f['name']) ?>">
                <button type="submit" class="media-delete">✕</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

</body>
</html>
