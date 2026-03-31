<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';

require_login();

// ── Required schema columns ───────────────────────────────────────────────────
$required_columns = [
    'year'         => 'TEXT',
    'category'     => 'TEXT',
    'cover_image'  => 'TEXT',
    'video_url'    => 'TEXT',
    'is_published' => 'INTEGER DEFAULT 1',
    'created_at'   => 'TEXT',
    'updated_at'   => 'TEXT',
    'edit_count'   => 'INTEGER DEFAULT 0',
];

// ── Run migration if requested ────────────────────────────────────────────────
$migration_results = [];
$migration_ran     = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'migrate') {
    $migration_ran = true;
    try {
        $db       = get_db();
        $existing = array_column(
            $db->query("PRAGMA table_info(projects)")->fetchAll(PDO::FETCH_ASSOC),
            'name'
        );
        foreach ($required_columns as $col => $def) {
            if (in_array($col, $existing, true)) {
                $migration_results[] = ['status' => 'skip', 'col' => $col, 'def' => $def];
            } else {
                $db->exec("ALTER TABLE projects ADD COLUMN $col $def");
                $migration_results[] = ['status' => 'added', 'col' => $col, 'def' => $def];
            }
        }
    } catch (Throwable $e) {
        $migration_results[] = ['status' => 'error', 'col' => '—', 'def' => $e->getMessage()];
    }
}

// ── Permissions & write tests ─────────────────────────────────────────────────
$path     = get_db_path();
$dir      = dirname($path);

$process_user = function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : get_current_user();
$process_uid  = function_exists('posix_geteuid')  ? posix_geteuid() : '?';

$exists       = file_exists($path);
$readable     = $exists && is_readable($path);
$writable     = $exists && is_writable($path);
$dir_writable = is_writable($dir);
$file_perms   = $exists       ? substr(sprintf('%o', fileperms($path)), -4) : '—';
$dir_perms    = is_dir($dir)  ? substr(sprintf('%o', fileperms($dir)),  -4) : '—';
$file_owner   = $exists      && function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($path))['name'] : '?';
$dir_owner    = is_dir($dir) && function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($dir))['name']  : '?';

// Directory write test
$write_test_ok  = false;
$write_test_err = '';
$tmp = $dir . '/_write_test_' . time() . '.tmp';
try {
    $r = file_put_contents($tmp, 'test');
    if ($r !== false) { $write_test_ok = true; unlink($tmp); }
    else { $write_test_err = 'file_put_contents returned false'; }
} catch (Throwable $e) { $write_test_err = $e->getMessage(); }

// SQLite write test
$sqlite_write_ok  = false;
$sqlite_write_err = '';
if ($exists) {
    try {
        $db = get_db();
        $db->exec("CREATE TABLE IF NOT EXISTS _write_test (id INTEGER PRIMARY KEY)");
        $db->exec("DROP TABLE _write_test");
        $sqlite_write_ok = true;
    } catch (Throwable $e) { $sqlite_write_err = $e->getMessage(); }
}

// ── Current columns ───────────────────────────────────────────────────────────
$columns  = [];
$col_names = [];
if ($exists) {
    try {
        $db      = get_db();
        $columns = $db->query("PRAGMA table_info(projects)")->fetchAll(PDO::FETCH_ASSOC);
        $col_names = array_column($columns, 'name');
    } catch (Throwable $e) {}
}

