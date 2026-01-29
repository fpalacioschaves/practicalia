<?php
// practicalia/public/prospectos/buscar.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

// Carga opcional de claves/ajustes locales
$envPath = __DIR__ . '/../../config/env.php';
if (is_file($envPath)) {
  require_once $envPath;
}

if (session_status() === PHP_SESSION_NONE)
  session_start();

$user = current_user();
$isAdmin = require_role('admin');
$isProf = require_role('profesor');
$profId = (int) ($user['id'] ?? 0);

function h(?string $s): string
{
  return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
function csrf_html(): string
{
  return h(csrf_token());
}

/* =========================================================
   Cursos accesibles
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
   Semillas desde asignaturas
   ========================================================= */
function generarSemillasDesdeAsignaturas(PDO $pdo, array $cursoIds): string
{
  if (!$cursoIds)
    return '';
  $in = implode(',', array_fill(0, count($cursoIds), '?'));
  $sql = "SELECT nombre, codigo FROM asignaturas WHERE curso_id IN ($in) AND activo=1 ORDER BY nombre";
  $st = $pdo->prepare($sql);
  $st->execute(array_map('intval', $cursoIds));
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  $pal = [];
  foreach ($rows as $r) {
    $n = trim((string) ($r['nombre'] ?? ''));
    $c = trim((string) ($r['codigo'] ?? ''));
    if ($n !== '')
      $pal[] = $n;
    if ($c !== '')
      $pal[] = $c;
  }
  $extra = ['software', 'desarrollo', 'programación', 'web', 'apps', 'frontend', 'backend', 'datos', 'cloud', 'IA', 'FP Dual', 'prácticas'];
  $pal = array_values(array_unique(array_filter(array_map('trim', array_merge($pal, $extra)))));
  return implode(', ', $pal);
}

/* =========================================================
   Proveedores disponibles
   - overpass: búsqueda por categorías/etiquetas OSM (RECOMENDADO)
   - nominatim: búsqueda textual OSM
   - (opcional) Bing/GCS/Serp si tienes claves
   ========================================================= */
$hasBing = (bool) (getenv('BING_API_KEY') ?: ($_ENV['BING_API_KEY'] ?? ''));
$hasGCS = (bool) ((getenv('GOOGLE_API_KEY') && getenv('GOOGLE_CSE_ID')) ?: (($_ENV['GOOGLE_API_KEY'] ?? '') && ($_ENV['GOOGLE_CSE_ID'] ?? '')));
$hasSerp = (bool) (getenv('SERPAPI_KEY') ?: ($_ENV['SERPAPI_KEY'] ?? ''));

$providers = [
  'overpass' => 'OpenStreetMap (Overpass) — Empresas tech por ciudad (RECOMENDADO)',
  'nominatim' => 'OpenStreetMap (Nominatim) — Búsqueda textual',
];
if ($hasBing)
  $providers['bing'] = 'Bing Web Search';
if ($hasGCS)
  $providers['gcs'] = 'Google Custom Search';
if ($hasSerp)
  $providers['serp'] = 'SerpAPI (Google)';

/* =========================================================
   HTTP helpers
   Nominatim/Overpass requieren User-Agent y contact email
   Puedes definir NOMINATIM_CONTACT en config/env.php
   ========================================================= */
function ua(): string
{
  $contact = getenv('NOMINATIM_CONTACT') ?: ($_ENV['NOMINATIM_CONTACT'] ?? 'contacto@practicalia.local');
  return 'PracticaliaProspectos/1.9 (' . $contact . ')';
}
function http_get_json(string $url, array $headers = []): array
{
  $ch = curl_init($url);
  $headers = array_merge(['User-Agent: ' . ua()], $headers);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 35,
    CURLOPT_HTTPHEADER => $headers,
  ]);
  $resp = curl_exec($ch);
  $err = curl_error($ch);
  $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($resp === false)
    throw new RuntimeException("HTTP error: $err");
  if ($code >= 400)
    throw new RuntimeException("HTTP $code: $resp");
  $json = json_decode($resp, true);
  if (!is_array($json))
    throw new RuntimeException('Respuesta no JSON');
  return $json;
}
function http_post_json(string $url, string $body, array $headers = []): array
{
  $ch = curl_init($url);
  $headers = array_merge(['User-Agent: ' . ua(), 'Content-Type: application/x-www-form-urlencoded'], $headers);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body,
  ]);
  $resp = curl_exec($ch);
  $err = curl_error($ch);
  $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($resp === false)
    throw new RuntimeException("HTTP error: $err");
  if ($code >= 400)
    throw new RuntimeException("HTTP $code: $resp");
  $json = json_decode($resp, true);
  if (!is_array($json))
    throw new RuntimeException('Respuesta no JSON');
  return $json;
}

/* =========================================================
   Nominatim (FREE)
   ========================================================= */
