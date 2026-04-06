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
$all_media  = list_project_media($project['slug']);
$media_exts = ['jpg','jpeg','png','webp','gif','mp4','webm','mov','pdf','txt','doc','docx'];
$img_exts   = ['jpg','jpeg','png','webp','gif'];
$vid_exts   = ['mp4','webm','mov'];

// ── Load content sections ─────────────────────────────────────
if (!empty($project['sections'])) {
    $decoded_sections = json_decode($project['sections'], true);
    $content_sections = is_array($decoded_sections) ? $decoded_sections : [];
} else {
    $content_sections = [];
}
if (empty($content_sections)) {
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

$section_keys  = array_map(
    fn($s) => strtolower(preg_replace('/[^a-z0-9]/i', '', $s['label'])),
    $content_sections
);

$section_media = array_fill(0, count($content_sections), []);
$unassigned    = [];

foreach ($all_media as $f) {
    if (!in_array($f['ext'], $media_exts)) continue;
    // Thumbnail files are only for the Work list — skip on detail page
    if (preg_match('/^thumb[\-_.]/i', $f['name'])) continue;
    // Section buckets
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

// ── Hero media ────────────────────────────────────────────────
// Priority: 1) video_url embed  2) hero-* file (image or video)  3) first unassigned image
$has_video_embed = !empty($project['video_url']);
$hero_file       = null;

if (!$has_video_embed) {
    // Prefer explicit hero-* file (any media type)
    foreach ($unassigned as $i => $f) {
        if (preg_match('/^hero[\-_.]/i', $f['name'])) {
            $hero_file = $f;
            array_splice($unassigned, $i, 1);
            break;
        }
    }
    // Fallback: first unassigned image that isn't a gallery file
    if (!$hero_file) {
        foreach ($unassigned as $i => $f) {
            if (in_array($f['ext'], $img_exts) && !preg_match('/^gallery[\-_.]/i', $f['name'])) {
                $hero_file = $f;
                array_splice($unassigned, $i, 1);
                break;
            }
        }
    }
}

// ── Gallery carousel ──────────────────────────────────────────
// gallery-* files + remaining unassigned go to the carousel
$gallery = array_values(array_filter($unassigned, fn($f) => in_array($f['ext'], $media_exts)));

// Helper: convert YouTube/Vimeo watch URLs to embed URLs
function to_embed_url(string $url): string {
    // Already an embed URL — pass through as-is (preserves ?autoplay=1&muted=1 etc.)
    if (str_contains($url, 'player.vimeo.com') || str_contains($url, 'youtube.com/embed')) {
        return $url;
    }
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    if (preg_match('/vimeo\.com\/(?:.*\/)?(\d+)/', $url, $m)) {
        return 'https://player.vimeo.com/video/' . $m[1];
    }
    return $url;
}

// Helper: unified media renderer — image, video, PDF, TXT, DOC/DOCX
function render_any_file(array $f, string $cls = ''): void {
    $url  = htmlspecialchars($f['url']);
    $ext  = strtolower($f['ext']);
    $name = htmlspecialchars(basename($f['name']));

    if (in_array($ext, ['mp4','webm','mov'])) {
        echo '<video class="' . $cls . '" controls playsinline preload="metadata" src="' . $url . '"></video>';
    } elseif (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
        echo '<img class="' . $cls . '" src="' . $url . '" alt="" loading="lazy">';
    } elseif ($ext === 'pdf') {
        echo '<div class="media-pdf-wrap"><iframe src="' . $url . '" title="' . $name . '"></iframe></div>';
    } elseif ($ext === 'txt') {
        $raw = (isset($f['path']) && is_file($f['path']))
            ? htmlspecialchars(file_get_contents($f['path']))
            : '(Content unavailable)';
        echo '<div class="media-txt-wrap"><pre class="media-txt-content">' . $raw . '</pre></div>';
    } elseif (in_array($ext, ['doc','docx'])) {
        $badge = strtoupper($ext);
        echo '<div class="media-doc-card">'
           . '<span class="media-doc-badge">' . $badge . '</span>'
           . '<span class="media-doc-name">' . $name . '</span>'
           . '<a class="media-doc-dl" href="' . $url . '" download>Download</a>'
           . '</div>';
    }
}

render_header($project['title']);
?>

<!-- ── Hero media ─────────────────────────────────────────── -->
<div class="pd-hero">
  <?php if ($has_video_embed): ?>
    <iframe class="pd-hero-video"
            src="<?= htmlspecialchars(to_embed_url($project['video_url'])) ?>"
            frameborder="0" allowfullscreen allow="autoplay; fullscreen"></iframe>
  <?php elseif ($hero_file): ?>
    <?php if (in_array($hero_file['ext'], $vid_exts)): ?>
      <video class="pd-hero-vid" src="<?= htmlspecialchars($hero_file['url']) ?>"
             autoplay muted loop playsinline></video>
    <?php else: ?>
      <img class="pd-hero-img"
           src="<?= htmlspecialchars($hero_file['url']) ?>"
           alt="<?= htmlspecialchars($project['title']) ?>">
    <?php endif; ?>
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
    <div class="pd-section-text">
      <?= $text !== '' ? nl2br(htmlspecialchars($text)) : '' ?>
    </div>
    <div class="pd-section-right">
      <?php
      $sec_media_url = trim($s['media_url'] ?? '');
      if ($sec_media_url !== ''): ?>
        <div class="pd-section-iframe-wrap">
          <iframe src="<?= htmlspecialchars(to_embed_url($sec_media_url)) ?>"
                  frameborder="0" allowfullscreen allow="autoplay; fullscreen"></iframe>
        </div>
      <?php elseif (!empty($files)): ?>
        <div class="pd-section-media pd-section-media--<?= count($files) === 1 ? 'single' : 'grid' ?>">
          <?php foreach ($files as $f): ?>
          <div class="pd-media-item">
            <?php render_any_file($f, 'pd-media-asset'); ?>
          </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="pd-media-placeholder"><span>media</span></div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endforeach; ?>
</div><!-- /.pd-body -->

<!-- ── Gallery (peek carousel) ───────────────────────────── -->
<?php if (!empty($gallery)): ?>
<div class="pd-gallery" id="pd-gallery">
  <div class="pd-gallery-track" id="pd-gallery-track">
    <?php foreach ($gallery as $i => $f): ?>
    <div class="pd-gallery-slide <?= $i === 0 ? 'active' : '' ?>">
      <?php render_any_file($f, 'pd-gallery-asset'); ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if (count($gallery) > 1): ?>
  <div class="pd-gallery-zone pd-gallery-prev" id="pd-gprev">
    <div class="pd-gallery-arrow">&#8249;</div>
  </div>
  <div class="pd-gallery-zone pd-gallery-next" id="pd-gnext">
    <div class="pd-gallery-arrow">&#8250;</div>
  </div>
  <div class="pd-gallery-dots">
    <?php foreach ($gallery as $i => $f): ?>
    <span class="pd-gallery-dot <?= $i === 0 ? 'active' : '' ?>" data-idx="<?= $i ?>"></span>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<script>
(function () {
  var wrap    = document.getElementById('pd-gallery');
  var track   = document.getElementById('pd-gallery-track');
  var slides  = wrap.querySelectorAll('.pd-gallery-slide');
  var dots    = wrap.querySelectorAll('.pd-gallery-dot');
  var prevBtn = document.getElementById('pd-gprev');
  var nextBtn = document.getElementById('pd-gnext');
  if (!track || slides.length <= 1) return;

  var PEEK    = 64;
  var GAP     = 0;
  var current = 0;

  function slideW() { return wrap.offsetWidth - 2 * PEEK; }

  function layout() {
    var w = slideW();
    slides.forEach(function (s) { s.style.width = w + 'px'; });
    setTrack(current);
  }

  function setTrack(idx) {
    var w = slideW();
    track.style.transform = 'translateX(' + (PEEK - idx * (w + GAP)) + 'px)';
  }

  function goTo(idx) {
    idx = (idx + slides.length) % slides.length;
    slides[current].classList.remove('active');
    if (dots[current]) dots[current].classList.remove('active');
    current = idx;
    slides[current].classList.add('active');
    if (dots[current]) dots[current].classList.add('active');
    setTrack(current);
  }

  layout();
  window.addEventListener('resize', layout);

  if (prevBtn) prevBtn.addEventListener('click', function () { goTo(current - 1); });
  if (nextBtn) nextBtn.addEventListener('click', function () { goTo(current + 1); });

  slides.forEach(function (s, i) {
    s.addEventListener('click', function () { if (i !== current) goTo(i); });
  });

  dots.forEach(function (dot) {
    dot.addEventListener('click', function () { goTo(parseInt(dot.dataset.idx)); });
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowLeft')  goTo(current - 1);
    if (e.key === 'ArrowRight') goTo(current + 1);
  });

  var tx = 0;
  wrap.addEventListener('touchstart', function (e) { tx = e.touches[0].clientX; }, { passive: true });
  wrap.addEventListener('touchend', function (e) {
    var dx = e.changedTouches[0].clientX - tx;
    if (Math.abs(dx) > 40) goTo(current + (dx < 0 ? 1 : -1));
  });
}());
</script>
<?php endif; ?>

<!-- ── Back link ─────────────────────────────────────────── -->
<div class="pd-foot">
  <a href="/work" class="pd-back">← All Work</a>
</div>

<?php render_footer(); ?>
