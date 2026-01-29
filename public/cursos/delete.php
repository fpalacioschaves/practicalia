<?php
// practicalia/public/cursos/delete.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_admin.php';
require_once __DIR__ . '/../../lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Método no permitido');
}

try {
  csrf_check($_POST['csrf'] ?? null);
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) throw new RuntimeException('ID inválido');

  // Borrar relaciones con profesores
  $pdo->prepare('DELETE FROM cursos_profesores WHERE curso_id = :id')->execute([':id'=>$id]);

  // Opción A: borrado duro
  $pdo->prepare('DELETE FROM cursos WHERE id = :id')->execute([':id'=>$id]);

  // (Si prefieres soft delete, reemplaza por: UPDATE cursos SET activo=0 ... y opcionalmente una columna deleted_at)
  header('Location: ./index.php');
  exit;

} catch (Throwable $e) {
  http_response_code(400);
  exit('Error: ' . $e->getMessage());
}
