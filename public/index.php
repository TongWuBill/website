<?php
require_once __DIR__ . '/../src/functions.php';

$all = get_all_projects();
$selected_slugs = ['beyond-pitaya', 'bodily-nature', 'tidal-vile', 'rice-journey', 'gestural-resonance'];
$selected = array_filter($all, fn($p) => in_array($p['slug'], $selected_slugs));

render_header('Tong Wu');
?>

<!-- ── Hero ── -->
<section class="home-hero">
  <div class="home-hero-image">
    <!-- replace with: <img src="/media/home/hero.jpg" alt=""> -->
  </div>
  <div class="home-hero-text">
    <h1 class="home-name">Tong Wu</h1>
    <p class="home-tagline">Interactive Artist<br>Creative Technologist</p>
  </div>
</section>

<!-- ── Ideology ── -->
<section class="home-ideology">
  <p class="home-ideology-text">
    fuck. I make work that lives between body and system —<br>
    where sensation becomes data, and data becomes sensation.
  </p>
</section>

<!-- ── Selected Works ── -->
<section class="home-selected">

  <div class="home-selected-header">
    <span class="home-selected-label">Selected Works</span>
    <a href="/work" class="view-all">View all</a>
  </div>

  <div class="home-works-grid">
    <?php $i = 0; foreach ($selected as $p):
        $img = '/media/projects/' . $p['slug'] . '.jpg';
        $img_exists = file_exists(__DIR__ . '/media/projects/' . $p['slug'] . '.jpg');
        $large = ($i === 0 || $i === 3); // items 1 and 4 are large
    ?>
    <a href="/p/<?= htmlspecialchars($p['slug']) ?>"
       class="home-work-card<?= $large ? ' home-work-card--large' : '' ?>">
      <div class="home-work-image">
        <?php if ($img_exists): ?>
          <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($p['title']) ?>">
        <?php endif; ?>
      </div>
      <div class="home-work-meta">
        <span class="home-work-title"><?= htmlspecialchars($p['title']) ?></span>
      </div>
    </a>
    <?php $i++; endforeach; ?>
  </div>

</section>

<?php render_footer(); ?>
