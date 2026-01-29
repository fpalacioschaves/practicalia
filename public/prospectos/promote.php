<?php
// practicalia/public/prospectos/promote.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); exit('Método no permitido');
}

try {
  csrf_check($_POST['csrf'] ?? null);
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) throw new RuntimeException('ID inválido');

  // Cargamos lead
  $st = $pdo->prepare("SELECT * FROM empresas_prospectos WHERE id=:id AND deleted_at IS NULL LIMIT 1");
  $st->execute([':id'=>$id]);
  $p = $st->fetch(PDO::FETCH_ASSOC);
  if (!$p) throw new RuntimeException('Prospecto no encontrado');

  $pdo->beginTransaction();

  // ¿Existe empresa con misma web o email?
  $stE = $pdo->prepare("SELECT * FROM empresas WHERE deleted_at IS NULL AND ( (web IS NOT NULL AND web = :web) OR (email IS NOT NULL AND email = :email) ) LIMIT 1");
  $stE->execute([':web'=>$p['web'], ':email'=>$p['email']]);
  $empresa = $stE->fetch(PDO::FETCH_ASSOC);

  if (!$empresa) {
    // Crear empresa
    $stIns = $pdo->prepare("
      INSERT INTO empresas
        (nombre, sector, web, email, telefono, ciudad, provincia,
         responsable_nombre, responsable_cargo, responsable_email, responsable_telefono,
         activo)
      VALUES
        (:nombre,:sector,:web,:email,:telefono,:ciudad,:provincia,
         :rnom,:rcargo,:remail,:rtel, 1)
    ");
    $stIns->execute([
      ':nombre'=>$p['nombre'],
      ':sector'=>$p['sector'],
      ':web'=>$p['web'] ?: null,
      ':email'=>$p['email'] ?: null,
      ':telefono'=>$p['telefono'] ?: null,
      ':ciudad'=>$p['ciudad'] ?: null,
      ':provincia'=>$p['provincia'] ?: null,
      ':rnom'=>$p['responsable_nombre'] ?: null,
      ':rcargo'=>$p['responsable_cargo'] ?: null,
      ':remail'=>$p['responsable_email'] ?: null,
      ':rtel'=>$p['responsable_telefono'] ?: null,
    ]);
    $empresaId = (int)$pdo->lastInsertId();
  } else {
    $empresaId = (int)$empresa['id'];
  }

  // Vincular curso si procede
  if (!empty($p['curso_id'])) {
    // evitar duplicados
    $stChk = $pdo->prepare("SELECT 1 FROM empresa_cursos WHERE empresa_id=:e AND curso_id=:c LIMIT 1");
    $stChk->execute([':e'=>$empresaId, ':c'=>$p['curso_id']]);
    if (!$stChk->fetch()) {
      $pdo->prepare("INSERT INTO empresa_cursos (empresa_id, curso_id) VALUES (:e,:c)")
          ->execute([':e'=>$empresaId, ':c'=>$p['curso_id']]);
    }
  }

  // Marcar lead como convertido (borrado lógico + nota)
  $pdo->prepare("UPDATE empresas_prospectos SET deleted_at = NOW(), estado = 'interesada' WHERE id=:id")->execute([':id'=>$id]);

  $pdo->commit();

  header('Location: ../empresas/edit.php?id='.$empresaId.'&ok=1');
  exit;

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(400);
  exit('Error: ' . $e->getMessage());
}
