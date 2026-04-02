<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/media.php';

function slugify(string $title): string {
    $s = strtolower(trim($title));
    $s = preg_replace('/[^\w\s-]/u', '', $s);   // strip non-word chars
    $s = preg_replace('/[\s_]+/', '-', $s);       // spaces/underscores → hyphen
    $s = preg_replace('/-{2,}/', '-', $s);        // collapse multiple hyphens
    return trim($s, '-') ?: 'project';
}

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
            title, slug, subtitle, year, category, skillset, material, exhibition, location,
            sections, video_url, is_published, created_at, updated_at, sort_order
        ) VALUES (
            :title, :slug, :subtitle, :year, :category, :skillset, :material, :exhibition, :location,
            :sections, :video_url, :is_published, :created_at, :updated_at, :sort_order
        )
    ");

    $stmt->execute([
        ':title'        => $data['title'],
        ':slug'         => $data['slug'],
        ':subtitle'     => $data['subtitle']   ?? null,
        ':year'         => $data['year']       ?? null,
        ':category'     => $data['category']   ?? null,
        ':skillset'     => $data['skillset']   ?? null,
        ':material'     => $data['material']   ?? null,
        ':exhibition'   => $data['exhibition'] ?? null,
        ':location'     => $data['location']   ?? null,
        ':sections'     => $data['sections']   ?? null,
        ':video_url'    => $data['video_url']  ?? null,
        ':is_published' => $data['is_published'] ?? 1,
        ':created_at'   => $now,
        ':updated_at'   => $now,
        ':sort_order'   => $next_sort,
    ]);

    $id = (int) $db->lastInsertId();
    create_project_folder($data['slug']);
    return $id;
}

function update_project(int $id, array $data): void {
    $db = get_db();

    $before = get_project_by_id($id);

    $stmt = $db->prepare("
        UPDATE projects SET
            title        = :title,
            slug         = :slug,
            subtitle     = :subtitle,
            year         = :year,
            category     = :category,
            skillset     = :skillset,
            material     = :material,
            exhibition   = :exhibition,
            location     = :location,
            sections     = :sections,
            video_url    = :video_url,
            is_published = :is_published,
            updated_at   = :updated_at,
            edit_count   = COALESCE(edit_count, 0) + 1
        WHERE id = :id
    ");

    $stmt->execute([
        ':title'        => $data['title'],
        ':slug'         => $data['slug'],
        ':subtitle'     => $data['subtitle']   ?? null,
        ':year'         => $data['year']       ?? null,
        ':category'     => $data['category']   ?? null,
        ':skillset'     => $data['skillset']   ?? null,
        ':material'     => $data['material']   ?? null,
        ':exhibition'   => $data['exhibition'] ?? null,
        ':location'     => $data['location']   ?? null,
        ':sections'     => $data['sections']   ?? null,
        ':video_url'    => $data['video_url']  ?? null,
        ':is_published' => $data['is_published'] ?? 1,
        ':updated_at'   => date('Y-m-d H:i:s'),
        ':id'           => $id,
    ]);

    if ($before && $before['slug'] !== $data['slug']) {
        rename_project_folder($before['slug'], $data['slug']);
    }
}
