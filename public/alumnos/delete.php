<?php
// practicalia/public/alumnos/delete.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

$user = current_user();
$isAdmin = require_role('admin');
$profId  = (int)($user['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Método no permitido');
}

try {
  csrf_check($_POST['csrf'] ?? null);
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) throw new RuntimeException('ID inválido');

  // Si es profesor, comprobar que el alumno es suyo
  if (!$isAdmin) {
    $stChk = $pdo->prepare("
      SELECT 1
      FROM alumnos a
      JOIN alumnos_cursos ac ON ac.alumno_id = a.id
      JOIN cursos_profesores cp ON cp.curso_id = ac.curso_id AND cp.profesor_id = :pid
      WHERE a.id = :id AND a.deleted_at IS NULL
      LIMIT 1
    ");
    $stChk->execute([':pid'=>$profId, ':id'=>$id]);
    if (!$stChk->fetch()) {
      throw new RuntimeException('No tienes permiso para eliminar este alumno.');
    }
  }

  // Eliminar relaciones y alumno
  $pdo->prepare('DELETE FROM alumnos_cursos WHERE alumno_id = :id')->execute([':id'=>$id]);
  $pdo->prepare('DELETE FROM alumnos WHERE id = :id')->execute([':id'=>$id]);

  header('Location: ./index.php');
  exit;

} catch (Throwable $e) {
  http_response_code(400);
  exit('Error: ' . $e->getMessage());
}
