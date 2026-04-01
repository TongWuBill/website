<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/media.php';

function get_all_projects_admin(): array {
    $db = get_db();
    $stmt = $db->query("SELECT * FROM projects ORDER BY sort_order DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_project_by_id(int $id): array|false {
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function create_project(array $data): int {
    $db  = get_db();
    $now = date('Y-m-d H:i:s');

    $next_sort = (int) $db->query("SELECT COALESCE(MAX(sort_order), 0) FROM projects")->fetchColumn() + 1;

    $stmt = $db->prepare("
        INSERT INTO projects (
            title, slug, year, category,
            immersion, context, system_text, interaction_text,
            material, reflection, video_url,
            is_published, created_at, updated_at, sort_order
        ) VALUES (
            :title, :slug, :year, :category,
            :immersion, :context, :system_text, :interaction_text,
            :material, :reflection, :video_url,
            :is_published, :created_at, :updated_at, :sort_order
        )
    ");

    $stmt->execute([
        ':title'            => $data['title'],
        ':slug'             => $data['slug'],
        ':year'             => $data['year']             ?? null,
        ':category'         => $data['category']         ?? null,
        ':immersion'        => $data['immersion']        ?? null,
        ':context'          => $data['context']          ?? null,
        ':system_text'      => $data['system_text']      ?? null,
        ':interaction_text' => $data['interaction_text'] ?? null,
        ':material'         => $data['material']         ?? null,
        ':reflection'       => $data['reflection']       ?? null,
        ':video_url'        => $data['video_url']        ?? null,
        ':is_published'     => $data['is_published']     ?? 1,
        ':created_at'       => $now,
        ':updated_at'       => $now,
        ':sort_order'       => $next_sort,
    ]);

    $id = (int) $db->lastInsertId();
    create_project_folder($data['slug']);
    return $id;
}

function update_project(int $id, array $data): void {
    $db = get_db();

    // Fetch old slug before overwriting it
    $before = get_project_by_id($id);

    $stmt = $db->prepare("
        UPDATE projects SET
            title            = :title,
            slug             = :slug,
            year             = :year,
            category         = :category,
            immersion        = :immersion,
            context          = :context,
            system_text      = :system_text,
            interaction_text = :interaction_text,
            material         = :material,
            reflection       = :reflection,
            video_url        = :video_url,
            is_published     = :is_published,
            updated_at       = :updated_at,
            edit_count       = COALESCE(edit_count, 0) + 1
        WHERE id = :id
    ");

    $stmt->execute([
        ':title'            => $data['title'],
        ':slug'             => $data['slug'],
        ':year'             => $data['year']             ?? null,
        ':category'         => $data['category']         ?? null,
        ':immersion'        => $data['immersion']        ?? null,
        ':context'          => $data['context']          ?? null,
        ':system_text'      => $data['system_text']      ?? null,
        ':interaction_text' => $data['interaction_text'] ?? null,
        ':material'         => $data['material']         ?? null,
        ':reflection'       => $data['reflection']       ?? null,
        ':video_url'        => $data['video_url']        ?? null,
        ':is_published'     => $data['is_published']     ?? 1,
        ':updated_at'       => date('Y-m-d H:i:s'),
        ':id'               => $id,
    ]);

    // Rename media folder if slug changed
    if ($before && $before['slug'] !== $data['slug']) {
        rename_project_folder($before['slug'], $data['slug']);
    }
}

