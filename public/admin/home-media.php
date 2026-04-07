<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/media.php';

require_login();

$allowed = ['jpg','jpeg','png','webp','gif','mp4','mov','webm'];
$upload_error = '';
$upload_ok    = false;

$is_ajax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

// ── Upload ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload') {
    $dir = get_home_media_path();
    if (!is_dir($dir)) mkdir($dir, 0775, true);

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
        $msg = 'No file received';
        if ($is_ajax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>$msg]); exit; }
        header('Location: /admin/home-media.php?upload_error=' . urlencode($msg)); exit;
    }

    $uploaded = []; $errors = [];
    foreach ($entries as $entry) {
        $ext = strtolower(pathinfo($entry['name'], PATHINFO_EXTENSION));
        if ($entry['error'] !== UPLOAD_ERR_OK) { $errors[] = $entry['name'] . ': error ' . $entry['error']; continue; }
        if (!in_array($ext, $allowed)) { $errors[] = '.' . $ext . ' not allowed'; continue; }
        if (!is_writable($dir)) { $errors[] = 'Folder not writable'; break; }
        $dest = $dir . '/' . time() . '_' . preg_replace('/[^a-z0-9._-]/i', '_', $entry['name']);
        if (move_uploaded_file($entry['tmp'], $dest)) {
            $rel = '/uploads/home/' . basename($dest);
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
    $upload_ok    = !empty($uploaded);
    $upload_error = implode('; ', $errors);
    header('Location: /admin/home-media.php'
        . ($upload_ok ? '?uploaded=1' : '?upload_error=' . urlencode($upload_error)));
    exit;
}

// ── Delete ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $fname = basename($_POST['filename'] ?? '');
    if ($fname !== '') {
        $path = get_home_media_path() . '/' . $fname;
        if (is_file($path)) unlink($path);
    }
    header('Location: /admin/home-media.php');
    exit;
}

