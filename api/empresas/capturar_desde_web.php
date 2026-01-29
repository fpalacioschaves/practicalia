<?php
// api/empresas/capturar_desde_web.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../lib/empresas/HtmlCompanyExtractor.php';

try {
    // Body JSON: { url, html, criterios: { sector, provincia?, ciudad?, etiquetas?[] } }
    $raw = file_get_contents('php://input');
    if (!$raw) throw new RuntimeException('BODY_VACIO');
    $in = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

    $url   = trim((string)($in['url']  ?? ''));
    $html  = (string)($in['html'] ?? '');
    $crit  = (array)($in['criterios'] ?? []);
    $sector = isset($crit['sector']) ? trim((string)$crit['sector']) : null;
    $prov   = isset($crit['provincia']) ? trim((string)$crit['provincia']) : null;
    $ciu    = isset($crit['ciudad']) ? trim((string)$crit['ciudad']) : null;

    if ($url === '' || $html === '') throw new RuntimeException('FALTAN_PARAMETROS');

    // 1) Extraer de HTML
    $ext = new HtmlCompanyExtractor();
    $info = $ext->extract($html, $url);

    // 2) Mezclar criterios del profesor (mandan ellos)
    if ($sector)   $info['sector']   = $sector;
    if ($prov && empty($info['provincia'])) $info['provincia'] = $prov;
    if ($ciu  && empty($info['ciudad']))    $info['ciudad']    = $ciu;

    // 3) Conectar BBDD
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=practicalia;charset=utf8mb4','root','',[
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // 4) Deduplicación: por web o (nombre + ciudad)
    $sel = $pdo->prepare("
        SELECT id FROM empresas
        WHERE (web = :web AND web IS NOT NULL AND web <> '')
           OR (nombre = :nombre AND (ciudad <=> :ciudad))
        LIMIT 1
    ");
    $sel->execute([
        ':web'    => $info['web'],
        ':nombre' => $info['nombre'],
        ':ciudad' => $info['ciudad'] ?? null,
    ]);
    $existingId = $sel->fetchColumn();

    // 5) Insert o Update
    if ($existingId) {
        $upd = $pdo->prepare("
            UPDATE empresas SET
              nombre = COALESCE(:nombre, nombre),
              sector = COALESCE(:sector, sector),
              provincia = COALESCE(:provincia, provincia),
              ciudad = COALESCE(:ciudad, ciudad),
              direccion = COALESCE(:direccion, direccion),
              codigo_postal = COALESCE(:cp, codigo_postal),
              web = COALESCE(:web, web),
              email = COALESCE(:email, email),
              telefono = COALESCE(:telefono, telefono),
              activo = 1
            WHERE id = :id
        ");
        $upd->execute([
            ':nombre'   => $info['nombre'],
            ':sector'   => $info['sector'] ?? null,
            ':provincia'=> $info['provincia'],
            ':ciudad'   => $info['ciudad'],
            ':direccion'=> $info['direccion'],
            ':cp'       => $info['codigo_postal'],
            ':web'      => $info['web'],
            ':email'    => $info['email'],
            ':telefono' => $info['telefono'],
            ':id'       => (int)$existingId
        ]);
        $id = (int)$existingId;
        $accion = 'actualizado';
    } else {
        $ins = $pdo->prepare("
            INSERT INTO empresas
            (nombre, sector, provincia, ciudad, direccion, codigo_postal, web, email, telefono, activo)
            VALUES
            (:nombre, :sector, :provincia, :ciudad, :direccion, :cp, :web, :email, :telefono, 1)
        ");
        $ins->execute([
            ':nombre'   => $info['nombre'],
            ':sector'   => $info['sector'] ?? null,
            ':provincia'=> $info['provincia'],
            ':ciudad'   => $info['ciudad'],
            ':direccion'=> $info['direccion'],
            ':cp'       => $info['codigo_postal'],
            ':web'      => $info['web'],
            ':email'    => $info['email'],
            ':telefono' => $info['telefono'],
        ]);
        $id = (int)$pdo->lastInsertId();
        $accion = 'creado';
    }

    echo json_encode([
        'ok' => true,
        'accion' => $accion,
        'id' => $id,
        'empresa' => $info
    ], JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