// Overall health
$needs_migration = !empty(array_diff(array_keys($required_columns), $col_names));
$healthy = $exists && $readable && $writable && $dir_writable && $sqlite_write_ok && !$needs_migration;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System — Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: monospace; background: #f4f4f4; color: #222; padding: 2rem; font-size: 14px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        h1 { font-size: 1.2rem; font-family: sans-serif; }
        h2 { font-size: 0.75rem; text-transform: uppercase; letter-spacing: .08em; color: #888;
             margin: 1.75rem 0 0.6rem; padding-bottom: 0.3rem; border-bottom: 1px solid #ddd; }
        a.btn { display: inline-block; padding: 0.4rem 0.9rem; font-size: 0.85rem; text-decoration: none;
                border: 1px solid #222; color: #222; background: #fff; font-family: sans-serif; }
        a.btn:hover { background: #222; color: #fff; }
        .status-bar { display: flex; align-items: center; gap: 0.75rem; background: #fff;
                      border: 1px solid #ddd; padding: 0.75rem 1rem; margin-bottom: 1.5rem; }
        .dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
        .dot-ok  { background: #28a745; }
        .dot-err { background: #c00; }
        .dot-warn { background: #f0a500; }
        table { border-collapse: collapse; background: #fff; border: 1px solid #ddd;
                width: 100%; max-width: 680px; margin-bottom: 0.25rem; }
        th, td { border-bottom: 1px solid #eee; padding: 0.4rem 0.75rem; text-align: left; }
        th { background: #f7f7f7; width: 220px; color: #555; font-weight: normal; }
        tr:last-child th, tr:last-child td { border-bottom: none; }
        .ok   { color: #155724; font-weight: bold; }
        .err  { color: #c00;    font-weight: bold; }
        .warn { color: #856404; font-weight: bold; }
        .path { word-break: break-all; background: #fff; padding: 0.4rem 0.6rem;
                border: 1px solid #ddd; max-width: 680px; margin-bottom: 0.5rem; }
        .cmd  { background: #1e1e1e; color: #d4d4d4; padding: 0.75rem 1rem; margin: 0.5rem 0;
                white-space: pre; overflow-x: auto; max-width: 680px; line-height: 1.6; }
        .migrate-btn { padding: 0.5rem 1.2rem; background: #222; color: #fff; border: none;
                       font-size: 0.9rem; cursor: pointer; font-family: sans-serif; }
        .migrate-btn:hover { background: #444; }
        .m-added { color: #155724; }
        .m-skip  { color: #888; }
        .m-error { color: #c00; }
        .col-missing { color: #c00; }
        .col-ok      { color: #155724; }
    </style>
</head>
<body>

<div class="header">
    <h1>System Health</h1>
    <a href="/admin/dashboard.php" class="btn">&larr; Dashboard</a>
</div>

<!-- ── Overall status ──────────────────────────────────────────────────── -->
<div class="status-bar">
    <div class="dot <?= $healthy ? 'dot-ok' : 'dot-err' ?>"></div>
    <span style="font-family:sans-serif;font-size:0.95rem">
        <?php if ($healthy): ?>
            <span class="ok">All systems operational</span>
        <?php elseif (!$sqlite_write_ok): ?>
            <span class="err">Database is read-only — create, edit, delete will fail</span>
        <?php elseif ($needs_migration): ?>
            <span class="warn">Schema is out of date — run migration below</span>
        <?php else: ?>
            <span class="err">Issues detected — see details below</span>
        <?php endif; ?>
    </span>
</div>

<!-- ── PHP process ────────────────────────────────────────────────────── -->
<h2>PHP Process</h2>
<table>
    <tr><th>Running as</th><td><?= htmlspecialchars($process_user) ?> (uid <?= htmlspecialchars($process_uid) ?>)</td></tr>
</table>

<!-- ── Database file ──────────────────────────────────────────────────── -->
<h2>Database File</h2>
<div class="path"><?= htmlspecialchars($path) ?></div>
<table>
    <tr><th>Exists</th>      <td><?= $exists   ? '<span class="ok">YES</span>'  : '<span class="err">NO</span>'  ?></td></tr>
    <tr><th>Owner</th>       <td><?= htmlspecialchars($file_owner) ?></td></tr>
    <tr><th>Permissions</th> <td><?= $file_perms ?></td></tr>
    <tr><th>Readable</th>    <td><?= $readable  ? '<span class="ok">YES</span>'  : '<span class="err">NO</span>'  ?></td></tr>
    <tr><th>Writable</th>    <td><?= $writable  ? '<span class="ok">YES</span>'  : '<span class="err">NO</span>'  ?></td></tr>
</table>

<!-- ── Directory ──────────────────────────────────────────────────────── -->
<h2>Database Directory</h2>
<div class="path"><?= htmlspecialchars($dir) ?></div>
<table>
    <tr><th>Owner</th>       <td><?= htmlspecialchars($dir_owner) ?></td></tr>
    <tr><th>Permissions</th> <td><?= $dir_perms ?></td></tr>
    <tr><th>Writable</th>    <td><?= $dir_writable ? '<span class="ok">YES</span>' : '<span class="err">NO</span>' ?></td></tr>
</table>

<!-- ── Write tests ────────────────────────────────────────────────────── -->
<h2>Write Tests</h2>
<table>
    <tr>
        <th>Directory write</th>
        <td><?= $write_test_ok   ? '<span class="ok">PASSED</span>'
                                 : '<span class="err">FAILED — ' . htmlspecialchars($write_test_err) . '</span>' ?></td>
    </tr>
    <tr>
        <th>SQLite write</th>
        <td><?= $sqlite_write_ok ? '<span class="ok">PASSED</span>'
                                 : '<span class="err">FAILED — ' . htmlspecialchars($sqlite_write_err) . '</span>' ?></td>
    </tr>
</table>

<?php if (!$sqlite_write_ok): ?>
<!-- ── Fix commands ───────────────────────────────────────────────────── -->
<h2>Fix: Permission Commands</h2>
<p style="margin-bottom:0.5rem;font-family:sans-serif;font-size:0.85rem">Run via SSH on your server:</p>
<div class="cmd">chown <?= htmlspecialchars($process_user) ?>:<?= htmlspecialchars($process_user) ?> <?= htmlspecialchars($path) ?>

chown <?= htmlspecialchars($process_user) ?>:<?= htmlspecialchars($process_user) ?> <?= htmlspecialchars($dir) ?>

chmod 664 <?= htmlspecialchars($path) ?>

chmod 775 <?= htmlspecialchars($dir) ?></div>
<?php endif; ?>

<!-- ── Schema migration ───────────────────────────────────────────────── -->
<h2>Schema Migration</h2>

<?php if ($migration_ran): ?>
<table style="margin-bottom:1rem">
    <?php foreach ($migration_results as $r): ?>
    <tr>
        <th style="width:80px">
            <?php if ($r['status'] === 'added'): ?>
                <span class="ok">added</span>
            <?php elseif ($r['status'] === 'skip'): ?>
                <span class="m-skip">skip</span>
            <?php else: ?>
                <span class="err">error</span>
            <?php endif; ?>
        </th>
        <td><?= htmlspecialchars($r['col']) ?> <span style="color:#aaa">(<?= htmlspecialchars($r['def']) ?>)</span></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<table style="margin-bottom:1rem">
    <tr><th style="width:220px">Required column</th><th>Status</th></tr>
    <?php foreach ($required_columns as $col => $def): ?>
    <tr>
        <td><?= htmlspecialchars($col) ?></td>
        <td>
            <?php if (in_array($col, $col_names, true)): ?>
                <span class="col-ok">present</span>
            <?php else: ?>
                <span class="col-missing">MISSING — run migration</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<form method="POST">
    <input type="hidden" name="action" value="migrate">
    <button type="submit" class="migrate-btn">Run Migration</button>
</form>

<!-- ── All current columns ────────────────────────────────────────────── -->
<?php if (!empty($columns)): ?>
<h2>All Columns in projects (<?= count($columns) ?>)</h2>
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
<?php endif; ?>

</body>
</html>