function nominatim_geocode_bbox(string $city, string $province): ?array
{
  $query = trim($city . ' ' . $province);
  if ($query === '')
    return null;
  $base = 'https://nominatim.openstreetmap.org/search';
  $params = [
    'q' => $query,
    'format' => 'jsonv2',
    'addressdetails' => '1',
    'limit' => '1',
    'countrycodes' => 'es'
  ];
  $url = $base . '?' . http_build_query($params);
  $json = http_get_json($url, ['Accept-Language: es']);
  if (!$json)
    return null;
  $r = $json[0] ?? null;
  if (!$r || empty($r['boundingbox']))
    return null;
  // Nominatim bboxes: [south, north, west, east]
  return [
    'south' => (float) $r['boundingbox'][0],
    'north' => (float) $r['boundingbox'][1],
    'west' => (float) $r['boundingbox'][2],
    'east' => (float) $r['boundingbox'][3],
    'display_name' => (string) ($r['display_name'] ?? $query),
  ];
}

function nominatim_search(string $query, string $city, string $province, int $limit = 20): array
{
  $qParts = [];
  if ($query !== '')
    $qParts[] = $query;
  $qParts[] = 'empresa software tecnología informática desarrollo web programación';
  $loc = trim($city . ' ' . $province);
  if ($loc !== '')
    $qParts[] = $loc;

  $qFinal = trim(implode(' ', array_filter($qParts)));
  $base = 'https://nominatim.openstreetmap.org/search';
  $params = [
    'q' => $qFinal,
    'format' => 'jsonv2',
    'addressdetails' => '1',
    'namedetails' => '1',
    'extratags' => '1',
    'limit' => max(1, min(50, $limit)),
    'countrycodes' => 'es'
  ];
  $url = $base . '?' . http_build_query($params);
  $json = http_get_json($url, ['Accept-Language: es']);

  $out = [];
  foreach ($json as $it) {
    $name = '';
    if (!empty($it['namedetails']['name']))
      $name = (string) $it['namedetails']['name'];
    elseif (!empty($it['display_name']))
      $name = preg_replace('/,.*/', '', (string) $it['display_name']);
    else
      $name = 'Empresa';

    $addr = (string) ($it['display_name'] ?? '');
    $xt = (array) ($it['extratags'] ?? []);
    $urlSite = '';
    foreach (['website', 'contact:website', 'url', 'contact:url'] as $k) {
      if (!empty($xt[$k])) {
        $urlSite = (string) $xt[$k];
        break;
      }
    }
    $phone = '';
    foreach (['phone', 'contact:phone'] as $k) {
      if (!empty($xt[$k])) {
        $phone = (string) $xt[$k];
        break;
      }
    }

    // Enlace OSM correcto: /{node|way|relation}/{id}
    $osmLink = '';
    if (!empty($it['osm_type']) && !empty($it['osm_id'])) {
      $t = strtolower((string) $it['osm_type']);
      if (!in_array($t, ['node', 'way', 'relation'], true))
        $t = 'node';
      $osmLink = 'https://www.openstreetmap.org/' . $t . '/' . (string) $it['osm_id'];
    }

    $out[] = [
      'title' => $name,
      'url' => $urlSite !== '' ? $urlSite : $osmLink,
      'snippet' => $addr,
      '_phone' => $phone !== '' ? $phone : null,
      '_addr' => $addr,
      '_source' => 'nominatim',
      '_maps' => $osmLink,
    ];
  }
  return $out;
}

/* =========================================================
   Overpass (FREE)
   - Saca bbox de ciudad con Nominatim
   - Busca POIs típicos de empresas tech:
     * office=it | office=technology | office=company (con nombre que contenga software/web/tec/informática)
     * craft=software
     * name =~ /(software|web|informática|tecno)/i
   ========================================================= */
