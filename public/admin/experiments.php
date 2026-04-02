<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/experiment-model.php';

require_login();

// ── Seed hardcoded data on first visit if table is empty ──────
$all = get_all_experiments();
if (empty($all)) {
    $seed = [
        ['title' => 'Noise Field Study #1',  'category' => 'Sound Studies',             'date' => '2024-11'],
        ['title' => 'Breath Sequencer',       'category' => 'Sound Studies',             'date' => '2024-09'],
        ['title' => 'Resonance Loop',         'category' => 'Sound Studies',             'date' => '2024-08'],
        ['title' => 'White Noise Portrait',   'category' => 'Sound Studies',             'date' => '2024-07'],
        ['title' => 'Frequency Map 03',       'category' => 'Sound Studies',             'date' => '2024-06'],
        ['title' => 'Particle Drift',         'category' => 'TouchDesigner Sketches',    'date' => '2024-10'],
        ['title' => 'Feedback Web',           'category' => 'TouchDesigner Sketches',    'date' => '2024-09'],
        ['title' => 'Shadow Tracing',         'category' => 'TouchDesigner Sketches',    'date' => '2024-08'],
        ['title' => 'Mesh Breath',            'category' => 'TouchDesigner Sketches',    'date' => '2024-07'],
        ['title' => 'Optical Flow Study',     'category' => 'TouchDesigner Sketches',    'date' => '2024-05'],
        ['title' => 'Membrane',               'category' => 'Material / Interface Tests','date' => '2024-08'],
        ['title' => 'Soft Circuit v1',        'category' => 'Material / Interface Tests','date' => '2024-07'],
        ['title' => 'Pressure Surface',       'category' => 'Material / Interface Tests','date' => '2024-06'],
        ['title' => 'Conductive Thread 02',   'category' => 'Material / Interface Tests','date' => '2024-04'],
        ['title' => 'Dust Archive',           'category' => 'Daily Visual Fragments',    'date' => '2024-07'],
        ['title' => 'Light Residue',          'category' => 'Daily Visual Fragments',    'date' => '2024-06'],
        ['title' => 'Still 004',              'category' => 'Daily Visual Fragments',    'date' => '2024-05'],
        ['title' => 'Morning Grain',          'category' => 'Daily Visual Fragments',    'date' => '2024-04'],
        ['title' => 'Overexposed 01',         'category' => 'Daily Visual Fragments',    'date' => '2024-03'],
        ['title' => 'Fragment 12',            'category' => 'Daily Visual Fragments',    'date' => '2024-02'],
    ];
    foreach ($seed as $s) {
        create_experiment($s);
    }
    $all = get_all_experiments();
}

// ── Collect unique categories for filter ─────────────────────
$categories = array_unique(array_filter(array_column($all, 'category')));
sort($categories);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Experiments — Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; background: #f4f4f4; color: #222; padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 0.75rem; }
        h1 { font-size: 1.3rem; }
        .header-actions { display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap; }
        a.btn { padding: 0.4rem 0.9rem; font-size: 0.85rem; text-decoration: none; border: 1px solid #222; color: #222; background: #fff; }
        a.btn:hover { background: #222; color: #fff; }
        a.btn-primary { background: #222; color: #fff; }
        a.btn-primary:hover { background: #444; }
        a.btn-danger { border-color: #c00; color: #c00; }
        a.btn-danger:hover { background: #c00; color: #fff; }
        .nav-tabs { display: flex; gap: 0; margin-bottom: 1.5rem; border-bottom: 2px solid #ddd; }
        .nav-tab { padding: 0.5rem 1.25rem; font-size: 0.85rem; cursor: pointer; color: #888; border-bottom: 2px solid transparent; margin-bottom: -2px; user-select: none; }
        .nav-tab:hover { color: #222; }
        .nav-tab.active { color: #222; font-weight: 600; border-bottom-color: #222; }
        table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #ddd; }
        th, td { text-align: left; padding: 0.6rem 0.75rem; border-bottom: 1px solid #eee; font-size: 0.85rem; }
        th { background: #f0f0f0; font-weight: 600; }
        tr:last-child td { border-bottom: none; }
        .thumb { width: 56px; height: 36px; object-fit: cover; background: #e0e0e0; display: block; }
        .thumb-placeholder { width: 56px; height: 36px; background: #e8e8e8; border: 1px solid #ddd; }
        .actions { display: flex; gap: 0.5rem; }
        .cat-badge { display: inline-block; padding: 0.15rem 0.5rem; font-size: 0.75rem; background: #e9ecef; color: #555; border-radius: 3px; }
    </style>
</head>
<body>

<div class="header">
    <h1>Experiments</h1>
    <div class="header-actions">
        <a href="/admin/experiment-create.php" class="btn btn-primary">+ New Experiment</a>
        <a href="/admin/dashboard.php" class="btn">Projects</a>
        <a href="/admin/debug-db.php" class="btn">System</a>
        <a href="/admin/logout.php" class="btn btn-danger">Logout</a>
    </div>
</div>

<?php if (empty($all)): ?>
    <p>No experiments yet.</p>
<?php else: ?>
    <p style="font-size:0.8rem;color:#aaa;margin-bottom:0.75rem"><?= count($all) ?> experiments</p>
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
            <?php foreach ($all as $e):
                $media = list_experiment_media((int)$e['id']);
                $thumb = null;
                foreach ($media as $f) {
                    if (in_array($f['ext'], ['jpg','jpeg','png','webp','gif'])) {
                        $thumb = $f; break;
                    }
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

</body>
</html>
