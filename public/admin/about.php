<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/media.php';
require_once __DIR__ . '/../../src/about-content.php';

require_login();

$is_ajax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
$allowed_img = ['jpg','jpeg','png','webp','gif'];

// ── Save text content ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_content') {
    $c = load_about_content();

    $c['eyebrow']  = ['en' => trim($_POST['eyebrow_en']  ?? ''), 'cn' => trim($_POST['eyebrow_cn']  ?? '')];
    $c['headline'] = ['en' => trim($_POST['headline_en'] ?? ''), 'cn' => trim($_POST['headline_cn'] ?? '')];
    $c['sub']      = ['en' => trim($_POST['sub_en']      ?? ''), 'cn' => trim($_POST['sub_cn']      ?? '')];
    $c['contact_email']    = trim($_POST['contact_email']    ?? '');
    $c['contact_linkedin'] = trim($_POST['contact_linkedin'] ?? '');

    // Education
    $edu = [];
    foreach ($_POST['edu_school'] ?? [] as $i => $v) {
        if (trim($v) === '') continue;
        $edu[] = [
            'school'     => trim($v),
            'degree_en'  => trim($_POST['edu_degree_en'][$i]  ?? ''),
            'degree_cn'  => trim($_POST['edu_degree_cn'][$i]  ?? ''),
            'years'      => trim($_POST['edu_years'][$i]      ?? ''),
        ];
    }
    $c['education'] = $edu;

    // Experience
    $exp = [];
    foreach ($_POST['exp_role_en'] ?? [] as $i => $v) {
        if (trim($v) === '' && trim($_POST['exp_org_en'][$i] ?? '') === '') continue;
        $exp[] = [
            'role_en' => trim($v),
            'role_cn' => trim($_POST['exp_role_cn'][$i] ?? ''),
            'org_en'  => trim($_POST['exp_org_en'][$i]  ?? ''),
            'org_cn'  => trim($_POST['exp_org_cn'][$i]  ?? ''),
            'years'   => trim($_POST['exp_years'][$i]   ?? ''),
        ];
    }
    $c['experience'] = $exp;

    // Focus
    $focus = [];
    foreach ($_POST['focus_en'] ?? [] as $i => $v) {
        if (trim($v) === '') continue;
        $focus[] = ['en' => trim($v), 'cn' => trim($_POST['focus_cn'][$i] ?? '')];
    }
    $c['focus'] = $focus;

    // Skills
    $skills = [];
    foreach ($_POST['skill_cat_en'] ?? [] as $i => $v) {
        if (trim($v) === '') continue;
        $skills[] = [
            'cat_en'   => trim($v),
            'cat_cn'   => trim($_POST['skill_cat_cn'][$i]   ?? ''),
            'tools_en' => trim($_POST['skill_tools_en'][$i] ?? ''),
            'tools_cn' => trim($_POST['skill_tools_cn'][$i] ?? ''),
        ];
    }
    $c['skills'] = $skills;

    save_about_content($c);
    header('Location: /admin/about.php?saved=1');
    exit;
}

// ── Photo upload ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload') {
    $dir = get_about_media_path();
    if (!is_dir($dir)) mkdir($dir, 0775, true);

    $raw = $_FILES['media'] ?? [];
    $entries = [];
    if (!empty($raw['name'])) {
        if (is_array($raw['name'])) {
            foreach ($raw['name'] as $i => $n)
                $entries[] = ['name' => $n, 'tmp' => $raw['tmp_name'][$i], 'error' => $raw['error'][$i]];
        } else {
            $entries[] = ['name' => $raw['name'], 'tmp' => $raw['tmp_name'], 'error' => $raw['error']];
        }
    }

    $uploaded = []; $errors = [];
    foreach ($entries as $entry) {
        $ext = strtolower(pathinfo($entry['name'], PATHINFO_EXTENSION));
        if ($entry['error'] !== UPLOAD_ERR_OK) { $errors[] = $entry['name'] . ': upload error'; continue; }
        if (!in_array($ext, $allowed_img))       { $errors[] = '.' . $ext . ' not allowed'; continue; }
        if (!is_writable($dir))                  { $errors[] = 'Folder not writable'; break; }
        $dest = $dir . '/' . uniqid('', true) . '_' . preg_replace('/[^a-z0-9._-]/i', '_', $entry['name']);
        if (move_uploaded_file($entry['tmp'], $dest)) {
            $uploaded[] = ['url' => '/uploads/about/' . basename($dest), 'name' => basename($dest), 'ext' => $ext];
        } else {
            $errors[] = $entry['name'] . ': move failed';
        }
    }
    if ($is_ajax) { header('Content-Type: application/json'); echo json_encode(['ok' => empty($errors), 'files' => $uploaded, 'errors' => $errors]); exit; }
    header('Location: /admin/about.php' . (empty($errors) ? '?saved=1' : '?upload_error=' . urlencode(implode('; ', $errors))));
    exit;
}

