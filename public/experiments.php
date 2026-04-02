<?php
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/experiment-model.php';

$grouped = get_experiments_grouped();

// Build a flat ordered list of all experiments with their media,
// so JS can navigate prev/next across categories.
$all_experiments_js = [];
foreach ($grouped as $cat => $items) {
    foreach ($items as $item) {
        $media = list_experiment_media((int)$item['id']);
        $media_list = [];
        foreach ($media as $f) {
            if (in_array($f['ext'], ['jpg','jpeg','png','webp','gif','mp4','webm','mov'])) {
                $media_list[] = ['url' => $f['url'], 'ext' => $f['ext']];
            }
        }
        $all_experiments_js[] = [
            'id'          => (int)$item['id'],
            'title'       => $item['title'],
            'category'    => $item['category'] ?? '',
            'date'        => $item['date']      ?? '',
            'description' => $item['description'] ?? '',
            'media'       => $media_list,
        ];
    }
}

render_header('Experiments');
?>

<div class="exp-wrap">

  <div class="exp-page-header">
    <h1>Experiments</h1>
    <p>Smaller works, sketches, and ongoing research.</p>
  </div>

  <?php
  $global_idx = 0; // index into the flat $all_experiments_js array
  foreach ($grouped as $cat_title => $items):
  ?>
  <section class="exp-row">

    <div class="exp-row-header">
      <h2 class="exp-row-title"><?= htmlspecialchars($cat_title) ?></h2>
    </div>

    <div class="exp-slider-track">
      <ul class="exp-slider">
        <?php foreach ($items as $item):
            // Find first image for thumbnail
            $media = list_experiment_media((int)$item['id']);
            $thumb = null;
            foreach ($media as $f) {
                if (in_array($f['ext'], ['jpg','jpeg','png','webp','gif'])) {
                    $thumb = $f; break;
                }
            }
        ?>
        <li class="exp-card" data-idx="<?= $global_idx++ ?>">
          <div class="exp-card-image">
            <?php if ($thumb): ?>
              <img src="<?= htmlspecialchars($thumb['url']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
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

<!-- ── Modal ──────────────────────────────────────────────────── -->
<div id="exp-modal" class="exp-modal" role="dialog" aria-modal="true" aria-label="Experiment viewer">
  <div class="exp-modal-backdrop" id="exp-modal-backdrop"></div>
  <div class="exp-modal-box">

    <button class="exp-modal-close" id="exp-modal-close" aria-label="Close">&#215;</button>

    <!-- Left: media -->
    <div class="exp-modal-media" id="exp-modal-media">
      <div class="exp-modal-media-wrap" id="exp-modal-media-wrap"></div>
      <button class="exp-modal-media-prev hidden" id="exp-media-prev" aria-label="Previous image">&#8592;</button>
      <button class="exp-modal-media-next hidden" id="exp-media-next" aria-label="Next image">&#8594;</button>
      <div class="exp-modal-dots" id="exp-modal-dots"></div>
    </div>

    <!-- Right: info -->
    <div class="exp-modal-info">
      <div class="exp-modal-cat"  id="exp-modal-cat"></div>
      <h2 class="exp-modal-title" id="exp-modal-title"></h2>
      <div class="exp-modal-date" id="exp-modal-date"></div>
      <p class="exp-modal-desc"   id="exp-modal-desc"></p>

      <div class="exp-modal-nav">
        <button class="exp-modal-nav-btn" id="exp-nav-prev">&#8592; Prev</button>
        <button class="exp-modal-nav-btn" id="exp-nav-next">Next &#8594;</button>
      </div>
    </div>

  </div>
</div>

