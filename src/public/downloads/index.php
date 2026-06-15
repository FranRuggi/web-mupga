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

  <!-- Instrucciones paso a paso -->
  <section class="section section--alt">
    <div class="dl-guide">
      <h2 class="section-title">&#191;C&#243;mo descargar y empezar a jugar?</h2>
      <div class="dl-guide-grid">

        <div class="dl-guide-card">
          <div class="dl-guide-card__head">Si ya ten&#233;s el juego instalado</div>
          <ol class="dl-guide-steps">
            <li><span>Descarg&#225; el launcher desde mupga.com.ar</span></li>
            <li><span>Copialo donde quieras (escritorio, carpeta del juego, donde prefieras)</span></li>
            <li><span>Abrilo y elegi&#769; <strong>&#34;Ya tengo el juego&#34;</strong></span></li>
            <li><span>Seleccion&#225; la carpeta donde ten&#233;s el juego instalado</span></li>
            <li><span>&#161;Listo! Dale a <strong>JUGAR</strong></span></li>
          </ol>
          <div class="dl-guide-card__foot">
            <a href="#" class="dl-guide-yt-btn">
              <span aria-hidden="true">&#9658;</span> Ver tutorial en YouTube
            </a>
          </div>
        </div>

        <div class="dl-guide-card">
          <div class="dl-guide-card__head">Si es tu primera vez</div>
          <ol class="dl-guide-steps">
            <li><span>Descarg&#225; el launcher desde mupga.com.ar</span></li>
            <li><span>Copialo donde quieras</span></li>
            <li><span>Abrilo y elegi&#769; <strong>&#34;Descargar el juego&#34;</strong></span></li>
            <li><span>Esper&#225; que termine la descarga (~1&nbsp;GB). Los archivos del juego se descargar&#225;n en la misma carpeta donde est&#225; el launcher</span></li>
            <li><span>&#161;Listo! Dale a <strong>JUGAR</strong></span></li>
          </ol>
          <div class="dl-guide-card__foot">
            <a href="#" class="dl-guide-yt-btn">
              <span aria-hidden="true">&#9658;</span> Ver tutorial en YouTube
            </a>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- Aviso de actualización del launcher -->
  <section class="section">
    <div class="dl-update-card">
      <div class="dl-update-card__head">&#191;El launcher te pide actualizar?</div>
      <p class="dl-update-card__text">De vez en cuando lanzamos mejoras al launcher. Cuando haya una nueva versi&#243;n disponible, vas a ver un aviso al abrirlo. Simplemente hac&#233; click en <strong>&#34;Descargar&#34;</strong>, esper&#225; que termine y volv&#233; a abrirlo. <strong>Tus archivos del juego no se tocan.</strong></p>
    </div>
  </section>

</main>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
