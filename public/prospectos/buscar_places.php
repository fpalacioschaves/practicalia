<?php
// practicalia/public/prospectos/buscar_places.php
declare(strict_types=1);

/*
  Buscar empresas con Google Places (Text Search + Place Details)
  - Requiere clave en entorno: GOOGLE_PLACES_API_KEY (o GOOGLE_API_KEY)
  - Permite buscar por palabras clave con sesgo de ubicación (geocodificada)
  - Devuelve: nombre, web, teléfono, dirección, rating (si hay)
  - Importa seleccionados en empresas_prospectos
*/

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

// Carga opcional de claves locales
$envPath = __DIR__ . '/../../config/env.php';
if (is_file($envPath)) { require_once $envPath; }

if (session_status() === PHP_SESSION_NONE) session_start();

$user     = current_user();
$isAdmin  = require_role('admin');
$isProf   = require_role('profesor');
$profId   = (int)($user['id'] ?? 0);

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function csrf_html(): string { return h(csrf_token()); }

$PLACES_KEY = getenv('GOOGLE_PLACES_API_KEY') ?: ($_ENV['GOOGLE_PLACES_API_KEY'] ?? (getenv('GOOGLE_API_KEY') ?: ($_ENV['GOOGLE_API_KEY'] ?? '')));
$hasKey     = $PLACES_KEY !== '';

/* =========================================================
   Cursos visibles (para etiquetar/curso destino)
   ========================================================= */
