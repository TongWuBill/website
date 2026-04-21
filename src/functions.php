<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lang.php';

function get_all_projects() {
    $db = get_db();
    $stmt = $db->query("SELECT * FROM projects WHERE is_published = 1 AND (page_section IS NULL OR page_section = 'work') ORDER BY sort_order DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_projects_by_category(string $cat): array {
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM projects WHERE is_published = 1 AND page_section = ? ORDER BY sort_order DESC");
    $stmt->execute([$cat]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_project_by_slug($slug) {
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM projects WHERE slug = ? AND is_published = 1");
    $stmt->execute([$slug]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function render_header($title = '') {
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= htmlspecialchars($title ?: 'Tong Wu') ?></title>
        <!-- sets initial state before first paint so the enter animation is visible even on initial load -->
        <style>.page-content { opacity: 0.08; transform: translateY(10px); }</style>
        <link rel="stylesheet" href="/css/style.css?v=<?= filemtime(__DIR__ . '/../public/css/style.css') ?>">
        <?php
        $fav_dir = __DIR__ . '/../public/uploads/home';
        foreach (glob($fav_dir . '/favicon.*') ?: [] as $_fav) {
            $fav_ext = pathinfo($_fav, PATHINFO_EXTENSION);
            $fav_type = $fav_ext === 'ico' ? 'image/x-icon' : ($fav_ext === 'svg' ? 'image/svg+xml' : 'image/' . $fav_ext);
            echo '<link rel="icon" type="' . $fav_type . '" href="/uploads/home/favicon.' . $fav_ext . '?v=' . filemtime($_fav) . '">' . "\n        ";
            break;
        }
        ?>
        <script defer src="https://cloud.umami.is/script.js" data-website-id="2baa3983-e078-4c3b-92af-5dd0b54ad5e6"></script>
    </head>
    <body>
        <script>
        (function () {
            // Enter: wait for DOMContentLoaded, then double-rAF to guarantee
            // the browser paints one frame at the initial state (opacity 0.08)
            // before the transition fires — works on both initial load and navigation.
            document.addEventListener('DOMContentLoaded', function () {
                var content = document.querySelector('.page-content');
                if (!content) return;
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        content.classList.add('is-visible');
                        document.body.classList.add('content-ready');
                    });
                });
            });

            // bfcache restore — remove leaving state, snap visible instantly
            window.addEventListener('pageshow', function (e) {
                if (e.persisted) {
                    document.body.classList.remove('page-leaving');
                    document.body.classList.add('instant');
                    var content = document.querySelector('.page-content');
                    if (content) content.classList.add('is-visible');
                }
            });

            // ── Media fade-in (Apple-style) ──────────────────────────
            // Images/video start at opacity:0 via CSS :not(.is-loaded).
            // We add .is-loaded once the media is fully decoded so the
            // browser-native transition reveals it smoothly.
            (function () {
                var SEL = [
                    '.pd-hero-img',
                    '.pd-media-asset',
                    '.pd-gallery-asset',
                    '.exp-card-image img',
                    '.work-card-image img',
                    '.work-card-thumb-vid',
                    '.home-work-image img',
                    '.hero-slide',
                    '.about-photo-img',
                    '.exp-modal-img',
                    '.exp-modal-vid'
                ].join(',');

                function reveal(el) { el.classList.add('is-loaded'); }

                function watch(el) {
                    if (el.tagName === 'IMG') {
                        // Already decoded (cached) — reveal immediately before next paint
                        if (el.complete && el.naturalWidth > 0) { reveal(el); return; }
                        el.addEventListener('load',  function () { reveal(this); }, { once: true });
                        el.addEventListener('error', function () { reveal(this); }, { once: true });
                    } else if (el.tagName === 'VIDEO') {
                        // readyState >= 1 (HAVE_METADATA) is enough for thumbnail cards
                        if (el.readyState >= 1) { reveal(el); return; }
                        el.addEventListener('loadedmetadata', function () { reveal(this); }, { once: true });
                        el.addEventListener('loadeddata',     function () { reveal(this); }, { once: true });
                        el.addEventListener('error',          function () { reveal(this); }, { once: true });
                        // Fallback: reveal after 800ms regardless
                        setTimeout(function () { reveal(el); }, 800);
                    }
                }

                document.addEventListener('DOMContentLoaded', function () {
                    document.querySelectorAll(SEL).forEach(watch);

                    // Work card video thumbnails: autoplay loop
                    document.querySelectorAll('.work-card-thumb-vid').forEach(function (v) {
                        v.play().catch(function () {});
                    });

                    // Watch DOM for dynamically inserted media (e.g. experiment modal)
                    new MutationObserver(function (mutations) {
                        mutations.forEach(function (m) {
                            m.addedNodes.forEach(function (n) {
                                if (n.nodeType !== 1) return;
                                if (n.matches && n.matches(SEL)) watch(n);
                                if (n.querySelectorAll) n.querySelectorAll(SEL).forEach(watch);
                            });
                        });
                    }).observe(document.body, { childList: true, subtree: true });
                });
            }());

            // Exit: fade body, navigate quickly so pages overlap
            document.addEventListener('click', function (e) {
                var link = e.target.closest('a[href]');
                if (!link) return;
                var href = link.getAttribute('href');
                if (!href || href.charAt(0) === '#' || link.target === '_blank' || link.hasAttribute('download')) return;
                if (/^https?:\/\//.test(href)) return;
                e.preventDefault();
                document.body.classList.add('page-leaving');
                setTimeout(function () { window.location.href = href; }, 30);
            });
        }());
        </script>
        <nav>
            <div class="nav-links">
                <a href="/"><?= t('nav.home') ?></a>
                <a href="/work"><?= t('nav.work') ?></a>
                <a href="/ai"><?= t('nav.ai') ?></a>
                <a href="/lab"><?= t('nav.lab') ?></a>
                <a href="/experiments"><?= t('nav.experiments') ?></a>
                <a href="/about"><?= t('nav.about') ?></a>
            </div>
            <?= lang_toggle_html() ?>
        </nav>

        <!-- Loading indicator — outside .page-content so it isn't masked by
             the content opacity transition. Visible by default, hidden via
             body.content-ready once JS fires. -->
        <div class="page-loader" aria-hidden="true">
            <div class="page-loader-sq"></div>
        </div>

        <div class="page-content">
    <?php
}

function render_footer() {
    $lang        = get_lang();
    $_ht_path    = realpath(__DIR__ . '/../public/uploads') . '/home/content.json';
    $home_text   = file_exists($_ht_path) ? (json_decode(file_get_contents($_ht_path), true) ?: []) : [];
    $footer_copy = ($lang === 'cn' && !empty($home_text['footer_cn']))
        ? $home_text['footer_cn']
        : (!empty($home_text['footer_en']) ? $home_text['footer_en'] : t('footer.copy'));
    ?>
        <footer class="site-footer">
            <p class="site-footer-copy"><?= htmlspecialchars($footer_copy) ?></p>
        </footer>
        </div><!-- /.page-content -->
    </body>
    </html>
    <?php
}