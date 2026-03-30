<?php
require_once __DIR__ . '/../src/functions.php';
render_header('About');
?>

<div class="about-wrap">

  <!-- 1. Hero -->
  <section class="about-hero">
    <div class="about-portrait">portrait</div>
    <div class="about-hero-text">
      <h1 class="about-name">Tong Wu</h1>
      <p class="about-title">Interactive Artist / Creative Technologist</p>
    </div>
  </section>

  <!-- 2. Intro -->
  <section class="about-section">
    <p class="section-label">In short</p>
    <p>Short statement here. One or two sentences that capture the core of the practice — what you make, how, and why.</p>
    <p>Second line if needed. Something about the kind of experiences or questions you work with.</p>
  </section>

  <!-- 3. Artist Statement -->
  <section class="about-section">
    <p class="section-label">Statement</p>
    <p>Artist statement goes here. A focused paragraph about the themes, methods, and intentions behind the work. Write in first person. Keep it direct and specific rather than abstract.</p>
  </section>

  <!-- 4. Practice / Method -->
  <section class="about-section">
    <p class="section-label">Practice</p>
    <p>Describe the first dimension of your practice — materials, systems, or processes you work with.</p>
    <p>Describe a second dimension — how the work operates in space or involves audience/interaction.</p>
    <p>Describe a third dimension if needed — research threads, collaborations, or ongoing questions.</p>
  </section>

  <!-- 5. Selected Experience -->
  <section class="about-section">
    <p class="section-label">Experience</p>
    <ul class="experience-list">
      <li>
        <span class="exp-year">2024</span>
        <div class="exp-detail">
          Exhibition title or residency name
          <span>Venue / Institution, City</span>
        </div>
      </li>
      <li>
        <span class="exp-year">2023</span>
        <div class="exp-detail">
          Exhibition title or residency name
          <span>Venue / Institution, City</span>
        </div>
      </li>
      <li>
        <span class="exp-year">2022</span>
        <div class="exp-detail">
          Degree / Programme name
          <span>School / University, City</span>
        </div>
      </li>
      <li>
        <span class="exp-year">2021</span>
        <div class="exp-detail">
          Exhibition title or role
          <span>Venue / Institution, City</span>
        </div>
      </li>
    </ul>
  </section>

  <!-- 6. Tools / Systems -->
  <section class="about-section">
    <p class="section-label">Tools &amp; Systems</p>
    <ul class="tools-list">
      <li>Tool one</li>
      <li>Tool two</li>
      <li>Tool three</li>
      <li>Tool four</li>
      <li>Tool five</li>
      <li>Tool six</li>
    </ul>
  </section>

  <!-- 7. Contact -->
  <section class="about-section">
    <p class="section-label">Contact</p>
    <ul class="contact-links">
      <li><a href="mailto:tongwubill@outlook.com">tongwubill@outlook.com</a></li>
      <li><a href="#">Instagram</a></li>
      <li><a href="#">LinkedIn</a></li>
    </ul>
  </section>

</div>

<?php render_footer(); ?>
