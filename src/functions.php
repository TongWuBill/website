<?php

require_once __DIR__ . '/db.php';

function get_all_projects() {
    $db = get_db();
    $stmt = $db->query("SELECT * FROM projects WHERE is_published = 1 ORDER BY sort_order DESC");
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
        <link rel="stylesheet" href="/css/style.css">
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

            // bfcache restore — skip transition, snap visible
            window.addEventListener('pageshow', function (e) {
                if (e.persisted) document.body.classList.add('instant');
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
                        if (el.readyState >= 2) { reveal(el); return; }
                        el.addEventListener('loadeddata', function () { reveal(this); }, { once: true });
                        el.addEventListener('error',      function () { reveal(this); }, { once: true });
                    }
                }

                document.addEventListener('DOMContentLoaded', function () {
                    document.querySelectorAll(SEL).forEach(watch);

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
                <a href="/">Home</a>
                <a href="/work">Work</a>
                <a href="/experiments">Experiments</a>
                <a href="/about">About</a>
            </div>
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
    ?>
        <footer class="site-footer">
            <p class="site-footer-copy">&copy; 2026 Tong Wu. All rights reserved.</p>
        </footer>
        </div><!-- /.page-content -->
    </body>
    </html>
    <?php
}