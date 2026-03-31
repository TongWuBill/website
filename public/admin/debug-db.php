<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';

require_login();

$path      = get_db_path();
$exists    = file_exists($path);
$readable  = $exists && is_readable($path);
$writable  = $exists && is_writable($path);
$dir       = dirname($path);
$dir_write = is_writable($dir);

// Fetch columns if the file exists
$columns = [];
$error   = null;

if ($exists) {
    try {
        $db   = get_db();
        $rows = $db->query("PRAGMA table_info(projects)")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $columns[] = $row;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DB Debug</title>
    <style>
        body { font-family: monospace; padding: 2rem; background: #f4f4f4; color: #222; }
        h2 { margin: 1.5rem 0 0.5rem; font-size: 1rem; }
        .ok  { color: green; }
        .err { color: red; }
        table { border-collapse: collapse; background: #fff; margin-top: 0.5rem; }
        th, td { border: 1px solid #ccc; padding: 0.3rem 0.75rem; font-size: 0.85rem; text-align: left; }
        th { background: #eee; }
        .path { word-break: break-all; background: #fff; padding: 0.4rem 0.6rem; border: 1px solid #ccc; }
    </style>
</head>
<body>

<h1>Database Debug</h1>

<h2>DB file path</h2>
<div class="path"><?= htmlspecialchars($path) ?></div>

<h2>File exists</h2>
<?= $exists   ? '<span class="ok">YES</span>' : '<span class="err">NO — file not found</span>' ?>

<h2>File readable</h2>
<?= $readable  ? '<span class="ok">YES</span>' : '<span class="err">NO — web server cannot read the file</span>' ?>

<h2>File writable</h2>
<?= $writable  ? '<span class="ok">YES</span>' : '<span class="err">NO — web server cannot write to the file (INSERT/UPDATE/DELETE will fail)</span>' ?>

<h2>Directory writable</h2>
<div class="path"><?= htmlspecialchars($dir) ?></div>
<?= $dir_write ? '<span class="ok">YES</span>' : '<span class="err">NO — SQLite needs write access to the directory too (for journal files)</span>' ?>

<?php if ($error): ?>
    <h2>Connection error</h2>
    <p class="err"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if (!empty($columns)): ?>
<h2>projects table columns (<?= count($columns) ?> total)</h2>
<table>
    <tr>
        <th>#</th>
        <th>name</th>
        <th>type</th>
        <th>notnull</th>
        <th>default</th>
        <th>pk</th>
    </tr>
    <?php foreach ($columns as $col): ?>
    <tr>
        <td><?= htmlspecialchars($col['cid']) ?></td>
        <td><strong><?= htmlspecialchars($col['name']) ?></strong></td>
        <td><?= htmlspecialchars($col['type']) ?></td>
        <td><?= $col['notnull'] ? 'YES' : 'no' ?></td>
        <td><?= htmlspecialchars($col['dflt_value'] ?? '—') ?></td>
        <td><?= $col['pk'] ? 'YES' : 'no' ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php elseif ($exists && !$error): ?>
    <p class="err">Table 'projects' not found or has no columns.</p>
<?php endif; ?>

<p style="margin-top:2rem"><a href="/admin/dashboard.php">&larr; Back to Dashboard</a></p>

</body>
</html>
