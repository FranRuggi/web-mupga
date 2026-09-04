/* ============================================================
   MuPGA — register.js
   Maneja el formulario de registro con validación client-side.
   Depende de: auth.js (isAuthenticated, BASE, API)
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
  if (isAuthenticated()) {
    window.location.replace(`${BASE}/usercp/`);
    return;
  }

  const form      = document.getElementById('register-form');
  const btnSubmit = document.getElementById('btn-register');

  form?.addEventListener('submit', async e => {
    e.preventDefault();
    if (!validateForm()) return;

    clearAlerts();

    const data = {
      username:              document.getElementById('username').value.trim(),
      password:              document.getElementById('password').value,
      password_confirm:      document.getElementById('password_confirm').value,
      email:                 document.getElementById('email').value.trim(),
      'cf-turnstile-response': document.querySelector('[name=cf-turnstile-response]')?.value ?? '',
    };

    setLoading(btnSubmit, true);

    try {
      const res  = await fetch(`${API}/auth/register.php`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body:    JSON.stringify(data),
      });
      const body = await res.json();

      if (res.ok) {
        // Sin redirección automática: mucha gente crea 2 o 3 cuentas seguidas
        // (main + mulas) y mandarlos al login los obligaba a volver a mano.
        // El link "¿Ya tenés cuenta? Iniciar sesión" está abajo del formulario.
        showAlert(body.message, 'success');
        form.reset();
        resetTurnstile();
        setLoading(btnSubmit, false);
        document.getElementById('username')?.focus();
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } else {
        if (body.field) showFieldError(body.field, body.error);
        else showAlert(body.error ?? 'Error al registrar.', 'error');
        resetTurnstile();
        setLoading(btnSubmit, false);
      }
    } catch {
      showAlert('No se pudo conectar con el servidor.', 'error');
      resetTurnstile();
      setLoading(btnSubmit, false);
    }
  });

  // Limpiar errores de campo al escribir
  form?.querySelectorAll('.form-input').forEach(input => {
    input.addEventListener('input', () => clearFieldError(input.id));
  });
});

// El token de Turnstile es de UN SOLO USO: sin este reset, el segundo intento
// en la misma página (crear otra cuenta, o reintentar después de un error)
// falla siempre con "Verificación de seguridad fallida" y no hay forma de
// salir sin recargar a mano.
function resetTurnstile() {
  try {
    if (window.turnstile && typeof window.turnstile.reset === 'function') {
      window.turnstile.reset();
    }
  } catch (e) { /* el widget no está cargado: no hay nada que resetear */ }
}

// ── Validación client-side ────────────────────────────────────

function validateForm() {
  let valid = true;

  const username = document.getElementById('username').value.trim();
  if (!/^[a-zA-Z0-9]{4,10}$/.test(username)) {
    showFieldError('username', 'Entre 4 y 10 caracteres, solo letras y números.');
    valid = false;
  }

  const password = document.getElementById('password').value;
  if (password.length < 8 || password.length > 10) {
    showFieldError('password', 'Entre 8 y 10 caracteres.');
    valid = false;
  }

  const confirm = document.getElementById('password_confirm').value;
  if (password !== confirm) {
    showFieldError('password_confirm', 'Las contraseñas no coinciden.');
    valid = false;
  }

  const email = document.getElementById('email').value.trim();
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    showFieldError('email', 'Email inválido.');
    valid = false;
  }

  return valid;
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

function showFieldError(fieldId, msg) {
  const input = document.getElementById(fieldId);
  const errEl = document.getElementById(`err-${fieldId}`);
  if (input) input.classList.add('error');
  if (errEl) { errEl.textContent = msg; errEl.classList.add('visible'); }
}

function clearFieldError(fieldId) {
  const input = document.getElementById(fieldId);
  const errEl = document.getElementById(`err-${fieldId}`);
  if (input) input.classList.remove('error');
  if (errEl) errEl.classList.remove('visible');
}

function setLoading(btn, loading) {
  if (!btn) return;
  btn.dataset.loading = loading;
  btn.textContent     = loading ? 'Registrando...' : 'Crear cuenta';
}
