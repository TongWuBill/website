<?php
require_once __DIR__ . '/../src/functions.php';

$slug    = $_GET['slug'] ?? null;
$project = get_project_by_slug($slug);

if (!$project) {
    http_response_code(404);
    echo "Project not found";
    exit;
}

render_header($project['title']);
?>

<div class="hero">
  <div class="hero-media-placeholder"></div>
  <h1 class="hero-title"><?= htmlspecialchars($project['title']) ?></h1>
</div>

<div class="container">

  <!-- ── Metadata row ─────────────────────────────────── -->
  <dl class="project-meta">
    <div class="project-meta-item">
      <dt>Year</dt>
      <dd><?= !empty($project['year']) ? htmlspecialchars($project['year']) : '—' ?></dd>
    </div>
    <div class="project-meta-item">
      <dt>Category</dt>
      <dd><?= !empty($project['category']) ? htmlspecialchars($project['category']) : '—' ?></dd>
    </div>
    <?php if (!empty($project['created_at'])): ?>
    <div class="project-meta-item">
      <dt>Added</dt>
      <dd><?= htmlspecialchars(substr($project['created_at'], 0, 10)) ?></dd>
    </div>
    <?php endif; ?>
  </dl>

  <!-- ── Video ─────────────────────────────────────────── -->
  <?php if (!empty($project['video_url'])): ?>
  <div class="project-section">
    <iframe src="<?= htmlspecialchars($project['video_url']) ?>"
            frameborder="0" allowfullscreen
            style="width:100%;aspect-ratio:16/9;display:block"></iframe>
  </div>
  <?php endif; ?>

  <!-- ── Artistic sections ─────────────────────────────── -->
  <?php
  $sections = [
    'Immersion'   => $project['immersion']        ?? '',
    'Context'     => $project['context']          ?? '',
    'System'      => $project['system_text']      ?? '',
    'Interaction' => $project['interaction_text'] ?? '',
    'Material'    => $project['material']         ?? '',
    'Reflection'  => $project['reflection']       ?? '',
  ];
  foreach ($sections as $label => $body):
    if (empty($body)) continue;
  ?>
  <div class="project-section">
    <p class="section-label"><?= $label ?></p>
    <p class="section-body"><?= htmlspecialchars($body) ?></p>
  </div>
  <?php endforeach; ?>

</div>

<?php render_footer(); ?>
