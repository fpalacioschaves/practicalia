<?php
// practicalia/public/empresas/alumno_add.php
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
  $alumnoId  = (int)($_POST['alumno_id'] ?? 0);
  if ($empresaId <= 0 || $alumnoId <= 0) throw new RuntimeException('Datos inválidos');

  if (!$isAdmin) {
    // Verifica que el alumno pertenece a cursos del profesor
    $st = $pdo->prepare("
      SELECT 1
      FROM alumnos_cursos ac
      JOIN cursos_profesores cp ON cp.curso_id = ac.curso_id AND cp.profesor_id = :pid
      WHERE ac.alumno_id = :al
      LIMIT 1
    ");
    $st->execute([':pid'=>$profId, ':al'=>$alumnoId]);
    if (!$st->fetch()) throw new RuntimeException('No puedes asociar a este alumno.');
  }

  $ins = $pdo->prepare('INSERT IGNORE INTO empresa_alumnos (empresa_id, alumno_id) VALUES (:e,:a)');
  $ins->execute([':e'=>$empresaId, ':a'=>$alumnoId]);

  header("Location: ./edit.php?id={$empresaId}");
  exit;

} catch (Throwable $e) {
  http_response_code(400);
  exit('Error: ' . $e->getMessage());
}
