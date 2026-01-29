<?php
// practicalia/public/empresas/contacto_create.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

$user = current_user();
$profId = (int)($user['id'] ?? 0); // aunque seas admin, se registrará tu id

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); exit('Método no permitido');
}

try {
  csrf_check($_POST['csrf'] ?? null);
  $empresaId = (int)($_POST['empresa_id'] ?? 0);
  $tipo      = trim($_POST['tipo'] ?? 'otro');
  $resumen   = trim($_POST['resumen'] ?? '');
  $notas     = trim($_POST['notas'] ?? '');

  if ($empresaId <= 0) throw new RuntimeException('Empresa inválida');
  if ($resumen === '') throw new RuntimeException('Resumen obligatorio.');

  $tipos = ['llamada','email','visita','otro'];
  if (!in_array($tipo, $tipos, true)) $tipo = 'otro';

  $st = $pdo->prepare('
    INSERT INTO empresa_contactos (empresa_id, profesor_id, tipo, resumen, notas)
    VALUES (:e,:p,:t,:r,:n)
  ');
  $st->execute([
    ':e'=>$empresaId, ':p'=>$profId, ':t'=>$tipo,
    ':r'=>$resumen, ':n'=>($notas!==''?$notas:null)
  ]);

  header("Location: ./edit.php?id={$empresaId}");
  exit;

} catch (Throwable $e) {
  http_response_code(400);
  exit('Error: ' . $e->getMessage());
}
