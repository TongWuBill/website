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
    'video_url'   => $exp['video_url']   ?? '',
];

// ── Handle media upload ───────────────────────────────────────
$upload_error = '';
$upload_ok    = false;

$is_ajax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    $allowed = ['jpg','jpeg','png','webp','gif','mp4','mov','webm','pdf','txt','doc','docx'];
    $dir = get_experiment_media_path((string) $id);
    if (!is_dir($dir)) mkdir($dir, 0775, true);

    // Normalize files — support both single (name=media) and multiple (name=media[])
    $raw = $_FILES['media'] ?? [];
    $entries = [];
    if (!empty($raw['name'])) {
        if (is_array($raw['name'])) {
            foreach ($raw['name'] as $i => $n) {
                $entries[] = ['name' => $n, 'tmp' => $raw['tmp_name'][$i], 'error' => $raw['error'][$i]];
            }
        } else {
            $entries[] = ['name' => $raw['name'], 'tmp' => $raw['tmp_name'], 'error' => $raw['error']];
        }
    }

    if (empty($entries)) {
        $cl = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
        $msg = $cl > 0 ? 'post_max_size exceeded (' . ini_get('post_max_size') . ')' : 'No file received';
        if ($is_ajax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>$msg]); exit; }
        header('Location: /admin/experiment-edit.php?id=' . $id . '&upload_error=' . urlencode($msg)); exit;
    }

    $uploaded = []; $errors = [];
    foreach ($entries as $entry) {
        $ext = strtolower(pathinfo($entry['name'], PATHINFO_EXTENSION));
        if ($entry['error'] !== UPLOAD_ERR_OK) { $errors[] = $entry['name'] . ': error code ' . $entry['error']; continue; }
        if (!in_array($ext, $allowed)) { $errors[] = $entry['name'] . ': type not allowed'; continue; }
        if (!is_writable($dir)) { $errors[] = 'Folder not writable'; break; }
        $dest = $dir . '/' . time() . '_' . preg_replace('/[^a-z0-9._-]/i', '_', $entry['name']);
        if (move_uploaded_file($entry['tmp'], $dest)) {
            $rel = '/uploads/experiments/' . $id . '/' . basename($dest);
            $uploaded[] = ['url' => $rel, 'name' => basename($dest), 'ext' => $ext];
        } else {
            $errors[] = $entry['name'] . ': move failed';
        }
    }

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => empty($errors), 'files' => $uploaded, 'errors' => $errors]);
        exit;
    }
    $upload_ok = !empty($uploaded);
    $upload_error = implode('; ', $errors);
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

    <div class="field">
        <label>Video / Embed URL</label>
        <input type="text" name="video_url" value="<?= iv('video_url', $fields) ?>" placeholder="https://www.youtube.com/watch?v=… or Vimeo URL">
        <p class="hint">YouTube or Vimeo — shown as the first media item in the modal. Leave blank if using uploaded files only.</p>
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

    <form id="exp-upload-form" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload">
        <div class="field" style="margin-bottom:0">
            <label>Upload files</label>
            <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap">
                <input type="file" name="media[]" accept="image/*,video/*,.pdf" multiple style="flex:1;min-width:0">
                <button type="submit" id="exp-upload-btn" style="padding:0.4rem 0.9rem;background:#fff;border:1px solid #999;font-size:0.82rem;cursor:pointer;font-family:inherit;white-space:nowrap">Upload</button>
                <span id="exp-upload-status" style="font-size:0.78rem"></span>
            </div>
            <p class="hint">Allowed: jpg, png, webp, gif, mp4, mov, webm, pdf — multiple files supported</p>
        </div>
    </form>

    <?php if (!empty($media)): ?>
    <div class="media-grid" id="exp-media-grid" style="margin-top:1rem">
    <?php else: ?>
    <div class="media-grid" id="exp-media-grid" style="margin-top:1rem;display:none">
    <?php endif; ?>
        <?php foreach ($media as $f): ?>
        <div class="media-item">
            <?php if (in_array($f['ext'], ['jpg','jpeg','png','webp','gif'])): ?>
                <img src="<?= hv($f['url']) ?>" alt="<?= hv($f['name']) ?>">
            <?php elseif (in_array($f['ext'], ['mp4','mov','webm'])): ?>
                <video src="<?= hv($f['url']) ?>" muted playsinline preload="metadata" style="width:100%;aspect-ratio:4/3;object-fit:cover;display:block"></video>
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
</div>

<script>
(function () {
    const form   = document.getElementById('exp-upload-form');
    const btn    = document.getElementById('exp-upload-btn');
    const status = document.getElementById('exp-upload-status');
    const grid   = document.getElementById('exp-media-grid');
    const IMG = ['jpg','jpeg','png','webp','gif'];
    const VID = ['mp4','mov','webm'];

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const fileInput = form.querySelector('input[type="file"]');
        if (!fileInput.files.length) return;
        btn.disabled = true;
        status.textContent = 'Uploading…'; status.style.color = '#888';

        const fd = new FormData();
        new FormData(form).forEach((val, key) => { if (key !== 'media[]') fd.append(key, val); });
        for (const f of fileInput.files) fd.append('media[]', f);

        try {
            const res  = await fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            });
            const data = await res.json();

            if (data.files && data.files.length) {
                grid.style.display = '';
                data.files.forEach(function (f) {
                    const item = document.createElement('div');
                    item.className = 'media-item';
                    if (IMG.includes(f.ext)) {
                        const img = document.createElement('img');
                        img.src = f.url; img.alt = f.name; item.appendChild(img);
                    } else if (VID.includes(f.ext)) {
                        const v = document.createElement('video');
                        v.src = f.url; v.muted = true; v.setAttribute('playsinline',''); v.setAttribute('preload','metadata');
                        v.style.cssText = 'width:100%;aspect-ratio:4/3;object-fit:cover;display:block';
                        item.appendChild(v);
                    } else {
                        const d = document.createElement('div');
                        d.className = 'media-ext'; d.textContent = f.ext; item.appendChild(d);
                    }
                    const n = document.createElement('div');
                    n.className = 'media-item-name'; n.textContent = f.name; item.appendChild(n);
                    // Delete button
                    const delForm = document.createElement('form');
                    delForm.method = 'POST';
                    delForm.addEventListener('submit', function(e) { if (!confirm('Delete this file?')) e.preventDefault(); });
                    delForm.innerHTML = '<input type="hidden" name="action" value="delete_file">'
                        + '<input type="hidden" name="filename" value="' + f.name.replace(/"/g,'&quot;') + '">'
                        + '<button type="submit" class="media-delete">✕</button>';
                    item.appendChild(delForm);
                    grid.appendChild(item);
                });
            }

            if (data.errors && data.errors.length) {
                status.textContent = '✗ ' + data.errors.join('; '); status.style.color = '#c00';
            } else {
                status.textContent = '✓ ' + (data.files?.length || 0) + ' file(s) uploaded';
                status.style.color = '#155724';
                fileInput.value = '';
                setTimeout(() => { status.textContent = ''; }, 3000);
            }
        } catch (err) {
            status.textContent = '✗ Network error'; status.style.color = '#c00';
        }
        btn.disabled = false;
    });
}());
</script>
</body>
</html>
