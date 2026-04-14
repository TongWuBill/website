<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/experiment-model.php';

require_login();

$errors = [];
$fields = ['title' => '', 'category' => $_GET['category'] ?? '', 'date' => '', 'description' => '', 'video_url' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($fields as $key => $_) {
        $fields[$key] = trim($_POST[$key] ?? '');
    }
    if ($fields['title'] === '') $errors['title'] = 'Title is required.';

    if (empty($errors)) {
        $id = create_experiment($fields);
        header('Location: /admin/experiment-edit.php?id=' . $id . '&new=1');
        exit;
    }
}

function hv(string $v): string { return htmlspecialchars($v, ENT_QUOTES); }
function iv(string $key, array $arr): string { return hv((string)($arr[$key] ?? '')); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Experiment — Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; background: #f4f4f4; color: #222; padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        h1 { font-size: 1.3rem; }
        .group { background: #fff; border: 1px solid #ddd; padding: 1.5rem; max-width: 600px; margin-bottom: 1.5rem; }
        .group-title { font-size: 0.75rem; text-transform: uppercase; letter-spacing: .1em; color: #888; margin-bottom: 1.25rem; padding-bottom: 0.5rem; border-bottom: 1px solid #eee; font-weight: 600; }
        .field { margin-bottom: 1.1rem; }
        .field:last-child { margin-bottom: 0; }
        label { display: block; font-size: 0.83rem; font-weight: 600; margin-bottom: 0.3rem; color: #333; }
        input[type="text"] { width: 100%; padding: 0.45rem 0.6rem; border: 1px solid #ccc; font-size: 0.9rem; font-family: inherit; }
        input:focus { outline: 2px solid #222; border-color: transparent; }
        .hint { font-size: 0.75rem; color: #888; margin-top: 0.25rem; }
        .error { font-size: 0.78rem; color: #c00; margin-top: 0.25rem; }
        .actions { display: flex; gap: 0.75rem; margin-top: 0.5rem; align-items: center; }
        button[type="submit"] { padding: 0.5rem 1.4rem; background: #222; color: #fff; border: none; font-size: 0.9rem; cursor: pointer; }
        button[type="submit"]:hover { background: #444; }
        a.btn { padding: 0.4rem 0.9rem; font-size: 0.85rem; text-decoration: none; border: 1px solid #aaa; color: #555; background: #fff; }
        a.btn:hover { background: #222; color: #fff; border-color: #222; }
    </style>
</head>
<body>

<div class="header">
    <h1>New Experiment</h1>
    <a href="/admin/dashboard.php?tab=experiments" class="btn">&larr; Experiments</a>
</div>

<form method="POST">
<div class="group">
    <div class="group-title">Info</div>

    <div class="field">
        <label>Title <span style="color:#c00">*</span></label>
        <input type="text" name="title" value="<?= iv('title', $fields) ?>" autofocus>
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
        <p class="hint">YouTube or Vimeo — shown as the first media item in the modal.</p>
    </div>
</div>

<div class="actions">
    <button type="submit">Create &amp; Add Media</button>
    <a href="/admin/dashboard.php?tab=experiments" class="btn">Cancel</a>
</div>
</form>

</body>
</html>
