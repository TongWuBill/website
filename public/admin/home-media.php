<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/media.php';

require_login();

$allowed = ['jpg','jpeg','png','webp','gif','mp4','mov','webm'];
$upload_error = '';
$upload_ok    = false;

// ── Upload ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload') {
    if (empty($_FILES['media']['name'])) {
        $cl = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
        $upload_error = $cl > 0
            ? 'post_max_size exceeded — current: ' . ini_get('post_max_size')
            : 'No file received';
    } else {
        $file = $_FILES['media'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $upload_error = 'File type .' . $ext . ' not allowed';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $upload_error = 'Upload error code: ' . $file['error'];
        } else {
            $dir = get_home_media_path();
            if (!is_dir($dir)) mkdir($dir, 0775, true);
            if (!is_writable($dir)) {
                $upload_error = 'Upload folder not writable: ' . $dir;
            } else {
                $dest = $dir . '/' . time() . '_' . preg_replace('/[^a-z0-9._-]/i', '_', $file['name']);
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $upload_ok = true;
                } else {
                    $upload_error = 'move_uploaded_file failed';
                }
            }
        }
    }
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
        <p class="media-empty">No hero media yet.</p>
    <?php else: ?>
    <div class="media-grid">
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
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload">
        <div class="upload-row">
            <input type="file" name="media" accept="image/*,video/*">
            <button type="submit" class="upload-btn">Upload</button>
        </div>
        <p class="hint">Allowed: jpg, png, webp, gif, mp4, mov, webm</p>
    </form>
</div>

</body>
</html>
