/* ============================================================
   MuPGA — reclamos.js
   Página /reclamos: form simple (mensaje + hasta 5 imágenes).

   Flujo de envío (el id del reclamo nombra la carpeta en R2, así que
   hay que crear la fila ANTES de subir las imágenes):
     1. authFetch('reclamos/create.php', { mensaje }) → { id }
     2. Por cada imagen:
        a. authFetch('reclamos/upload_url.php', { reclamoId: id, contentType })
           → { uploadUrl, publicUrl }
        b. fetch directo PUT a uploadUrl (R2, no lleva Authorization)
     3. authFetch('reclamos/finalize.php', { reclamoId: id, imagenes })
        → guarda las URLs y recién ahí notifica Discord

   Depende de: app.js (BASE, API, esc), auth.js (isAuthenticated, authFetch)
   ============================================================ */

const RECLAMOS_MAX_IMAGENES = 5;
const RECLAMOS_MAX_SIZE     = 5 * 1024 * 1024; // 5 MB
const RECLAMOS_TIPOS_OK     = ['image/jpeg', 'image/png', 'image/webp'];

let _archivos = []; // File[]

let $alert, $form, $mensaje, $errMensaje,
    $dropzone, $dropzoneInput, $previews, $btnSubmit;

document.addEventListener('DOMContentLoaded', () => {
  if (!isAuthenticated()) {
    window.location.replace(
      `${BASE}/login/?redirect=${encodeURIComponent(window.location.pathname)}`
    );
    return;
  }

  $alert         = document.getElementById('reclamo-alert');
  $form          = document.getElementById('reclamo-form');
  $mensaje       = document.getElementById('reclamo-mensaje');
  $errMensaje    = document.getElementById('err-mensaje');
  $dropzone      = document.getElementById('reclamo-dropzone');
  $dropzoneInput = document.getElementById('reclamo-image-file');
  $previews      = document.getElementById('reclamo-previews');
  $btnSubmit     = document.getElementById('btn-reclamo-submit');

  initDropzone();
  $form.addEventListener('submit', onSubmit);
});

// ── Dropzone ─────────────────────────────────────────────────
function initDropzone() {
  $dropzone.addEventListener('click', () => $dropzoneInput.click());
  $dropzoneInput.addEventListener('change', () => {
    addFiles(Array.from($dropzoneInput.files));
    $dropzoneInput.value = '';
  });

  ['dragover', 'dragenter'].forEach(ev => $dropzone.addEventListener(ev, e => {
    e.preventDefault(); $dropzone.classList.add('cp-dropzone--over');
  }));
  ['dragleave', 'drop'].forEach(ev => $dropzone.addEventListener(ev, e => {
    e.preventDefault(); $dropzone.classList.remove('cp-dropzone--over');
  }));
  $dropzone.addEventListener('drop', e => {
    addFiles(Array.from(e.dataTransfer?.files ?? []));
  });
}

function addFiles(files) {
  clearAlert();

  for (const file of files) {
    if (_archivos.length >= RECLAMOS_MAX_IMAGENES) {
      showAlert(`Máximo ${RECLAMOS_MAX_IMAGENES} imágenes.`, true);
      break;
    }
    if (!RECLAMOS_TIPOS_OK.includes(file.type)) {
      showAlert(`"${file.name}" no es JPG, PNG o WebP.`, true);
      continue;
    }
    if (file.size > RECLAMOS_MAX_SIZE) {
      showAlert(`"${file.name}" supera los 5 MB.`, true);
      continue;
    }
    _archivos.push(file);
  }

  renderPreviews();
}

function renderPreviews() {
  $previews.innerHTML = _archivos.map((file, i) => `
    <div class="reclamo-preview-item">
      <img src="${URL.createObjectURL(file)}" alt="${esc(file.name)}">
      <button type="button" class="reclamo-preview-remove" data-idx="${i}" aria-label="Quitar imagen">✕</button>
    </div>
  `).join('');

  $previews.querySelectorAll('.reclamo-preview-remove').forEach(btn => {
    btn.addEventListener('click', () => {
      _archivos.splice(Number(btn.dataset.idx), 1);
      renderPreviews();
    });
  });
}

