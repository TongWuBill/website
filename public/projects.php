<?php
require_once __DIR__ . '/../src/functions.php';

$projects = get_all_projects();

render_header('Work');
?>

<div class="work-wrap">

  <div class="work-page-header">
    <h1>Work</h1>
    <span class="work-count"><?= count($projects) ?> projects</span>
  </div>

  <div class="work-grid">
    <?php foreach ($projects as $i => $p):
        $img = '/media/projects/' . $p['slug'] . '.jpg';
        $img_exists = file_exists(__DIR__ . '/media/projects/' . $p['slug'] . '.jpg');
        $featured = ($i === 0) ? ' work-card--featured' : '';
    ?>
    <a href="/p/<?= htmlspecialchars($p['slug']) ?>" class="work-card<?= $featured ?>">
      <div class="work-card-image">
        <?php if ($img_exists): ?>
          <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($p['title']) ?>">
        <?php endif; ?>
        <div class="work-card-overlay"></div>
      </div>
      <div class="work-card-meta">
        <span class="work-card-index"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span>
        <span class="work-card-title"><?= htmlspecialchars($p['title']) ?></span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

</div>

<?php render_footer(); ?>