function overpass_search(string $query, string $city, string $province, int $limit = 20): array
{
  $bbox = nominatim_geocode_bbox($city, $province);
  if (!$bbox)
    return [];

  $south = $bbox['south'];
  $west = $bbox['west'];
  $north = $bbox['north'];
  $east = $bbox['east'];
  // Regex a partir de semillas/sector/must:
  $terms = [];
  if ($query !== '') {
    foreach (preg_split('/[\s,;]+/u', $query) as $w) {
      $w = trim($w);
      if ($w !== '')
        $terms[] = preg_quote($w, '/');
    }
  }
  // Términos base
  $baseTerms = ['software', 'web', 'informática', 'tecnolog', 'desarrollo', 'programación', 'apps'];
  foreach ($baseTerms as $w)
    $terms[] = $w;
  $terms = array_values(array_unique($terms));
  $regex = count($terms) ? '(' . implode('|', $terms) . ')' : '(software|web|informática|tecnolog|desarrollo)';

  $ql = <<<QL
[out:json][timeout:30];
(
  node
    ["office"~"^(it|technology|company)$",i]
    ["name"~"$regex",i]
    ($south,$west,$north,$east);
  way
    ["office"~"^(it|technology|company)$",i]
    ["name"~"$regex",i]
    ($south,$west,$north,$east);
  relation
    ["office"~"^(it|technology|company)$",i]
    ["name"~"$regex",i]
    ($south,$west,$north,$east);

  node["craft"~"^(software)$",i]($south,$west,$north,$east);
  way ["craft"~"^(software)$",i]($south,$west,$north,$east);
  relation["craft"~"^(software)$",i]($south,$west,$north,$east);

  node["name"~"$regex",i]($south,$west,$north,$east);
  way ["name"~"$regex",i]($south,$west,$north,$east);
  relation["name"~"$regex",i]($south,$west,$north,$east);
);
out tags center;
QL;

  $url = 'https://overpass-api.de/api/interpreter';
  $body = 'data=' . urlencode($ql);
  $json = http_post_json($url, $body);

  $elements = $json['elements'] ?? [];
  if (!$elements)
    return [];

  $out = [];
  foreach ($elements as $el) {
    $tags = (array) ($el['tags'] ?? []);
    $name = trim((string) ($tags['name'] ?? 'Empresa'));

    // Dirección rápida:
    $addrParts = [];
    foreach (['addr:street', 'addr:housenumber', 'addr:postcode', 'addr:city', 'addr:state'] as $k) {
      if (!empty($tags[$k]))
        $addrParts[] = $tags[$k];
    }
    $addr = implode(', ', $addrParts);

    // Web, teléfono:
    $site = '';
    foreach (['website', 'contact:website', 'url', 'contact:url'] as $k) {
      if (!empty($tags[$k])) {
        $site = $tags[$k];
        break;
      }
    }
    $phone = '';
    foreach (['phone', 'contact:phone'] as $k) {
      if (!empty($tags[$k])) {
        $phone = $tags[$k];
        break;
      }
    }

    // Enlace OSM correcto: /{node|way|relation}/{id}
    $osmType = strtolower((string) ($el['type'] ?? 'node'));
    if (!in_array($osmType, ['node', 'way', 'relation'], true))
      $osmType = 'node';
    $osmId = (string) ($el['id'] ?? '');
    $osmLink = $osmId !== '' ? 'https://www.openstreetmap.org/' . $osmType . '/' . $osmId : '';

    $out[] = [
      'title' => $name !== '' ? $name : 'Empresa',
      'url' => $site !== '' ? $site : $osmLink,
      'snippet' => $addr,
      '_phone' => $phone !== '' ? $phone : null,
      '_addr' => $addr,
      '_source' => 'overpass',
      '_maps' => $osmLink,
    ];
  }

  // Devolver como mucho $limit, priorizando los que tienen website
  usort($out, function ($a, $b) {
    $aw = $a['url'] ? 1 : 0;
    $bw = $b['url'] ? 1 : 0;
    if ($aw !== $bw)
      return $bw - $aw;
    return strcmp($a['title'], $b['title']);
  });
  return array_slice($out, 0, max(1, min(50, $limit)));
}

/* =========================================================
   Proveedores "web": Bing / GCS / Serp
   ========================================================= */
function search_bing(string $q, int $count = 10): array
{
  $key = getenv('BING_API_KEY') ?: ($_ENV['BING_API_KEY'] ?? '');
  if ($key === '')
    throw new RuntimeException('Falta BING_API_KEY');
  $url = 'https://api.bing.microsoft.com/v7.0/search?q=' . rawurlencode($q) . '&count=' . $count . '&responseFilter=Webpages';
  $json = http_get_json($url, ['Ocp-Apim-Subscription-Key: ' . $key]);
  $items = $json['webPages']['value'] ?? [];
  $out = [];
  foreach ($items as $it) {
    $out[] = [
      'title' => (string) ($it['name'] ?? ''),
      'url' => (string) ($it['url'] ?? ''),
      'snippet' => (string) ($it['snippet'] ?? ''),
    ];
  }
  return $out;
}
function search_gcs(string $q, int $count = 10): array
{
  $api = getenv('GOOGLE_API_KEY') ?: ($_ENV['GOOGLE_API_KEY'] ?? '');
  $cx = getenv('GOOGLE_CSE_ID') ?: ($_ENV['GOOGLE_CSE_ID'] ?? '');
  if ($api === '' || $cx === '')
    throw new RuntimeException('Faltan GOOGLE_API_KEY/GOOGLE_CSE_ID');
  $url = 'https://www.googleapis.com/customsearch/v1?key=' . rawurlencode($api) . '&cx=' . rawurlencode($cx) . '&q=' . rawurlencode($q) . '&num=' . $count;
  $json = http_get_json($url);
  $items = $json['items'] ?? [];
  $out = [];
  foreach ($items as $it) {
    $out[] = [
      'title' => (string) ($it['title'] ?? ''),
      'url' => (string) ($it['link'] ?? ''),
      'snippet' => (string) ($it['snippet'] ?? ''),
    ];
  }
  return $out;
}
function search_serp(string $q, int $count = 10): array
{
  $key = getenv('SERPAPI_KEY') ?: ($_ENV['SERPAPI_KEY'] ?? '');
  if ($key === '')
    throw new RuntimeException('Falta SERPAPI_KEY');
  $url = 'https://serpapi.com/search.json?engine=google&q=' . rawurlencode($q) . '&num=' . $count . '&api_key=' . rawurlencode($key);
  $json = http_get_json($url);
  $items = $json['organic_results'] ?? [];
  $out = [];
  foreach ($items as $it) {
    $out[] = [
      'title' => (string) ($it['title'] ?? ''),
      'url' => (string) ($it['link'] ?? ''),
      'snippet' => (string) ($it['snippet'] ?? ''),
    ];
  }
  return $out;
}

/* =========================================================
   Utilidades de consulta (para proveedores web)
   ========================================================= */
