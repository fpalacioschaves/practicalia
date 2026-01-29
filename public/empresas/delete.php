<?php
// practicalia/public/empresas/delete.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Método no permitido');
}

$empresaService = new \App\Services\EmpresaService($pdo);

try {
  csrf_check($_POST['csrf'] ?? null);

  $id = (int) ($_POST['id'] ?? 0);
  $force = isset($_POST['force']) && $_POST['force'] === '1';

  if ($id <= 0)
    throw new RuntimeException('ID inválido');

  $empresaService->delete($id, $isAdmin, $force);

  header('Location: ./index.php?ok=1');
  exit;

} catch (Throwable $e) {
  http_response_code(400);
  exit('Error: ' . $e->getMessage());
}
