<?php
// public/empresas/descubrir.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

$pageTitle = 'Descubrir Empresas';
require_once __DIR__ . '/../partials/_header.php';
?>

<div class="mb-6">
  <h1 class="text-2xl font-bold">Descubrir empresas automáticamente</h1>
  <p class="text-gray-600">El sistema rastrea OpenStreetMap para encontrar nuevas empresas colaboradoras en tu zona.</p>
</div>

<div class="bg-white rounded-2xl shadow p-6 mb-6">
  <form id="f" onsubmit="return false;" class="space-y-4">
    <div>
      <label class="block text-sm font-medium mb-1">Sector (obligatorio)</label>
      <input id="sector" class="w-full border rounded-xl p-2"
        placeholder="p.ej. Desarrollo web, Software, Ciberseguridad" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium mb-1">Provincia (obligatorio)</label>
        <input id="provincia" class="w-full border rounded-xl p-2" placeholder="p.ej. Málaga" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Ciudad (opcional)</label>
        <input id="ciudad" class="w-full border rounded-xl p-2" placeholder="p.ej. Málaga" />
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Palabras clave (opcional)</label>
      <input id="keywords" class="w-full border rounded-xl p-2" placeholder="wordpress, laravel, e-commerce" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium mb-1">Límite de resultados</label>
        <input id="limit" type="number" value="50" min="1" max="500" class="w-full border rounded-xl p-2" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Modo previsualización</label>
        <select id="dry" class="w-full border rounded-xl p-2">
          <option value="1">Sí (no guarda, sólo muestra)</option>
          <option value="0">No (permitir guardar)</option>
        </select>
      </div>
    </div>

    <button id="go" class="btn-primary w-full md:w-auto">🚀 Lanzar rastreo</button>
  </form>
</div>

<div class="mb-6">
  <h3 class="text-xl font-bold mb-4">Resultado</h3>
  <div id="out" class="bg-white rounded-2xl shadow p-6 min-h-[100px] text-gray-500">—</div>
</div>

