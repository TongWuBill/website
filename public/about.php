<?php
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/media.php';

$about_photos = list_about_media();
$about_photo  = !empty($about_photos) ? $about_photos[0] : null;

render_header('About — Tong Wu');
?>

<div class="about-page">
  <div class="about-layout">

    <!-- ── Main content ── -->
    <div class="about-main">

      <!-- Intro -->
      <header class="about-head">
        <p class="about-eyebrow">Tong Wu</p>
        <h1 class="about-headline">Creative technologist working with AI,
real-time systems, and interactive media.</h1>
        <p class="about-sub">Focused on translating data, sound, and human behavior
into responsive and embodied experiences.</p>
      </header>

      <!-- Education -->
      <section class="ab-row">
        <h2 class="ab-label">Education</h2>
        <div class="ab-content">

          <div class="ab-entry">
            <div class="ab-entry-primary">Pratt Institute</div>
            <div class="ab-entry-secondary">MFA, Digital Art (Interactive Arts)</div>
            <div class="ab-entry-meta">2023–2025</div>
          </div>

          <div class="ab-entry">
            <div class="ab-entry-primary">Duke University</div>
            <div class="ab-entry-secondary">BFA, Media and Arts</div>
            <div class="ab-entry-meta">2018–2022</div>
          </div>

        </div>
      </section>

      <!-- Experience -->
      <section class="ab-row">
        <h2 class="ab-label">Experience</h2>
        <div class="ab-content">

          <div class="ab-entry">
            <div class="ab-entry-primary">Assistant Researcher</div>
            <div class="ab-entry-secondary">Experimental Sound &amp; Interactive Media, Pratt Institute</div>
            <div class="ab-entry-meta">2025–Present</div>
          </div>

          <div class="ab-entry">
            <div class="ab-entry-primary">Lab Manager</div>
            <div class="ab-entry-secondary">Digital Fabrication Lab, Pratt Institute</div>
            <div class="ab-entry-meta">2024–2025</div>
          </div>

          <div class="ab-entry">
            <div class="ab-entry-primary">Assistant Researcher</div>
            <div class="ab-entry-secondary">HCI Lab, Duke Kunshan University</div>
            <div class="ab-entry-meta">2022–2023</div>
          </div>

          <div class="ab-entry">
            <div class="ab-entry-primary">Creative Art Producer</div>
            <div class="ab-entry-secondary">Leo Burnett</div>
            <div class="ab-entry-meta">2021</div>
          </div>

        </div>
      </section>

      <!-- Selected Focus -->
      <section class="ab-row">
        <h2 class="ab-label">Selected Focus</h2>
        <div class="ab-content">
          <ul class="ab-list">
            <li>Real-time audiovisual systems</li>
            <li>Projection mapping installations</li>
            <li>Interactive performance systems</li>
            <li>Tangible / embodied interaction</li>
          </ul>
        </div>
      </section>

      <!-- Skills -->
      <section class="ab-row">
        <h2 class="ab-label">Skills</h2>
        <div class="ab-content">
          <ul class="ab-list ab-list--skills">
            <li>
              <span class="ab-skill-cat">Interactive systems</span>
              <span class="ab-skill-tools">TouchDesigner, OSC, sensors</span>
            </li>
            <li>
              <span class="ab-skill-cat">AI &amp; generative media</span>
              <span class="ab-skill-tools">ComfyUI, video synthesis</span>
            </li>
            <li>
              <span class="ab-skill-cat">Hardware &amp; prototyping</span>
              <span class="ab-skill-tools">Arduino, signal processing</span>
            </li>
            <li>
              <span class="ab-skill-cat">3D &amp; fabrication</span>
              <span class="ab-skill-tools">Fusion 360, digital fabrication</span>
            </li>
          </ul>
        </div>
      </section>

      <!-- Contact -->
      <section class="ab-row ab-row--last">
        <h2 class="ab-label">Contact</h2>
        <div class="ab-content">
          <div class="ab-contact-links">

            <a href="mailto:tongwubill@outlook.com" class="ab-contact-link">
              <span class="ab-contact-icon">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <rect x="1" y="2.5" width="12" height="9" rx="1" stroke="currentColor" stroke-width="1.1"/>
                  <path d="M1 4L7 8.5L13 4" stroke="currentColor" stroke-width="1.1" stroke-linejoin="round"/>
                </svg>
              </span>
              <span class="ab-contact-text">tongwubill@outlook.com</span>
            </a>

            <a href="https://www.linkedin.com/in/tongwubill" class="ab-contact-link" target="_blank" rel="noopener noreferrer">
              <span class="ab-contact-icon">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <rect x="1" y="1" width="12" height="12" rx="1.5" stroke="currentColor" stroke-width="1.1"/>
                  <circle cx="4.2" cy="4.2" r="0.85" fill="currentColor"/>
                  <line x1="4.2" y1="6" x2="4.2" y2="10.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
                  <path d="M7 10.5V8C7 6.9 7.7 6.3 8.7 6.3S10.5 6.9 10.5 8V10.5" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/>
                  <line x1="7" y1="6" x2="7" y2="10.5" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/>
                </svg>
              </span>
              <span class="ab-contact-text">linkedin.com/in/tongwubill</span>
            </a>

          </div>
        </div>
      </section>

    </div><!-- /.about-main -->

    <!-- ── Sidebar photo ── -->
    <aside class="about-sidebar">
      <div class="about-photo">
        <?php if ($about_photo): ?>
          <img src="<?= htmlspecialchars($about_photo['url']) ?>"
               alt="Tong Wu"
               class="about-photo-img">
        <?php else: ?>
          <div class="about-photo-placeholder"></div>
        <?php endif; ?>
      </div>
    </aside>

  </div><!-- /.about-layout -->
</div><!-- /.about-page -->

<?php render_footer(); ?>
