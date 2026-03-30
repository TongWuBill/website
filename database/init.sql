CREATE TABLE projects (
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

INSERT INTO projects (
    title, slug, immersion, context, system_text,
    interaction_text, material, reflection
) VALUES (
    'Beyond Pitaya',
    'beyond-pitaya',
    'Immersive intro...',
    'Concept...',
    'System...',
    'Interaction...',
    'Material...',
    'Reflection...'
);