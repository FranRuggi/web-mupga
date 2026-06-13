<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$pageTitle = 'Inicio';
$extraJs   = 'hero-slider.js';

ob_start();
?>

<main class="site-main">

  <!-- ── Hero ── -->
  <section class="hero">
    <div class="hero-slider" aria-hidden="true">
      <div class="hero-slide"><video src="assets/img/slider/vid-2.mp4" muted playsinline preload="metadata"></video></div>
      <div class="hero-slide"><img src="assets/img/slider/img-1.jpg" alt=""></div>
      <div class="hero-slide"><img src="assets/img/slider/img-3.jpg" alt=""></div>
      <div class="hero-slide"><img src="assets/img/slider/img-4.jpg" alt=""></div>
      <div class="hero-slide"><img src="assets/img/slider/img-5.jpg" alt=""></div>
      <div class="hero-slide"><video src="assets/img/slider/vid-3.mp4" muted playsinline preload="metadata"></video></div>
      <div class="hero-slide"><img src="assets/img/slider/img-6.jpg" alt=""></div>
      <div class="hero-slide"><img src="assets/img/slider/img-2.jpg" alt=""></div>
      <div class="hero-slide"><img src="assets/img/slider/img-8.jpg" alt=""></div>
      <div class="hero-slide"><img src="assets/img/slider/img-9.jpg" alt=""></div>
      <div class="hero-slide"><img src="assets/img/slider/img-10.jpg" alt=""></div>
      <div class="hero-slide"><video src="assets/img/slider/vid-4.mp4" muted playsinline preload="metadata"></video></div>
    </div>

    <!-- Orbes decorativos animados -->
    <div class="hero-orb hero-orb--1" aria-hidden="true"></div>
    <div class="hero-orb hero-orb--2" aria-hidden="true"></div>
    <div class="hero-orb hero-orb--3" aria-hidden="true"></div>

    <div class="hero-content">
      <p class="hero-eyebrow animate-in">Servidor privado · Season 6 </p>
      <h1 class="hero-title animate-in delay-1">MuPGA</h1>
      <p id="hero-greeting" hidden></p>
      <p class="hero-tagline animate-in delay-2">
        La experiencia clásica de MU Online, renovada.<br>
        Resets, Castle Siege y comunidad activa. Unite hoy.
      </p>
      <div class="hero-actions animate-in delay-3">
        <a href="register/" class="btn btn-primary" id="hero-cta">Registrarme</a>
        <a href="downloads/" class="btn btn-secondary">Descargar el juego</a>
      </div>
      <div class="hero-social animate-in delay-3">
        <a href="https://discord.com/invite/xTxFHSmVhf" target="_blank" rel="noopener" class="hero-social-link hero-social-link--discord">
          <svg width="18" height="14" viewBox="0 0 18 14" fill="currentColor"><path d="M15.25 1.17A14.85 14.85 0 0 0 11.5 0c-.17.3-.37.7-.5 1.02a13.7 13.7 0 0 0-4 0C6.86.7 6.65.3 6.48 0A14.9 14.9 0 0 0 2.73 1.18C.39 4.7-.24 8.13.08 11.5a14.94 14.94 0 0 0 4.56 2.3c.37-.5.7-1.03.98-1.6a9.7 9.7 0 0 1-1.54-.74l.37-.29a10.66 10.66 0 0 0 9.1 0l.37.29a9.7 9.7 0 0 1-1.55.74c.28.57.6 1.1.98 1.6a14.9 14.9 0 0 0 4.57-2.3c.37-3.9-.63-7.3-2.73-10.33ZM6.01 9.43c-.86 0-1.57-.8-1.57-1.78s.69-1.78 1.57-1.78c.87 0 1.58.8 1.57 1.78 0 .98-.7 1.78-1.57 1.78Zm5.98 0c-.87 0-1.57-.8-1.57-1.78s.69-1.78 1.57-1.78c.87 0 1.58.8 1.57 1.78 0 .98-.7 1.78-1.57 1.78Z"/></svg>
          Discord
        </a>
        <a href="https://chat.whatsapp.com/DqaUqom63aFALaBsK2l7of" target="_blank" rel="noopener" class="hero-social-link hero-social-link--whatsapp">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.28-.1-.48-.15-.68.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.68-1.63-.93-2.23-.24-.58-.49-.5-.68-.51-.17 0-.37-.01-.57-.01-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.87 1.22 3.07.15.2 2.1 3.2 5.08 4.49.71.31 1.27.49 1.7.63.72.23 1.37.2 1.89.12.58-.09 1.76-.72 2.01-1.42.25-.7.25-1.3.17-1.42-.07-.13-.28-.2-.57-.35Zm-5.43 7.43h-.01a9.87 9.87 0 0 1-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.38A9.86 9.86 0 0 1 2.1 12c0-5.46 4.44-9.9 9.91-9.9a9.86 9.86 0 0 1 7.01 2.9 9.86 9.86 0 0 1 2.9 7c-.01 5.46-4.45 9.91-9.88 9.91ZM12.04 0C5.37 0 0 5.37 0 12c0 2.11.55 4.18 1.6 6.01L0 24l6.14-1.61A12 12 0 0 0 12.04 24C18.7 24 24 18.63 24 12S18.7 0 12.04 0Z"/></svg>
          WhatsApp
        </a>
      </div>
    </div>
  </section>

  <!-- ── Stats del servidor ── -->
  <section class="section section--alt">
    <h2 class="section-title">Información del servidor</h2>
    <div class="info-grid" id="info-cards">
      <!-- JS renderiza las cards con datos reales desde /api/serverinfo.php -->
      <?php for ($i = 0; $i < 6; $i++): ?>
        <div class="info-card skeleton" style="height:130px"></div>
      <?php endfor; ?>
    </div>
  </section>

  <!-- ── Top jugadores ── -->
  <section class="section">
    <h2 class="section-title">Top 3 — Resets</h2>
    <div class="ranking-list" id="top-players">
      <!-- JS renderiza desde /api/rankings.php?type=resets&limit=3 -->
    </div>
    <div class="center-action">
      <a href="rankings/" class="btn btn-secondary">Ver todos los rankings</a>
    </div>
  </section>

  <!-- ── Últimas noticias ── -->
  <section class="section section--alt">
    <h2 class="section-title">Últimas noticias</h2>
    <div id="home-news" class="card-grid card-grid--3">
      <div class="card"><div class="card-body"><div class="skeleton" style="height:0.7rem;margin-bottom:.5rem;border-radius:4px"></div><div class="skeleton" style="height:1rem;width:70%;margin-bottom:.5rem;border-radius:4px"></div><div class="skeleton" style="height:3rem;border-radius:4px"></div></div></div>
      <div class="card"><div class="card-body"><div class="skeleton" style="height:0.7rem;margin-bottom:.5rem;border-radius:4px"></div><div class="skeleton" style="height:1rem;width:60%;margin-bottom:.5rem;border-radius:4px"></div><div class="skeleton" style="height:3rem;border-radius:4px"></div></div></div>
      <div class="card"><div class="card-body"><div class="skeleton" style="height:0.7rem;margin-bottom:.5rem;border-radius:4px"></div><div class="skeleton" style="height:1rem;width:65%;margin-bottom:.5rem;border-radius:4px"></div><div class="skeleton" style="height:3rem;border-radius:4px"></div></div></div>
    </div>
  </section>

</main>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
