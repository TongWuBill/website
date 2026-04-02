<?php
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/media.php';

$projects = get_all_projects();

// Pre-fetch the first image for each project card
$img_exts = ['jpg','jpeg','png','webp','gif'];
$thumbs   = [];
foreach ($projects as $p) {
    $files = list_project_media($p['slug']);
    // Prefer explicit thumbnail file
    foreach ($files as $f) {
        if (preg_match('/^thumb[\-_.]/i', $f['name']) && in_array($f['ext'], $img_exts)) {
            $thumbs[$p['slug']] = $f['url'];
            break;
        }
    }
    // Fallback: first image in folder
    if (!isset($thumbs[$p['slug']])) {
        foreach ($files as $f) {
            if (in_array($f['ext'], $img_exts)) {
                $thumbs[$p['slug']] = $f['url'];
                break;
            }
        }
    }
}

render_header('Work');
?>

<div class="work-wrap">

  <div class="work-page-header">
    <h1>Work</h1>
    <span class="work-count"><?= count($projects) ?> projects</span>
  </div>

  <div class="work-grid">
    <?php foreach ($projects as $i => $p):
        $thumb    = $thumbs[$p['slug']] ?? null;
        $featured = ($i === 0) ? ' work-card--featured' : '';
    ?>
    <a href="/p/<?= htmlspecialchars($p['slug']) ?>" class="work-card<?= $featured ?>">
      <div class="work-card-image">
        <?php if ($thumb): ?>
          <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($p['title']) ?>">
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
