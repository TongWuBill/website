<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/media.php';

// ── Table bootstrap ───────────────────────────────────────────
function experiments_ensure_table(): void {
    $db = get_db();
    $db->exec("
        CREATE TABLE IF NOT EXISTS experiments (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            title       TEXT    NOT NULL,
            category    TEXT,
            date        TEXT,
            description TEXT,
            sort_order  INTEGER DEFAULT 0,
            created_at  TEXT,
            updated_at  TEXT
        )
    ");
    // Add description column if missing (for existing databases)
    $cols = array_column($db->query("PRAGMA table_info(experiments)")->fetchAll(PDO::FETCH_ASSOC), 'name');
    if (!in_array('description', $cols)) {
        $db->exec("ALTER TABLE experiments ADD COLUMN description TEXT");
    }
}

// ── Queries ───────────────────────────────────────────────────
function get_all_experiments(): array {
    experiments_ensure_table();
    $db = get_db();
    return $db->query("SELECT * FROM experiments ORDER BY sort_order DESC, id DESC")
              ->fetchAll(PDO::FETCH_ASSOC);
}

function get_experiments_grouped(): array {
    $rows = get_all_experiments();
    $groups = [];
    foreach ($rows as $r) {
        $cat = $r['category'] ?: 'Uncategorised';
        $groups[$cat][] = $r;
    }
    return $groups;
}

function get_experiment_by_id(int $id): array|false {
    experiments_ensure_table();
    $db   = get_db();
    $stmt = $db->prepare("SELECT * FROM experiments WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function create_experiment(array $data): int {
    experiments_ensure_table();
    $db  = get_db();
    $now = date('Y-m-d H:i:s');
    $next_sort = (int) $db->query("SELECT COALESCE(MAX(sort_order),0) FROM experiments")->fetchColumn() + 1;

    $stmt = $db->prepare("
        INSERT INTO experiments (title, category, date, description, sort_order, created_at, updated_at)
        VALUES (:title, :category, :date, :description, :sort_order, :created_at, :updated_at)
    ");
    $stmt->execute([
        ':title'       => $data['title'],
        ':category'    => $data['category']    ?? null,
        ':date'        => $data['date']         ?? null,
        ':description' => $data['description']  ?? null,
        ':sort_order'  => $next_sort,
        ':created_at'  => $now,
        ':updated_at'  => $now,
    ]);
    $id = (int) $db->lastInsertId();
    create_experiment_folder((string) $id);
    return $id;
}

function update_experiment(int $id, array $data): void {
    experiments_ensure_table();
    $db   = get_db();
    $stmt = $db->prepare("
        UPDATE experiments SET
            title       = :title,
            category    = :category,
            date        = :date,
            description = :description,
            updated_at  = :updated_at
        WHERE id = :id
    ");
    $stmt->execute([
        ':title'       => $data['title'],
        ':category'    => $data['category']    ?? null,
        ':date'        => $data['date']         ?? null,
        ':description' => $data['description']  ?? null,
        ':updated_at'  => date('Y-m-d H:i:s'),
        ':id'          => $id,
    ]);
}

function delete_experiment(int $id): void {
    experiments_ensure_table();
    $db   = get_db();
    $stmt = $db->prepare("DELETE FROM experiments WHERE id = ?");
    $stmt->execute([$id]);
    // remove media folder
    $path = get_experiment_media_path((string) $id);
    if (is_dir($path)) {
        _delete_directory($path);
    }
}

function list_experiment_media(int $id): array {
    return _list_media_files(get_experiment_media_path((string) $id));
}
