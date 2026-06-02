<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$pageTitle = 'Inicio';

ob_start();
?>

<main class="site-main">

  <!-- ── Hero ── -->
  <section class="hero">
    <img class="hero-bg"
         src="assets/img/hero-bg.jpg"
         alt=""
         aria-hidden="true"
         onerror="this.style.display='none'">

    <div class="hero-content">
      <p class="hero-eyebrow animate-in">Servidor privado · Season 6 · MuEmu</p>
      <h1 class="hero-title animate-in delay-1">MuPGA</h1>
      <p class="hero-tagline animate-in delay-2">
        La experiencia clásica de MU Online, renovada.<br>
        Resets, Castle Siege y comunidad activa. Unite hoy.
      </p>
      <div class="hero-actions animate-in delay-3">
        <a href="register/" class="btn btn-primary">Registrarme</a>
        <a href="downloads/" class="btn btn-secondary">Descargar el juego</a>
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
