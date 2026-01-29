<?php
// practicalia/public/alumnos/contacto_create.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

$user = current_user();
$profId = (int)($user['id'] ?? 0); // admin o profe; se registra tu id

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); exit('Método no permitido');
}

try {
  csrf_check($_POST['csrf'] ?? null);

  $alumnoId = (int)($_POST['alumno_id'] ?? 0);
  $tipo     = trim($_POST['tipo'] ?? 'otro');
  $resumen  = trim($_POST['resumen'] ?? '');
  $notas    = trim($_POST['notas'] ?? '');

  if ($alumnoId <= 0) throw new RuntimeException('Alumno inválido');
  if ($resumen === '') throw new RuntimeException('Resumen obligatorio.');

  $tipos = ['llamada','email','tutoria','visita','otro'];
  if (!in_array($tipo, $tipos, true)) $tipo = 'otro';

  // Verificar acceso al alumno (si es profe)
  if (!require_role('admin')) {
    $st = $pdo->prepare("
      SELECT 1
      FROM alumnos a
      JOIN alumnos_cursos ac ON ac.alumno_id = a.id
      JOIN cursos_profesores cp ON cp.curso_id = ac.curso_id AND cp.profesor_id = :pid
      WHERE a.id = :id AND a.deleted_at IS NULL
      LIMIT 1
    ");
    $st->execute([':pid'=>$profId, ':id'=>$alumnoId]);
    if (!$st->fetch()) throw new RuntimeException('No puedes añadir contacto a este alumno.');
  }

  $stIns = $pdo->prepare('
    INSERT INTO alumno_contactos (alumno_id, profesor_id, tipo, resumen, notas)
    VALUES (:a,:p,:t,:r,:n)
  ');
  $stIns->execute([
    ':a'=>$alumnoId, ':p'=>$profId, ':t'=>$tipo,
    ':r'=>$resumen, ':n'=>($notas!==''?$notas:null)
  ]);

  header('Location: ./edit.php?id='.$alumnoId);
  exit;

} catch (Throwable $e) {
  http_response_code(400);
  exit('Error: ' . $e->getMessage());
}
