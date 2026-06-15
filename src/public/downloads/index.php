<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
$pageTitle = 'Descargas';
$extraJs   = 'downloads.js';
ob_start();
?>

<main class="site-main">

  <div class="page-hero">
    <h1 class="page-hero-title">Descargas</h1>
    <p class="page-hero-sub">Descargá el cliente del juego y empezá a jugar en MuPGA.</p>
  </div>

  <section class="section">
    <div id="downloads-content">
      <!-- downloads.js carga api/downloadsdata.php y renderiza acá -->
      <div class="downloads-grid">
        <div class="download-card skeleton-block">
          <div class="skeleton" style="height:120px;border-radius:var(--radius)"></div>
        </div>
        <div class="download-card skeleton-block">
          <div class="skeleton" style="height:120px;border-radius:var(--radius)"></div>
        </div>
      </div>
    </div>
  </section>

  <!-- Instrucciones de instalación -->
  <section class="section section--alt">
    <div class="dl-howto">
      <h2 class="section-title">&#191;C&#243;mo descargar y empezar a jugar?</h2>
      <div class="dl-howto-grid">

        <div class="dl-howto-card">
          <h3 class="dl-howto-card__title">Si ya ten&#233;s el juego instalado</h3>
          <ol class="dl-steps">
            <li>Descarg&#225; el launcher desde mupga.com.ar</li>
            <li>Copialo donde quieras (escritorio, carpeta del juego, donde prefieras)</li>
            <li>Abrilo y elegi&#769; <strong>&#34;Ya tengo el juego&#34;</strong></li>
            <li>Seleccion&#225; la carpeta donde ten&#233;s el juego instalado</li>
            <li>&#161;Listo! Dale a <strong>JUGAR</strong></li>
          </ol>
          <a href="#" class="dl-yt-btn">
            <span class="dl-yt-btn__icon" aria-hidden="true">&#9658;</span>
            Ver tutorial en YouTube
          </a>
        </div>

        <div class="dl-howto-card">
          <h3 class="dl-howto-card__title">Si es tu primera vez</h3>
          <ol class="dl-steps">
            <li>Descarg&#225; el launcher desde mupga.com.ar</li>
            <li>Copialo donde quieras</li>
            <li>Abrilo y elegi&#769; <strong>&#34;Descargar el juego&#34;</strong></li>
            <li>Esper&#225; que termine la descarga (~1&nbsp;GB). Los archivos del juego se descargar&#225;n en la misma carpeta donde est&#225; el launcher</li>
            <li>&#161;Listo! Dale a <strong>JUGAR</strong></li>
          </ol>
          <a href="#" class="dl-yt-btn">
            <span class="dl-yt-btn__icon" aria-hidden="true">&#9658;</span>
            Ver tutorial en YouTube
          </a>
        </div>

      </div>
    </div>
  </section>

  <!-- Aviso de actualización del launcher -->
  <section class="section">
    <div class="dl-update-callout">
      <div class="dl-update-callout__icon" aria-hidden="true">&#8593;</div>
      <div class="dl-update-callout__body">
        <h3 class="dl-update-callout__title">&#191;El launcher te pide actualizar?</h3>
        <p class="dl-update-callout__text">De vez en cuando lanzamos mejoras al launcher. Cuando haya una nueva versi&#243;n disponible, vas a ver un aviso al abrirlo. Simplemente hac&#233; click en <strong>&#34;Descargar&#34;</strong>, esper&#225; que termine y volv&#233; a abrirlo. Tus archivos del juego no se tocan.</p>
      </div>
    </div>
  </section>

</main>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