// ── Photo delete ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $fname = basename($_POST['filename'] ?? '');
    if ($fname !== '') { $path = get_about_media_path() . '/' . $fname; if (is_file($path)) unlink($path); }
    if ($is_ajax) { header('Content-Type: application/json'); echo json_encode(['ok' => true]); exit; }
    header('Location: /admin/about.php');
    exit;
}

$c      = load_about_content();
$photos = list_about_media();

// Pre-fill defaults from lang.php if JSON is empty
function cv(array $c, string $key, string $lang, string $default_en = '', string $default_cn = ''): string {
    $val = $c[$key] ?? null;
    if (!$val) return $lang === 'en' ? $default_en : $default_cn;
    if (is_array($val)) return $lang === 'en' ? ($val['en'] ?? $default_en) : ($val['cn'] ?? $default_cn);
    return (string)$val;
}

$def_edu = [
    ['school' => 'Pratt Institute', 'degree_en' => 'MFA, Digital Art (Interactive Arts)', 'degree_cn' => '数字艺术 MFA（互动艺术方向）', 'years' => '2023–2025'],
    ['school' => 'Duke University',  'degree_en' => 'BFA, Media and Arts',                  'degree_cn' => '媒体与艺术 BFA',              'years' => '2018–2022'],
];
$def_exp = [
    ['role_en' => 'Assistant Researcher',    'role_cn' => '研究助理',       'org_en' => 'Experimental Sound & Interactive Media, Pratt Institute', 'org_cn' => '实验声音与交互媒体实验室，Pratt Institute', 'years' => '2025–Present'],
    ['role_en' => 'Lab Manager',             'role_cn' => '实验室管理员',   'org_en' => 'Digital Fabrication Lab, Pratt Institute',                'org_cn' => '数字制造实验室，Pratt Institute',           'years' => '2024–2025'],
    ['role_en' => 'Assistant Researcher',    'role_cn' => '研究助理',       'org_en' => 'HCI Lab, Duke Kunshan University',                        'org_cn' => 'HCI 实验室，昆山杜克大学',                  'years' => '2022–2023'],
    ['role_en' => 'Creative Art Producer',   'role_cn' => '创意艺术制片人', 'org_en' => 'Leo Burnett',                                             'org_cn' => 'Leo Burnett',                               'years' => '2021'],
];
$def_focus = [
    ['en' => 'Real-time audiovisual systems',   'cn' => '实时视听系统'],
    ['en' => 'Projection mapping installations', 'cn' => '投影映射装置'],
    ['en' => 'Interactive performance systems',  'cn' => '交互表演系统'],
    ['en' => 'Tangible / embodied interaction',  'cn' => '具身交互与有形界面'],
];
$def_skills = [
    ['cat_en' => 'Interactive systems',    'cat_cn' => '交互系统',      'tools_en' => 'TouchDesigner, OSC, sensors',    'tools_cn' => 'TouchDesigner、OSC、传感器'],
    ['cat_en' => 'AI & generative media',  'cat_cn' => 'AI 与生成媒体', 'tools_en' => 'ComfyUI, video synthesis',        'tools_cn' => 'ComfyUI、视频合成'],
    ['cat_en' => 'Hardware & prototyping', 'cat_cn' => '硬件与原型制作', 'tools_en' => 'Arduino, signal processing',     'tools_cn' => 'Arduino、信号处理'],
    ['cat_en' => '3D & fabrication',       'cat_cn' => '3D 与数字制造', 'tools_en' => 'Fusion 360, digital fabrication', 'tools_cn' => 'Fusion 360、数字制造'],
];

$edu    = !empty($c['education'])  ? $c['education']  : $def_edu;
$exp    = !empty($c['experience']) ? $c['experience'] : $def_exp;
$focus  = !empty($c['focus'])      ? $c['focus']      : $def_focus;
$skills = !empty($c['skills'])     ? $c['skills']     : $def_skills;

