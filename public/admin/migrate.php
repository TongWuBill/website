<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';

require_login();

$required = [
    'year'         => 'TEXT',
    'category'     => 'TEXT',
    'cover_image'  => 'TEXT',
    'video_url'    => 'TEXT',
    'is_published' => 'INTEGER DEFAULT 1',
    'created_at'   => 'TEXT',
    'updated_at'   => 'TEXT',
    'edit_count'   => 'INTEGER DEFAULT 0',
    'sort_order'   => 'INTEGER DEFAULT 0',
];

$results = [];
$ran     = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ran = true;
    $db  = get_db();

    $existing = array_column(
        $db->query("PRAGMA table_info(projects)")->fetchAll(PDO::FETCH_ASSOC),
        'name'
    );

    foreach ($required as $column => $definition) {
        if (in_array($column, $existing, true)) {
            $results[] = ['status' => 'skip', 'column' => $column, 'def' => $definition];
        } else {
            $db->exec("ALTER TABLE projects ADD COLUMN $column $definition");
            $results[] = ['status' => 'added', 'column' => $column, 'def' => $definition];
        }
    }

    // Backfill sort_order = id for any rows where it is 0 or NULL
    $db->exec("UPDATE projects SET sort_order = id WHERE sort_order = 0 OR sort_order IS NULL");
    $results[] = ['status' => 'added', 'column' => 'backfill', 'def' => 'sort_order = id for existing rows'];
}

// Always read current columns for display
$db      = get_db();
$columns = $db->query("PRAGMA table_info(projects)")->fetchAll(PDO::FETCH_ASSOC);
$db_path = get_db_path();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Migrate DB — Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: monospace; background: #f4f4f4; color: #222; padding: 2rem; }
        h1 { font-size: 1.2rem; margin-bottom: 1.5rem; }
        h2 { font-size: 0.85rem; text-transform: uppercase; letter-spacing: .06em; color: #888; margin: 1.5rem 0 0.5rem; }
        .path { background: #fff; border: 1px solid #ddd; padding: 0.4rem 0.6rem; font-size: 0.85rem; word-break: break-all; margin-bottom: 1.5rem; }
        .result { padding: 0.25rem 0; font-size: 0.9rem; }
        .added { color: #155724; }
        .skip  { color: #888; }
        table { border-collapse: collapse; background: #fff; border: 1px solid #ddd; margin-top: 0.5rem; }
        th, td { border-bottom: 1px solid #eee; padding: 0.4rem 0.75rem; font-size: 0.85rem; text-align: left; }
        th { background: #f0f0f0; }
        .actions { margin-top: 1.5rem; display: flex; gap: 0.75rem; align-items: center; }
        button { padding: 0.5rem 1.2rem; background: #222; color: #fff; border: none; font-size: 0.9rem; cursor: pointer; }
        button:hover { background: #444; }
        a.btn { display: inline-block; padding: 0.4rem 0.9rem; font-size: 0.85rem; text-decoration: none; border: 1px solid #222; color: #222; background: #fff; }
        a.btn:hover { background: #222; color: #fff; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
    </style>
</head>
<body>

<div class="header">
    <h1>Database Migration</h1>
    <a href="/admin/dashboard.php" class="btn">&larr; Dashboard</a>
</div>

<h2>Database file</h2>
<div class="path"><?= htmlspecialchars($db_path) ?></div>

<?php if ($ran): ?>
<h2>Migration result</h2>
<?php foreach ($results as $r): ?>
    <div class="result <?= $r['status'] ?>">
        <?= $r['status'] === 'added' ? '+ added' : '  skip ' ?>
        &nbsp; <?= htmlspecialchars($r['column']) ?>
        <span style="color:#aaa">(<?= htmlspecialchars($r['def']) ?>)</span>
    </div>
<?php endforeach; ?>
<?php endif; ?>

<h2>Current columns in `projects` (<?= count($columns) ?>)</h2>
<table>
    <tr><th>name</th><th>type</th><th>default</th><th>pk</th></tr>
    <?php foreach ($columns as $col): ?>
    <tr>
        <td><strong><?= htmlspecialchars($col['name']) ?></strong></td>
        <td><?= htmlspecialchars($col['type']) ?></td>
        <td><?= htmlspecialchars($col['dflt_value'] ?? '—') ?></td>
        <td><?= $col['pk'] ? 'YES' : '' ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<div class="actions">
    <form method="POST">
        <button type="submit">Run Migration</button>
    </form>
</div>

</body>
</html>
