<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';

require_login();

$path     = get_db_path();
$dir      = dirname($path);

// Who is PHP running as?
$process_user  = function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : get_current_user();
$process_uid   = function_exists('posix_geteuid')  ? posix_geteuid() : '?';

// File info
$exists       = file_exists($path);
$readable     = $exists && is_readable($path);
$writable     = $exists && is_writable($path);
$dir_writable = is_writable($dir);
$file_perms   = $exists ? substr(sprintf('%o', fileperms($path)), -4) : '—';
$dir_perms    = file_exists($dir) ? substr(sprintf('%o', fileperms($dir)), -4) : '—';
$file_owner   = $exists && function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($path))['name'] : '?';
$dir_owner    = file_exists($dir) && function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($dir))['name'] : '?';

// Actual write test — try creating a temp file in the same directory
$write_test_path = $dir . '/_write_test_' . time() . '.tmp';
$write_test_ok   = false;
$write_test_err  = '';
try {
    $result = file_put_contents($write_test_path, 'test');
    if ($result !== false) {
        $write_test_ok = true;
        unlink($write_test_path);
    } else {
        $write_test_err = 'file_put_contents returned false';
    }
} catch (Throwable $e) {
    $write_test_err = $e->getMessage();
}

// Actual SQLite write test
$sqlite_write_ok  = false;
$sqlite_write_err = '';
if ($exists) {
    try {
        $db = get_db();
        $db->exec("CREATE TABLE IF NOT EXISTS _write_test (id INTEGER PRIMARY KEY)");
        $db->exec("DROP TABLE _write_test");
        $sqlite_write_ok = true;
    } catch (Throwable $e) {
        $sqlite_write_err = $e->getMessage();
    }
}

// Columns
$columns = [];
if ($exists && $sqlite_write_ok) {
    $columns = $db->query("PRAGMA table_info(projects)")->fetchAll(PDO::FETCH_ASSOC);
}

function row(string $label, bool $ok, string $yes = 'YES', string $no = 'NO — '): string {
    $val = $ok ? "<span class='ok'>$yes</span>" : "<span class='err'>$no</span>";
    return "<tr><td>$label</td><td>$val</td></tr>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DB Debug</title>
    <style>
        body { font-family: monospace; padding: 2rem; background: #f4f4f4; color: #222; font-size: 14px; }
        h2 { margin: 1.5rem 0 0.5rem; font-size: 0.9rem; text-transform: uppercase; letter-spacing: .05em; color: #888; }
        .ok  { color: #155724; font-weight: bold; }
        .err { color: #c00; font-weight: bold; }
        table { border-collapse: collapse; background: #fff; border: 1px solid #ddd; width: 100%; max-width: 680px; margin-bottom: 1rem; }
        th, td { border-bottom: 1px solid #eee; padding: 0.4rem 0.75rem; text-align: left; }
        th { background: #f0f0f0; width: 220px; }
        .path { word-break: break-all; background: #fff; padding: 0.4rem 0.6rem; border: 1px solid #ddd; max-width: 680px; margin-bottom: 0.5rem; }
        .cmd { background: #222; color: #eee; padding: 0.75rem 1rem; margin: 0.5rem 0 1rem; white-space: pre; overflow-x: auto; max-width: 680px; }
        a { color: #222; }
    </style>
</head>
<body>

<h1 style="margin-bottom:1.5rem">Database Debug</h1>

<h2>PHP Process</h2>
<table>
    <tr><th>Running as user</th><td><?= htmlspecialchars($process_user) ?></td></tr>
    <tr><th>UID</th><td><?= htmlspecialchars($process_uid) ?></td></tr>
</table>

<h2>Database File</h2>
<div class="path"><?= htmlspecialchars($path) ?></div>
<table>
    <tr><th>Exists</th><td><?= $exists   ? '<span class="ok">YES</span>' : '<span class="err">NO</span>' ?></td></tr>
    <tr><th>Owner</th><td><?= htmlspecialchars($file_owner) ?></td></tr>
    <tr><th>Permissions</th><td><?= $file_perms ?></td></tr>
    <tr><th>Readable</th><td><?= $readable  ? '<span class="ok">YES</span>' : '<span class="err">NO</span>' ?></td></tr>
    <tr><th>Writable (is_writable)</th><td><?= $writable  ? '<span class="ok">YES</span>' : '<span class="err">NO</span>' ?></td></tr>
</table>

<h2>Database Directory</h2>
<div class="path"><?= htmlspecialchars($dir) ?></div>
<table>
    <tr><th>Owner</th><td><?= htmlspecialchars($dir_owner) ?></td></tr>
    <tr><th>Permissions</th><td><?= $dir_perms ?></td></tr>
    <tr><th>Writable</th><td><?= $dir_writable ? '<span class="ok">YES</span>' : '<span class="err">NO</span>' ?></td></tr>
</table>

<h2>Write Tests</h2>
<table>
    <tr>
        <th>Directory write test</th>
        <td><?= $write_test_ok ? '<span class="ok">PASSED</span>' : '<span class="err">FAILED — ' . htmlspecialchars($write_test_err) . '</span>' ?></td>
    </tr>
    <tr>
        <th>SQLite write test</th>
        <td><?= $sqlite_write_ok ? '<span class="ok">PASSED</span>' : '<span class="err">FAILED — ' . htmlspecialchars($sqlite_write_err) . '</span>' ?></td>
    </tr>
</table>

<?php if (!$sqlite_write_ok): ?>
<h2>Fix Commands</h2>
<p style="margin-bottom:0.5rem">Run these on your server via SSH, replacing the path if needed:</p>
<div class="cmd">
# Give the PHP process user (<?= htmlspecialchars($process_user) ?>) write access:
chown <?= htmlspecialchars($process_user) ?>:<?= htmlspecialchars($process_user) ?> <?= htmlspecialchars($path) ?>

chown <?= htmlspecialchars($process_user) ?>:<?= htmlspecialchars($process_user) ?> <?= htmlspecialchars($dir) ?>

chmod 664 <?= htmlspecialchars($path) ?>

chmod 775 <?= htmlspecialchars($dir) ?>
</div>
<?php endif; ?>

<?php if (!empty($columns)): ?>
<h2>projects columns (<?= count($columns) ?>)</h2>
<table>
    <tr><th>name</th><th>type</th><th>default</th></tr>
    <?php foreach ($columns as $col): ?>
    <tr>
        <td><strong><?= htmlspecialchars($col['name']) ?></strong></td>
        <td><?= htmlspecialchars($col['type']) ?></td>
        <td><?= htmlspecialchars($col['dflt_value'] ?? '—') ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<p style="margin-top:1.5rem"><a href="/admin/dashboard.php">&larr; Back to Dashboard</a></p>

</body>
</html>
