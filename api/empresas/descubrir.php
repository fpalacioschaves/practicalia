<?php
// public/empresas/descubrir.php
declare(strict_types=1);

// (actívalo si quieres protegerlo)
// require_once __DIR__ . '/../../middleware/require_staff.php';

// Detecta base URL según entorno (local vs hosting), igual que en el menú
$host = $_SERVER['HTTP_HOST'] ?? '';
if ($host === 'localhost' || $host === '127.0.0.1') {
  // Estructura local: http://localhost/practicalia/public/...
  $BASE_ROOT   = '/practicalia';
  $BASE_PUBLIC = $BASE_ROOT . '/public';
} else {
  // Hosting: https://tusitio/public/...
  $BASE_ROOT   = '';            // raíz del dominio
  $BASE_PUBLIC = '/public';
}

// Endpoint API real (está fuera de /public)
$ENDPOINT = $BASE_ROOT . '/api/empresas/descubrir.php';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <title>Descubrir empresas automáticamente</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <link rel="stylesheet"
        href="https://unpkg.com/@picocss/pico@2.0.6/css/pico.min.css">
  <style>
    main{max-width: 920px; margin: 2rem auto;}
    pre{background:#0f172a;color:#e2e8f0;padding:12px;border-radius:8px;overflow:auto}
    .muted{color:#666}
  </style>
</head>
<body>
<main>
  <h1>Descubrir empresas automáticamente</h1>
  <p class="muted">
    Rastrea fuentes semilla y añade empresas nuevas con los criterios indicados.
  </p>

  <article>
    <form id="f" onsubmit="return false;">
      <label>Sector (obligatorio)</label>
      <input id="sector" placeholder="p.ej. Desarrollo web, Software, Ciberseguridad" required/>

      <div class="grid">
        <div>
          <label>Provincia (obligatorio)</label>
          <input id="provincia" placeholder="p.ej. Málaga" required/>
        </div>
        <div>
          <label>Ciudad (opcional)</label>
          <input id="ciudad" placeholder="p.ej. Málaga"/>
        </div>
      </div>

      <label>Palabras clave (opcional, separadas por comas)</label>
      <input id="keywords" placeholder="wordpress, laravel, ecommerce"/>

      <div class="grid">
        <div>
          <label>Límite por fuente</label>
          <input id="limit" type="number" value="100" min="1" max="500"/>
        </div>
        <div>
          <label>Modo previsualización</label>
          <select id="dry">
            <option value="1">Sí (no guarda, sólo cuenta)</option>
            <option value="0">No (insertar/actualizar)</option>
          </select>
        </div>
      </div>

      <button id="go">Lanzar rastreo</button>
    </form>
  </article>

  <h3>Resultado</h3>
  <pre id="out">—</pre>
</main>

<script>
const ENDPOINT = "<?= $ENDPOINT ?>";
const out = document.getElementById('out');
const btn = document.getElementById('go');

function payload(){
  return {
    sector: document.getElementById('sector').value.trim(),
    provincia: document.getElementById('provincia').value.trim(),
    ciudad: document.getElementById('ciudad').value.trim(),
    keywords: (document.getElementById('keywords').value||'')
                .split(',').map(s=>s.trim()).filter(Boolean),
    limit_per_source: parseInt(document.getElementById('limit').value, 10) || 100,
    dry_run: document.getElementById('dry').value === '1'
  };
}

btn.onclick = async ()=>{
  const p = payload();
  if (!p.sector || !p.provincia) {
    alert('Sector y Provincia son obligatorios.');
    return;
  }

  out.textContent = `Llamando a ${ENDPOINT}...\n`;

  try {
    const res = await fetch(ENDPOINT, {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify(p)
    });

    const ct = res.headers.get('content-type') || '';
    if (!ct.includes('application/json')) {
      // Si la API no existe o devuelve HTML (404), mostramos el HTML truncado para diagnóstico
      const text = await res.text();
      out.textContent += `\n⚠️ Respuesta no JSON (status ${res.status}). Probable ruta incorrecta.\n` +
                         `Primeros 300 caracteres:\n` + text.slice(0,300);
      return;
    }

   
