<?php
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/media.php';

$slug    = $_GET['slug'] ?? null;
$project = get_project_by_slug($slug);

if (!$project) {
    http_response_code(404);
    echo "Project not found";
    exit;
}

// ── Media bucketing ───────────────────────────────────────────
// All files for this project
$all_media = list_project_media($project['slug']);

$media_exts = ['jpg','jpeg','png','webp','gif','mp4','webm','mov'];
$img_exts   = ['jpg','jpeg','png','webp','gif'];
$vid_exts   = ['mp4','webm','mov'];

// ── Load content sections ─────────────────────────────────────
// Prefer new sections JSON; fall back to legacy fixed fields.
if (!empty($project['sections'])) {
    $decoded_sections = json_decode($project['sections'], true);
    $content_sections = is_array($decoded_sections) ? $decoded_sections : [];
} else {
    $content_sections = [];
}
if (empty($content_sections)) {
    // Legacy fallback
    foreach ([
        'Concept'      => $project['immersion']        ?? '',
        'Context'      => $project['context']          ?? '',
        'Process'      => $project['system_text']      ?? '',
        'Interaction'  => $project['interaction_text'] ?? '',
        'Presentation' => $project['reflection']       ?? '',
    ] as $label => $body) {
        if (trim((string)$body) !== '') {
            $content_sections[] = ['label' => $label, 'body' => (string)$body];
        }
    }
}

// Section keys for media bucketing: slugified label
$section_keys = array_map(
    fn($s) => strtolower(preg_replace('/[^a-z0-9]/i', '', $s['label'])),
    $content_sections
);

// Bucket files: prefix match → section index, else → unassigned
$section_media = array_fill(0, count($content_sections), []);
$unassigned    = [];

foreach ($all_media as $f) {
    if (!in_array($f['ext'], $media_exts)) continue;
    $matched = false;
    foreach ($section_keys as $idx => $key) {
        if ($key === '') continue;
        if (preg_match('/^' . preg_quote($key, '/') . '[\-_.\d]/i', $f['name']) ||
            strcasecmp(pathinfo($f['name'], PATHINFO_FILENAME), $key) === 0) {
            $section_media[$idx][] = $f;
            $matched = true;
            break;
        }
    }
    if (!$matched) {
        $unassigned[] = $f;
    }
}

// Hero: video embed takes priority.
// Otherwise look for an explicit hero-* file first, then fall back to first image.
$has_video = !empty($project['video_url']);
$hero_img  = null;
if (!$has_video) {
    // Prefer file named hero-* or hero.*
    foreach ($unassigned as $i => $f) {
        if (preg_match('/^hero[\-_.]/i', $f['name']) && in_array($f['ext'], $img_exts)) {
            $hero_img = $f;
            array_splice($unassigned, $i, 1);
            break;
        }
    }
    // Fallback: first unassigned image (backward compat)
    if (!$hero_img) {
        foreach ($unassigned as $i => $f) {
            if (in_array($f['ext'], $img_exts) && !preg_match('/^gallery[\-_.]/i', $f['name'])) {
                $hero_img = $f;
                array_splice($unassigned, $i, 1);
                break;
            }
        }
    }
}

// gallery-* files go to gallery; remaining unassigned files also go to gallery
$gallery = array_values(array_filter($unassigned, fn($f) => in_array($f['ext'], $media_exts)));

// Helper: render one media file (img or video)
function render_media_file(array $f, string $cls = ''): void {
    $url = htmlspecialchars($f['url']);
    $ext = $f['ext'];
    if (in_array($ext, ['mp4','webm','mov'])) {
        echo '<video class="' . $cls . '" controls playsinline preload="metadata" src="' . $url . '"></video>';
    } else {
        echo '<img class="' . $cls . '" src="' . $url . '" alt="" loading="lazy">';
    }
}

render_header($project['title']);
?>