function or_group(array $terms): string
{
  $terms = array_values(array_filter(array_map('trim', $terms)));
  if (!$terms)
    return '';
  if (count($terms) === 1)
    return '"' . addslashes($terms[0]) . '"';
  $quoted = array_map(fn($t) => '"' . addslashes($t) . '"', $terms);
  return '(' . implode(' OR ', $quoted) . ')';
}
function build_search_query(array $opts): string
{
  $seedStr = trim((string) ($opts['seeds'] ?? ''));
  $sector = trim((string) ($opts['sector'] ?? ''));
  $loc = trim((string) ($opts['loc'] ?? ''));
  $must = trim((string) ($opts['must'] ?? ''));
  $mode = (string) ($opts['mode'] ?? 'general');
  $legal = (bool) ($opts['legal_forms'] ?? true);
  $antiruido = (bool) ($opts['antiruido'] ?? true);

  $seeds = array_values(array_filter(array_map('trim', preg_split('/[,]/', $seedStr))));
  $seedGroup = $seeds ? or_group($seeds) : '';
  $sectorGroup = $sector !== '' ? or_group(preg_split('/\s*[;,]\s*|\s+/', $sector)) : '';
  $locPart = $loc !== '' ? '"' . addslashes($loc) . '"' : '';

  $mustPart = '';
  if ($must !== '') {
    $mustWords = array_values(array_filter(preg_split('/\s+/', $must)));
    $mustPart = implode(' ', array_map(fn($w) => '+' . $w, $mustWords));
  }

  $basePieces = [];
  if ($seedGroup !== '' && $sectorGroup !== '')
    $basePieces[] = '(' . $seedGroup . ' OR ' . $sectorGroup . ')';
  elseif ($seedGroup !== '')
    $basePieces[] = $seedGroup;
  elseif ($sectorGroup !== '')
    $basePieces[] = $sectorGroup;
  if ($locPart !== '')
    $basePieces[] = $locPart;
  if ($mustPart !== '')
    $basePieces[] = $mustPart;

  $q = implode(' ', $basePieces);

  if ($legal) {
    $q .= ' ' . or_group(['S.L.', 'SL', 'S.A.', 'SA', 'SLU', 'SLL']);
  }

  if ($antiruido) {
    $noise = [
      '-empleo',
      '-oferta',
      '-ofertas',
      '-trabajo',
      '-prácticas',
      '-practicas',
      '-infojobs',
      '-indeed',
      '-linkedin',
      '-glassdoor',
      '-github',
      '-gitlab',
      '-stackoverflow',
      '-stack overflow',
      '-pdf',
      '-blog',
      '-foro',
      '-noticia',
      '-wikipedia'
    ];
    $q .= ' ' . implode(' ', $noise);
  }

  if ($mode === 'directorios') {
    $sites = [
      'paginasamarillas.es',
      'einforma.com',
      'infoempresa.com',
      'kompass.com',
      'expansion.com/directorio-empresas',
      'iberinform.es'
    ];
    $q .= ' site:(' . implode(' OR ', $sites) . ')';
  } elseif ($mode === 'corporativa') {
    $q .= ' site:.es (inurl:empresa OR inurl:servicios OR inurl:contacto OR intitle:empresa)';
  }

  return trim(preg_replace('/\s+/', ' ', $q));
}
function guess_name_from_result(string $title, string $url): string
{
  $title = trim($title);
  if ($title !== '') {
    $title = preg_replace('/\s+[\|\-–]\s+.*/u', '', $title);
  }
  if ($title !== '')
    return mb_substr($title, 0, 190);
  $host = parse_url($url, PHP_URL_HOST) ?: '';
  $host = preg_replace('/^www\./i', '', (string) $host);
  $host = preg_replace('/\.[a-z]{2,}$/i', '', (string) $host);
  return $host ?: 'Empresa sin nombre';
}

/* =========================================================
   Controlador POST
   ========================================================= */
