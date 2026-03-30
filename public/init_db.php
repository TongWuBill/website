<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
ob_implicit_flush(true);
if (ob_get_level()) ob_end_flush();

echo "init_db.php started<br>";

require __DIR__ . '/../src/db.php';

echo "db.php loaded<br>";

try {
    $db = get_db();
} catch (Exception $e) {
    echo "DB connection failed: " . htmlspecialchars($e->getMessage()) . "<br>";
    exit(1);
}

echo "database connected<br>";

$db->exec("
CREATE TABLE IF NOT EXISTS projects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    slug TEXT,
    immersion TEXT,
    context TEXT,
    system_text TEXT,
    interaction_text TEXT,
    material TEXT,
    reflection TEXT
);
");

echo "table created<br>";

$db->exec("DELETE FROM projects;");

echo "old data cleared<br>";

$projects = [
    [
        'title'            => 'Beyond Pitaya',
        'slug'             => 'beyond-pitaya',
        'immersion'        => 'Immersive intro...',
        'context'          => 'Concept...',
        'system_text'      => 'System...',
        'interaction_text' => 'Interaction...',
        'material'         => 'Material...',
        'reflection'       => 'Reflection...',
    ],
    [
        'title'            => 'Bodily Nature',
        'slug'             => 'bodily-nature',
        'immersion'        => 'Immersive intro...',
        'context'          => 'Concept...',
        'system_text'      => 'System...',
        'interaction_text' => 'Interaction...',
        'material'         => 'Material...',
        'reflection'       => 'Reflection...',
    ],
    [
        'title'            => 'Tidal Vile',
        'slug'             => 'tidal-vile',
        'immersion'        => 'Immersive intro...',
        'context'          => 'Concept...',
        'system_text'      => 'System...',
        'interaction_text' => 'Interaction...',
        'material'         => 'Material...',
        'reflection'       => 'Reflection...',
    ],
    [
        'title'            => 'Rice Journey',
        'slug'             => 'rice-journey',
        'immersion'        => 'Immersive intro...',
        'context'          => 'Concept...',
        'system_text'      => 'System...',
        'interaction_text' => 'Interaction...',
        'material'         => 'Material...',
        'reflection'       => 'Reflection...',
    ],
    [
        'title'            => 'Gestural Resonance',
        'slug'             => 'gestural-resonance',
        'immersion'        => 'Immersive intro...',
        'context'          => 'Concept...',
        'system_text'      => 'System...',
        'interaction_text' => 'Interaction...',
        'material'         => 'Material...',
        'reflection'       => 'Reflection...',
    ],
    [
        'title'            => 'City in Flow',
        'slug'             => 'city-in-flow',
        'immersion'        => 'Immersive intro...',
        'context'          => 'Concept...',
        'system_text'      => 'System...',
        'interaction_text' => 'Interaction...',
        'material'         => 'Material...',
        'reflection'       => 'Reflection...',
    ],
    [
        'title'            => 'Obscured Self',
        'slug'             => 'obscured-self',
        'immersion'        => 'Immersive intro...',
        'context'          => 'Concept...',
        'system_text'      => 'System...',
        'interaction_text' => 'Interaction...',
        'material'         => 'Material...',
        'reflection'       => 'Reflection...',
    ],
    [
        'title'            => 'Cosmic Chimes',
        'slug'             => 'cosmic-chimes',
        'immersion'        => 'Immersive intro...',
        'context'          => 'Concept...',
        'system_text'      => 'System...',
        'interaction_text' => 'Interaction...',
        'material'         => 'Material...',
        'reflection'       => 'Reflection...',
    ],
    [
        'title'            => 'Cosmic Blossom',
        'slug'             => 'cosmic-blossom',
        'immersion'        => 'Immersive intro...',
        'context'          => 'Concept...',
        'system_text'      => 'System...',
        'interaction_text' => 'Interaction...',
        'material'         => 'Material...',
        'reflection'       => 'Reflection...',
    ],
];

$stmt = $db->prepare("
    INSERT INTO projects (title, slug, immersion, context, system_text, interaction_text, material, reflection)
    VALUES (:title, :slug, :immersion, :context, :system_text, :interaction_text, :material, :reflection)
");

foreach ($projects as $p) {
    $stmt->execute($p);
    echo "Inserted: " . htmlspecialchars($p['title']) . "<br>";
}

echo "<br>Database initialized successfully! " . count($projects) . " projects inserted.";
