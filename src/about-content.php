<?php

function get_about_content_path(): string {
    return (defined('UPLOADS_ROOT') ? UPLOADS_ROOT : __DIR__ . '/../public/uploads') . '/about/content.json';
}

function load_about_content(): array {
    $path = get_about_content_path();
    if (!file_exists($path)) return [];
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function save_about_content(array $data): bool {
    $dir = dirname(get_about_content_path());
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    return file_put_contents(
        get_about_content_path(),
        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    ) !== false;
}

// Get a bilingual field from about content, with lang.php fallback
function ac(array $content, string $key, string $lang, string $fallback_en = '', string $fallback_cn = ''): string {
    $val = $content[$key] ?? null;
    if ($val === null || $val === '') {
        return $lang === 'cn' ? ($fallback_cn ?: $fallback_en) : $fallback_en;
    }
    if (is_array($val)) {
        return $lang === 'cn' ? ($val['cn'] ?? $val['en'] ?? $fallback_en) : ($val['en'] ?? $fallback_en);
    }
    return (string)$val;
}
