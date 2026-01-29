<?php
// practicalia/public/ras/delete.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ./index.php');
  exit;
}

$user    = current_user();
$isAdmin = require_role('admin');
$profId  = (int)($user['id'] ?? 0);

try {
  csrf_check($_POST['csrf'] ?? null);

  $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
  if (!$id || $id <= 0) throw new RuntimeException('ID inválido');

  // Cargar RA con su curso para validar permisos
  $st = $pdo->prepare("
    SELECT ra.id, a.curso_id
    FROM asignatura_ras ra
    JOIN asignaturas a ON a.id = ra.asignatura_id
    WHERE ra.id = :id AND ra.deleted_at IS NULL AND a.deleted_at IS NULL
    LIMIT 1
  ");
  $st->execute([':id'=>$id]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if (!$row) throw new RuntimeException('RA no encontrado');

  // Si es profesor, debe impartir el grado
  if (!$isAdmin) {
    $chk = $pdo->prepare("SELECT 1 FROM cursos_profesores WHERE curso_id=:c AND profesor_id=:p LIMIT 1");
    $chk->execute([':c'=>(int)$row['curso_id'], ':p'=>$profId]);
    if (!$chk->fetch()) throw new RuntimeException('No tienes permiso para eliminar este RA.');
  }

  // Borrado lógico
  $pdo->prepare("UPDATE asignatura_ras SET deleted_at = NOW() WHERE id = :id")->execute([':id'=>$id]);

  header('Location: ./index.php?deleted=1');
  exit;

} catch (Throwable $e) {
  $msg = urlencode($e->getMessage());
  header("Location: ./index.php?error={$msg}");
  exit;
}