$error = '';
$results = [];
$semillas = '';
$diagMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $accion = $_POST['accion'] ?? '';

  if ($accion === 'generar_semillas') {
    try {
      csrf_check($_POST['csrf'] ?? null);
      $cursoIds = array_map('intval', (array) $_POST['curso_ids'] ?? []);
      if (!$cursoIds) {
        global $cursos;
        $cursoIds = array_map(fn($c) => (int) $c['id'], $cursos);
      }
      if (!$cursoIds)
        throw new RuntimeException('No hay cursos disponibles para generar semillas.');

      if (!$isAdmin && $cursoIds) {
        $in = implode(',', array_fill(0, count($cursoIds), '?'));
        $chk = $pdo->prepare("SELECT COUNT(*) FROM cursos_profesores WHERE profesor_id=? AND curso_id IN ($in)");
        $chk->execute(array_merge([$profId], $cursoIds));
        if ((int) $chk->fetchColumn() !== count($cursoIds)) {
          throw new RuntimeException('No puedes usar cursos que no tienes asignados.');
        }
      }

      $semillas = generarSemillasDesdeAsignaturas($pdo, $cursoIds);
      $diagMsg = 'Semillas generadas a partir de cursos: ' . implode(', ', array_map('strval', $cursoIds));
      if ($semillas === '')
        $diagMsg .= ' · Aviso: no hay asignaturas activas asociadas a esos cursos.';
    } catch (Throwable $e) {
      $error = $e->getMessage();
      $semillas = '';
    }
  }

  if ($accion === 'buscar') {
    try {
      csrf_check($_POST['csrf'] ?? null);

      $prov = $_POST['provider'] ?? 'overpass';
      $qInput = trim((string) ($_POST['q'] ?? ''));
      $autoQ = isset($_POST['autoq']) && $_POST['autoq'] === '1';

      // Afinado (solo aplica a proveedores web tipo Bing/GCS/Serp)
      $mode = $_POST['mode'] ?? 'general';
      $legal = isset($_POST['legal']) && $_POST['legal'] === '1';
      $antiruido = isset($_POST['antiruido']) && $_POST['antiruido'] === '1';

      // Partes de la consulta
      $ciudad = trim((string) ($_POST['ciudad'] ?? ''));
      $provincia = trim((string) ($_POST['provincia'] ?? ''));
      $sector = trim((string) ($_POST['sector'] ?? ''));
      $must = trim((string) ($_POST['must'] ?? ''));
      $seeds = $autoQ ? (string) ($_POST['semillas'] ?? '') : $qInput;

      $num = max(1, min(20, (int) ($_POST['num'] ?? 10)));
      $cursoId = (int) ($_POST['curso_id'] ?? 0);
      $tags = trim((string) ($_POST['tags'] ?? ''));

      if ($prov === 'overpass') {
        $parts = [];
        if ($seeds !== '')
          $parts[] = $seeds;
        if ($sector !== '')
          $parts[] = $sector;
        if ($must !== '') {
          foreach (array_values(array_filter(preg_split('/\s+/', $must))) as $w)
            $parts[] = $w;
        }
        $query = trim(implode(' ', $parts));

        if ($ciudad === '' && $provincia === '') {
          throw new RuntimeException('Para Overpass indica al menos ciudad o provincia.');
        }

        $results = overpass_search($query, $ciudad, $provincia, $num);
        $locText = trim($ciudad . ($provincia ? ', ' . $provincia : ''));
        $diagMsg = 'Consulta (Overpass): ' . ($query !== '' ? $query : '[genérica]') . ' · Zona: ' . ($locText !== '' ? $locText : '—');

      } elseif ($prov === 'nominatim') {
        $parts = [];
        if ($seeds !== '')
          $parts[] = $seeds;
        if ($sector !== '')
          $parts[] = $sector;
        if ($must !== '') {
          foreach (array_values(array_filter(preg_split('/\s+/', $must))) as $w)
            $parts[] = $w;
        }
        $query = trim(implode(' ', $parts));

        $results = nominatim_search($query, $ciudad, $provincia, $num);
        $locText = trim($ciudad . ($provincia ? ', ' . $provincia : ''));
        $diagMsg = 'Consulta (Nominatim): ' . ($query !== '' ? $query : '[genérica]') . ' · Ubicación: ' . ($locText !== '' ? $locText : '—');

      } else {
        // Proveedores "web" con afinado
        $locGlue = trim($ciudad . ' ' . $provincia);
        $query = build_search_query([
          'seeds' => $seeds,
          'sector' => $sector,
          'loc' => $locGlue,
          'must' => $must,
          'mode' => $mode,
          'legal_forms' => $legal,
          'antiruido' => $antiruido,
        ]);

        switch ($prov) {
          case 'bing':
            $fn = 'search_bing';
            break;
          case 'gcs':
            $fn = 'search_gcs';
            break;
          case 'serp':
            $fn = 'search_serp';
            break;
          default:
            throw new RuntimeException('Proveedor no disponible');
        }
        $results = $fn($query, $num);
        $diagMsg = 'Consulta enviada: ' . $query;
      }

      $_SESSION['prospectos_buscar_echo'] = [
        'curso_id' => (int) ($_POST['curso_id'] ?? 0),
        'tags' => (string) ($_POST['tags'] ?? ''),
        'ciudad' => (string) ($_POST['ciudad'] ?? ''),
        'provincia' => (string) ($_POST['provincia'] ?? ''),
        'provider' => (string) $prov,
        'mode' => (string) $mode,
      ];

    } catch (Throwable $e) {
      $error = $e->getMessage();
      $results = [];
    }
  }

  if ($accion === 'importar') {
    try {
      csrf_check($_POST['csrf'] ?? null);

      $sel = (array) ($_POST['sel'] ?? []);
      $rows = (array) ($_POST['rows'] ?? []);
      if (!$sel)
        throw new RuntimeException('No has seleccionado resultados');

      $echo = $_SESSION['prospectos_buscar_echo'] ?? [];
      $cursoId = (int) ($_POST['curso_id'] ?? ($echo['curso_id'] ?? 0));
      $tags = trim((string) ($_POST['tags'] ?? ($echo['tags'] ?? '')));
      $ciudad = trim((string) ($_POST['ciudad'] ?? ($echo['ciudad'] ?? '')));
      $provincia = trim((string) ($_POST['provincia'] ?? ($echo['provincia'] ?? '')));
      $sector = trim((string) ($_POST['sector'] ?? ''));

      if (!$isAdmin && $cursoId) {
        $chk = $pdo->prepare("SELECT 1 FROM cursos_profesores WHERE profesor_id=:p AND curso_id=:c LIMIT 1");
        $chk->execute([':p' => $profId, ':c' => $cursoId]);
        if (!$chk->fetch())
          throw new RuntimeException('No puedes asignar prospectos a ese curso.');
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
           'nuevo', :origen, :fuente, :tags, :curso, :prof)
      ");

      $n = 0;
      foreach ($sel as $idx) {
        if (!isset($rows[$idx]) || !is_array($rows[$idx]))
          continue;
        $r = $rows[$idx];

        $title = trim((string) ($r['title'] ?? ''));
        $url = trim((string) ($r['url'] ?? ''));
        $snippet = trim((string) ($r['snippet'] ?? ''));

        $phone = trim((string) ($r['_phone'] ?? ''));
        $addr = trim((string) ($r['_addr'] ?? ''));
        $source = (string) ($r['_source'] ?? 'web'); // 'overpass' | 'nominatim' | 'web'
        $mapsUrl = trim((string) ($r['_maps'] ?? ''));

        $name = $title !== '' ? $title : guess_name_from_result($title, $url);
        $notas = $addr !== '' ? $addr : $snippet;
        $tel = $phone !== '' ? $phone : null;

        // origen/fuente
        $origen = ($source === 'overpass' || $source === 'nominatim') ? 'osm' : 'busqueda';
        $fuente = $url !== '' ? $url : ($mapsUrl !== '' ? $mapsUrl : null);

        $ins->execute([
          ':nombre' => $name,
          ':web' => $url !== '' ? $url : ($mapsUrl !== '' ? $mapsUrl : null),
          ':tel' => $tel,
          ':sector' => ($sector !== '' ? $sector : null),
          ':ciudad' => ($ciudad !== '' ? $ciudad : null),
          ':provincia' => ($provincia !== '' ? $provincia : null),
          ':notas' => ($notas !== '' ? $notas : null),
          ':origen' => $origen,
          ':fuente' => $fuente,
          ':tags' => ($tags !== '' ? $tags : null),
          ':curso' => ($cursoId > 0 ? $cursoId : null),
          ':prof' => $profId ?: null,
        ]);
        $n++;
      }

      header('Location: ./index.php?ok=1&imp=' . (int) $n);
      exit;

    } catch (Throwable $e) {
      $error = $e->getMessage();
    }
  }
}

