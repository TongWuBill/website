<?php
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/media.php';
require_once __DIR__ . '/../src/about-content.php';

$about_photos = list_about_media();
$about_photo  = !empty($about_photos) ? $about_photos[0] : null;

$c    = load_about_content();
$lang = get_lang();

// Helper: get bilingual field with lang.php string fallback
function afield(array $c, string $key, string $lang, string $fk_en, string $fk_cn = ''): string {
    $val = $c[$key] ?? null;
    if (!empty($val)) {
        if (is_array($val)) return $lang === 'cn' ? ($val['cn'] ?? $val['en'] ?? '') : ($val['en'] ?? '');
        return (string)$val;
    }
    return $lang === 'cn' ? (t($fk_cn ?: $fk_en)) : t($fk_en);
}

// Education rows
$education = $c['education'] ?? [
    ['school' => '', 'degree_en' => '', 'degree_cn' => '', 'years' => ''],
    ['school' => '', 'degree_en' => '', 'degree_cn' => '', 'years' => ''],
];
if (empty($education[0]['school'])) {
    $education = [
        ['school' => t('about.pratt.school'), 'degree_en' => t('about.pratt.degree'), 'degree_cn' => ($lang === 'cn' ? t('about.pratt.degree') : ''), 'years' => '2023–2025'],
        ['school' => t('about.duke.school'),  'degree_en' => t('about.duke.degree'),  'degree_cn' => ($lang === 'cn' ? t('about.duke.degree') : ''),  'years' => '2018–2022'],
    ];
}

// Experience rows
$experience = $c['experience'] ?? [];
if (empty($experience)) {
    $experience = [
        ['role_en' => t('about.exp1.role'), 'role_cn' => '', 'org_en' => t('about.exp1.org'), 'org_cn' => '', 'years' => '2025–Present'],
        ['role_en' => t('about.exp2.role'), 'role_cn' => '', 'org_en' => t('about.exp2.org'), 'org_cn' => '', 'years' => '2024–2025'],
        ['role_en' => t('about.exp3.role'), 'role_cn' => '', 'org_en' => t('about.exp3.org'), 'org_cn' => '', 'years' => '2022–2023'],
        ['role_en' => t('about.exp4.role'), 'role_cn' => '', 'org_en' => t('about.exp4.org'), 'org_cn' => '', 'years' => '2021'],
    ];
}

// Focus items
$focus = $c['focus'] ?? [];
if (empty($focus)) {
    $focus = [
        ['en' => t('about.focus1'), 'cn' => ''],
        ['en' => t('about.focus2'), 'cn' => ''],
        ['en' => t('about.focus3'), 'cn' => ''],
        ['en' => t('about.focus4'), 'cn' => ''],
    ];
}

// Skills
$skills = $c['skills'] ?? [];
if (empty($skills)) {
    $skills = [
        ['cat_en' => t('about.skill1.cat'), 'cat_cn' => '', 'tools_en' => t('about.skill1.tools'), 'tools_cn' => ''],
        ['cat_en' => t('about.skill2.cat'), 'cat_cn' => '', 'tools_en' => t('about.skill2.tools'), 'tools_cn' => ''],
        ['cat_en' => t('about.skill3.cat'), 'cat_cn' => '', 'tools_en' => t('about.skill3.tools'), 'tools_cn' => ''],
        ['cat_en' => t('about.skill4.cat'), 'cat_cn' => '', 'tools_en' => t('about.skill4.tools'), 'tools_cn' => ''],
    ];
}

render_header(t('about.page_title'));
?>

