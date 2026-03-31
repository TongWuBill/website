<?php

require_once __DIR__ . '/db.php';

function get_all_projects() {
    $db = get_db();
    $stmt = $db->query("SELECT * FROM projects WHERE is_published = 1 ORDER BY id ASC");
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
        <link rel="stylesheet" href="/css/style.css">
    </head>
    <body>
        <nav>
            <div class="nav-links">
                <a href="/">Home</a>
                <a href="/work">Work</a>
                <a href="/experiments">Experiments</a>
                <a href="/about">About</a>
            </div>
        </nav>
    <?php
}

function render_footer() {
    ?>
    </body>
    </html>
    <?php
}