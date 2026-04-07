<?php

// Uploads lives inside /public so files are web-accessible
define('UPLOADS_ROOT', realpath(__DIR__ . '/../public/uploads') ?: __DIR__ . '/../public/uploads');

// ── Path resolvers ────────────────────────────────────────────────────────────

function get_project_media_path(string $slug): string {
    return UPLOADS_ROOT . '/projects/' . $slug;
}

function get_home_media_path(): string {
    return UPLOADS_ROOT . '/home';
}

function get_experiment_media_path(string $slug): string {
    return UPLOADS_ROOT . '/experiments/' . $slug;
}

function get_about_media_path(): string {
    return UPLOADS_ROOT . '/about';
}

// ── Folder creation ───────────────────────────────────────────────────────────

function create_project_folder(string $slug): bool {
    $path = get_project_media_path($slug);
    if (is_dir($path)) return true;
    return mkdir($path, 0775, true);
}

function create_experiment_folder(string $slug): bool {
    $path = get_experiment_media_path($slug);
    if (is_dir($path)) return true;
    return mkdir($path, 0775, true);
}

// ── Folder rename ─────────────────────────────────────────────────────────────

function rename_project_folder(string $old_slug, string $new_slug): bool {
    if ($old_slug === $new_slug) return true;

    $old = get_project_media_path($old_slug);
    $new = get_project_media_path($new_slug);

    if (!is_dir($old)) {
        // Old folder doesn't exist — just create the new one
        return mkdir($new, 0775, true);
    }

    if (is_dir($new)) return false; // Target already exists, refuse to overwrite

    return rename($old, $new);
}

// ── Folder deletion ───────────────────────────────────────────────────────────

function delete_project_folder(string $slug): bool {
    $path = get_project_media_path($slug);
    return _delete_directory($path);
}

// Recursively deletes a directory and all its contents
function _delete_directory(string $path): bool {
    if (!is_dir($path)) return true; // Already gone, nothing to do

    $items = array_diff(scandir($path), ['.', '..']);

    foreach ($items as $item) {
        $full = "$path/$item";
        if (is_dir($full)) {
            _delete_directory($full);
        } else {
            unlink($full);
        }
    }

    return rmdir($path);
}

// ── Media listing ─────────────────────────────────────────────────────────────

function list_project_media(string $slug): array {
    $path = get_project_media_path($slug);
    return _list_media_files($path);
}

function list_home_media(): array {
    return array_values(array_filter(
        _list_media_files(get_home_media_path()),
        fn($f) => $f['ext'] !== 'json' && !str_starts_with($f['name'], 'favicon.')
    ));
}

function get_home_text(): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    $path = get_home_media_path() . '/content.json';
    $cache = file_exists($path) ? (json_decode(file_get_contents($path), true) ?: []) : [];
    return $cache;
}

function list_about_media(): array {
    return array_values(array_filter(
        _list_media_files(get_about_media_path()),
        fn($f) => $f['ext'] !== 'json'
    ));
}

// Returns all files in a folder as ['name' => ..., 'url' => ..., 'size' => ..., 'ext' => ...]
function _list_media_files(string $path): array {
    if (!is_dir($path)) return [];

    $files = [];
    $items = array_diff(scandir($path), ['.', '..']);

    foreach ($items as $item) {
        $full = "$path/$item";
        if (!is_file($full)) continue;

        // Build a public URL by stripping everything up to /uploads
        $rel = '/uploads' . substr($full, strlen(UPLOADS_ROOT));
        $rel = str_replace('\\', '/', $rel); // normalise on Windows

        $files[] = [
            'name' => $item,
            'url'  => $rel,
            'ext'  => strtolower(pathinfo($item, PATHINFO_EXTENSION)),
            'size' => filesize($full),
            'path' => $full,
        ];
    }

    return $files;
}

