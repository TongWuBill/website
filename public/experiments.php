<?php
require_once __DIR__ . '/../src/functions.php';

$categories = [
    [
        'title' => 'Sound Studies',
        'items' => [
            ['title' => 'Noise Field Study #1', 'date' => '2024-11', 'image' => ''],
            ['title' => 'Breath Sequencer',      'date' => '2024-09', 'image' => ''],
            ['title' => 'Resonance Loop',        'date' => '2024-08', 'image' => ''],
            ['title' => 'White Noise Portrait',  'date' => '2024-07', 'image' => ''],
            ['title' => 'Frequency Map 03',      'date' => '2024-06', 'image' => ''],
        ],
    ],
    [
        'title' => 'TouchDesigner Sketches',
        'items' => [
            ['title' => 'Particle Drift',        'date' => '2024-10', 'image' => ''],
            ['title' => 'Feedback Web',          'date' => '2024-09', 'image' => ''],
            ['title' => 'Shadow Tracing',        'date' => '2024-08', 'image' => ''],
            ['title' => 'Mesh Breath',           'date' => '2024-07', 'image' => ''],
            ['title' => 'Optical Flow Study',    'date' => '2024-05', 'image' => ''],
        ],
    ],
    [
        'title' => 'Material / Interface Tests',
        'items' => [
            ['title' => 'Membrane',              'date' => '2024-08', 'image' => ''],
            ['title' => 'Soft Circuit v1',       'date' => '2024-07', 'image' => ''],
            ['title' => 'Pressure Surface',      'date' => '2024-06', 'image' => ''],
            ['title' => 'Conductive Thread 02',  'date' => '2024-04', 'image' => ''],
        ],
    ],
    [
        'title' => 'Daily Visual Fragments',
        'items' => [
            ['title' => 'Dust Archive',          'date' => '2024-07', 'image' => ''],
            ['title' => 'Light Residue',         'date' => '2024-06', 'image' => ''],
            ['title' => 'Still 004',             'date' => '2024-05', 'image' => ''],
            ['title' => 'Morning Grain',         'date' => '2024-04', 'image' => ''],
            ['title' => 'Overexposed 01',        'date' => '2024-03', 'image' => ''],
            ['title' => 'Fragment 12',           'date' => '2024-02', 'image' => ''],
        ],
    ],
];

render_header('Experiments');
?>

<div class="exp-wrap">

  <div class="exp-page-header">
    <h1>Experiments</h1>
    <p>Smaller works, sketches, and ongoing research.</p>
  </div>

  <?php foreach ($categories as $cat): ?>
  <section class="exp-row">

    <div class="exp-row-header">
      <h2 class="exp-row-title"><?= htmlspecialchars($cat['title']) ?></h2>
    </div>

    <div class="exp-slider-track">
      <ul class="exp-slider">
        <?php foreach ($cat['items'] as $item): ?>
        <li class="exp-card">
          <div class="exp-card-image">
            <?php if (!empty($item['image'])): ?>
              <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
            <?php endif; ?>
          </div>
          <div class="exp-card-meta">
            <span class="exp-card-title"><?= htmlspecialchars($item['title']) ?></span>
            <?php if (!empty($item['date'])): ?>
              <span class="exp-card-date"><?= htmlspecialchars($item['date']) ?></span>
            <?php endif; ?>
          </div>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>

  </section>
  <?php endforeach; ?>

</div>

<script>
document.querySelectorAll('.exp-slider-track').forEach(track => {
    let isDown = false, startX, scrollLeft;
    track.addEventListener('mousedown', e => {
        isDown = true;
        startX = e.pageX - track.offsetLeft;
        scrollLeft = track.scrollLeft;
    });
    track.addEventListener('mouseleave', () => isDown = false);
    track.addEventListener('mouseup',    () => isDown = false);
    track.addEventListener('mousemove', e => {
        if (!isDown) return;
        e.preventDefault();
        track.scrollLeft = scrollLeft - (e.pageX - track.offsetLeft - startX);
    });
});
</script>

<?php render_footer(); ?>
