<?php
// practicalia/public/empresas/delete.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); exit('Método no permitido');
}

try {
  csrf_check($_POST['csrf'] ?? null);

  $id    = (int)($_POST['id'] ?? 0);
  $force = isset($_POST['force']) && $_POST['force'] === '1'; // purga definitiva opcional

  if ($id <= 0) throw new RuntimeException('ID inválido');

  // ¿Existe?
  $st = $pdo->prepare('SELECT id, deleted_at FROM empresas WHERE id = :id LIMIT 1');
  $st->execute([':id' => $id]);
  $empresa = $st->fetch(PDO::FETCH_ASSOC);
  if (!$empresa) throw new RuntimeException('La empresa no existe');
  $yaBorrada = !empty($empresa['deleted_at']);

  $pdo->beginTransaction();

  if ($force) {
    // PURGA DEFINITIVA (requiere que tengas ON DELETE CASCADE configurado en tablas relacionadas)
    // Si no lo tienes, elimina manualmente dependencias aquí antes del DELETE.
    $pdo->prepare('DELETE FROM empresas WHERE id = :id')->execute([':id' => $id]);
  } else {
    if ($yaBorrada) {
      // Ya estaba en borrado lógico; no hacemos nada para evitar sorpresas
      $pdo->commit();
      header('Location: ./index.php?ok=1');
      exit;
    }

    // BORRADO LÓGICO: marcamos deleted_at y (opcional) anonimizamos datos sensibles del responsable
    $stUpd = $pdo->prepare('
      UPDATE empresas SET
        deleted_at = NOW(),
        responsable_nombre = NULL,
        responsable_cargo = NULL,
        responsable_email = NULL,
        responsable_telefono = NULL
      WHERE id = :id
    ');
    $stUpd->execute([':id' => $id]);

    // (Opcional) si la empresa no debe aparecer en listados de vínculos, puedes desactivarla también
    // $pdo->prepare('UPDATE empresas SET activo = 0 WHERE id = :id')->execute([':id' => $id]);
  }

  $pdo->commit();

  header('Location: ./index.php?ok=1');
  exit;

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(400);
  exit('Error: ' . $e->getMessage());
}
