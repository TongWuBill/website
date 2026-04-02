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
                    });
                });
            });

            // bfcache restore — skip transition, snap visible
            window.addEventListener('pageshow', function (e) {
                if (e.persisted) document.body.classList.add('instant');
            });

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
        <div class="page-content">
    <?php
}

function render_footer() {
    ?>
        </div><!-- /.page-content -->
    </body>
    </html>
    <?php
}