<script>
// ── Data ─────────────────────────────────────────────────────
const EXPERIMENTS = <?= json_encode($all_experiments_js, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>;

// ── State ─────────────────────────────────────────────────────
let currentExp   = 0;  // index into EXPERIMENTS
let currentMedia = 0;  // index into current experiment's media array

// ── Elements ──────────────────────────────────────────────────
const modal      = document.getElementById('exp-modal');
const backdrop   = document.getElementById('exp-modal-backdrop');
const closeBtn   = document.getElementById('exp-modal-close');
const mediaWrap  = document.getElementById('exp-modal-media-wrap');
const dotsEl     = document.getElementById('exp-modal-dots');
const mediaPrev  = document.getElementById('exp-media-prev');
const mediaNext  = document.getElementById('exp-media-next');
const navPrev    = document.getElementById('exp-nav-prev');
const navNext    = document.getElementById('exp-nav-next');
const catEl      = document.getElementById('exp-modal-cat');
const titleEl    = document.getElementById('exp-modal-title');
const dateEl     = document.getElementById('exp-modal-date');
const descEl     = document.getElementById('exp-modal-desc');

// ── Render ────────────────────────────────────────────────────
function renderModal(expIdx, mediaIdx) {
    const exp   = EXPERIMENTS[expIdx];
    const media = exp.media;

    // Info
    catEl.textContent   = exp.category || '';
    titleEl.textContent = exp.title;
    dateEl.textContent  = exp.date || '';
    descEl.textContent  = exp.description || '';

    // Media
    mediaWrap.innerHTML = '';
    if (media.length === 0) {
        mediaWrap.innerHTML = '<div class="exp-modal-no-media"><span>No media</span></div>';
    } else {
        const f = media[mediaIdx];
        if (['mp4','webm','mov'].includes(f.ext)) {
            const v = document.createElement('video');
            v.src      = f.url;
            v.controls = true;
            v.autoplay = false;
            v.style.cssText = 'width:100%;height:100%;object-fit:contain;display:block';
            mediaWrap.appendChild(v);
        } else {
            const img = document.createElement('img');
            img.src = f.url;
            img.alt = exp.title;
            mediaWrap.appendChild(img);
        }
    }

    // Dots
    dotsEl.innerHTML = '';
    if (media.length > 1) {
        media.forEach((_, i) => {
            const d = document.createElement('button');
            d.className = 'exp-modal-dot' + (i === mediaIdx ? ' active' : '');
            d.setAttribute('aria-label', 'Image ' + (i + 1));
            d.addEventListener('click', e => { e.stopPropagation(); goMedia(i); });
            dotsEl.appendChild(d);
        });
    }

    // Media arrow visibility
    mediaPrev.classList.toggle('hidden', media.length <= 1 || mediaIdx === 0);
    mediaNext.classList.toggle('hidden', media.length <= 1 || mediaIdx === media.length - 1);

    // Experiment nav
    navPrev.disabled = expIdx === 0;
    navNext.disabled = expIdx === EXPERIMENTS.length - 1;

    currentExp   = expIdx;
    currentMedia = mediaIdx;
}

function openModal(expIdx) {
    renderModal(expIdx, 0);
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    modal.focus();
}

function closeModal() {
    modal.classList.remove('is-open');
    document.body.style.overflow = '';
    // Stop any playing video
    const v = mediaWrap.querySelector('video');
    if (v) { v.pause(); }
}

function goExp(delta) {
    const next = currentExp + delta;
    if (next < 0 || next >= EXPERIMENTS.length) return;
    renderModal(next, 0);
}

function goMedia(idx) {
    const exp = EXPERIMENTS[currentExp];
    if (idx < 0 || idx >= exp.media.length) return;
    renderModal(currentExp, idx);
}

// ── Card clicks ───────────────────────────────────────────────
// Distinguish drag (scroll) from click using movement threshold
document.querySelectorAll('.exp-slider-track').forEach(track => {
    let mouseDownX = 0, mouseDownY = 0, didDrag = false;

    track.addEventListener('mousedown', e => {
        mouseDownX = e.clientX;
        mouseDownY = e.clientY;
        didDrag    = false;
    });

    track.addEventListener('mousemove', e => {
        if (Math.abs(e.clientX - mouseDownX) > 5 || Math.abs(e.clientY - mouseDownY) > 5) {
            didDrag = true;
        }
    });

    track.addEventListener('click', e => {
        if (didDrag) return;
        const card = e.target.closest('.exp-card[data-idx]');
        if (!card) return;
        openModal(parseInt(card.dataset.idx, 10));
    });
});

// ── Controls ──────────────────────────────────────────────────
backdrop.addEventListener('click', closeModal);
closeBtn.addEventListener('click', closeModal);
navPrev.addEventListener('click',  () => goExp(-1));
navNext.addEventListener('click',  () => goExp(+1));
mediaPrev.addEventListener('click', e => { e.stopPropagation(); goMedia(currentMedia - 1); });
mediaNext.addEventListener('click', e => { e.stopPropagation(); goMedia(currentMedia + 1); });

// Keyboard
document.addEventListener('keydown', e => {
    if (!modal.classList.contains('is-open')) return;
    if (e.key === 'Escape')      closeModal();
    if (e.key === 'ArrowLeft')   EXPERIMENTS[currentExp].media.length > 1 ? goMedia(currentMedia - 1) : goExp(-1);
    if (e.key === 'ArrowRight')  EXPERIMENTS[currentExp].media.length > 1 ? goMedia(currentMedia + 1) : goExp(+1);
});

// Touch swipe (modal)
let touchStartX = 0;
modal.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
modal.addEventListener('touchend', e => {
    const dx = e.changedTouches[0].clientX - touchStartX;
    if (Math.abs(dx) < 40) return;
    if (dx < 0) EXPERIMENTS[currentExp].media.length > 1 ? goMedia(currentMedia + 1) : goExp(+1);
    else        EXPERIMENTS[currentExp].media.length > 1 ? goMedia(currentMedia - 1) : goExp(-1);
});

// ── Horizontal scroll (existing drag behaviour) ───────────────
document.querySelectorAll('.exp-slider-track').forEach(track => {
    let isDown = false, startX, scrollLeft;
    track.addEventListener('mousedown', e => {
        isDown     = true;
        startX     = e.pageX - track.offsetLeft;
        scrollLeft = track.scrollLeft;
        track.style.cursor = 'grabbing';
    });
    track.addEventListener('mouseleave', () => { isDown = false; track.style.cursor = 'grab'; });
    track.addEventListener('mouseup',    () => { isDown = false; track.style.cursor = 'grab'; });
    track.addEventListener('mousemove', e => {
        if (!isDown) return;
        e.preventDefault();
        track.scrollLeft = scrollLeft - (e.pageX - track.offsetLeft - startX);
    });
});
</script>

<?php render_footer(); ?>
