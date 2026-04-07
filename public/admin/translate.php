<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/project-model.php';
require_once __DIR__ . '/../../src/experiment-model.php';

require_login();

define('DEEPL_API_KEY', '045bcf86-71a9-4bee-81a3-06037fa0aa56:fx');
define('DEEPL_URL', 'https://api-free.deepl.com/v2/translate');

// ── DeepL helper ─────────────────────────────────────────────────────────────

function deepl_translate(array $texts, string $target = 'ZH'): array {
    if (empty($texts)) return [];

    // Filter out empty strings, keep index mapping
    $non_empty = [];
    $index_map = [];
    foreach ($texts as $i => $t) {
        if (trim($t) !== '') {
            $index_map[] = $i;
            $non_empty[] = $t;
        }
    }
    if (empty($non_empty)) return array_fill(0, count($texts), '');

    $payload = json_encode(['text' => $non_empty, 'target_lang' => $target]);
    $ch = curl_init(DEEPL_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Authorization: DeepL-Auth-Key ' . DEEPL_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        throw new RuntimeException("DeepL API error $code: $body");
    }

    $data = json_decode($body, true);
    $translated = array_column($data['translations'], 'text');

    // Reconstruct full array (empty strings stay empty)
    $result = array_fill(0, count($texts), '');
    foreach ($index_map as $pos => $orig_i) {
        $result[$orig_i] = $translated[$pos] ?? '';
    }
    return $result;
}

// ── Run translation ───────────────────────────────────────────────────────────

$log     = [];
$errors  = [];
$dry_run = !isset($_POST['run']);

function log_msg(string $msg): void { global $log; $log[] = $msg; }

