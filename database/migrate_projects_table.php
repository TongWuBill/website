<?php
// Run from project root: php database/migrate_projects_table.php

// Use the exact same path logic as /src/db.php
$db_path = realpath(__DIR__ . '/../database/portfolio.sqlite')
    ?: __DIR__ . '/../database/portfolio.sqlite';

echo "DB path : $db_path\n";

if (!file_exists($db_path)) {
    echo "ERROR   : file does not exist at the path above.\n";
    echo "          Create the database first or check the path.\n";
    exit(1);
}

echo "File    : EXISTS\n\n";

$db = new PDO('sqlite:' . $db_path);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Read existing columns
$rows     = $db->query("PRAGMA table_info(projects)")->fetchAll(PDO::FETCH_ASSOC);
$existing = array_column($rows, 'name');

if (empty($existing)) {
    echo "ERROR   : table 'projects' not found. Run your init.sql first.\n";
    exit(1);
}

echo "Existing columns: " . implode(', ', $existing) . "\n\n";

// Columns to add if missing
$required = [
    'year'         => 'TEXT',
    'category'     => 'TEXT',
    'cover_image'  => 'TEXT',
    'video_url'    => 'TEXT',
    'is_published' => 'INTEGER DEFAULT 1',
    'created_at'   => 'TEXT',
    'updated_at'   => 'TEXT',
];

foreach ($required as $column => $definition) {
    if (in_array($column, $existing, true)) {
        echo "  skip  : $column (already exists)\n";
    } else {
        $db->exec("ALTER TABLE projects ADD COLUMN $column $definition");
        echo "  added : $column ($definition)\n";
    }
}

// Print final column list
echo "\nFinal columns:\n";
$final = $db->query("PRAGMA table_info(projects)")->fetchAll(PDO::FETCH_ASSOC);
foreach ($final as $col) {
    echo "  [{$col['cid']}] {$col['name']} {$col['type']}";
    if ($col['dflt_value'] !== null) echo " DEFAULT {$col['dflt_value']}";
    echo "\n";
}

echo "\nMigration complete.\n";
