<?php

require_once __DIR__ . '/db.php';

function get_all_projects_admin(): array {
    $db = get_db();
    $stmt = $db->query("SELECT * FROM projects ORDER BY id ASC");
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

    $stmt = $db->prepare("
        INSERT INTO projects (
            title, slug, year, category,
            immersion, context, system_text, interaction_text,
            material, reflection, video_url,
            is_published, created_at, updated_at
        ) VALUES (
            :title, :slug, :year, :category,
            :immersion, :context, :system_text, :interaction_text,
            :material, :reflection, :video_url,
            :is_published, :created_at, :updated_at
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
    ]);

    return (int) $db->lastInsertId();
}

function update_project(int $id, array $data): void {
    $db = get_db();

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
}