// Valores vista
$postedSemillas = ($semillas !== '') ? $semillas : trim((string) ($_POST['semillas'] ?? ''));
$selectedProv = $_POST['provider'] ?? 'overpass';
$postedMode = $_POST['mode'] ?? ($_SESSION['prospectos_buscar_echo']['mode'] ?? 'general');
$pageTitle = 'Buscar empresas';
require_once __DIR__ . '/../partials/_header.php';
?>
<div class="flex items-center justify-between">
  <h1 class="text-xl font-semibold">Buscar empresas (automática a demanda)</h1>
  <a href="./index.php" class="rounded-xl px-4 py-2 border">Volver</a>
</div>

<?php if ($error): ?>
  <div class="bg-red-50 text-red-700 p-3 rounded">
    <?= h($error) ?>
  </div>
<?php endif; ?>

<?php if ($diagMsg): ?>
  <div class="bg-blue-50 text-blue-800 p-3 rounded">
    <?= h($diagMsg) ?>
  </div>
<?php endif; ?>

<!-- 1) Generar semillas desde cursos/asignaturas -->
<section class="bg-white p-6 rounded-2xl shadow space-y-4">
  <h2 class="font-medium">1) Generar palabras clave desde tus asignaturas</h2>
  <form method="post" class="space-y-3">
    <input type="hidden" name="csrf" value="<?= csrf_html() ?>">
    <input type="hidden" name="accion" value="generar_semillas">

    <div>
      <label class="block text-sm font-medium mb-1">Cursos (marca alguno o deja vacío para usar todos)</label>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
        <?php if ($cursos):
          foreach ($cursos as $c): ?>
            <label class="flex items-center gap-2 border rounded-xl p-2">
              <input type="checkbox" name="curso_ids[]" value="<?= (int) $c['id'] ?>">
              <span>
                <?= h($c['nombre']) ?>
              </span>
            </label>
          <?php endforeach; else: ?>
          <p class="text-gray-500 text-sm">No tienes cursos asignados.</p>
        <?php endif; ?>
      </div>
    </div>

    <div>
      <button class="rounded-xl bg-black text-white px-4 py-2">Generar semillas</button>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Semillas (editable)</label>
      <textarea name="semillas" rows="3" class="w-full border rounded-xl p-2"
        placeholder="Bases de datos, 0484, desarrollo, web..."><?= h($postedSemillas) ?></textarea>
      <p class="text-xs text-gray-500 mt-1">Se usarán en la búsqueda si marcas “Construir consulta desde mis
        grados/asignaturas”.</p>
    </div>
  </form>
</section>

