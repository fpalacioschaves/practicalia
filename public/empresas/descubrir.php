<?php
// public/empresas/descubrir.php
declare(strict_types=1);
// require_once __DIR__ . '/../../middleware/require_staff.php';
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
  </style>
</head>
<body>
<main>
  <h1>Descubrir empresas automáticamente</h1>
  <p>El sistema rastrea fuentes semilla (configurables) y captura webs de empresas nuevas, las visita, extrae datos de contacto y las añade a tu base con los criterios que indiques.</p>

  <form id="f" onsubmit="return false;">
    <label>Sector (obligatorio)</label>
    <input id="sector" placeholder="p.ej. Desarrollo web, Software, Ciberseguridad"/>

    <div class="grid">
      <div>
        <label>Provincia (obligatorio)</label>
        <input id="provincia" placeholder="p.ej. Málaga"/>
      </div>
      <div>
        <label>Ciudad (opcional)</label>
        <input id="ciudad" placeholder="p.ej. Málaga"/>
      </div>
    </div>

    <label>Palabras clave (opcional, separadas por comas)</label>
    <input id="keywords" placeholder="wordpress, laravel, e-commerce"/>

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

  <h3>Resultado</h3>
  <pre id="out">—</pre>
</main>

<script>
const ep = location.origin + '/api/empresas/descubrir.php';
const out = document.getElementById('out');

document.getElementById('go').onclick = async ()=>{
  const payload = {
    sector: document.getElementById('sector').value.trim(),
    provincia: document.getElementById('provincia').value.trim(),
    ciudad: document.getElementById('ciudad').value.trim(),
    keywords: document.getElementById('keywords').value.split(',').map(s=>s.trim()).filter(Boolean),
    limit_per_source: parseInt(document.getElementById('limit').value,10)||100,
    dry_run: document.getElementById('dry').value === '1'
  };
  out.textContent = 'Rastreando...';
  const res = await fetch(ep, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
  const json = await res.json();
  out.textContent = JSON.stringify(json, null, 2);
};
</script>
</body>
</html>
