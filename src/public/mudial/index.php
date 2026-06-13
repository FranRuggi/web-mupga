<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$pageTitle = 'Prode MuPGA';
$extraJs   = 'mudial.js';

ob_start();
?>

<main class="site-main">

  <div class="page-hero">
    <h1 class="page-hero-title">Prode MuPGA</h1>
    <p class="page-hero-sub">Predecí los resultados y ganá WCoins y días VIP automáticamente.</p>
  </div>

  <section class="section">

    <div id="prode-alert" class="alert" role="alert"></div>

    <!-- Reglamento colapsable -->
    <details class="prode-rules">
      <summary class="prode-rules-toggle">
        <span class="prode-rules-chevron" aria-hidden="true">›</span>
        Ver reglamento
      </summary>
      <div class="prode-rules-body">

        <div class="prode-rules-section">
          <h3 class="prode-rules-heading">¿Cómo funciona?</h3>
          <p class="prode-rules-text">Predecí el resultado de cada partido antes de que empiece. Por cada acierto ganás premios automáticos en tu cuenta y puntos para el ranking final del mundial.</p>
        </div>

        <div class="prode-rules-divider"></div>

        <div class="prode-rules-section">
          <h3 class="prode-rules-heading">Premios por partido</h3>
          <ul class="prode-rules-list">
            <li>
              <span class="prode-badge prode-badge--exact">Resultado exacto</span>
              Acertás marcador y ganador: <strong>1000 WCoins + 3 días VIP</strong>
            </li>
            <li>
              <span class="prode-badge prode-badge--winner">Solo ganador</span>
              Acertás quién gana: <strong>500 WCoins + 1 día VIP</strong>
            </li>
            <li class="prode-rules-note">Los premios se acreditan automáticamente al cargar el resultado.</li>
          </ul>
        </div>

        <div class="prode-rules-divider"></div>

        <div class="prode-rules-section">
          <h3 class="prode-rules-heading">Puntos para el ranking</h3>
          <ul class="prode-rules-list">
            <li><span class="prode-rules-pts">3 pts</span> Resultado exacto</li>
            <li><span class="prode-rules-pts">1 pt</span> Solo ganador</li>
          </ul>
        </div>

        <div class="prode-rules-divider"></div>

        <div class="prode-rules-section">
          <h3 class="prode-rules-heading">Premios finales</h3>
          <p class="prode-rules-text">Los jugadores con más puntos acumulados al finalizar el torneo recibirán premios especiales. Los detalles se anunciarán próximamente — ¡seguí sumando puntos! En caso de empate en el podio, todos los empatados reciben el mismo premio.</p>
        </div>

        <div class="prode-rules-divider"></div>

        <div class="prode-rules-section">
          <h3 class="prode-rules-heading">Reglas</h3>
          <ul class="prode-rules-list">
            <li>Solo podés predecir hasta <strong>1 hora antes</strong> del inicio de cada partido.</li>
            <li>Podés modificar tu predicción las veces que quieras mientras el partido esté abierto.</li>
            <li>El prode cubre todo el Mundial 2026: fase de grupos y eliminatorias. Los partidos se van cargando a medida que avanza el torneo.</li>
            <li>Una cuenta por jugador. Usar múltiples cuentas resulta en descalificación de todas las involucradas.</li>
            <li>Las decisiones del staff sobre descalificaciones son definitivas.</li>
          </ul>
        </div>

      </div>
    </details>

    <!-- Tabs: Partidos / Ranking -->
    <div class="tab-nav" id="prode-tabs">
      <button class="tab-btn active" data-tab="matches">Partidos</button>
      <button class="tab-btn"        data-tab="ranking">Ranking</button>
    </div>

    <!-- Panel Partidos -->
    <div id="panel-matches">
      <div id="matches-container">
        <div class="prode-loading">
          <?php for ($i = 0; $i < 4; $i++): ?>
            <div class="prode-match-card skeleton" style="height:110px;border-radius:10px;margin-bottom:0.75rem"></div>
          <?php endfor; ?>
        </div>
      </div>
    </div>

    <!-- Panel Ranking (oculto hasta click) -->
    <div id="panel-ranking" hidden>
      <div id="ranking-container">
        <div class="prode-loading"><?= implode('', array_map(fn() => '<div class="skeleton" style="height:52px;border-radius:8px;margin-bottom:0.5rem"></div>', range(1,8))) ?></div>
      </div>
    </div>

  </section>

</main>

<?php
$content = ob_get_clean();
require_once SRC_ROOT . '/templates/layout.php';
