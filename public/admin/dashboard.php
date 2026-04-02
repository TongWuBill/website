<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/project-model.php';
require_once __DIR__ . '/../../src/experiment-model.php';

require_login();

$tab = $_GET['tab'] ?? 'system';
if (!in_array($tab, ['system', 'projects', 'experiments'])) $tab = 'system';

// ══════════════════════════════════════════════════════════════
// SYSTEM TAB — data
// ══════════════════════════════════════════════════════════════
$required_columns = [
    'year'         => 'TEXT',
    'subtitle'     => 'TEXT',
    'category'     => 'TEXT',
    'skillset'     => 'TEXT',
    'material'     => 'TEXT',
    'exhibition'   => 'TEXT',
    'location'     => 'TEXT',
    'sections'     => 'TEXT',
    'cover_image'  => 'TEXT',
    'video_url'    => 'TEXT',
    'is_published' => 'INTEGER DEFAULT 1',
    'created_at'   => 'TEXT',
    'updated_at'   => 'TEXT',
    'edit_count'   => 'INTEGER DEFAULT 0',
    'sort_order'   => 'INTEGER DEFAULT 0',
];

$migration_results = [];
$migration_ran     = false;

if ($tab === 'system' && $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action']) && $_POST['action'] === 'migrate') {
    $migration_ran = true;
    try {
        $db       = get_db();
        $existing = array_column(
            $db->query("PRAGMA table_info(projects)")->fetchAll(PDO::FETCH_ASSOC), 'name'
        );
        foreach ($required_columns as $col => $def) {
            if (in_array($col, $existing, true)) {
                $migration_results[] = ['status' => 'skip',  'col' => $col, 'def' => $def];
            } else {
                $db->exec("ALTER TABLE projects ADD COLUMN $col $def");
                $migration_results[] = ['status' => 'added', 'col' => $col, 'def' => $def];
            }
        }
        $db->exec("UPDATE projects SET sort_order = id WHERE sort_order = 0 OR sort_order IS NULL");
    } catch (Throwable $e) {
        $migration_results[] = ['status' => 'error', 'col' => '—', 'def' => $e->getMessage()];
    }
}

// Permissions
$db_path      = get_db_path();
$db_dir       = dirname($db_path);
$db_exists    = file_exists($db_path);
$db_readable  = $db_exists && is_readable($db_path);
$db_writable  = $db_exists && is_writable($db_path);
$dir_writable = is_writable($db_dir);

$write_test_ok  = false;
$write_test_err = '';
$tmp = $db_dir . '/_write_test_' . time() . '.tmp';
try {
    $r = file_put_contents($tmp, 'test');
    if ($r !== false) { $write_test_ok = true; unlink($tmp); }
    else { $write_test_err = 'file_put_contents returned false'; }
} catch (Throwable $e) { $write_test_err = $e->getMessage(); }

$sqlite_write_ok  = false;
$sqlite_write_err = '';
if ($db_exists) {
    try {
        $db = get_db();
        $db->exec("CREATE TABLE IF NOT EXISTS _write_test (id INTEGER PRIMARY KEY)");
        $db->exec("DROP TABLE _write_test");
        $sqlite_write_ok = true;
    } catch (Throwable $e) { $sqlite_write_err = $e->getMessage(); }
}

$col_names = [];
$all_columns = [];
if ($db_exists) {
    try {
        $db = get_db();
        $all_columns = $db->query("PRAGMA table_info(projects)")->fetchAll(PDO::FETCH_ASSOC);
        $col_names   = array_column($all_columns, 'name');
    } catch (Throwable $e) {}
}
$needs_migration = !empty(array_diff(array_keys($required_columns), $col_names));
$sys_healthy = $db_exists && $db_readable && $db_writable && $dir_writable && $sqlite_write_ok && !$needs_migration;

// ══════════════════════════════════════════════════════════════
// PROJECTS TAB — data
// ══════════════════════════════════════════════════════════════
$projects = ($tab === 'projects') ? get_all_projects_admin() : [];

