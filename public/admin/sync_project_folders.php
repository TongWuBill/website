<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/project-model.php';
require_once __DIR__ . '/../../src/media.php';

require_login();

$projects = get_all_projects_admin();

echo "<pre>\n";
echo "Syncing project folders...\n\n";

$created = 0;
$skipped = 0;

foreach ($projects as $p) {
    $slug = $p['slug'];
    $path = get_project_media_path($slug);

    if (is_dir($path)) {
        echo "  skip    $slug\n";
        $skipped++;
    } else {
        $ok = create_project_folder($slug);
        echo ($ok ? "  created $slug\n" : "  FAILED  $slug  (check permissions)\n");
        if ($ok) $created++;
    }
}

echo "\nDone. Created: $created  |  Already existed: $skipped\n";
echo "</pre>\n";
echo '<p><a href="/admin/dashboard.php">&larr; Back to Dashboard</a></p>';