<!-- 2) Búsqueda automática vía API -->
<section class="bg-white p-6 rounded-2xl shadow space-y-4">
  <h2 class="font-medium">2) Buscar ahora (API)</h2>
  <form method="post" class="space-y-4" id="form-buscar">
    <input type="hidden" name="csrf" value="<?= csrf_html() ?>">
    <input type="hidden" name="accion" value="buscar">

    <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
      <div class="md:col-span-3">
        <label class="block text-sm font-medium">Consulta</label>
        <input name="q" class="mt-1 w-full border rounded-xl p-2"
          placeholder='p.ej. "empresa desarrollo web prácticas FP"' value="<?= h($_POST['q'] ?? '') ?>">
        <label class="mt-2 inline-flex items-center gap-2 text-sm">
          <input type="checkbox" name="autoq" value="1" <?= isset($_POST['autoq']) && $_POST['autoq'] === '1' ? 'checked' : '' ?>>
          Construir consulta desde mis grados/asignaturas (usa “Semillas”)
        </label>
      </div>

      <div>
        <label class="block text-sm font-medium">Proveedor</label>
        <select name="provider" id="provider" class="mt-1 w-full border rounded-xl p-2">
          <?php foreach ($providers as $k => $v): ?>
            <option value="<?= h($k) ?>" <?= ($selectedProv === $k) ? 'selected' : '' ?>>
              <?= h($v) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium">Modo</label>
        <select name="mode" id="mode" class="mt-1 w-full border rounded-xl p-2">
          <?php
          $modes = ['general' => 'General', 'directorios' => 'Directorios ES', 'corporativa' => 'Web corporativa (.es)'];
          foreach ($modes as $k => $v):
            ?>
            <option value="<?= h($k) ?>" <?= $postedMode === $k ? 'selected' : '' ?>>
              <?= h($v) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-gray-500 mt-1">El modo aplica solo a proveedores web (Bing/GCS/Serp), no a
          Overpass/Nominatim.</p>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
      <div>
        <label class="block text-sm font-medium">Ciudad</label>
        <input name="ciudad" class="mt-1 w-full border rounded-xl p-2" placeholder="Málaga, Sevilla, Granada..."
          value="<?= h($_POST['ciudad'] ?? '') ?>">
      </div>
      <div>
        <label class="block text-sm font-medium">Provincia</label>
        <input name="provincia" class="mt-1 w-full border rounded-xl p-2" placeholder="Málaga, Sevilla..."
          value="<?= h($_POST['provincia'] ?? '') ?>">
      </div>
      <div>
        <label class="block text-sm font-medium">Sector / palabras</label>
        <input name="sector" class="mt-1 w-full border rounded-xl p-2" placeholder="software, frontend, cloud..."
          value="<?= h($_POST['sector'] ?? '') ?>">
      </div>
      <div>
        <label class="block text-sm font-medium">Debe contener</label>
        <input name="must" class="mt-1 w-full border rounded-xl p-2" placeholder="prácticas, dual, FP..."
          value="<?= h($_POST['must'] ?? '') ?>">
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
      <div>
        <label class="block text-sm font-medium">Curso objetivo</label>
        <select name="curso_id" class="mt-1 w-full border rounded-xl p-2">
          <option value="">—</option>
          <?php foreach ($cursos as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (isset($_POST['curso_id']) && (int) $_POST['curso_id'] === (int) $c['id']) ? 'selected' : '' ?>>
              <?= h($c['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium">Etiquetas (coma)</label>
        <input name="tags" class="mt-1 w-full border rounded-xl p-2" placeholder="DAW, DAM, web, backend"
          value="<?= h($_POST['tags'] ?? '') ?>">
      </div>
      <div>
        <label class="block text-sm font-medium">Resultados</label>
        <input type="number" min="1" max="20" name="num" class="mt-1 w-full border rounded-xl p-2"
          value="<?= h($_POST['num'] ?? '10') ?>">
      </div>
    </div>

    <div class="flex gap-2 items-center">
      <div class="flex items-center gap-2">
        <input type="hidden" name="legal" value="0">
        <input type="checkbox" id="legal" name="legal" value="1" <?= (!isset($_POST['legal']) || $_POST['legal'] === '1') ? 'checked' : '' ?>>
        <label for="legal" class="text-sm">Incluir formas jurídicas (SL, SA, SLU…)</label>
      </div>
      <div class="flex items-center gap-2">
        <input type="hidden" name="antiruido" value="0">
        <input type="checkbox" id="antiruido" name="antiruido" value="1" <?= (!isset($_POST['antiruido']) || $_POST['antiruido'] === '1') ? 'checked' : '' ?>>
        <label for="antiruido" class="text-sm">Excluir empleo/foros/noticias (.pdf, infojobs, linkedin…)</label>
      </div>
    </div>

    <div class="flex gap-2">
      <button class="rounded-xl bg-black text-white px-4 py-2">Buscar</button>
      <a href="./buscar.php" class="rounded-xl px-4 py-2 border">Limpiar</a>
    </div>
  </form>
</section>

<?php if ($results): ?>
  <!-- 3) Resultados e importación -->
  <form method="post" class="bg-white p-6 rounded-2xl shadow space-y-4">
    <input type="hidden" name="csrf" value="<?= csrf_html() ?>">
    <input type="hidden" name="accion" value="importar">

    <!-- eco de filtros para import -->
    <input type="hidden" name="curso_id" value="<?= h($_POST['curso_id'] ?? '') ?>">
    <input type="hidden" name="tags" value="<?= h($_POST['tags'] ?? '') ?>">
    <input type="hidden" name="ciudad" value="<?= h($_POST['ciudad'] ?? '') ?>">
    <input type="hidden" name="provincia" value="<?= h($_POST['provincia'] ?? '') ?>">
    <input type="hidden" name="sector" value="<?= h($_POST['sector'] ?? '') ?>">

    <div class="flex items-center justify-between">
      <h2 class="font-semibold">Resultados</h2>
      <div class="text-sm text-gray-600">
        <?= count($results) ?> encontrados
      </div>
    </div>

    <div class="overflow-x-auto border rounded-2xl">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="p-3">
              <input type="checkbox"
                onclick="document.querySelectorAll('[data-row]').forEach(cb=>cb.checked=this.checked)">
            </th>
            <th class="text-left p-3">Empresa (estimada)</th>
            <th class="text-left p-3">URL</th>
            <th class="text-left p-3">Descripción</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($results as $i => $r):
            $name = guess_name_from_result((string) $r['title'], (string) $r['url']);
            ?>
            <tr class="border-t">
              <td class="p-3 align-top">
                <input type="checkbox" name="sel[]" value="<?= $i ?>" data-row>
              </td>
              <td class="p-3 align-top font-medium">
                <?= h($name) ?>
              </td>
              <td class="p-3 align-top">
                <?php if (!empty($r['url'])): ?>
                  <a class="underline break-all" target="_blank" href="<?= h($r['url']) ?>">
                    <?= h($r['url']) ?>
                  </a>
                  <?php if (!empty($r['_maps'])): ?>
                    <div class="text-xs"><a class="underline text-gray-600" target="_blank" href="<?= h($r['_maps']) ?>">Ver en
                        OpenStreetMap</a></div>
                  <?php endif; ?>
                <?php elseif (!empty($r['_maps'])): ?>
                  <a class="underline break-all" target="_blank" href="<?= h($r['_maps']) ?>">OpenStreetMap</a>
                <?php else: ?>
                  <span class="text-gray-400">—</span>
                <?php endif; ?>
              </td>
              <td class="p-3 align-top">
                <?= h($r['snippet'] ?? '') ?>
              </td>
            </tr>

            <!-- Campos ocultos para importar -->
            <input type="hidden" name="rows[<?= $i ?>][title]" value="<?= h($r['title'] ?? '') ?>">
            <input type="hidden" name="rows[<?= $i ?>][url]" value="<?= h($r['url'] ?? '') ?>">
            <input type="hidden" name="rows[<?= $i ?>][snippet]" value="<?= h($r['snippet'] ?? '') ?>">
            <input type="hidden" name="rows[<?= $i ?>][_phone]" value="<?= h($r['_phone'] ?? '') ?>">
            <input type="hidden" name="rows[<?= $i ?>][_addr]" value="<?= h($r['_addr'] ?? '') ?>">
            <input type="hidden" name="rows[<?= $i ?>][_source]" value="<?= h($r['_source'] ?? '') ?>">
            <input type="hidden" name="rows[<?= $i ?>][_maps]" value="<?= h($r['_maps'] ?? '') ?>">
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div>
      <button class="rounded-xl bg-black text-white px-4 py-2">Importar seleccionados</button>
    </div>
  </form>
<?php endif; ?>

<!-- 4) Fallback manual -->
<section class="bg-white p-6 rounded-2xl shadow space-y-4">
  <h2 class="font-medium">4) Añadir prospectos pegando listado (manual)</h2>
  <p class="text-sm text-gray-600">Formato por línea (opcional):
    <code>Nombre | web | email | teléfono | ciudad | provincia</code>
  </p>
  <form method="post" class="space-y-3">
    <input type="hidden" name="csrf" value="<?= csrf_html() ?>">
    <input type="hidden" name="accion" value="guardar_prospectos">

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
      <div>
        <label class="block text-sm font-medium mb-1">Curso (opcional)</label>
        <select name="curso_id" class="w-full border rounded-xl p-2">
          <option value="">— Sin curso —</option>
          <?php foreach ($cursos as $c): ?>
            <option value="<?= (int) $c['id'] ?>">
              <?= h($c['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Origen</label>
        <select name="origen" class="w-full border rounded-xl p-2">
          <option value="manual">Manual</option>
          <option value="busqueda">Búsqueda</option>
          <option value="import">Importación</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Etiquetas</label>
        <input name="semillas" value="<?= h($postedSemillas) ?>" class="w-full border rounded-xl p-2"
          placeholder="DAW, DAM, web, backend">
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Listado</label>
      <textarea name="prospectos_raw" rows="6" class="w-full border rounded-2xl p-3"
        placeholder="MiEmpresa SL | https://miep.com | rrhh@miep.com | 600000000 | Málaga | Málaga"></textarea>
    </div>

    <div>
      <button class="rounded-xl bg-black text-white px-4 py-2">Añadir al listado de prospectos</button>
    </div>
  </form>
</section>
</main>

<script>
  // Habilita/deshabilita "Modo" según proveedor (web vs OSM)
  function toggleMode() {
    const prov = document.getElementById('provider');
    const mode = document.getElementById('mode');
    if (!prov || !mode) return;
    const p = prov.value;
    const isOSM = (p === 'overpass' || p === 'nominatim');
    mode.disabled = isOSM;
    if (isOSM && mode.value !== 'general') mode.value = 'general';
  }
  document.addEventListener('DOMContentLoaded', toggleMode);
  document.getElementById('provider').addEventListener('change', toggleMode);
</script>
<?php require_once __DIR__ . '/../partials/_footer.php'; ?>