<?php
// ── Language detection ────────────────────────────────────────────────────────

function get_lang(): string {
    if (isset($_COOKIE['lang']) && $_COOKIE['lang'] === 'cn') return 'cn';
    return 'en';
}

// ── Translation helper ────────────────────────────────────────────────────────

function t(string $key): string {
    static $cache = null;
    if ($cache === null) $cache = _lang_strings();
    $lang = get_lang();
    return $cache[$lang][$key] ?? $cache['en'][$key] ?? $key;
}

// ── Lang toggle HTML (for nav) ────────────────────────────────────────────────

function lang_toggle_html(): string {
    $lang    = get_lang();
    $label   = $lang === 'en' ? '中文' : 'EN';
    $next    = $lang === 'en' ? 'cn'   : 'en';
    return '<button class="lang-toggle" onclick="document.cookie=\'lang=' . $next
         . ';path=/;max-age=31536000\';location.reload()" aria-label="Switch language">'
         . $label . '</button>';
}

// ── Strings ───────────────────────────────────────────────────────────────────

function _lang_strings(): array {
    return [
        'en' => [
            // Nav
            'nav.home'        => 'Home',
            'nav.work'        => 'Work',
            'nav.experiments' => 'Experiments',
            'nav.about'       => 'About',

            // Footer
            'footer.copy' => '&copy; 2026 Tong Wu. All rights reserved.',

            // Home page
            'home.ideology' => "Creative technologist working with AI,\nreal-time systems, and interactive media.",

            // About page
            'about.page_title'  => 'About — Tong Wu',
            'about.eyebrow'     => 'Tong Wu',
            'about.headline'    => 'Creative technologist working with AI, real-time systems, and interactive media.',
            'about.sub'         => 'Focused on translating data, sound, and human behavior into responsive and embodied experiences.',
            'about.education'   => 'Education',
            'about.experience'  => 'Experience',
            'about.focus'       => 'Selected Focus',
            'about.skills'      => 'Skills',
            'about.contact'     => 'Contact',

            'about.pratt.school'  => 'Pratt Institute',
            'about.pratt.degree'  => 'MFA, Digital Art (Interactive Arts)',
            'about.duke.school'   => 'Duke University',
            'about.duke.degree'   => 'BFA, Media and Arts',

            'about.exp1.role' => 'Assistant Researcher',
            'about.exp1.org'  => 'Experimental Sound &amp; Interactive Media, Pratt Institute',
            'about.exp2.role' => 'Lab Manager',
            'about.exp2.org'  => 'Digital Fabrication Lab, Pratt Institute',
            'about.exp3.role' => 'Assistant Researcher',
            'about.exp3.org'  => 'HCI Lab, Duke Kunshan University',
            'about.exp4.role' => 'Creative Art Producer',
            'about.exp4.org'  => 'Leo Burnett',

            'about.focus1' => 'Real-time audiovisual systems',
            'about.focus2' => 'Projection mapping installations',
            'about.focus3' => 'Interactive performance systems',
            'about.focus4' => 'Tangible / embodied interaction',

            'about.skill1.cat'   => 'Interactive systems',
            'about.skill1.tools' => 'TouchDesigner, OSC, sensors',
            'about.skill2.cat'   => 'AI &amp; generative media',
            'about.skill2.tools' => 'ComfyUI, video synthesis',
            'about.skill3.cat'   => 'Hardware &amp; prototyping',
            'about.skill3.tools' => 'Arduino, signal processing',
            'about.skill4.cat'   => '3D &amp; fabrication',
            'about.skill4.tools' => 'Fusion 360, digital fabrication',

            // Work page
            'work.page_title' => 'Work — Tong Wu',
            'work.heading'    => 'Work',

            // Experiments page
            'experiments.page_title' => 'Experiments — Tong Wu',
            'experiments.heading'    => 'Experiments',

            // Project detail meta labels
            'project.year'       => 'Year',
            'project.category'   => 'Category',
            'project.materials'  => 'Materials',
            'project.skillset'   => 'Skillset',
            'project.exhibition' => 'Exhibition',
            'project.location'   => 'Location',
        ],

        'cn' => [
            // Nav
            'nav.home'        => '首页',
            'nav.work'        => '作品',
            'nav.experiments' => '实验',
            'nav.about'       => '关于',

            // Footer
            'footer.copy' => '&copy; 2026 吴彤. 保留所有权利。',

            // Home page
            'home.ideology' => "创意技术研究者，专注于 AI、实时系统\n与交互媒体领域的创作实践。",

            // About page
            'about.page_title'  => '关于 — 吴彤',
            'about.eyebrow'     => '吴彤',
            'about.headline'    => '创意技术研究者，专注于 AI、实时系统与交互媒体领域的创作实践。',
            'about.sub'         => '致力于将数据、声音与人类行为转化为具有响应性和身体性的沉浸体验。',
            'about.education'   => '教育背景',
            'about.experience'  => '工作经历',
            'about.focus'       => '研究方向',
            'about.skills'      => '技能',
            'about.contact'     => '联系方式',

            'about.pratt.school'  => 'Pratt Institute',
            'about.pratt.degree'  => '数字艺术 MFA（互动艺术方向）',
            'about.duke.school'   => 'Duke University',
            'about.duke.degree'   => '媒体与艺术 BFA',

            'about.exp1.role' => '研究助理',
            'about.exp1.org'  => '实验声音与交互媒体实验室，Pratt Institute',
            'about.exp2.role' => '实验室管理员',
            'about.exp2.org'  => '数字制造实验室，Pratt Institute',
            'about.exp3.role' => '研究助理',
            'about.exp3.org'  => 'HCI 实验室，昆山杜克大学',
            'about.exp4.role' => '创意艺术制片人',
            'about.exp4.org'  => 'Leo Burnett',

            'about.focus1' => '实时视听系统',
            'about.focus2' => '投影映射装置',
            'about.focus3' => '交互表演系统',
            'about.focus4' => '具身交互与有形界面',

            'about.skill1.cat'   => '交互系统',
            'about.skill1.tools' => 'TouchDesigner、OSC、传感器',
            'about.skill2.cat'   => 'AI 与生成媒体',
            'about.skill2.tools' => 'ComfyUI、视频合成',
            'about.skill3.cat'   => '硬件与原型制作',
            'about.skill3.tools' => 'Arduino、信号处理',
            'about.skill4.cat'   => '3D 与数字制造',
            'about.skill4.tools' => 'Fusion 360、数字制造',

            // Work page
            'work.page_title' => '作品 — 吴彤',
            'work.heading'    => '作品',

            // Experiments page
            'experiments.page_title' => '实验 — 吴彤',
            'experiments.heading'    => '实验',

            // Project detail meta labels
            'project.year'       => '年份',
            'project.category'   => '类别',
            'project.materials'  => '材料',
            'project.skillset'   => '技能',
            'project.exhibition' => '展览',
            'project.location'   => '地点',
        ],
    ];
}
