<?php
/**
 * Landing de mupga.com.ar — selector de servidores.
 * Las cards se renderizan en JS desde /api/site/servidores.php: agregar un
 * servidor es una fila en mupga_admin.dbo.servidores, no un deploy de HTML.
 */
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$pageTitle = 'Elegí tu servidor';

ob_start();
?>

<main class="site-main">

  <section class="landing-hero">
    <div class="hero-orb hero-orb--1" aria-hidden="true"></div>
    <div class="hero-orb hero-orb--2" aria-hidden="true"></div>

    <div class="landing-hero__content">
      <p class="hero-eyebrow">MU Online · Season 6</p>
      <h1 class="landing-hero__title">MuPGA</h1>
      <p class="landing-hero__tagline">
        Elegí tu servidor y empezá a jugar.<br>
        Cada uno con sus propias rates, su economía y su comunidad.
      </p>
    </div>
  </section>

  <section class="section">
    <h2 class="section-title">Nuestros servidores</h2>

    <div class="servidor-grid" id="servidor-grid">
      <!-- landing.js renderiza acá. Skeletons mientras carga. -->
      <div class="servidor-card skeleton" style="height:460px"></div>
      <div class="servidor-card skeleton" style="height:460px"></div>
    </div>

    <p class="servidor-empty" id="servidor-error" hidden>
      No pudimos cargar la lista de servidores.
      <button type="button" class="btn btn-secondary btn-sm" id="servidor-retry">Reintentar</button>
    </p>
  </section>

</main>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout_landing.php';
