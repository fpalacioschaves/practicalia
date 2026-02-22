<?php
// api/prospectos/contactar.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/Services/EmailService.php';

use App\Services\EmailService;

try {
    $raw = file_get_contents('php://input');
    $in = json_decode($raw, true);

    $id = (int) ($in['id'] ?? 0);
    $asunto = trim((string) ($in['asunto'] ?? ''));
    $mensaje = trim((string) ($in['mensaje'] ?? ''));

    if (!$id || !$asunto || !$mensaje) {
        throw new RuntimeException('Faltan parámetros obligatorios');
    }

    // Obtener datos del prospecto
    $st = $pdo->prepare("SELECT * FROM empresas_prospectos WHERE id = ?");
    $st->execute([$id]);
    $p = $st->fetch();

    if (!$p || !$p['email']) {
        throw new RuntimeException('Prospecto no encontrado o sin email');
    }

    $profesor = current_user();

    // Enviar email
    $emailService = new EmailService($pdo);
    $result = $emailService->sendEmail(
        $p['email'],
        $asunto,
        $mensaje,
        [],
        $profesor['email']
    );

    if ($result['success']) {
        // Actualizar estado
        $upd = $pdo->prepare("UPDATE empresas_prospectos SET estado = 'contactado', updated_at = NOW() WHERE id = ?");
        $upd->execute([$id]);

        // Registrar contacto (opcional, si hay tabla de contactos_prospectos, si no, al menos el estado)
        echo json_encode(['ok' => true]);
    } else {
        throw new RuntimeException('Error al enviar el correo');
    }

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
