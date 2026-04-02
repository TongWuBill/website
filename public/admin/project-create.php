<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/project-model.php';

require_login();

$errors = [];
$info = [
    'title'        => '',
    'subtitle'     => '',
    'year'         => '',
    'category'     => '',
    'skillset'     => '',
    'material'     => '',
    'exhibition'   => '',
    'location'     => '',
    'video_url'    => '',
    'is_published' => 1,
];

$default_sections = [
    ['label' => 'Concept',      'body' => ''],
    ['label' => 'Context',      'body' => ''],
    ['label' => 'Process',      'body' => ''],
    ['label' => 'Interaction',  'body' => ''],
    ['label' => 'Presentation', 'body' => ''],
];
$sections_data = $default_sections;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($info as $key => $v) {
        $info[$key] = trim($_POST[$key] ?? '');
    }
    $info['is_published'] = isset($_POST['is_published']) ? 1 : 0;

    if ($info['title'] === '') $errors['title'] = 'Title is required.';
    $info['slug'] = slugify($info['title']);

    $raw_sections  = $_POST['sections_json'] ?? '[]';
    $decoded       = json_decode($raw_sections, true);
    $sections_data = is_array($decoded) ? $decoded : $default_sections;

    if (empty($errors)) {
        $data             = $info;
        $data['sections'] = json_encode($sections_data, JSON_UNESCAPED_UNICODE);
        create_project($data);
        header('Location: /admin/dashboard.php?tab=projects');
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
    <title>New Project — Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; background: #f4f4f4; color: #222; padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        h1 { font-size: 1.3rem; }
        .group { background: #fff; border: 1px solid #ddd; padding: 1.5rem; max-width: 800px; margin-bottom: 1.5rem; }
        .group-title { font-size: 0.75rem; text-transform: uppercase; letter-spacing: .1em; color: #888; margin-bottom: 1.25rem; padding-bottom: 0.5rem; border-bottom: 1px solid #eee; font-weight: 600; }
        .field { margin-bottom: 1.1rem; }
        .field:last-child { margin-bottom: 0; }
        label { display: block; font-size: 0.83rem; font-weight: 600; margin-bottom: 0.3rem; color: #333; }
        input[type="text"], input[type="url"], textarea { width: 100%; padding: 0.45rem 0.6rem; border: 1px solid #ccc; font-size: 0.9rem; font-family: inherit; }
        input:focus, textarea:focus { outline: 2px solid #222; border-color: transparent; }
        textarea { resize: vertical; }
        .hint { font-size: 0.75rem; color: #888; margin-top: 0.25rem; }
        .error { font-size: 0.78rem; color: #c00; margin-top: 0.25rem; }
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1rem; }
        .checkbox-row { display: flex; align-items: center; gap: 0.5rem; }
        .checkbox-row label { margin: 0; font-weight: normal; }

        #sections-list { display: flex; flex-direction: column; gap: 1rem; }
        .section-row { border: 1px solid #ddd; background: #fafafa; }
        .section-row-head { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 0.75rem; background: #f0f0f0; border-bottom: 1px solid #ddd; }
        .section-row-head input { flex: 1; padding: 0.3rem 0.5rem; border: 1px solid #ccc; font-size: 0.85rem; font-weight: 600; background: #fff; }
        .section-row-head input:focus { outline: 2px solid #222; border-color: transparent; }
        .section-row textarea { width: 100%; padding: 0.6rem 0.75rem; border: none; font-size: 0.9rem; font-family: inherit; background: #fafafa; }
        .section-row textarea:focus { outline: 2px solid #222; }
        .btn-remove { padding: 0.25rem 0.6rem; background: none; border: 1px solid #ccc; color: #999; font-size: 0.75rem; cursor: pointer; flex-shrink: 0; }
        .btn-remove:hover { background: #fee; border-color: #f99; color: #c00; }
        .btn-add { display: inline-block; margin-top: 0.75rem; padding: 0.45rem 1rem; background: #fff; border: 1px dashed #aaa; color: #666; font-size: 0.85rem; cursor: pointer; }
        .btn-add:hover { background: #f0f0f0; }

        .actions { display: flex; gap: 0.75rem; margin-top: 0.5rem; }
        button[type="submit"] { padding: 0.5rem 1.4rem; background: #222; color: #fff; border: none; font-size: 0.9rem; cursor: pointer; }
        button[type="submit"]:hover { background: #444; }
        a.btn { padding: 0.4rem 0.9rem; font-size: 0.85rem; text-decoration: none; border: 1px solid #aaa; color: #555; background: #fff; }
        a.btn:hover { background: #222; color: #fff; border-color: #222; }
    </style>
</head>
<body>

<div class="header">
    <h1>New Project</h1>
    <a href="/admin/dashboard.php?tab=projects" class="btn">&larr; Projects</a>
</div>

<form method="POST" id="create-form">
<input type="hidden" name="sections_json" id="sections-json-input">

<!-- ── INFO ─────────────────────────────────────────────── -->
<div class="group">
    <div class="group-title">Info</div>

    <div class="field">
        <label>Title <span style="color:#c00">*</span></label>
        <input type="text" name="title" value="<?= iv('title', $info) ?>">
        <?php if (isset($errors['title'])): ?><p class="error"><?= hv($errors['title']) ?></p><?php endif; ?>
    </div>

    <div class="field">
        <label>Subtitle</label>
        <input type="text" name="subtitle" value="<?= iv('subtitle', $info) ?>" placeholder="e.g. An interactive installation for two voices">
    </div>

    <div class="two-col">
        <div class="field">
            <label>Year</label>
            <input type="text" name="year" value="<?= iv('year', $info) ?>" placeholder="e.g. 2024">
        </div>
        <div class="field">
            <label>Category</label>
            <input type="text" name="category" value="<?= iv('category', $info) ?>" placeholder="e.g. Installation">
        </div>
    </div>

    <div class="field">
        <label>Skillset</label>
        <input type="text" name="skillset" value="<?= iv('skillset', $info) ?>" placeholder="e.g. TouchDesigner, Arduino, Python">
    </div>

    <div class="field">
        <label>Materials</label>
        <input type="text" name="material" value="<?= iv('material', $info) ?>" placeholder="e.g. Projection, sensor, custom hardware">
    </div>

    <div class="two-col">
        <div class="field">
            <label>Exhibition</label>
            <input type="text" name="exhibition" value="<?= iv('exhibition', $info) ?>" placeholder="e.g. Thesis Show 2025">
        </div>
        <div class="field">
            <label>Location</label>
            <input type="text" name="location" value="<?= iv('location', $info) ?>" placeholder="e.g. Brooklyn, NY">
        </div>
    </div>

    <div class="field">
        <label>Video URL</label>
        <input type="url" name="video_url" value="<?= iv('video_url', $info) ?>" placeholder="https://vimeo.com/...">
        <p class="hint">Embed URL — shown as hero on the project page.</p>
    </div>

    <div class="field">
        <div class="checkbox-row">
            <input type="checkbox" id="is_published" name="is_published" value="1"
                <?= $info['is_published'] ? 'checked' : '' ?>>
            <label for="is_published">Published</label>
        </div>
    </div>
</div>

<!-- ── CONTENT ───────────────────────────────────────────── -->
<div class="group">
    <div class="group-title">Content — editable sections</div>

    <div id="sections-list">
        <?php foreach ($sections_data as $sec): ?>
        <div class="section-row">
            <div class="section-row-head">
                <input type="text" class="sec-label" value="<?= hv($sec['label']) ?>" placeholder="Section title">
                <button type="button" class="btn-remove" onclick="removeSection(this)">Remove</button>
            </div>
            <textarea class="sec-body" rows="4" placeholder="Write content here…"><?= hv($sec['body']) ?></textarea>
        </div>
        <?php endforeach; ?>
    </div>

    <button type="button" class="btn-add" onclick="addSection()">+ Add section</button>
</div>

<div class="actions">
    <button type="submit">Create Project</button>
    <a href="/admin/dashboard.php?tab=projects" class="btn">Cancel</a>
</div>

</form>

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
        <textarea class="sec-body" rows="4" placeholder="Write content here…"></textarea>`;
    list.appendChild(row);
    row.querySelector('.sec-label').focus();
}

function removeSection(btn) {
    if (document.querySelectorAll('.section-row').length <= 1) return;
    btn.closest('.section-row').remove();
}

document.getElementById('create-form').addEventListener('submit', function () {
    const rows = document.querySelectorAll('.section-row');
    const sections = [...rows].map(row => ({
        label: row.querySelector('.sec-label').value.trim(),
        body:  row.querySelector('.sec-body').value.trim(),
    })).filter(s => s.label !== '');
    document.getElementById('sections-json-input').value = JSON.stringify(sections);
});
</script>

</body>
</html>