// ══════════════════════════════════════════════════════════════
// EXPERIMENTS TAB — data
// ══════════════════════════════════════════════════════════════
$all_experiments = ($tab === 'experiments') ? get_all_experiments() : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; background: #f4f4f4; color: #222; }

        /* ── Top nav ── */
        .topnav {
            display: flex; justify-content: space-between; align-items: center;
            padding: 0 2rem; height: 52px; background: #1a1a1a; color: #fff;
            position: sticky; top: 0; z-index: 100;
        }
        .topnav-brand { font-size: 0.9rem; font-weight: 600; letter-spacing: .04em; color: #fff; text-decoration: none; }
        .topnav-right { display: flex; align-items: center; gap: 0.25rem; }
        .tab-btn {
            padding: 0.35rem 0.9rem; font-size: 0.82rem; text-decoration: none;
            color: #aaa; border: 1px solid transparent; background: none; cursor: pointer;
            font-family: inherit; transition: color .15s, border-color .15s;
        }
        .tab-btn:hover { color: #fff; }
        .tab-btn.active { color: #fff; border-color: rgba(255,255,255,0.25); }
        .tab-btn-danger { color: #f88 !important; }
        .tab-btn-danger:hover { color: #faa !important; }
        .tab-divider { width: 1px; height: 18px; background: #444; margin: 0 0.25rem; }

        /* ── Page body ── */
        .page { padding: 2rem; }

        /* ── Shared buttons ── */
        a.btn { display: inline-block; padding: 0.4rem 0.9rem; font-size: 0.82rem; text-decoration: none; border: 1px solid #555; color: #333; background: #fff; }
        a.btn:hover { background: #222; color: #fff; border-color: #222; }
        a.btn-primary { background: #222; color: #fff; border-color: #222; }
        a.btn-primary:hover { background: #444; border-color: #444; }
        a.btn-danger { border-color: #c00; color: #c00; }
        a.btn-danger:hover { background: #c00; color: #fff; }

        /* ── Section heading ── */
        .section-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .section-head h1 { font-size: 1.15rem; }
        .section-head-right { display: flex; gap: 0.5rem; align-items: center; }

        /* ── Tables ── */
        table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #ddd; }
        th, td { text-align: left; padding: 0.6rem 0.75rem; border-bottom: 1px solid #eee; font-size: 0.83rem; }
        th { background: #f0f0f0; font-weight: 600; }
        tr:last-child td { border-bottom: none; }
        .actions { display: flex; gap: 0.4rem; }
        .badge { display: inline-block; padding: 0.15rem 0.5rem; font-size: 0.72rem; border-radius: 3px; }
        .badge-yes { background: #d4edda; color: #155724; }
        .badge-no  { background: #f8d7da; color: #721c24; }
        .cat-badge { display: inline-block; padding: 0.15rem 0.5rem; font-size: 0.72rem; background: #e9ecef; color: #555; border-radius: 3px; }
        .drag-hint { font-size: 0.75rem; color: #aaa; margin-bottom: 0.6rem; }
        #reorder-status { font-size: 0.78rem; color: #888; }
        tbody tr { cursor: grab; user-select: none; }
        tbody tr:active { cursor: grabbing; }
        tbody tr.dragging { opacity: 0.35; background: #f9f9f9; }
        tbody tr.drag-over td:first-child { border-left: 3px solid #222; }
        .thumb { width: 52px; height: 34px; object-fit: cover; display: block; }
        .thumb-placeholder { width: 52px; height: 34px; background: #e8e8e8; border: 1px solid #ddd; }

        /* ── System tab ── */
        .sys-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; max-width: 900px; }
        @media (max-width: 700px) { .sys-grid { grid-template-columns: 1fr; } }
        .sys-card { background: #fff; border: 1px solid #ddd; padding: 1.25rem; }
        .sys-card h2 { font-size: 0.7rem; text-transform: uppercase; letter-spacing: .08em; color: #888; margin-bottom: 0.9rem; padding-bottom: 0.4rem; border-bottom: 1px solid #eee; }
        .sys-row { display: flex; justify-content: space-between; align-items: center; padding: 0.3rem 0; border-bottom: 1px solid #f0f0f0; font-size: 0.82rem; }
        .sys-row:last-child { border-bottom: none; }
        .sys-key { color: #666; }
        .ok   { color: #155724; font-weight: 600; }
        .err  { color: #c00;    font-weight: 600; }
        .warn { color: #856404; font-weight: 600; }
        .status-bar { display: flex; align-items: center; gap: 0.75rem; background: #fff; border: 1px solid #ddd; padding: 0.7rem 1rem; margin-bottom: 1.25rem; max-width: 900px; }
        .dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
        .dot-ok  { background: #28a745; }
        .dot-err { background: #c00; }
        .dot-warn { background: #f0a500; }
        .sys-migrate-card { background: #fff; border: 1px solid #ddd; padding: 1.25rem; max-width: 900px; margin-top: 1.25rem; }
        .migrate-btn { padding: 0.45rem 1.2rem; background: #222; color: #fff; border: none; font-size: 0.85rem; cursor: pointer; font-family: sans-serif; }
        .migrate-btn:hover { background: #444; }
        .m-added { color: #155724; font-weight: 600; }
        .m-skip  { color: #888; }
        .m-error { color: #c00; font-weight: 600; }
        .col-ok      { color: #155724; }
        .col-missing { color: #c00; }
        .col-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
        .col-table td { padding: 0.25rem 0.5rem; border-bottom: 1px solid #f0f0f0; }
        .col-table tr:last-child td { border-bottom: none; }
    </style>
</head>
<body>

<!-- ── Top nav ───────────────────────────────────────────────── -->
<nav class="topnav">
    <a href="/admin/dashboard.php" class="topnav-brand">Admin</a>
    <div class="topnav-right">
        <a href="/admin/dashboard.php?tab=system"      class="tab-btn <?= $tab==='system'      ? 'active':'' ?>">System</a>
        <a href="/admin/dashboard.php?tab=projects"    class="tab-btn <?= $tab==='projects'    ? 'active':'' ?>">Projects</a>
        <a href="/admin/dashboard.php?tab=experiments" class="tab-btn <?= $tab==='experiments' ? 'active':'' ?>">Experiments</a>
        <div class="tab-divider"></div>
        <a href="/admin/logout.php" class="tab-btn tab-btn-danger">Logout</a>
    </div>
</nav>

<div class="page">

<?php /* ════════════════ SYSTEM TAB ════════════════ */ if ($tab === 'system'): ?>

<!-- Status bar -->
<div class="status-bar">
    <div class="dot <?= $sys_healthy ? 'dot-ok' : ($needs_migration ? 'dot-warn' : 'dot-err') ?>"></div>
    <span style="font-size:0.9rem">
        <?php if ($sys_healthy): ?>
            <span class="ok">All systems operational</span>
        <?php elseif (!$sqlite_write_ok): ?>
            <span class="err">Database is read-only — create, edit, delete will fail</span>
        <?php elseif ($needs_migration): ?>
            <span class="warn">Schema out of date — run migration below</span>
        <?php else: ?>
            <span class="err">Issues detected — see details below</span>
        <?php endif; ?>
    </span>
</div>

<div class="sys-grid">

    <!-- DB file -->
    <div class="sys-card">
        <h2>Database</h2>
        <div class="sys-row"><span class="sys-key">Path</span><span style="font-size:0.75rem;color:#888;word-break:break-all;text-align:right;max-width:240px"><?= htmlspecialchars(basename($db_path)) ?></span></div>
        <div class="sys-row"><span class="sys-key">Exists</span>   <span><?= $db_exists   ? '<span class="ok">YES</span>' : '<span class="err">NO</span>' ?></span></div>
        <div class="sys-row"><span class="sys-key">Readable</span> <span><?= $db_readable  ? '<span class="ok">YES</span>' : '<span class="err">NO</span>' ?></span></div>
        <div class="sys-row"><span class="sys-key">Writable</span> <span><?= $db_writable  ? '<span class="ok">YES</span>' : '<span class="err">NO</span>' ?></span></div>
        <div class="sys-row"><span class="sys-key">Dir writable</span><span><?= $dir_writable ? '<span class="ok">YES</span>' : '<span class="err">NO</span>' ?></span></div>
    </div>

    <!-- Write tests -->
    <div class="sys-card">
        <h2>Write Tests</h2>
        <div class="sys-row">
            <span class="sys-key">Directory write</span>
            <span><?= $write_test_ok   ? '<span class="ok">PASSED</span>' : '<span class="err">FAILED</span>' ?></span>
        </div>
        <div class="sys-row">
            <span class="sys-key">SQLite write</span>
            <span><?= $sqlite_write_ok ? '<span class="ok">PASSED</span>' : '<span class="err">FAILED</span>' ?></span>
        </div>
        <?php if (!$write_test_ok):    ?><div class="sys-row"><span class="sys-key" style="color:#c00">Error</span><span style="font-size:0.75rem;color:#c00"><?= htmlspecialchars($write_test_err)   ?></span></div><?php endif; ?>
        <?php if (!$sqlite_write_ok):  ?><div class="sys-row"><span class="sys-key" style="color:#c00">Error</span><span style="font-size:0.75rem;color:#c00"><?= htmlspecialchars($sqlite_write_err) ?></span></div><?php endif; ?>
    </div>

    <!-- Schema status -->
    <div class="sys-card" style="grid-column: 1 / -1">
        <h2>Schema — projects table (<?= count($col_names) ?> columns present)</h2>
        <table class="col-table">
            <tr>
                <?php
                $chunks = array_chunk(array_keys($required_columns), (int)ceil(count($required_columns)/2));
                foreach ($chunks as $chunk): ?>
                <td style="vertical-align:top;width:50%;padding-right:1rem">
                    <?php foreach ($chunk as $col): ?>
                    <div class="sys-row">
                        <span class="sys-key"><?= htmlspecialchars($col) ?></span>
                        <?php if (in_array($col, $col_names, true)): ?>
                            <span class="col-ok">present</span>
                        <?php else: ?>
                            <span class="col-missing">MISSING</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </td>
                <?php endforeach; ?>
            </tr>
        </table>
    </div>

</div>

<!-- Migration -->
<div class="sys-migrate-card">
    <h2 style="font-size:0.7rem;text-transform:uppercase;letter-spacing:.08em;color:#888;margin-bottom:0.9rem;padding-bottom:0.4rem;border-bottom:1px solid #eee">Schema Migration</h2>

    <?php if ($migration_ran): ?>
    <table class="col-table" style="margin-bottom:1rem">
        <?php foreach ($migration_results as $r): ?>
        <tr>
            <td style="width:60px">
                <?php if ($r['status']==='added'): ?><span class="m-added">added</span>
                <?php elseif ($r['status']==='skip'): ?><span class="m-skip">skip</span>
                <?php else: ?><span class="m-error">error</span><?php endif; ?>
            </td>
            <td><?= htmlspecialchars($r['col']) ?> <span style="color:#bbb;font-size:0.75rem">(<?= htmlspecialchars($r['def']) ?>)</span></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="action" value="migrate">
        <button type="submit" class="migrate-btn">Run Migration</button>
    </form>
</div>

<?php /* ════════════════ PROJECTS TAB ════════════════ */ elseif ($tab === 'projects'): ?>

<div class="section-head">
    <h1>Projects</h1>
    <div class="section-head-right">
        <span id="reorder-status"></span>
        <a href="/admin/project-create.php" class="btn btn-primary">+ New Project</a>
    </div>
</div>

<?php if (empty($projects)): ?>
    <p style="color:#888;font-size:0.9rem">No projects yet.</p>
<?php else: ?>
    <p class="drag-hint">Drag rows to reorder — saves automatically.</p>
    <p style="font-size:0.78rem;color:#aaa;margin-bottom:0.6rem"><?= count($projects) ?> projects</p>
    <table>
        <thead>
            <tr>
                <th style="width:32px">#</th>
                <th>Title</th>
                <th>Year</th>
                <th>Category</th>
                <th>Published</th>
                <th>Updated</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($projects as $p): ?>
            <tr draggable="true" data-id="<?= (int)$p['id'] ?>">
                <td style="color:#aaa"><?= str_pad((int)($p['sort_order'] ?? 0), 2, '0', STR_PAD_LEFT) ?></td>
                <td><?= htmlspecialchars($p['title']) ?></td>
                <td><?= htmlspecialchars($p['year'] ?? '—') ?></td>
                <td><?= htmlspecialchars($p['category'] ?? '—') ?></td>
                <td><?= !empty($p['is_published']) ? '<span class="badge badge-yes">Yes</span>' : '<span class="badge badge-no">No</span>' ?></td>
                <td style="color:#888"><?= htmlspecialchars($p['updated_at'] ?? '—') ?></td>
                <td>
                    <div class="actions">
                        <a href="/admin/project-edit.php?id=<?= (int)$p['id'] ?>" class="btn">Edit</a>
                        <a href="/admin/project-delete.php?id=<?= (int)$p['id'] ?>" class="btn btn-danger">Delete</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<script>
(function () {
    const tbody  = document.querySelector('tbody');
    const status = document.getElementById('reorder-status');
    if (!tbody) return;
    let dragged = null;
    tbody.addEventListener('dragstart', e => {
        dragged = e.target.closest('tr');
        setTimeout(() => dragged.classList.add('dragging'), 0);
    });
    tbody.addEventListener('dragend', () => {
        if (dragged) dragged.classList.remove('dragging');
        document.querySelectorAll('tr.drag-over').forEach(r => r.classList.remove('drag-over'));
        dragged = null;
        saveOrder();
    });
    tbody.addEventListener('dragover', e => {
        e.preventDefault();
        const target = e.target.closest('tr');
        if (!target || target === dragged) return;
        document.querySelectorAll('tr.drag-over').forEach(r => r.classList.remove('drag-over'));
        target.classList.add('drag-over');
        const rows = [...tbody.querySelectorAll('tr')];
        if (rows.indexOf(dragged) < rows.indexOf(target)) target.after(dragged);
        else target.before(dragged);
    });
    function saveOrder() {
        const rows = [...tbody.querySelectorAll('tr')];
        const ids  = rows.map(r => parseInt(r.dataset.id, 10));
        status.textContent = 'Saving…';
        fetch('/admin/reorder.php', {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids })
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                rows.forEach((row, i) => {
                    row.querySelector('td').textContent = String(ids.length - i).padStart(2, '0');
                });
                status.textContent = 'Saved ✓';
                setTimeout(() => { status.textContent = ''; }, 2000);
            } else {
                status.textContent = 'Error: ' + (data.error || 'unknown');
            }
        })
        .catch(() => { status.textContent = 'Save failed'; });
    }
}());
</script>

<?php /* ════════════════ EXPERIMENTS TAB ════════════════ */ elseif ($tab === 'experiments'): ?>

<div class="section-head">
    <h1>Experiments</h1>
    <div class="section-head-right">
        <a href="/admin/experiment-create.php" class="btn btn-primary">+ New Experiment</a>
    </div>
</div>

<?php if (empty($all_experiments)): ?>
    <p style="color:#888;font-size:0.9rem">No experiments yet.</p>
<?php else: ?>
    <p style="font-size:0.78rem;color:#aaa;margin-bottom:0.6rem"><?= count($all_experiments) ?> experiments</p>
    <table>
        <thead>
            <tr>
                <th style="width:64px">Media</th>
                <th>Title</th>
                <th>Category</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($all_experiments as $e):
                $media = list_experiment_media((int)$e['id']);
                $thumb = null;
                foreach ($media as $f) {
                    if (in_array($f['ext'], ['jpg','jpeg','png','webp','gif'])) { $thumb = $f; break; }
                }
            ?>
            <tr>
                <td>
                    <?php if ($thumb): ?>
                        <img class="thumb" src="<?= htmlspecialchars($thumb['url']) ?>" alt="">
                    <?php else: ?>
                        <div class="thumb-placeholder"></div>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($e['title']) ?></td>
                <td><span class="cat-badge"><?= htmlspecialchars($e['category'] ?? '—') ?></span></td>
                <td><?= htmlspecialchars($e['date'] ?? '—') ?></td>
                <td>
                    <div class="actions">
                        <a href="/admin/experiment-edit.php?id=<?= (int)$e['id'] ?>" class="btn">Edit</a>
                        <a href="/admin/experiment-delete.php?id=<?= (int)$e['id'] ?>" class="btn btn-danger">Delete</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php endif; ?>

</div><!-- .page -->
</body>
</html>
