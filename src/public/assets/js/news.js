/* MuPGA — news.js */
document.addEventListener('DOMContentLoaded', async () => {
  const el   = document.getElementById('news-list');
  const data = await apiFetch('newsdata.php');

  if (!data?.length) {
    el.innerHTML = '<p class="state-message">No hay noticias disponibles.</p>';
    return;
  }

  el.innerHTML = data.map((n, i) => `
    <article class="news-card" id="news-${i}">
      <div class="news-meta">
        <span class="news-category">${esc(n.category)}</span>
        <span class="news-date">${esc(n.date)}</span>
      </div>
      <h2 class="news-title">${esc(n.title)}</h2>
      <p class="news-summary">${esc(n.summary)}</p>
      ${n.content ? `<p class="news-content">${esc(n.content)}</p>` : ''}
    </article>`).join('');
});
