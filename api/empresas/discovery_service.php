<?php
// api/empresas/discovery_service.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// Aumentar tiempo ejecución para permitir reintentos en múltiples servidores
set_time_limit(300);

// Registrar errores en un archivo para depuración
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/discovery_errors.log');
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../config/db.php';

try {
    if (!function_exists('curl_init')) {
        throw new RuntimeException('La extensión CURL no está habilitada en este servidor.');
    }

    $raw = file_get_contents('php://input');
    $in = json_decode($raw, true);

    $sector = trim((string) ($in['sector'] ?? ''));
    $provincia = trim((string) ($in['provincia'] ?? ''));
    $ciudad = trim((string) ($in['ciudad'] ?? ''));
    $limit = (int) ($in['limit_per_source'] ?? 50);

    if (!$sector || !$provincia) {
        throw new RuntimeException('Sector y Provincia son obligatorios');
    }

    // 1. Construir query de Overpass
    // Buscamos nodos y formas que tengan tags relacionados con el sector
    // Usamos el área de la ciudad o provincia
    $areaName = $ciudad ?: $provincia;

    // Mapeo avanzado de sectores a tags de OSM
    $tagFilter = "";
    $s = mb_strtolower($sector);

    // Categorías comunes para FCT / FP Dual
    if (str_contains($s, 'informat') || str_contains($s, 'software') || str_contains($s, ' it') || str_contains($s, 'program')) {
        $tagFilter = '["office"~"it|software|telecommunication"]';
    } elseif (str_contains($s, 'comercio') || str_contains($s, 'tienda') || str_contains($s, 'retail')) {
        $tagFilter = '[shop]';
    } elseif (str_contains($s, 'restauran') || str_contains($s, 'hosteleri') || str_contains($s, 'bar') || str_contains($s, 'cafe')) {
        $tagFilter = '["amenity"~"restaurant|cafe|bar|fast_food|pub"]';
    } elseif (str_contains($s, 'guarder') || str_contains($s, 'infantil') || str_contains($s, 'niño') || str_contains($s, 'cole')) {
        $tagFilter = '["amenity"~"kindergarten|childcare|school"]';
    } elseif (str_contains($s, 'taller') || str_contains($s, 'mecanic') || str_contains($s, 'coche')) {
        $tagFilter = '["craft"~"car_repair"]';
    } elseif (str_contains($s, 'estetica') || str_contains($s, 'peluquer') || str_contains($s, 'belleza')) {
        $tagFilter = '["shop"~"hairdresser|beauty|massage"]';
    } elseif (str_contains($s, 'abogado') || str_contains($s, 'gestor') || str_contains($s, 'asesor') || str_contains($s, 'consult')) {
        $tagFilter = '["office"~"lawyer|accountant|tax_advisor|consulting"]';
    } elseif (str_contains($s, 'farmacia') || str_contains($s, 'salud') || str_contains($s, 'clinic')) {
        $tagFilter = '["amenity"~"pharmacy|clinic|doctors"]';
    }

    // Construcción de la query
    // Si hemos detectado una categoría clara, buscamos nodos y áreas de esa categoría
    if ($tagFilter) {
        $queryBody = "
          node$tagFilter(area.searchArea);
          way$tagFilter(area.searchArea);
        ";
    } else {
        // Fallback: Si no conocemos el sector, buscamos la palabra clave en el nombre o tags descriptivos
        // El operador [~"key_regex"~"value_regex"] es muy potente para esto
        $safeSector = addslashes($sector);
        $queryBody = "
          node[~\"amenity|office|shop|craft|name|description\"~\"$safeSector\", i](area.searchArea);
          way[~\"amenity|office|shop|craft|name|description\"~\"$safeSector\", i](area.searchArea);
        ";
    }

    // 60 segundos de timeout para peticiones pesadas o servidores lentos
    $timeout = 60;

    $overpassQuery = "
    [out:json][timeout:$timeout];
    area[name=\"$areaName\"]->.searchArea;
    (
      $queryBody
    );
    out center $limit;
    ";

    // Listado de servidores espejo de Overpass para evitar saturación
    $servers = [
        'https://overpass-api.de/api/interpreter',
        'https://overpass.kumi.systems/api/interpreter',
        'https://lz4.overpass-api.de/api/interpreter'
    ];

    $response = null;
    $lastError = '';
    $success = false;

    foreach ($servers as $serverUrl) {
        $ch = curl_init($serverUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "data=" . urlencode($overpassQuery));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: PracticaliaAutomation/1.0']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $success = true;
            break;
        } else {
            $lastError = "Servidor $serverUrl falló (HTTP $httpCode) " . ($curlError ?: '');
            // Continuar al siguiente servidor
        }
    }

    if (!$success) {
        throw new RuntimeException("Todos los servidores de OpenStreetMap están saturados ahora mismo. Último error: $lastError. Por favor, intenta de nuevo en unos minutos o busca en una zona más pequeña.");
    }

    $data = json_decode($response, true);
    $elements = $data['elements'] ?? [];

    $results = [];
    foreach ($elements as $el) {
        $tags = $el['tags'] ?? [];
        $nombre = $tags['name'] ?? $tags['brand'] ?? $tags['operator'] ?? null;

        if (!$nombre)
            continue;

        $results[] = [
            'nombre' => $nombre,
            'web' => $tags['website'] ?? $tags['url'] ?? null,
            'email' => $tags['email'] ?? null,
            'telefono' => $tags['phone'] ?? $tags['contact:phone'] ?? null,
            'direccion' => ($tags['addr:street'] ?? '') . ' ' . ($tags['addr:housenumber'] ?? ''),
            'ciudad' => $tags['addr:city'] ?? $tags['addr:suburb'] ?? $ciudad,
            'cp' => $tags['addr:postcode'] ?? null,
            'sector' => $sector,
            'osm_id' => $el['id'],
            'lat' => $el['lat'] ?? $el['center']['lat'] ?? null,
            'lon' => $el['lon'] ?? $el['center']['lon'] ?? null
        ];
    }

    $json = json_encode([
        'ok' => true,
        'count' => count($results),
        'results' => $results
    ], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

    if ($json === false) {
        throw new RuntimeException("Error al codificar los resultados en JSON: " . json_last_error_msg());
    }

    echo $json;

} catch (Throwable $e) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
        'trace' => (str_contains($_SERVER['HTTP_HOST'] ?? '', 'localhost')) ? $e->getTraceAsString() : null
    ]);
}