if (!$dry_run) {
    $db = get_db();

    // ── Projects ─────────────────────────────────────────────────────────────
    $projects = $db->query("SELECT id, title, subtitle, sections, title_cn, subtitle_cn, sections_cn FROM projects")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($projects as $p) {
        $id = (int)$p['id'];
        try {
            // Title + subtitle in one call
            $texts = [$p['title'], $p['subtitle'] ?? ''];
            [$title_cn, $subtitle_cn] = deepl_translate($texts);

            // Sections
            $sections_cn = null;
            if (!empty($p['sections'])) {
                $secs = json_decode($p['sections'], true);
                if (is_array($secs)) {
                    $labels = array_column($secs, 'label');
                    $bodies = array_column($secs, 'body');
                    $all    = array_merge($labels, $bodies);
                    $trans  = deepl_translate($all);
                    $n      = count($labels);
                    $cn_secs = [];
                    for ($i = 0; $i < $n; $i++) {
                        $cn_secs[] = [
                            'label' => $trans[$i],
                            'body'  => $trans[$i + $n],
                        ];
                    }
                    $sections_cn = json_encode($cn_secs, JSON_UNESCAPED_UNICODE);
                }
            }

            $db->prepare("UPDATE projects SET title_cn=?, subtitle_cn=?, sections_cn=? WHERE id=?")
               ->execute([$title_cn, $subtitle_cn ?: null, $sections_cn, $id]);

            log_msg("✓ Project #{$id} "{$p['title']}" → "{$title_cn}"");
        } catch (Throwable $e) {
            $errors[] = "Project #{$id}: " . $e->getMessage();
        }
    }

    // ── Experiments ──────────────────────────────────────────────────────────
    experiments_ensure_table();
    $exps = $db->query("SELECT id, title, description FROM experiments")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($exps as $e) {
        $id = (int)$e['id'];
        try {
            $texts = [$e['title'], $e['description'] ?? ''];
            [$title_cn, $desc_cn] = deepl_translate($texts);

            $db->prepare("UPDATE experiments SET title_cn=?, description_cn=? WHERE id=?")
               ->execute([$title_cn, $desc_cn ?: null, $id]);

            log_msg("✓ Experiment #{$id} "{$e['title']}" → "{$title_cn}"");
        } catch (Throwable $e) {
            $errors[] = "Experiment #{$id}: " . $e->getMessage();
        }
    }
} else {
    // Dry run — just count what will be translated
    $db       = get_db();
    $projects = $db->query("SELECT id, title FROM projects")->fetchAll(PDO::FETCH_ASSOC);
    experiments_ensure_table();
    $exps     = $db->query("SELECT id, title FROM experiments")->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Translate — Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; background: #f4f4f4; color: #222; padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        h1 { font-size: 1.3rem; }
        .card { background: #fff; border: 1px solid #ddd; padding: 1.5rem; max-width: 700px; margin-bottom: 1.5rem; }
        .card h2 { font-size: 0.75rem; text-transform: uppercase; letter-spacing: .1em; color: #888; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #eee; }
        .note { font-size: 0.85rem; color: #555; line-height: 1.7; margin-bottom: 1rem; }
        .warn { color: #856404; background: #fff3cd; border: 1px solid #ffc107; padding: 0.6rem 1rem; font-size: 0.83rem; margin-bottom: 1rem; }
        .item-list { font-size: 0.82rem; color: #444; line-height: 1.9; }
        .ok   { color: #155724; }
        .err  { color: #c00; }
        .run-btn { padding: 0.5rem 1.4rem; background: #222; color: #fff; border: none; font-size: 0.9rem; cursor: pointer; font-family: inherit; }
        .run-btn:hover { background: #444; }
        a.btn { padding: 0.4rem 0.9rem; font-size: 0.85rem; text-decoration: none; border: 1px solid #aaa; color: #555; background: #fff; display: inline-block; }
        a.btn:hover { background: #222; color: #fff; border-color: #222; }
        .log-item { font-size: 0.8rem; font-family: monospace; line-height: 1.8; }
    </style>
</head>
<body>

<div class="header">
    <h1>Auto-Translate (DeepL)</h1>
    <a href="/admin/dashboard.php" class="btn">&larr; Dashboard</a>
</div>

<?php if ($dry_run): ?>

<div class="card">
    <h2>What will be translated</h2>
    <p class="note">This will call the DeepL API to translate all project and experiment content into Chinese, and save it to the <code>_cn</code> fields in the database. Existing CN content will be <strong>overwritten</strong>.</p>
    <div class="warn">⚠ This runs on the <strong>current database</strong>. Make sure you're running this on production, not local.</div>

    <p class="note"><strong><?= count($projects) ?> projects</strong> to translate (title, subtitle, all sections):</p>
    <div class="item-list">
        <?php foreach ($projects as $p): ?>
        <div>— <?= htmlspecialchars($p['title']) ?> <span style="color:#bbb">(#<?= $p['id'] ?>)</span></div>
        <?php endforeach; ?>
    </div>

    <br>
    <p class="note"><strong><?= count($exps) ?> experiments</strong> to translate (title, description):</p>
    <div class="item-list">
        <?php foreach ($exps as $e): ?>
        <div>— <?= htmlspecialchars($e['title']) ?> <span style="color:#bbb">(#<?= $e['id'] ?>)</span></div>
        <?php endforeach; ?>
    </div>
</div>

<form method="POST">
    <button type="submit" name="run" value="1" class="run-btn"
            onclick="return confirm('Run DeepL translation on all content? This will overwrite existing CN fields.')">
        Run Translation
    </button>
</form>

<?php else: ?>

<div class="card">
    <h2>Translation results</h2>
    <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $err): ?>
        <div class="log-item err">✗ <?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php foreach ($log as $line): ?>
    <div class="log-item ok"><?= htmlspecialchars($line) ?></div>
    <?php endforeach; ?>
    <?php if (empty($errors)): ?>
    <br><p style="font-size:0.85rem;color:#155724;font-weight:600">All done! You can now switch to 中文 mode on the site to check the results, and edit individual entries in the admin to fix anything.</p>
    <?php endif; ?>
</div>

<a href="/admin/translate.php" class="btn">Run Again</a>

<?php endif; ?>

</body>
</html>
