<?php
// practicalia/public/empresas/contacto_delete.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

$user = current_user();
$isAdmin = require_role('admin');
$profId  = (int)($user['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); exit('Método no permitido');
}

try {
  csrf_check($_POST['csrf'] ?? null);
  $empresaId = (int)($_POST['empresa_id'] ?? 0);
  $id        = (int)($_POST['id'] ?? 0);
  if ($empresaId <= 0 || $id <= 0) throw new RuntimeException('Datos inválidos');

  // Comprobar autor si no es admin
  if (!$isAdmin) {
    $st = $pdo->prepare('SELECT profesor_id FROM empresa_contactos WHERE id = :id AND empresa_id = :e LIMIT 1');
    $st->execute([':id'=>$id, ':e'=>$empresaId]);
    $row = $st->fetch();
    if (!$row || (int)$row['profesor_id'] !== $profId) {
      throw new RuntimeException('No puedes eliminar este contacto.');
    }
  }

  $pdo->prepare('DELETE FROM empresa_contactos WHERE id = :id AND empresa_id = :e')->execute([':id'=>$id, ':e'=>$empresaId]);

  header("Location: ./edit.php?id={$empresaId}");
  exit;

} catch (Throwable $e) {
  http_response_code(400);
  exit('Error: ' . $e->getMessage());
}