<div class="about-page">
  <div class="about-layout">

    <div class="about-main">

      <header class="about-head">
        <p class="about-eyebrow"><?= htmlspecialchars(afield($c, 'eyebrow', $lang, 'about.eyebrow')) ?></p>
        <h1 class="about-headline"><?= htmlspecialchars(afield($c, 'headline', $lang, 'about.headline')) ?></h1>
        <p class="about-sub"><?= htmlspecialchars(afield($c, 'sub', $lang, 'about.sub')) ?></p>
      </header>

      <section class="ab-row">
        <h2 class="ab-label"><?= t('about.education') ?></h2>
        <div class="ab-content">
          <?php foreach ($education as $ed): ?>
          <div class="ab-entry">
            <div class="ab-entry-primary"><?= htmlspecialchars($ed['school'] ?? '') ?></div>
            <div class="ab-entry-secondary"><?= htmlspecialchars($lang === 'cn' && !empty($ed['degree_cn']) ? $ed['degree_cn'] : ($ed['degree_en'] ?? '')) ?></div>
            <div class="ab-entry-meta"><?= htmlspecialchars($ed['years'] ?? '') ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="ab-row">
        <h2 class="ab-label"><?= t('about.experience') ?></h2>
        <div class="ab-content">
          <?php foreach ($experience as $ex): ?>
          <div class="ab-entry">
            <div class="ab-entry-primary"><?= htmlspecialchars($lang === 'cn' && !empty($ex['role_cn']) ? $ex['role_cn'] : ($ex['role_en'] ?? '')) ?></div>
            <div class="ab-entry-secondary"><?= htmlspecialchars($lang === 'cn' && !empty($ex['org_cn'])  ? $ex['org_cn']  : ($ex['org_en']  ?? '')) ?></div>
            <div class="ab-entry-meta"><?= htmlspecialchars($ex['years'] ?? '') ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="ab-row">
        <h2 class="ab-label"><?= t('about.focus') ?></h2>
        <div class="ab-content">
          <ul class="ab-list">
            <?php foreach ($focus as $f): ?>
            <li><?= htmlspecialchars($lang === 'cn' && !empty($f['cn']) ? $f['cn'] : ($f['en'] ?? '')) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </section>

      <section class="ab-row">
        <h2 class="ab-label"><?= t('about.skills') ?></h2>
        <div class="ab-content">
          <ul class="ab-list ab-list--skills">
            <?php foreach ($skills as $sk): ?>
            <li>
              <span class="ab-skill-cat"><?= htmlspecialchars($lang === 'cn' && !empty($sk['cat_cn'])   ? $sk['cat_cn']   : ($sk['cat_en']   ?? '')) ?></span>
              <span class="ab-skill-tools"><?= htmlspecialchars($lang === 'cn' && !empty($sk['tools_cn']) ? $sk['tools_cn'] : ($sk['tools_en'] ?? '')) ?></span>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </section>

      <section class="ab-row ab-row--last">
        <h2 class="ab-label"><?= t('about.contact') ?></h2>
        <div class="ab-content">
          <div class="ab-contact-links">
            <?php $email = $c['contact_email'] ?? 'tongwubill@outlook.com'; ?>
            <?php $linkedin = $c['contact_linkedin'] ?? 'tongwubill'; ?>
            <a href="mailto:<?= htmlspecialchars($email) ?>" class="ab-contact-link">
              <span class="ab-contact-icon">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <rect x="1" y="2.5" width="12" height="9" rx="1" stroke="currentColor" stroke-width="1.1"/>
                  <path d="M1 4L7 8.5L13 4" stroke="currentColor" stroke-width="1.1" stroke-linejoin="round"/>
                </svg>
              </span>
              <span class="ab-contact-text"><?= htmlspecialchars($email) ?></span>
            </a>
            <a href="https://www.linkedin.com/in/<?= htmlspecialchars($linkedin) ?>" class="ab-contact-link" target="_blank" rel="noopener noreferrer">
              <span class="ab-contact-icon">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <rect x="1" y="1" width="12" height="12" rx="1.5" stroke="currentColor" stroke-width="1.1"/>
                  <circle cx="4.2" cy="4.2" r="0.85" fill="currentColor"/>
                  <line x1="4.2" y1="6" x2="4.2" y2="10.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
                  <path d="M7 10.5V8C7 6.9 7.7 6.3 8.7 6.3S10.5 6.9 10.5 8V10.5" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/>
                  <line x1="7" y1="6" x2="7" y2="10.5" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/>
                </svg>
              </span>
              <span class="ab-contact-text">linkedin.com/in/<?= htmlspecialchars($linkedin) ?></span>
            </a>
          </div>
        </div>
      </section>

    </div>

    <aside class="about-sidebar">
      <div class="about-photo">
        <?php if ($about_photo): ?>
          <img src="<?= htmlspecialchars($about_photo['url']) ?>" alt="Tong Wu" class="about-photo-img">
        <?php else: ?>
          <div class="about-photo-placeholder"></div>
        <?php endif; ?>
      </div>
    </aside>

  </div>
</div>

<?php render_footer(); ?>
