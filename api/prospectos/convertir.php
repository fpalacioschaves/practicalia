<?php
// api/prospectos/convertir.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../config/db.php';

try {
    $raw = file_get_contents('php://input');
    $in = json_decode($raw, true);

    $id = (int) ($in['id'] ?? 0);
    if (!$id)
        throw new RuntimeException('ID de prospecto no válido');

    $pdo->beginTransaction();

    // 1. Obtener prospecto
    $st = $pdo->prepare("SELECT * FROM empresas_prospectos WHERE id = ? FOR UPDATE");
    $st->execute([$id]);
    $p = $st->fetch();

    if (!$p)
        throw new RuntimeException('Prospecto no encontrado');

    // 2. Insertar en empresas
    $ins = $pdo->prepare("
        INSERT INTO empresas (nombre, sector, ciudad, web, email, telefono, activo)
        VALUES (:nom, :sec, :ciu, :web, :mail, :tel, 1)
    ");
    $ins->execute([
        ':nom' => $p['nombre'],
        ':sec' => $p['sector'],
        ':ciu' => $p['ciudad'],
        ':web' => $p['web'],
        ':mail' => $p['email'],
        ':tel' => $p['telefono']
    ]);

    $nuevaEmpresaId = $pdo->lastInsertId();

    // 3. Marcar prospecto como convertido (o borrar lógicamente)
    $upd = $pdo->prepare("UPDATE empresas_prospectos SET estado = 'convertido', updated_at = NOW(), deleted_at = NOW() WHERE id = ?");
    $upd->execute([$id]);

    $pdo->commit();

    echo json_encode(['ok' => true, 'empresa_id' => $nuevaEmpresaId]);

} catch (Throwable $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
