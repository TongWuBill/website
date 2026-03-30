<?php
require_once __DIR__ . '/../src/functions.php';

$slug = $_GET['slug'] ?? null;
$project = get_project_by_slug($slug);

if (!$project) {
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

  <div class="project-section">
    <p class="section-label">Immersion</p>
    <p class="section-body"><?= htmlspecialchars($project['immersion']) ?></p>
    <div class="media-slot">image / video</div>
  </div>

  <div class="project-section">
    <p class="section-label">Context</p>
    <p class="section-body"><?= htmlspecialchars($project['context']) ?></p>
  </div>

  <div class="project-section">
    <p class="section-label">System</p>
    <p class="section-body"><?= htmlspecialchars($project['system_text']) ?></p>
  </div>

  <div class="project-section">
    <p class="section-label">Interaction</p>
    <p class="section-body"><?= htmlspecialchars($project['interaction_text']) ?></p>
    <div class="media-slot">image / video</div>
  </div>

  <div class="project-section">
    <p class="section-label">Material</p>
    <p class="section-body"><?= htmlspecialchars($project['material']) ?></p>
  </div>

  <div class="project-section">
    <p class="section-label">Reflection</p>
    <p class="section-body"><?= htmlspecialchars($project['reflection']) ?></p>
  </div>

</div>

<?php render_footer(); ?>
