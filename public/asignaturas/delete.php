<?php
// practicalia/public/asignaturas/delete.php
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

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

try {
  csrf_check($_POST['csrf'] ?? null);
  if (!$id || $id <= 0) {
    throw new RuntimeException('ID inválido');
  }

  // Cargar asignatura viva
  $st = $pdo->prepare("
    SELECT a.id, a.curso_id
    FROM asignaturas a
    WHERE a.id = :id AND a.deleted_at IS NULL
    LIMIT 1
  ");
  $st->execute([':id' => $id]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if (!$row) {
    throw new RuntimeException('Asignatura no encontrada');
  }

  // Si es profesor, debe impartir al menos uno de los cursos asociados (principal o N:M)
  if (!$isAdmin) {
    $chk = $pdo->prepare("
      SELECT 1
      FROM cursos_profesores cp
      WHERE cp.profesor_id = :p
        AND (cp.curso_id = :c1
             OR EXISTS (
                  SELECT 1 FROM asignatura_cursos ac
                  WHERE ac.asignatura_id = :a AND ac.curso_id = cp.curso_id
             ))
      LIMIT 1
    ");
    $chk->execute([
      ':p' => $profId,
      ':c1' => (int)$row['curso_id'],
      ':a' => (int)$row['id'],
    ]);
    if (!$chk->fetch()) {
      throw new RuntimeException('No tienes permiso para eliminar esta asignatura.');
    }
  }

  // Borrado lógico
  $pdo->prepare("UPDATE asignaturas SET deleted_at = NOW() WHERE id = :id")->execute([':id' => $id]);

  // Limpieza opcional de la tabla puente (no afecta al borrado lógico)
  $pdo->prepare("DELETE FROM asignatura_cursos WHERE asignatura_id = :a")->execute([':a' => $id]);

  header('Location: ./index.php?deleted=1');
  exit;

} catch (Throwable $e) {
  $msg = urlencode($e->getMessage());
  header("Location: ./index.php?error={$msg}");
  exit;
}
