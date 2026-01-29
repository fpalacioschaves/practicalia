<?php
// practicalia/public/alumnos/contacto_create.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

$user = current_user();
$profId = (int) ($user['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Método no permitido');
}

$alumnoService = new \App\Services\AlumnoService($pdo);

try {
  csrf_check($_POST['csrf'] ?? null);

  $alumnoId = (int) ($_POST['alumno_id'] ?? 0);
  if ($alumnoId <= 0)
    throw new RuntimeException('Alumno inválido');

  if (!$alumnoService->checkAccess($alumnoId, $isAdmin, $profId)) {
    throw new RuntimeException('No puedes añadir contacto a este alumno.');
  }

  $alumnoService->createContacto($alumnoId, $profId, $_POST);

  header('Location: ./edit.php?id=' . $alumnoId);
  exit;

} catch (Throwable $e) {
  http_response_code(400);
  exit('Error: ' . $e->getMessage());
}
