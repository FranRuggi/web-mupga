/* ============================================================
   MuPGA — news.js
   Lista de noticias + vista de artículo individual (?id=N).
   Depende de app.js (apiFetch, esc, BASE).
   ============================================================ */

document.addEventListener('DOMContentLoaded', async () => {
  const el   = document.getElementById('news-list');
  const data = await apiFetch('site/news.php');

  if (!data?.length) {
    el.innerHTML = '<p class="state-message">No hay noticias disponibles.</p>';
    return;
  }

  const id = new URLSearchParams(window.location.search).get('id');

  if (id) {
    const n = data.find(x => String(x.id) === id);
    el.innerHTML = n ? renderArticle(n) : `
      <p class="state-message">Noticia no encontrada.</p>
      <p style="text-align:center"><a class="btn btn-secondary" href="${BASE}/news/">← Volver a noticias</a></p>`;
    return;
  }

  el.innerHTML = data.map(renderListCard).join('');
});

// ── Tarjeta del listado ───────────────────────────────────────
function renderListCard(n) {
  return `
    <article class="news-card news-card--list">
      ${n.image ? `
        <a href="${BASE}/news/?id=${n.id}" class="news-card__thumb">
          <img src="${esc(n.image)}" alt="" loading="lazy">
        </a>` : ''}
      <div class="news-card__content">
        <div class="news-meta">
          <span class="news-category">${esc(n.category)}</span>
          <span class="news-date">${esc(n.date)}</span>
        </div>
        <h2 class="news-title">
          <a href="${BASE}/news/?id=${n.id}">${esc(n.title)}</a>
        </h2>
        <p class="news-summary">${esc(n.summary)}</p>
        <a class="card-link" href="${BASE}/news/?id=${n.id}">Leer completa</a>
      </div>
    </article>`;
}

// ── Artículo individual ───────────────────────────────────────
function renderArticle(n) {
  return `
    <article class="news-article animate-in">
      <a class="news-article__back" href="${BASE}/news/">← Todas las noticias</a>
      <div class="news-meta">
        <span class="news-category">${esc(n.category)}</span>
        <span class="news-date">${esc(n.date)}</span>
      </div>
      <h1 class="news-article__title">${esc(n.title)}</h1>
      <p class="news-article__summary">${esc(n.summary)}</p>
      ${n.image ? `<img class="news-article__image" src="${esc(n.image)}" alt="">` : ''}
      <div class="news-article__body">
        ${renderRichText(n.content ?? '')}
      </div>
      <a class="btn btn-secondary" href="${BASE}/news/">← Volver a noticias</a>
    </article>`;
}