$media = list_home_media();
$img_exts = ['jpg','jpeg','png','webp','gif'];
$vid_exts = ['mp4','mov','webm'];
function hv(string $v): string { return htmlspecialchars($v, ENT_QUOTES); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Media — Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; background: #f4f4f4; color: #222; padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        h1 { font-size: 1.3rem; }
        .group { background: #fff; border: 1px solid #ddd; padding: 1.5rem; max-width: 700px; margin-bottom: 1.5rem; }
        .group-title { font-size: 0.75rem; text-transform: uppercase; letter-spacing: .1em; color: #888; margin-bottom: 1.25rem; padding-bottom: 0.5rem; border-bottom: 1px solid #eee; font-weight: 600; }
        .hint { font-size: 0.75rem; color: #888; margin-top: 0.25rem; }
        a.btn { padding: 0.4rem 0.9rem; font-size: 0.85rem; text-decoration: none; border: 1px solid #aaa; color: #555; background: #fff; display: inline-block; }
        a.btn:hover { background: #222; color: #fff; border-color: #222; }
        .upload-row { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; margin-top: 0.75rem; }
        .upload-row input[type="file"] { flex: 1; min-width: 0; padding: 0.3rem; border: 1px solid #ccc; font-size: 0.9rem; }
        .upload-btn { padding: 0.4rem 0.9rem; background: #fff; border: 1px solid #999; font-size: 0.82rem; cursor: pointer; white-space: nowrap; font-family: inherit; color: #333; }
        .upload-btn:hover { background: #222; color: #fff; border-color: #222; }
        .upload-ok  { color: #155724; font-size: 0.85rem; margin-bottom: 0.75rem; }
        .upload-err { color: #c00; font-size: 0.85rem; margin-bottom: 0.75rem; word-break: break-all; }
        .media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 0.75rem; margin-top: 1rem; }
        .media-item { position: relative; border: 1px solid #ddd; background: #fafafa; }
        .media-item img, .media-item video { width: 100%; aspect-ratio: 16/9; object-fit: cover; display: block; }
        .media-item-name { padding: 0.3rem 0.5rem; font-size: 0.7rem; color: #666; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .media-del { position: absolute; top: 4px; right: 4px; background: rgba(200,0,0,0.85); color: #fff; border: none; font-size: 0.7rem; padding: 0.15rem 0.4rem; cursor: pointer; }
        .media-del:hover { background: #c00; }
        .media-empty { font-size: 0.85rem; color: #aaa; font-style: italic; }
        .order-note { font-size: 0.78rem; color: #888; margin-top: 0.75rem; }
    </style>
</head>
<body>

<div class="header">
    <h1>Home Hero Media</h1>
    <a href="/admin/dashboard.php" class="btn">&larr; Dashboard</a>
</div>

<div class="group">
    <div class="group-title">Hero Images / Videos (<?= count($media) ?> file<?= count($media) !== 1 ? 's' : '' ?>)</div>

    <?php if (isset($_GET['uploaded'])): ?>
        <p class="upload-ok">File uploaded successfully.</p>
    <?php endif; ?>
    <?php $derr = $_GET['upload_error'] ?? ''; ?>
    <?php if ($derr): ?>
        <p class="upload-err"><?= hv($derr) ?></p>
    <?php endif; ?>

    <?php if (empty($media)): ?>
        <p class="media-empty" id="home-empty">No hero media yet.</p>
    <?php endif; ?>
    <div class="media-grid" id="home-grid"<?= empty($media) ? ' style="display:none"' : '' ?>>
        <?php foreach ($media as $f): ?>
        <div class="media-item">
            <?php if (in_array($f['ext'], $img_exts)): ?>
                <img src="<?= hv($f['url']) ?>" alt="">
            <?php elseif (in_array($f['ext'], $vid_exts)): ?>
                <video src="<?= hv($f['url']) ?>" muted playsinline preload="metadata"></video>
            <?php endif; ?>
            <div class="media-item-name"><?= hv($f['name']) ?></div>
            <form method="POST" onsubmit="return confirm('Delete this file?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="filename" value="<?= hv($f['name']) ?>">
                <button type="submit" class="media-del">✕</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <p class="order-note">Files are shown in alphabetical order — rename files to control display order (e.g. 01_image.jpg, 02_image.jpg).</p>

    <form id="home-upload-form" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload">
        <div class="upload-row">
            <input type="file" name="media[]" accept="image/*,video/*" multiple>
            <button type="submit" class="upload-btn" id="home-upload-btn">Upload</button>
            <span id="home-upload-status" style="font-size:0.78rem"></span>
        </div>
        <p class="hint">Allowed: jpg, png, webp, gif, mp4, mov, webm — multiple files supported</p>
    </form>
</div>

<script>
(function () {
    const form   = document.getElementById('home-upload-form');
    const btn    = document.getElementById('home-upload-btn');
    const status = document.getElementById('home-upload-status');
    const grid   = document.getElementById('home-grid');
    const empty  = document.getElementById('home-empty');
    const IMG = ['jpg','jpeg','png','webp','gif'];
    const VID = ['mp4','mov','webm'];

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const fileInput = form.querySelector('input[type="file"]');
        if (!fileInput.files.length) return;
        btn.disabled = true;
        status.textContent = 'Uploading…'; status.style.color = '#888';

        const fd = new FormData(form);
        fd.delete('media[]');
        for (const f of fileInput.files) fd.append('media[]', f);

        try {
            const res  = await fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            });
            const data = await res.json();

            if (data.files && data.files.length) {
                if (empty) empty.remove();
                grid.style.display = '';
                data.files.forEach(function (f) {
                    const item = document.createElement('div');
                    item.className = 'media-item';
                    if (IMG.includes(f.ext)) {
                        const img = document.createElement('img'); img.src = f.url; img.alt = ''; item.appendChild(img);
                    } else if (VID.includes(f.ext)) {
                        const v = document.createElement('video');
                        v.src = f.url; v.muted = true; v.setAttribute('playsinline',''); v.setAttribute('preload','metadata');
                        item.appendChild(v);
                    }
                    const n = document.createElement('div'); n.className = 'media-item-name'; n.textContent = f.name; item.appendChild(n);
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