<!-- ── Hero ──────────────────────────────────────────────── -->
<div class="pd-hero">
  <?php if ($has_video): ?>
    <iframe class="pd-hero-video"
            src="<?= htmlspecialchars($project['video_url']) ?>"
            frameborder="0" allowfullscreen allow="autoplay; fullscreen"></iframe>
  <?php elseif ($hero_img): ?>
    <img class="pd-hero-img"
         src="<?= htmlspecialchars($hero_img['url']) ?>"
         alt="<?= htmlspecialchars($project['title']) ?>">
  <?php else: ?>
    <div class="pd-hero-placeholder"></div>
  <?php endif; ?>
</div>

<!-- ── Project header ────────────────────────────────────── -->
<div class="pd-header">
  <div class="pd-title-row">
    <h1 class="pd-title"><?= htmlspecialchars($project['title']) ?></h1>
    <?php if (!empty($project['subtitle'])): ?>
    <span class="pd-subtitle"><?= htmlspecialchars($project['subtitle']) ?></span>
    <?php endif; ?>
  </div>
  <div class="pd-meta">
    <?php if (!empty($project['year'])): ?>
    <div class="pd-meta-item">
      <span class="pd-meta-label">Year</span>
      <span class="pd-meta-value"><?= htmlspecialchars($project['year']) ?></span>
    </div>
    <?php endif; ?>
    <?php if (!empty($project['category'])): ?>
    <div class="pd-meta-item">
      <span class="pd-meta-label">Category</span>
      <span class="pd-meta-value"><?= htmlspecialchars($project['category']) ?></span>
    </div>
    <?php endif; ?>
    <?php if (!empty($project['material'])): ?>
    <div class="pd-meta-item">
      <span class="pd-meta-label">Materials</span>
      <span class="pd-meta-value"><?= htmlspecialchars($project['material']) ?></span>
    </div>
    <?php endif; ?>
    <?php if (!empty($project['skillset'])): ?>
    <div class="pd-meta-item">
      <span class="pd-meta-label">Skillset</span>
      <span class="pd-meta-value"><?= htmlspecialchars($project['skillset']) ?></span>
    </div>
    <?php endif; ?>
    <?php if (!empty($project['exhibition'])): ?>
    <div class="pd-meta-item">
      <span class="pd-meta-label">Exhibition</span>
      <span class="pd-meta-value"><?= htmlspecialchars($project['exhibition']) ?></span>
    </div>
    <?php endif; ?>
    <?php if (!empty($project['location'])): ?>
    <div class="pd-meta-item">
      <span class="pd-meta-label">Location</span>
      <span class="pd-meta-value"><?= htmlspecialchars($project['location']) ?></span>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ── Content sections ──────────────────────────────────── -->
<div class="pd-body">

<?php foreach ($content_sections as $idx => $s):
  $text  = trim($s['body'] ?? '');
  $files = $section_media[$idx] ?? [];
?>
<div class="pd-section">

  <div class="pd-section-label"><?= htmlspecialchars($s['label'] ?? '') ?></div>

  <div class="pd-section-content">

    <!-- left: text -->
    <div class="pd-section-text">
      <?= $text !== '' ? nl2br(htmlspecialchars($text)) : '' ?>
    </div>

    <!-- right: media or placeholder -->
    <div class="pd-section-right">
      <?php if (!empty($files)): ?>
        <div class="pd-section-media pd-section-media--<?= count($files) === 1 ? 'single' : 'grid' ?>">
          <?php foreach ($files as $f): ?>
          <div class="pd-media-item">
            <?php render_media_file($f, 'pd-media-asset'); ?>
          </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="pd-media-placeholder">
          <span>media</span>
        </div>
      <?php endif; ?>
    </div>

  </div><!-- /.pd-section-content -->

</div><!-- /.pd-section -->
<?php endforeach; ?>

</div><!-- /.pd-body -->

<!-- ── Bottom gallery (unassigned files) ─────────────────── -->
<?php if (!empty($gallery)): ?>
<div class="pd-gallery">
  <?php foreach ($gallery as $f): ?>
  <div class="pd-gallery-item">
    <?php render_media_file($f, 'pd-gallery-asset'); ?>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Back link ─────────────────────────────────────────── -->
<div class="pd-foot">
  <a href="/work" class="pd-back">← All Work</a>
</div>

<?php render_footer(); ?>
