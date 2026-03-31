<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/project-model.php';

require_login();

$id      = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$project = $id ? get_project_by_id($id) : false;

if (!$project) {
    http_response_code(404);
    echo '<p>Project not found. <a href="/admin/dashboard.php">Back to dashboard</a></p>';
    exit;
}

$errors = [];
$input = [
    'title'            => $project['title']            ?? '',
    'slug'             => $project['slug']             ?? '',
    'year'             => $project['year']             ?? '',
    'category'         => $project['category']         ?? '',
    'immersion'        => $project['immersion']        ?? '',
    'context'          => $project['context']          ?? '',
    'system_text'      => $project['system_text']      ?? '',
    'interaction_text' => $project['interaction_text'] ?? '',
    'material'         => $project['material']         ?? '',
    'reflection'       => $project['reflection']       ?? '',
    'video_url'        => $project['video_url']        ?? '',
    'is_published'     => $project['is_published']     ?? 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($input as $key => $_) {
        $input[$key] = trim($_POST[$key] ?? '');
    }
    $input['is_published'] = isset($_POST['is_published']) ? 1 : 0;

    if ($input['title'] === '') {
        $errors['title'] = 'Title is required.';
    }
    if ($input['slug'] === '') {
        $errors['slug'] = 'Slug is required.';
    } elseif (!preg_match('/^[a-z0-9-]+$/', $input['slug'])) {
        $errors['slug'] = 'Slug may only contain lowercase letters, numbers, and hyphens.';
    }

    if (empty($errors)) {
        update_project($id, $input);
        header('Location: /admin/dashboard.php');
        exit;
    }
}

function val(string $key, array $input): string {
    return htmlspecialchars($input[$key]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Project — Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; background: #f4f4f4; color: #222; padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        h1 { font-size: 1.3rem; }
        h2 { font-size: 0.85rem; text-transform: uppercase; letter-spacing: .06em; color: #888; margin: 1.5rem 0 0.75rem; border-bottom: 1px solid #eee; padding-bottom: 0.4rem; }
        a.btn { padding: 0.4rem 0.9rem; font-size: 0.85rem; text-decoration: none; border: 1px solid #222; color: #222; background: #fff; }
        a.btn:hover { background: #222; color: #fff; }
        form { background: #fff; border: 1px solid #ddd; padding: 1.5rem; max-width: 720px; }
        .field { margin-bottom: 1.1rem; }
        label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.3rem; }
        input[type="text"],
        input[type="url"],
        textarea { width: 100%; padding: 0.45rem 0.6rem; border: 1px solid #ccc; font-size: 0.9rem; font-family: inherit; }
        input[type="text"]:focus,
        input[type="url"]:focus,
        textarea:focus { outline: 2px solid #222; border-color: transparent; }
        textarea { resize: vertical; }
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1rem; }
        .hint { font-size: 0.75rem; color: #666; margin-top: 0.2rem; }
        .error { font-size: 0.8rem; color: #c00; margin-top: 0.2rem; }
        .checkbox-row { display: flex; align-items: center; gap: 0.5rem; }
        .checkbox-row label { margin: 0; font-weight: normal; }
        .actions { display: flex; gap: 0.75rem; margin-top: 1.5rem; align-items: center; }
        button[type="submit"] { padding: 0.5rem 1.2rem; background: #222; color: #fff; border: none; font-size: 0.9rem; cursor: pointer; }
        button[type="submit"]:hover { background: #444; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Edit Project <span style="color:#888;font-weight:normal;font-size:1rem">#<?= $id ?></span></h1>
        <a href="/admin/dashboard.php" class="btn">&larr; Back to Dashboard</a>
    </div>

    <form method="POST">

        <!-- ── Basic info ─────────────────────────────── -->
        <h2>Basic Info</h2>

        <div class="field">
            <label for="title">Title <span style="color:#c00">*</span></label>
            <input type="text" id="title" name="title" value="<?= val('title', $input) ?>">
            <?php if (isset($errors['title'])): ?>
                <p class="error"><?= htmlspecialchars($errors['title']) ?></p>
            <?php endif; ?>
        </div>

        <div class="field">
            <label for="slug">Slug <span style="color:#c00">*</span></label>
            <input type="text" id="slug" name="slug" value="<?= val('slug', $input) ?>">
            <p class="hint">Lowercase letters, numbers, hyphens only. Used in URLs.</p>
            <?php if (isset($errors['slug'])): ?>
                <p class="error"><?= htmlspecialchars($errors['slug']) ?></p>
            <?php endif; ?>
        </div>

        <div class="two-col">
            <div class="field">
                <label for="year">Year</label>
                <input type="text" id="year" name="year" value="<?= val('year', $input) ?>" placeholder="e.g. 2024">
            </div>
            <div class="field">
                <label for="category">Category</label>
                <input type="text" id="category" name="category" value="<?= val('category', $input) ?>">
            </div>
        </div>

        <div class="field">
            <label for="video_url">Video URL</label>
            <input type="url" id="video_url" name="video_url" value="<?= val('video_url', $input) ?>">
        </div>

        <div class="field">
            <div class="checkbox-row">
                <input type="checkbox" id="is_published" name="is_published" value="1"
                    <?= $input['is_published'] ? 'checked' : '' ?>>
                <label for="is_published">Published</label>
            </div>
        </div>

        <!-- ── Artistic content ───────────────────────── -->
        <h2>Artistic Content</h2>

        <div class="field">
            <label for="immersion">Immersion</label>
            <textarea id="immersion" name="immersion" rows="3"><?= val('immersion', $input) ?></textarea>
        </div>

        <div class="field">
            <label for="context">Context</label>
            <textarea id="context" name="context" rows="3"><?= val('context', $input) ?></textarea>
        </div>

        <div class="field">
            <label for="system_text">System</label>
            <textarea id="system_text" name="system_text" rows="3"><?= val('system_text', $input) ?></textarea>
        </div>

        <div class="field">
            <label for="interaction_text">Interaction</label>
            <textarea id="interaction_text" name="interaction_text" rows="3"><?= val('interaction_text', $input) ?></textarea>
        </div>

        <div class="field">
            <label for="material">Material</label>
            <textarea id="material" name="material" rows="3"><?= val('material', $input) ?></textarea>
        </div>

        <div class="field">
            <label for="reflection">Reflection</label>
            <textarea id="reflection" name="reflection" rows="3"><?= val('reflection', $input) ?></textarea>
        </div>

        <div class="actions">
            <button type="submit">Save Changes</button>
            <a href="/admin/dashboard.php" class="btn">Cancel</a>
        </div>

    </form>
</body>
</html>
