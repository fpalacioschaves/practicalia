<?php
// api/prospectos/create.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../config/db.php';

try {
    $raw = file_get_contents('php://input');
    $in = json_decode($raw, true);

    $nombre = trim((string) ($in['nombre'] ?? ''));
    if (!$nombre)
        throw new RuntimeException('El nombre es obligatorio');

    $profId = current_user()['id'];

    // Insertar en empresas_prospectos
    $st = $pdo->prepare("
        INSERT INTO empresas_prospectos 
        (nombre, sector, ciudad, web, email, telefono, asignado_profesor_id, origen, estado)
        VALUES (:nom, :sec, :ciu, :web, :mail, :tel, :prof, 'busqueda', 'nuevo')
    ");

    $st->execute([
        ':nom' => $nombre,
        ':sec' => $in['sector'] ?? null,
        ':ciu' => $in['ciudad'] ?? null,
        ':web' => $in['web'] ?? null,
        ':mail' => $in['email'] ?? null,
        ':tel' => $in['telefono'] ?? null,
        ':prof' => $profId
    ]);

    echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