function hv(string $v): string { return htmlspecialchars($v, ENT_QUOTES); }
function fv(array $c, string $key, string $sub = ''): string {
    $val = $c[$key] ?? '';
    if ($sub && is_array($val)) $val = $val[$sub] ?? '';
    return hv((string)$val);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About — Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; background: #f4f4f4; color: #222; padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        h1 { font-size: 1.3rem; }
        .group { background: #fff; border: 1px solid #ddd; padding: 1.5rem; max-width: 820px; margin-bottom: 1.5rem; }
        .group-title { font-size: 0.75rem; text-transform: uppercase; letter-spacing: .1em; color: #888; margin-bottom: 1.25rem; padding-bottom: 0.5rem; border-bottom: 1px solid #eee; font-weight: 600; }
        .field { margin-bottom: 1rem; }
        .field:last-child { margin-bottom: 0; }
        label { display: block; font-size: 0.78rem; color: #666; margin-bottom: 4px; }
        input[type="text"], input[type="email"], input[type="url"], textarea {
            width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #ccc; font-size: 0.88rem;
            font-family: inherit; background: #fff;
        }
        input:focus, textarea:focus { outline: 2px solid #222; border-color: transparent; }
        textarea { resize: vertical; }
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
        .en-cn { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.5rem; }
        .en-cn label { font-size: 0.72rem; }
        .row-block { border: 1px solid #e8e8e8; padding: 0.75rem; margin-bottom: 0.75rem; background: #fafafa; }
        .row-block:last-child { margin-bottom: 0; }
        .row-num { font-size: 0.7rem; color: #aaa; margin-bottom: 0.5rem; }
        .actions { display: flex; gap: 0.5rem; margin-top: 1.5rem; max-width: 820px; }
        .save-btn { padding: 0.5rem 1.4rem; background: #222; color: #fff; border: none; font-size: 0.9rem; cursor: pointer; font-family: inherit; }
        .save-btn:hover { background: #444; }
        a.btn { padding: 0.4rem 0.9rem; font-size: 0.85rem; text-decoration: none; border: 1px solid #aaa; color: #555; background: #fff; display: inline-block; }
        a.btn:hover { background: #222; color: #fff; border-color: #222; }
        .ok  { color: #155724; font-size: 0.85rem; margin-bottom: 1rem; }
        .err { color: #c00; font-size: 0.85rem; margin-bottom: 1rem; }
        .hint { font-size: 0.72rem; color: #aaa; margin-top: 3px; }

        /* Photo section */
        .media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px,1fr)); gap: 0.6rem; margin-top: 0.75rem; }
        .media-item { position: relative; border: 1px solid #ddd; background: #fafafa; }
        .media-item img { width: 100%; aspect-ratio: 3/4; object-fit: cover; display: block; }
        .media-item-name { padding: 0.25rem 0.4rem; font-size: 0.68rem; color: #888; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .media-del { position: absolute; top: 4px; right: 4px; background: rgba(200,0,0,0.85); color: #fff; border: none; font-size: 0.7rem; padding: 0.15rem 0.4rem; cursor: pointer; }
        .media-del:hover { background: #c00; }
        .upload-row { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; margin-top: 0.75rem; }
        .upload-row input[type="file"] { flex: 1; min-width: 0; border: 1px solid #ccc; padding: 0.3rem; font-size: 0.85rem; }
        .upload-btn { padding: 0.4rem 0.9rem; background: #fff; border: 1px solid #999; font-size: 0.82rem; cursor: pointer; font-family: inherit; white-space: nowrap; }
        .upload-btn:hover { background: #222; color: #fff; border-color: #222; }
        .section-note { font-size: 0.75rem; color: #888; margin-top: 0.5rem; }
    </style>
</head>
<body>

<div class="header">
    <h1>About Page</h1>
    <a href="/admin/dashboard.php" class="btn">&larr; Dashboard</a>
</div>

<?php if (isset($_GET['saved'])): ?>
<p class="ok" style="max-width:820px">Saved successfully.</p>
<?php endif; ?>
<?php if (!empty($_GET['upload_error'])): ?>
<p class="err" style="max-width:820px"><?= hv($_GET['upload_error']) ?></p>
<?php endif; ?>

<!-- ── Photo ─────────────────────────────────────────────────── -->
<div class="group">
    <div class="group-title">Sidebar Photo</div>
    <?php if (empty($photos)): ?>
    <p style="font-size:0.85rem;color:#aaa;font-style:italic" id="about-empty">No photo uploaded yet.</p>
    <?php endif; ?>
    <div class="media-grid" id="about-grid"<?= empty($photos) ? ' style="display:none"' : '' ?>>
        <?php foreach ($photos as $f): ?>
        <div class="media-item">
            <img src="<?= hv($f['url']) ?>" alt="">
            <div class="media-item-name"><?= hv($f['name']) ?></div>
            <form class="del-form" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="filename" value="<?= hv($f['name']) ?>">
                <button type="submit" class="media-del">✕</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <form id="photo-upload-form" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload">
        <div class="upload-row">
            <input type="file" name="media[]" accept="image/*">
            <button type="submit" class="upload-btn" id="photo-upload-btn">Upload Photo</button>
            <span id="photo-upload-status" style="font-size:0.78rem"></span>
        </div>
    </form>
    <p class="section-note">Only the first photo is shown. Upload a portrait-orientation image.</p>
</div>

<!-- ── Text content form ─────────────────────────────────────── -->
<form method="POST">
<input type="hidden" name="action" value="save_content">

<!-- Intro -->
<div class="group">
    <div class="group-title">Intro Text</div>
    <div class="field">
        <div class="en-cn">
            <div><label>Name / Eyebrow (EN)</label><input type="text" name="eyebrow_en" value="<?= fv($c, 'eyebrow', 'en') ?: 'Tong Wu' ?>"></div>
            <div><label>Name / Eyebrow (中文)</label><input type="text" name="eyebrow_cn" value="<?= fv($c, 'eyebrow', 'cn') ?: '吴彤' ?>"></div>
        </div>
    </div>
    <div class="field">
        <div class="en-cn">
            <div><label>Headline (EN)</label><textarea name="headline_en" rows="3"><?= fv($c, 'headline', 'en') ?: 'Creative technologist working with AI, real-time systems, and interactive media.' ?></textarea></div>
            <div><label>Headline (中文)</label><textarea name="headline_cn" rows="3"><?= fv($c, 'headline', 'cn') ?: '创意技术研究者，专注于 AI、实时系统与交互媒体领域的创作实践。' ?></textarea></div>
        </div>
    </div>
    <div class="field">
        <div class="en-cn">
            <div><label>Sub Text (EN)</label><textarea name="sub_en" rows="3"><?= fv($c, 'sub', 'en') ?: 'Focused on translating data, sound, and human behavior into responsive and embodied experiences.' ?></textarea></div>
            <div><label>Sub Text (中文)</label><textarea name="sub_cn" rows="3"><?= fv($c, 'sub', 'cn') ?: '致力于将数据、声音与人类行为转化为具有响应性和身体性的沉浸体验。' ?></textarea></div>
        </div>
    </div>
</div>

<!-- Education -->
<div class="group">
    <div class="group-title">Education</div>
    <?php foreach ($edu as $i => $ed): ?>
    <div class="row-block">
        <div class="row-num">#<?= $i+1 ?></div>
        <div class="field">
            <div class="two-col">
                <div><label>School (same in both languages)</label><input type="text" name="edu_school[]" value="<?= hv($ed['school'] ?? '') ?>"></div>
                <div><label>Years</label><input type="text" name="edu_years[]" value="<?= hv($ed['years'] ?? '') ?>" placeholder="2023–2025"></div>
            </div>
        </div>
        <div class="field">
            <div class="en-cn">
                <div><label>Degree (EN)</label><input type="text" name="edu_degree_en[]" value="<?= hv($ed['degree_en'] ?? '') ?>"></div>
                <div><label>Degree (中文)</label><input type="text" name="edu_degree_cn[]" value="<?= hv($ed['degree_cn'] ?? '') ?>"></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Experience -->
<div class="group">
    <div class="group-title">Experience</div>
    <?php foreach ($exp as $i => $ex): ?>
    <div class="row-block">
        <div class="row-num">#<?= $i+1 ?></div>
        <div class="field">
            <div class="two-col">
                <div>
                    <div class="en-cn">
                        <div><label>Role (EN)</label><input type="text" name="exp_role_en[]" value="<?= hv($ex['role_en'] ?? '') ?>"></div>
                        <div><label>Role (中文)</label><input type="text" name="exp_role_cn[]" value="<?= hv($ex['role_cn'] ?? '') ?>"></div>
                    </div>
                </div>
                <div><label>Years</label><input type="text" name="exp_years[]" value="<?= hv($ex['years'] ?? '') ?>" placeholder="2024–2025"></div>
            </div>
        </div>
        <div class="field">
            <div class="en-cn">
                <div><label>Organisation (EN)</label><input type="text" name="exp_org_en[]" value="<?= hv($ex['org_en'] ?? '') ?>"></div>
                <div><label>Organisation (中文)</label><input type="text" name="exp_org_cn[]" value="<?= hv($ex['org_cn'] ?? '') ?>"></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Selected Focus -->
<div class="group">
    <div class="group-title">Selected Focus</div>
    <?php foreach ($focus as $i => $f): ?>
    <div class="row-block">
        <div class="en-cn">
            <div><label>Item <?= $i+1 ?> (EN)</label><input type="text" name="focus_en[]" value="<?= hv($f['en'] ?? '') ?>"></div>
            <div><label>Item <?= $i+1 ?> (中文)</label><input type="text" name="focus_cn[]" value="<?= hv($f['cn'] ?? '') ?>"></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Skills -->
<div class="group">
    <div class="group-title">Skills</div>
    <?php foreach ($skills as $i => $sk): ?>
    <div class="row-block">
        <div class="row-num">#<?= $i+1 ?></div>
        <div class="field">
            <div class="en-cn">
                <div><label>Category (EN)</label><input type="text" name="skill_cat_en[]" value="<?= hv($sk['cat_en'] ?? '') ?>"></div>
                <div><label>Category (中文)</label><input type="text" name="skill_cat_cn[]" value="<?= hv($sk['cat_cn'] ?? '') ?>"></div>
            </div>
        </div>
        <div class="field">
            <div class="en-cn">
                <div><label>Tools (EN)</label><input type="text" name="skill_tools_en[]" value="<?= hv($sk['tools_en'] ?? '') ?>"></div>
                <div><label>Tools (中文)</label><input type="text" name="skill_tools_cn[]" value="<?= hv($sk['tools_cn'] ?? '') ?>"></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Contact -->
<div class="group">
    <div class="group-title">Contact</div>
    <div class="field"><label>Email</label><input type="text" name="contact_email" value="<?= hv($c['contact_email'] ?? 'tongwubill@outlook.com') ?>"></div>
    <div class="field"><label>LinkedIn username</label><input type="text" name="contact_linkedin" value="<?= hv($c['contact_linkedin'] ?? 'tongwubill') ?>" placeholder="tongwubill"><p class="hint">Just the username, not the full URL.</p></div>
</div>

<div class="actions">
    <button type="submit" class="save-btn">Save Changes</button>
    <a href="/about" class="btn" target="_blank">Preview About Page</a>
</div>
</form>

<script>
(function () {
    const form   = document.getElementById('photo-upload-form');
    const btn    = document.getElementById('photo-upload-btn');
    const status = document.getElementById('photo-upload-status');
    const grid   = document.getElementById('about-grid');
    const empty  = document.getElementById('about-empty');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const fi = form.querySelector('input[type="file"]');
        if (!fi.files.length) return;
        btn.disabled = true; status.textContent = 'Uploading…'; status.style.color = '#888';
        const fd = new FormData();
        new FormData(form).forEach((v, k) => { if (k !== 'media[]') fd.append(k, v); });
        for (const f of fi.files) fd.append('media[]', f);
        try {
            const res  = await fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd });
            const data = await res.json();
            if (data.files && data.files.length) {
                if (empty) empty.remove();
                grid.style.display = '';
                data.files.forEach(f => {
                    const item = document.createElement('div'); item.className = 'media-item';
                    const img = document.createElement('img'); img.src = f.url; img.alt = ''; item.appendChild(img);
                    const n = document.createElement('div'); n.className = 'media-item-name'; n.textContent = f.name; item.appendChild(n);
                    const df = document.createElement('form'); df.method = 'POST'; df.className = 'del-form';
                    df.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="filename" value="' + f.name.replace(/"/g,'&quot;') + '"><button type="submit" class="media-del">✕</button>';
                    item.appendChild(df);
                    grid.appendChild(item);
                });
            }
            status.textContent = data.errors && data.errors.length ? '✗ ' + data.errors.join('; ') : 'Uploaded';
            status.style.color = data.errors && data.errors.length ? '#c00' : '#155724';
            if (!data.errors || !data.errors.length) { fi.value = ''; setTimeout(() => { status.textContent = ''; }, 3000); }
        } catch { status.textContent = '✗ Network error'; status.style.color = '#c00'; }
        btn.disabled = false;
    });

    document.addEventListener('submit', async function (e) {
        const f = e.target;
        if (!f.classList.contains('del-form')) return;
        e.preventDefault();
        if (!confirm('Delete this photo?')) return;
        const fd = new FormData(f);
        await fetch(window.location.href, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd });
        const item = f.closest('.media-item');
        if (item) item.remove();
        if (!grid.querySelector('.media-item')) grid.style.display = 'none';
    });
}());
</script>
</body>
</html>