<script>
  // Usamos la variable global si existe, o el fallback relativo
  const ep = (window.PRACTICALIA_API || '/api') + '/empresas/discovery_service.php';
  const out = document.getElementById('out');
  const btn = document.getElementById('go');

  btn.onclick = async () => {
    const sector = document.getElementById('sector').value.trim();
    const provincia = document.getElementById('provincia').value.trim();

    if (!sector || !provincia) {
      alert('Sector y Provincia son obligatorios');
      return;
    }

    const payload = {
      sector: sector,
      provincia: provincia,
      ciudad: document.getElementById('ciudad').value.trim(),
      keywords: document.getElementById('keywords').value.split(',').map(s => s.trim()).filter(Boolean),
      limit_per_source: parseInt(document.getElementById('limit').value, 10) || 50,
      dry_run: document.getElementById('dry').value === '1'
    };

    out.textContent = '🔍 Buscando empresas en OpenStreetMap...';
    btn.disabled = true;

    try {
      const res = await fetch(ep, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      });

      const text = await res.text();
      if (!text || text.trim() === '') {
        throw new Error('El servidor devolvió una respuesta vacía. Esto puede deberse a un error interno o timeout.');
      }

      const ct = res.headers.get('content-type') || '';
      if (!ct.includes('application/json')) {
        throw new Error('La respuesta del servidor no es JSON. Contenido recibido:\n' + text.slice(0, 500));
      }

      let json;
      try {
        json = JSON.parse(text);
      } catch (je) {
        throw new Error('Error al procesar JSON: ' + je.message + '\nContenido: ' + text.slice(0, 200));
      }

      if (!json.ok) throw new Error(json.error || 'Error desconocido');

      if (json.count === 0) {
        out.innerHTML = '<p class="muted">No se encontraron empresas nuevas en esta zona con esos criterios.</p>';
      } else {
        renderResults(json.results);
      }
    } catch (e) {
      out.innerHTML = `<span style="color:red; white-space: pre-wrap;">Error: ${e.message}</span>
      <p class="text-xs text-gray-400 mt-2">Sugerencia: Prueba a reducir el "Límite de resultados" o a buscar en una zona más específica.</p>`;
    } finally {
      btn.disabled = false;
    }
  };

  function renderResults(results) {
    let html = `<p class="mb-4">Se han encontrado <strong>${results.length}</strong> posibles empresas colaboradoras:</p>`;
    html += '<div style="max-height:500px; overflow:auto; border:1px solid #e5e7eb; border-radius:12px;">';
    html += '<table class="w-full text-sm"><thead><tr class="bg-gray-50 border-b"><th class="text-left p-3">Empresa / Detalle</th><th class="text-left p-3">Web / Info</th><th class="text-left p-3">Acción</th></tr></thead><tbody>';

    results.forEach((r, idx) => {
      const hasWeb = !!r.web;
      html += `<tr class="border-b hover:bg-gray-50">
      <td class="p-3">
        <div class="font-bold text-gray-900">${r.nombre}</div>
        <div class="text-xs text-gray-500">${r.direccion || ''} · ${r.ciudad || ''}</div>
      </td>
      <td class="p-3">
        ${r.web ? `<a href="${r.web}" target="_blank" class="text-blue-600 block underline truncate max-w-[180px]">${r.web}</a>` : '<span class="text-gray-400 text-xs italic">Web no detectada</span>'}
        <div class="text-xs text-gray-600">${r.email || ''} ${r.telefono || ''}</div>
      </td>
      <td class="p-3">
        <div class="flex flex-col gap-1">
          <button class="rounded-xl bg-black text-white px-2 py-1 text-xs" onclick="prepareProspect(${idx})">Revisar y Añadir</button>
          ${!hasWeb ? `<a href="https://www.google.com/search?q=${encodeURIComponent(r.nombre + ' ' + (r.ciudad || ''))}" target="_blank" class="text-[10px] text-center text-gray-500 hover:underline">🔍 Buscar web</a>` : ''}
        </div>
      </td>
    </tr>`;
    });

    html += '</tbody></table></div>';
    out.innerHTML = html;
    window.lastDiscovery = results;
  }

  // Modal de pre-guardado
  function prepareProspect(idx) {
    const r = window.lastDiscovery[idx];
    const modal = document.getElementById('reviewModal');
    document.getElementById('rev_nombre').value = r.nombre || '';
    document.getElementById('rev_web').value = r.web || '';
    document.getElementById('rev_email').value = r.email || '';
    document.getElementById('rev_tel').value = r.telefono || '';
    document.getElementById('rev_ciu').value = r.ciudad || '';
    document.getElementById('rev_dir').value = r.direccion || '';
    document.getElementById('rev_idx').value = idx;

    modal.classList.remove('hidden');
  }

  async function confirmAddProspect() {
    const idx = document.getElementById('rev_idx').value;
    const payload = {
      nombre: document.getElementById('rev_nombre').value,
      web: document.getElementById('rev_web').value,
      email: document.getElementById('rev_email').value,
      telefono: document.getElementById('rev_tel').value,
      ciudad: document.getElementById('rev_ciu').value,
      direccion: document.getElementById('rev_dir').value,
      sector: window.lastDiscovery[idx].sector
    };

    const saveEp = (window.PRACTICALIA_API || '/api') + '/prospectos/create.php';
    const btn = document.getElementById('confirmBtn');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    try {
      const res = await fetch(saveEp, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload)
      });
      const json = await res.json();
      if (json.ok) {
        alert('¡Empresa añadida a prospectos!');
        document.getElementById('reviewModal').classList.add('hidden');
      } else {
        alert('Error: ' + json.error);
      }
    } catch (e) {
      alert('Error de conexión con la API');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Confirmar y Añadir';
    }
  }
</script>

<!-- Modal para revisar y editar antes de guardar -->
<div id="reviewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
    <h2 class="text-xl font-bold mb-4">Revisar datos de la empresa</h2>
    <div class="space-y-4">
      <input type="hidden" id="rev_idx">
      <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Nombre Comercial</label>
        <input type="text" id="rev_nombre" class="w-full border rounded-xl p-3 text-sm focus:ring-2 focus:ring-black">
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Web oficial</label>
          <input type="url" id="rev_web" class="w-full border rounded-xl p-3 text-sm focus:ring-2 focus:ring-black">
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Teléfono</label>
          <input type="text" id="rev_tel" class="w-full border rounded-xl p-3 text-sm focus:ring-2 focus:ring-black">
        </div>
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Email de RRHH / Contacto</label>
        <p class="text-[10px] text-gray-400 mb-1">Necesario para el envío de propuestas automático.</p>
        <input type="email" id="rev_email" class="w-full border rounded-xl p-3 text-sm focus:ring-2 focus:ring-black"
          placeholder="ej: rrhh@empresa.com">
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Ciudad</label>
          <input type="text" id="rev_ciu" class="w-full border rounded-xl p-3 text-sm focus:ring-2 focus:ring-black">
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Dirección</label>
          <input type="text" id="rev_dir" class="w-full border rounded-xl p-3 text-sm focus:ring-2 focus:ring-black">
        </div>
      </div>
    </div>
    <div class="flex justify-end gap-3 mt-8">
      <button onclick="document.getElementById('reviewModal').classList.add('hidden')"
        class="btn-secondary">Cancelar</button>
      <button id="confirmBtn" onclick="confirmAddProspect()" class="btn-primary">Confirmar y Añadir</button>
    </div>
  </div>
</div>

<?php
// Al final del archivo cerramos el main y el body (vienen del header)
require_once __DIR__ . '/../partials/_footer.php';
?>
</body>

</html>