try {
  if ($isAdmin) {
    $cursos = $pdo->query("SELECT id, nombre FROM cursos WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
  } else {
    $st = $pdo->prepare("
      SELECT c.id, c.nombre
      FROM cursos c
      JOIN cursos_profesores cp ON cp.curso_id = c.id
      WHERE cp.profesor_id = :pid
      ORDER BY c.nombre
    ");
    $st->execute([':pid' => $profId]);
    $cursos = $st->fetchAll(PDO::FETCH_ASSOC);
  }
} catch (Throwable $e) {
  $cursos = [];
}

/* =========================================================
   HTTP helper
   ========================================================= */
function http_get_json(string $url): array {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 25,
    CURLOPT_USERAGENT => 'PracticaliaPlaces/1.0',
  ]);
  $resp = curl_exec($ch);
  $err  = curl_error($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($resp === false) throw new RuntimeException("HTTP error: $err");
  if ($code >= 400) throw new RuntimeException("HTTP $code: $resp");
  $json = json_decode($resp, true);
  if (!is_array($json)) throw new RuntimeException('Respuesta no JSON');
  return $json;
}

/* =========================================================
   Geocoding (para sesgar búsquedas a una ciudad/provincia)
   ========================================================= */
function geocode_location(string $addr, string $apiKey): ?array {
  $base = 'https://maps.googleapis.com/maps/api/geocode/json';
  $url  = $base . '?address=' . rawurlencode($addr) . '&key=' . rawurlencode($apiKey);
  $json = http_get_json($url);
  if (($json['status'] ?? '') !== 'OK') return null;
  $res = $json['results'][0] ?? null;
  if (!$res) return null;
  $loc = $res['geometry']['location'] ?? null;
  if (!$loc) return null;
  return ['lat' => (float)$loc['lat'], 'lng' => (float)$loc['lng']];
}

/* =========================================================
   Google Places: Text Search + Details
   ========================================================= */
function places_text_search(string $query, ?array $location, int $radius, string $key, ?string $pagetoken = null): array {
  $base = 'https://maps.googleapis.com/maps/api/place/textsearch/json';
  $params = ['query' => $query, 'key' => $key, 'language' => 'es'];
  if ($location) {
    $params['location'] = $location['lat'] . ',' . $location['lng'];
    $params['radius']   = (string)$radius;
  }
  if ($pagetoken) {
    // Cuando usas pagetoken, Google ignora otros params salvo key y pagetoken
    $params = ['pagetoken' => $pagetoken, 'key' => $key];
  }
  $url = $base . '?' . http_build_query($params);
  $json = http_get_json($url);
  $status = $json['status'] ?? 'UNKNOWN_ERROR';
  if (!in_array($status, ['OK','ZERO_RESULTS','INVALID_REQUEST','OVER_QUERY_LIMIT','REQUEST_DENIED','UNKNOWN_ERROR'], true)) {
    throw new RuntimeException('Error Places: ' . $status);
  }
  return $json;
}

function place_details(string $placeId, string $key): array {
  $base = 'https://maps.googleapis.com/maps/api/place/details/json';
  $fields = [
    'name','formatted_address','international_phone_number','formatted_phone_number',
    'website','url','place_id','geometry/location','rating','user_ratings_total'
  ];
  $url = $base . '?' . http_build_query([
    'place_id' => $placeId,
    'fields'   => implode(',', $fields),
    'language' => 'es',
    'key'      => $key,
  ]);
  $json = http_get_json($url);
  if (($json['status'] ?? '') !== 'OK') return [];
  return $json['result'] ?? [];
}

function normalize_phone(?string $p): ?string {
  $p = trim((string)$p);
  if ($p === '') return null;
  // Quita espacios raros
  $p = preg_replace('/[^\d\+]/', ' ', $p);
  $p = preg_replace('/\s+/', ' ', $p);
  return trim($p);
}

/* =========================================================
   Controlador
   ========================================================= */
$error = '';
$diagMsg = '';
$results = []; // cada item: ['name','website','phone','address','rating','url','place_id','lat','lng','source']

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $accion = $_POST['accion'] ?? '';

  if ($accion === 'buscar_places') {
    try {
      csrf_check($_POST['csrf'] ?? null);
      global $PLACES_KEY, $hasKey;
      if (!$hasKey) throw new RuntimeException('Falta GOOGLE_PLACES_API_KEY (o GOOGLE_API_KEY).');

      $qBase    = trim((string)($_POST['q'] ?? ''));
      $locText  = trim((string)($_POST['loc'] ?? ''));
      $radiusKm = max(1, min(50, (int)($_POST['radius_km'] ?? 10))); // 1..50 km
      $radius   = $radiusKm * 1000;
      $tipo     = trim((string)($_POST['tipo'] ?? '')); // opcional: software, consultora, etc.
      $include  = trim((string)($_POST['must'] ?? '')); // palabras obligatorias

      // Construcción de query para Text Search
      $parts = [];
      if ($qBase !== '') $parts[] = $qBase;
      if ($tipo  !== '') $parts[] = $tipo;
      if ($include !== '') {
        $mustWords = array_values(array_filter(preg_split('/\s+/', $include)));
        foreach ($mustWords as $w) $parts[] = '+' . $w;
      }
      // Sesgo “empresa”
      $parts[] = '(empresa OR compañía OR consultora OR software OR tecnología OR informática)';
      $query = trim(implode(' ', $parts));

      // Geocodifica ubicación si se indicó
      $loc = null;
      if ($locText !== '') {
        $loc = geocode_location($locText, $PLACES_KEY);
        if (!$loc) throw new RuntimeException('No se pudo geocodificar la ubicación: ' . $locText);
      }

      // Primera página
      $page1 = places_text_search($query, $loc, $radius, $PLACES_KEY, null);
      $items = $page1['results'] ?? [];

      // Si hay segunda página (pagetoken), la pedimos también
      if (!empty($page1['next_page_token'])) {
        // Google pide esperar unos 2s para que el token sea válido
        usleep(2000000);
        $page2 = places_text_search($query, $loc, $radius, $PLACES_KEY, $page1['next_page_token']);
        $items = array_merge($items, $page2['results'] ?? []);
      }

      // Mapeo + Details
      $out = [];
      foreach ($items as $it) {
        $placeId = (string)($it['place_id'] ?? '');
        if ($placeId === '') continue;

        $details = place_details($placeId, $PLACES_KEY);
        $name    = (string)($details['name'] ?? ($it['name'] ?? 'Empresa'));
        $website = (string)($details['website'] ?? '');
        $phone   = (string)($details['formatted_phone_number'] ?? ($details['international_phone_number'] ?? ''));
        $addr    = (string)($details['formatted_address'] ?? ($it['formatted_address'] ?? ''));
        $rating  = $details['rating'] ?? ($it['rating'] ?? null);
        $url     = (string)($details['url'] ?? '');

        $locObj  = $details['geometry']['location'] ?? ($it['geometry']['location'] ?? null);
        $lat     = isset($locObj['lat']) ? (float)$locObj['lat'] : null;
        $lng     = isset($locObj['lng']) ? (float)$locObj['lng'] : null;

        $out[] = [
          'name'     => $name,
          'website'  => $website ?: ($url ?: ''),
          'phone'    => normalize_phone($phone),
          'address'  => $addr,
          'rating'   => $rating ? number_format((float)$rating, 1) : null,
          'url'      => $url,
          'place_id' => $placeId,
          'lat'      => $lat,
          'lng'      => $lng,
          'source'   => 'Google Places',
        ];
      }

      $results = $out;
      $diagMsg = 'Resultados de Google Places para: ' . $query . ($locText ? ' · Ubicación: '.$locText.' (radio '.$radiusKm.'km)' : '');

      // Guarda eco para import
      $_SESSION['prospectos_places_echo'] = [
        'curso_id'  => (int)($_POST['curso_id'] ?? 0),
        'tags'      => (string)($_POST['tags'] ?? ''),
        'ciudad'    => (string)($_POST['ciudad'] ?? ''),
        'provincia' => (string)($_POST['provincia'] ?? ''),
        'sector'    => (string)($_POST['tipo'] ?? ''),
      ];

    } catch (Throwable $e) {
      $error = $e->getMessage();
      $results = [];
    }
  }

  if ($accion === 'importar') {
    try {
      csrf_check($_POST['csrf'] ?? null);

      $sel  = (array)($_POST['sel'] ?? []);
      $rows = (array)($_POST['rows'] ?? []);
      if (!$sel) throw new RuntimeException('No has seleccionado resultados');

      $echo     = $_SESSION['prospectos_places_echo'] ?? [];
      $cursoId  = (int)($_POST['curso_id'] ?? ($echo['curso_id'] ?? 0));
      $tags     = trim((string)($_POST['tags'] ?? ($echo['tags'] ?? '')));
      $ciudad   = trim((string)($_POST['ciudad'] ?? ($echo['ciudad'] ?? '')));
      $provincia= trim((string)($_POST['provincia'] ?? ($echo['provincia'] ?? '')));
      $sector   = trim((string)($_POST['sector'] ?? ($echo['sector'] ?? '')));

      if (!$isAdmin && $cursoId) {
        $chk = $pdo->prepare("SELECT 1 FROM cursos_profesores WHERE profesor_id=:p AND curso_id=:c LIMIT 1");
        $chk->execute([':p'=>$profId, ':c'=>$cursoId]);
        if (!$chk->fetch()) throw new RuntimeException('No puedes asignar prospectos a ese curso.');
      }

      $ins = $pdo->prepare("
        INSERT INTO empresas_prospectos
          (nombre, web, email, telefono, sector,
           responsable_nombre, responsable_email, responsable_telefono, responsable_cargo,
           ciudad, provincia, notas,
           estado, origen, fuente_url, prospecto_etiquetas, curso_id, asignado_profesor_id)
        VALUES
          (:nombre, :web, NULL, :tel, :sector,
           NULL, NULL, NULL, NULL,
           :ciudad, :provincia, :notas,
           'nuevo', 'maps', :fuente, :tags, :curso, :prof)
      ");

      $n = 0;
      foreach ($sel as $i) {
        if (!isset($rows[$i]) || !is_array($rows[$i])) continue;
        $r = $rows[$i];

        $ins->execute([
          ':nombre'   => trim((string)$r['name']),
          ':web'      => $r['website'] !== '' ? trim((string)$r['website']) : null,
          ':tel'      => $r['phone'] !== '' ? trim((string)$r['phone']) : null,
          ':sector'   => $sector !== '' ? $sector : null,
          ':ciudad'   => $ciudad !== '' ? $ciudad : null,
          ':provincia'=> $provincia !== '' ? $provincia : null,
          ':notas'    => $r['address'] !== '' ? trim((string)$r['address']) : null,
          ':fuente'   => $r['url'] !== '' ? trim((string)$r['url']) : null,
          ':tags'     => $tags !== '' ? $tags : null,
          ':curso'    => $cursoId > 0 ? $cursoId : null,
          ':prof'     => $profId ?: null,
        ]);
        $n++;
      }

      header('Location: ./index.php?ok=1&imp='.(int)$n);
      exit;

    } catch (Throwable $e) {
      $error = $e->getMessage();
    }
  }
}

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Buscar empresas (Google Places) — Practicalia</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
  <?php require_once __DIR__ . '/../partials/menu.php'; ?>

  <main class="max-w-6xl mx-auto p-4 space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">Buscar empresas (Google Maps / Places)</h1>
      <a href="./index.php" class="rounded-xl px-4 py-2 border">Volver</a>
    </div>

    <?php if (!$hasKey): ?>
      <div class="bg-amber-50 text-amber-800 p-3 rounded">
        Falta configurar <code>GOOGLE_PLACES_API_KEY</code> (o <code>GOOGLE_API_KEY</code>).
        Añádelo en <code>practicalia/config/env.php</code> con:
        <code>putenv('GOOGLE_PLACES_API_KEY=TU_CLAVE');</code>
      </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="bg-red-50 text-red-700 p-3 rounded"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($diagMsg)): ?>
      <div class="bg-blue-50 text-blue-800 p-3 rounded"><?= h($diagMsg) ?></div>
    <?php endif; ?>

    <section class="bg-white p-6 rounded-2xl shadow space-y-4">
      <h2 class="font-medium">1) Parámetros de búsqueda</h2>
      <form method="post" class="space-y-4">
        <input type="hidden" name="csrf" value="<?= csrf_html() ?>">
        <input type="hidden" name="accion" value="buscar_places">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium">Palabras clave</label>
            <input name="q" class="mt-1 w-full border rounded-xl p-2"
                   placeholder="desarrollo de software, web, consultoría TIC..."
                   value="<?= h($_POST['q'] ?? '') ?>">
            <p class="text-xs text-gray-500 mt-1">Se aplicará sesgo de “empresa/consultora” automáticamente.</p>
          </div>
          <div>
            <label class="block text-sm font-medium">Tipo/sector (opcional)</label>
            <input name="tipo" class="mt-1 w-full border rounded-xl p-2" placeholder="software, frontend, cloud" value="<?= h($_POST['tipo'] ?? '') ?>">
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div>
            <label class="block text-sm font-medium">Ubicación</label>
            <input name="loc" class="mt-1 w-full border rounded-xl p-2" placeholder="Sevilla, Andalucía..." value="<?= h($_POST['loc'] ?? '') ?>">
            <p class="text-xs text-gray-500 mt-1">Se geocodifica y se aplica radio en km.</p>
          </div>
          <div>
            <label class="block text-sm font-medium">Radio (km)</label>
            <input type="number" min="1" max="50" name="radius_km" class="mt-1 w-full border rounded-xl p-2" value="<?= h($_POST['radius_km'] ?? '10') ?>">
          </div>
          <div>
            <label class="block text-sm font-medium">Debe contener</label>
            <input name="must" class="mt-1 w-full border rounded-xl p-2" placeholder="+prácticas +FP +dual" value="<?= h($_POST['must'] ?? '') ?>">
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div>
            <label class="block text-sm font-medium">Curso objetivo</label>
            <select name="curso_id" class="mt-1 w-full border rounded-xl p-2">
              <option value="">—</option>
              <?php foreach ($cursos as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (isset($_POST['curso_id']) && (int)$_POST['curso_id']===(int)$c['id'])?'selected':'' ?>>
                  <?= h($c['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium">Etiquetas (coma)</label>
            <input name="tags" class="mt-1 w-full border rounded-xl p-2" placeholder="DAW, DAM, web, backend" value="<?= h($_POST['tags'] ?? '') ?>">
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium">Ciudad (guardar)</label>
              <input name="ciudad" class="mt-1 w-full border rounded-xl p-2" value="<?= h($_POST['ciudad'] ?? '') ?>">
            </div>
            <div>
              <label class="block text-sm font-medium">Provincia (guardar)</label>
              <input name="provincia" class="mt-1 w-full border rounded-xl p-2" value="<?= h($_POST['provincia'] ?? '') ?>">
            </div>
          </div>
        </div>

        <div class="flex gap-2">
          <button class="rounded-xl bg-black text-white px-4 py-2" <?= !$hasKey ? 'disabled' : '' ?>>Buscar</button>
          <a href="./buscar_places.php" class="rounded-xl px-4 py-2 border">Limpiar</a>
        </div>
      </form>
    </section>

    <?php if ($results): ?>
      <form method="post" class="bg-white p-6 rounded-2xl shadow space-y-4">
        <input type="hidden" name="csrf" value="<?= csrf_html() ?>">
        <input type="hidden" name="accion" value="importar">

        <!-- eco -->
        <input type="hidden" name="curso_id" value="<?= h($_POST['curso_id'] ?? '') ?>">
        <input type="hidden" name="tags" value="<?= h($_POST['tags'] ?? '') ?>">
        <input type="hidden" name="ciudad" value="<?= h($_POST['ciudad'] ?? '') ?>">
        <input type="hidden" name="provincia" value="<?= h($_POST['provincia'] ?? '') ?>">
        <input type="hidden" name="sector" value="<?= h($_POST['tipo'] ?? '') ?>">

        <div class="flex items-center justify-between">
          <h2 class="font-semibold">Resultados (Google Places)</h2>
          <div class="text-sm text-gray-600"><?= count($results) ?> encontrados</div>
        </div>

        <div class="overflow-x-auto border rounded-2xl">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="p-3"><input type="checkbox" onclick="document.querySelectorAll('[data-row]').forEach(cb=>cb.checked=this.checked)"></th>
                <th class="text-left p-3">Empresa</th>
                <th class="text-left p-3">Web</th>
                <th class="text-left p-3">Teléfono</th>
                <th class="text-left p-3">Dirección</th>
                <th class="text-left p-3">Rating</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($results as $i => $r): ?>
                <tr class="border-t">
                  <td class="p-3 align-top"><input type="checkbox" name="sel[]" value="<?= $i ?>" data-row></td>
                  <td class="p-3 align-top font-medium">
                    <?= h($r['name']) ?>
                    <?php if (!empty($r['url'])): ?>
                      <div><a class="underline text-xs" target="_blank" href="<?= h($r['url']) ?>">Ver en Maps</a></div>
                    <?php endif; ?>
                  </td>
                  <td class="p-3 align-top">
                    <?php if (!empty($r['website'])): ?>
                      <a class="underline" target="_blank" href="<?= h($r['website']) ?>"><?= h($r['website']) ?></a>
                    <?php else: ?><span class="text-gray-400">—</span><?php endif; ?>
                  </td>
                  <td class="p-3 align-top"><?= h($r['phone'] ?? '—') ?></td>
                  <td class="p-3 align-top"><?= h($r['address'] ?? '—') ?></td>
                  <td class="p-3 align-top"><?= h($r['rating'] ?? '—') ?></td>
                </tr>

                <!-- Campos ocultos para importar -->
                <input type="hidden" name="rows[<?= $i ?>][name]" value="<?= h($r['name']) ?>">
                <input type="hidden" name="rows[<?= $i ?>][website]" value="<?= h($r['website']) ?>">
                <input type="hidden" name="rows[<?= $i ?>][phone]" value="<?= h($r['phone']) ?>">
                <input type="hidden" name="rows[<?= $i ?>][address]" value="<?= h($r['address']) ?>">
                <input type="hidden" name="rows[<?= $i ?>][url]" value="<?= h($r['url']) ?>">
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div>
          <button class="rounded-xl bg-black text-white px-4 py-2">Importar seleccionados</button>
        </div>
      </form>
    <?php endif; ?>

  </main>
</body>
</html>
