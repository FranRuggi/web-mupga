/* ============================================================
   MuPGA — login.js
   Maneja el formulario de login y la redirección post-auth.
   Depende de: auth.js (getToken, setAuth, BASE, API)
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
  // Si ya está logueado, redirigir al panel
  if (isAuthenticated()) {
    window.location.replace(`${BASE}/usercp/`);
    return;
  }

  // Mensaje de token expirado
  if (new URLSearchParams(location.search).get('expired') === '1') {
    showAlert('Tu sesión expiró. Iniciá sesión nuevamente.', 'info');
  }

  const form     = document.getElementById('login-form');
  const btnSubmit = document.getElementById('btn-login');

  form?.addEventListener('submit', async e => {
    e.preventDefault();
    clearAlerts();

    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;

    setLoading(btnSubmit, true);

    try {
      const res  = await fetch(`${API}/auth/login.php`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body:    JSON.stringify({ username, password }),
      });
      const data = await res.json();

      if (res.ok) {
        setAuth(data.token, data.username, data.user_id);
        const redir = new URLSearchParams(location.search).get('redirect') ?? '';
        // Validar que sea ruta interna (evitar open redirect)
        window.location.href = (redir && redir.startsWith('/') && !redir.startsWith('//'))
          ? `${BASE}${redir}`
          : destinoPostLogin();
      } else {
        showAlert(data.error ?? 'Error al iniciar sesión.', 'error');
        setLoading(btnSubmit, false);
      }
    } catch {
      showAlert('No se pudo conectar con el servidor.', 'error');
      setLoading(btnSubmit, false);
    }
  });
});

// Destino por defecto después de loguearse. Durante la cuenta regresiva de la
// apertura /usercp/ está tapado por la pantalla y su API responde 503, así que
// caer ahí parece que el login falló: se manda a /downloads/, que está abierta
// y es lo que necesitan antes de que abramos. Pasada la hora, vuelve a /usercp/
// solo (la config viene de src/config/apertura.php).
function destinoPostLogin() {
  const ap = window.MUPGA_APERTURA;
  const enCurso = ap && ap.activa && Number(ap.objetivo_ms) > Date.now();
  return enCurso ? `${BASE}/downloads/` : `${BASE}/usercp/`;
}

// ── Helpers de UI ─────────────────────────────────────────────

function showAlert(msg, type = 'error') {
  const el = document.getElementById('form-alert');
  if (!el) return;
  el.textContent = msg;
  el.className   = `alert alert--${type} visible`;
}

function clearAlerts() {
  const el = document.getElementById('form-alert');
  if (el) el.className = 'alert';
}

function setLoading(btn, loading) {
  if (!btn) return;
  btn.dataset.loading = loading;
  btn.textContent     = loading ? 'Ingresando...' : 'Ingresar';
}
