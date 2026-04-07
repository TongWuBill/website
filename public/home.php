<?php
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/media.php';

$all = get_all_projects();
$selected_slugs = ['beyond-pitaya', 'bodily-nature', 'tidal-vile', 'rice-journey', 'gestural-resonance'];
$selected = array_filter($all, fn($p) => in_array($p['slug'], $selected_slugs));

// Load home text content (gitignored, production-only)
$home_content_path = get_home_media_path() . '/content.json';
$home_text = file_exists($home_content_path) ? (json_decode(file_get_contents($home_content_path), true) ?: []) : [];
$lang = get_lang();
$home_name    = $lang === 'cn' && !empty($home_text['name_cn'])    ? $home_text['name_cn']    : ($home_text['name_en']    ?? 'Tong Wu');
$home_tagline = $lang === 'cn' && !empty($home_text['tagline_cn']) ? $home_text['tagline_cn'] : ($home_text['tagline_en'] ?? "Interactive Artist\nCreative Technologist");

render_header('Tong Wu');
?>

<!-- ── Hero ── -->
<?php $hero_images = list_home_media(); ?>
<section class="home-hero">
  <div class="home-hero-image">
    <?php if (!empty($hero_images)): ?>
      <?php foreach ($hero_images as $i => $img): ?>
      <img src="<?= htmlspecialchars($img['url']) ?>"
           alt=""
           class="hero-slide<?= $i === 0 ? ' hero-slide--active' : '' ?>">
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <div class="home-hero-text">
    <h1 class="home-name"><?= htmlspecialchars($home_name) ?></h1>
    <p class="home-tagline"><?= nl2br(htmlspecialchars($home_tagline)) ?></p>
  </div>
</section>

<!-- ── Ideology ── (temporarily hidden)
<section class="home-ideology">
  <p class="home-ideology-text">
    hahah. I make work that lives between body and system —<br>
    where sensation becomes data, and data becomes sensation.
  </p>
</section>
-->

<!-- ── Selected Works ── (temporarily hidden)
<section class="home-selected">

  <div class="home-selected-header">
    <span class="home-selected-label">Selected Works</span>
    <a href="/work" class="view-all">View all</a>
  </div>

  <div class="home-works-grid">
    <?php $i = 0; foreach ($selected as $p):
        $img = '/media/projects/' . $p['slug'] . '.jpg';
        $img_exists = file_exists(__DIR__ . '/media/projects/' . $p['slug'] . '.jpg');
        $large = $i === 0 || $i === 3; // items 1 and 4 are large
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
-->

<script>
(function () {
  var slides = document.querySelectorAll('.hero-slide');
  if (slides.length < 2) return;
  var current = 0;
  setInterval(function () {
    slides[current].classList.remove('hero-slide--active');
    current = (current + 1) % slides.length;
    slides[current].classList.add('hero-slide--active');
  }, 5000);
}());
</script>

<?php render_footer(); ?>
