<?php
// practicalia/public/centros/delete.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); exit('Método no permitido');
}

try {
  csrf_check($_POST['csrf'] ?? null);
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) throw new RuntimeException('ID inválido');

  // Borrado "blando": marca deleted_at y listo
  $st = $pdo->prepare('UPDATE centros SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id AND deleted_at IS NULL');
  $st->execute([':id' => $id]);

  header('Location: ./index.php');
  exit;

} catch (Throwable $e) {
  http_response_code(400);
  exit('Error: ' . $e->getMessage());
}
