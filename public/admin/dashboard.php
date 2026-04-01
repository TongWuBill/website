<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/project-model.php';

require_login();

$projects = get_all_projects_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; background: #f4f4f4; color: #222; padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        h1 { font-size: 1.3rem; }
        .header-actions { display: flex; gap: 0.75rem; align-items: center; }
        a.btn { padding: 0.4rem 0.9rem; font-size: 0.85rem; text-decoration: none; border: 1px solid #222; color: #222; background: #fff; }
        a.btn:hover { background: #222; color: #fff; }
        a.btn-primary { background: #222; color: #fff; }
        a.btn-primary:hover { background: #444; }
        a.btn-danger { border-color: #c00; color: #c00; }
        a.btn-danger:hover { background: #c00; color: #fff; }
        table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #ddd; }
        th, td { text-align: left; padding: 0.6rem 0.75rem; border-bottom: 1px solid #eee; font-size: 0.85rem; }
        th { background: #f0f0f0; font-weight: 600; }
        tr:last-child td { border-bottom: none; }
        .badge { display: inline-block; padding: 0.15rem 0.5rem; font-size: 0.75rem; border-radius: 3px; }
        .badge-yes { background: #d4edda; color: #155724; }
        .badge-no  { background: #f8d7da; color: #721c24; }
        .actions { display: flex; gap: 0.5rem; }
        /* drag-and-drop */
        tbody tr { cursor: grab; user-select: none; }
        tbody tr:active { cursor: grabbing; }
        tbody tr.dragging { opacity: 0.35; background: #f9f9f9; }
        tbody tr.drag-over td:first-child { border-left: 3px solid #222; }
        .drag-hint { font-size: 0.75rem; color: #aaa; margin-bottom: 0.75rem; }
        #reorder-status { font-size: 0.8rem; color: #888; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Projects</h1>
        <div class="header-actions">
            <span id="reorder-status"></span>
            <a href="/admin/project-create.php" class="btn btn-primary">+ New Project</a>
            <a href="/admin/debug-db.php" class="btn">System</a>
            <a href="/admin/logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>

    <?php if (empty($projects)): ?>
        <p>No projects found.</p>
    <?php else: ?>
        <p class="drag-hint">Drag rows to reorder — changes save automatically.</p>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Slug</th>
                    <th>Year</th>
                    <th>Category</th>
                    <th>Published</th>
                    <th>Last Edited</th>
                    <th>Versions</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $p): ?>
                <tr draggable="true" data-id="<?= (int)$p['id'] ?>">
                    <td style="color:#888"><?= str_pad((int)($p['sort_order'] ?? 0), 2, '0', STR_PAD_LEFT) ?></td>
                    <td><?= htmlspecialchars($p['title']) ?></td>
                    <td><?= htmlspecialchars($p['slug']) ?></td>
                    <td><?= htmlspecialchars($p['year'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['category'] ?? '—') ?></td>
                    <td>
                        <?php if (!empty($p['is_published'])): ?>
                            <span class="badge badge-yes">Yes</span>
                        <?php else: ?>
                            <span class="badge badge-no">No</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($p['updated_at'] ?? '—') ?></td>
                    <td style="text-align:center"><?= (int)($p['edit_count'] ?? 0) ?>v</td>
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
        if (rows.indexOf(dragged) < rows.indexOf(target)) {
            target.after(dragged);
        } else {
            target.before(dragged);
        }
    });

    function saveOrder() {
        const rows  = [...tbody.querySelectorAll('tr')];
        const ids   = rows.map(r => parseInt(r.dataset.id, 10));
        const total = ids.length;

        status.textContent = 'Saving…';

        fetch('/admin/reorder.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ ids })
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                // Update displayed # values to match new sort_order
                rows.forEach((row, i) => {
                    row.querySelector('td').textContent =
                        String(total - i).padStart(2, '0');
                });
                status.textContent = 'Saved ✓';
                setTimeout(() => { status.textContent = ''; }, 2000);
            } else {
                status.textContent = 'Error saving';
            }
        })
        .catch(() => { status.textContent = 'Error saving'; });
    }
}());
</script>

</body>
</html>
