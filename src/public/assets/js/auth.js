/* ============================================================
   MuPGA — auth.js
   Gestión de tokens, fetch autenticado, estado de sesión.
   Cargado en TODAS las páginas (incluido en layout.php).
   ============================================================ */

const TOKEN_KEY = 'mupga_token';
const USER_KEY  = 'mupga_user';

// ── Token ─────────────────────────────────────────────────────

function getToken() {
  const token = localStorage.getItem(TOKEN_KEY);
  if (!token) return null;

  // Verificar expiración client-side (sin validar firma — eso es el servidor)
  try {
    const payload = JSON.parse(atob(token.split('.')[0].replace(/-/g,'+').replace(/_/g,'/')));
    if (payload.exp && payload.exp < Math.floor(Date.now() / 1000)) {
      clearAuth();
      return null;
    }
  } catch {
    clearAuth();
    return null;
  }

  return token;
}

function getUser() {
  try {
    return JSON.parse(localStorage.getItem(USER_KEY)) ?? null;
  } catch { return null; }
}

function setAuth(token, username, userId) {
  localStorage.setItem(TOKEN_KEY, token);
  localStorage.setItem(USER_KEY, JSON.stringify({ username, userId }));
}

function clearAuth() {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_KEY);
}

function isAuthenticated() {
  return getToken() !== null;
}

// ── Fetch autenticado ─────────────────────────────────────────
// Usa este wrapper para cualquier request a /api/account/*.php
// Incluye automáticamente el token y maneja 401 (redirige a login).

async function authFetch(endpoint, options = {}) {
  const token = getToken();
  if (!token) {
    window.location.href = `${BASE}/login/`;
    return null;
  }

  const headers = {
    'Accept':        'application/json',
    'Content-Type':  'application/json',
    'Authorization': `Bearer ${token}`,
    ...(options.headers ?? {}),
  };

  try {
    const res = await fetch(`${API}/${endpoint}`, { ...options, headers });

    if (res.status === 401) {
      clearAuth();
      window.location.href = `${BASE}/login/?expired=1`;
      return null;
    }

    return res;
  } catch (err) {
    console.warn('[authFetch]', endpoint, err.message);
    return null;
  }
}

// ── Nav dinámica según estado de sesión ───────────────────────

function updateNav() {
  const auth = isAuthenticated();
  const user = getUser();

  document.querySelectorAll('[data-auth-show]').forEach(el => {
    el.hidden = !auth;
    if (auth && el.dataset.authUser && user) {
      el.textContent = user.username;
    }
  });

  document.querySelectorAll('[data-guest-show]').forEach(el => {
    el.hidden = auth;
  });

  const greeting = document.getElementById('hero-greeting');
  if (greeting) {
    greeting.hidden = !auth;
    if (auth && user) greeting.textContent = `Hola, ${user.username} 👋`;
  }
}

// Logout desde la nav
document.addEventListener('DOMContentLoaded', () => {
  updateNav();

  document.getElementById('nav-logout')?.addEventListener('click', e => {
    e.preventDefault();
    clearAuth();
    window.location.href = `${BASE}/`;
  });
});