// ── Envío ────────────────────────────────────────────────────
async function onSubmit(e) {
  e.preventDefault();
  clearAlert();
  $errMensaje.classList.remove('visible');
  $mensaje.classList.remove('error');

  const mensaje = $mensaje.value.trim();
  if (!mensaje) {
    $mensaje.classList.add('error');
    $errMensaje.textContent = 'Contanos qué pasó.';
    $errMensaje.classList.add('visible');
    return;
  }
  if (mensaje.length > 2000) {
    $mensaje.classList.add('error');
    $errMensaje.textContent = 'El mensaje supera los 2000 caracteres.';
    $errMensaje.classList.add('visible');
    return;
  }

  setLoading(true);

  try {
    // Paso 1: crear el reclamo (rate limit se valida acá) — el id resultante
    // es la carpeta donde van a ir sus imágenes en R2.
    const createRes = await authFetch('reclamos/create.php', {
      method: 'POST',
      body: JSON.stringify({ mensaje }),
    });

    if (!createRes) { setLoading(false); return; }

    if (!createRes.ok) {
      const data = await createRes.json().catch(() => ({}));
      showAlert(data.error ?? 'No se pudo crear el reclamo.', true);
      setLoading(false);
      return;
    }

    const { id } = await createRes.json();

    // Paso 2: subir cada imagen a la carpeta reclamos/{id}/
    const imagenes = [];
    for (const file of _archivos) {
      const publicUrl = await uploadImagen(id, file);
      imagenes.push(publicUrl);
    }

    // Paso 3: guardar las URLs y notificar Discord
    const res = await authFetch('reclamos/finalize.php', {
      method: 'POST',
      body: JSON.stringify({ reclamoId: id, imagenes }),
    });

    if (!res) { setLoading(false); return; }

    if (res.ok) {
      showAlert('¡Reclamo enviado! Gracias, lo vamos a revisar pronto.', false);
      $form.reset();
      _archivos = [];
      renderPreviews();
    } else {
      const data = await res.json().catch(() => ({}));
      showAlert(data.error ?? 'No se pudo terminar de enviar el reclamo.', true);
    }
  } catch (err) {
    showAlert('Error al subir las imágenes: ' + esc(err.message), true);
  } finally {
    setLoading(false);
  }
}

// Pide la presigned URL para la carpeta del reclamo y sube la imagen directo a R2.
async function uploadImagen(reclamoId, file) {
  const res = await authFetch('reclamos/upload_url.php', {
    method: 'POST',
    body: JSON.stringify({ reclamoId, contentType: file.type }),
  });

  if (!res || !res.ok) {
    const data = await res?.json().catch(() => ({})) ?? {};
    throw new Error(data.error ?? 'No se pudo generar la URL de subida.');
  }

  const { uploadUrl, publicUrl } = await res.json();

  const putRes = await fetch(uploadUrl, {
    method: 'PUT',
    headers: { 'Content-Type': file.type },
    body: file,
  });

  if (!putRes.ok) {
    throw new Error(`No se pudo subir "${file.name}" (HTTP ${putRes.status}).`);
  }

  return publicUrl;
}

// ── UI helpers ───────────────────────────────────────────────
function setLoading(loading) {
  $btnSubmit.disabled = loading;
  $btnSubmit.textContent = loading ? 'Enviando…' : 'Enviar reclamo';
}

function showAlert(msg, isError) {
  $alert.textContent = msg;
  $alert.className = 'alert visible ' + (isError ? 'alert--error' : 'alert--success');
}

function clearAlert() {
  $alert.className = 'alert';
  $alert.textContent = '';
}
