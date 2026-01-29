<?php
// practicalia/public/empresas/create.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

$user    = current_user();
$isAdmin = require_role('admin');
$profId  = (int)($user['id'] ?? 0);

/** @var PDO $pdo */
$error = '';

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** Cursos para el selector (según rol) */
if ($isAdmin) {
  $cursos = $pdo->query("SELECT id, nombre FROM cursos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
} else {
  $st = $pdo->prepare("
    SELECT c.id, c.nombre
    FROM cursos c
    JOIN cursos_profesores cp ON cp.curso_id = c.id AND cp.profesor_id = :pid
    ORDER BY c.nombre
  ");
  $st->execute([':pid'=>$profId]);
  $cursos = $st->fetchAll(PDO::FETCH_ASSOC);
}

/** Para re-pintar selección tras error */
$postCursos = array_map('intval', $_POST['cursos_ids'] ?? []);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $nombre    = trim($_POST['nombre'] ?? '');
    $cif       = trim($_POST['cif'] ?? '');
    $nif       = trim($_POST['nif'] ?? '');
    $sector    = trim($_POST['sector'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $web       = trim($_POST['web'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $ciudad    = trim($_POST['ciudad'] ?? '');
    $provincia = trim($_POST['provincia'] ?? '');
    $cp        = trim($_POST['codigo_postal'] ?? '');
    $activo    = (isset($_POST['activo']) && $_POST['activo'] === '1') ? 1 : 0;

    // Normaliza cursos (al menos 1)
    $cursosIds = array_values(array_unique(array_filter(array_map('intval', $_POST['cursos_ids'] ?? []))));
    if ($nombre === '') throw new RuntimeException('El nombre es obligatorio.');
    if (count($cursosIds) === 0) throw new RuntimeException('Selecciona al menos un curso.');
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email no válido.');
    if ($cp !== '' && !preg_match('/^[0-9A-Za-z -]{3,10}$/', $cp)) throw new RuntimeException('Código postal no válido.');

    if ($nif !== '') {
      $stN = $pdo->prepare('SELECT 1 FROM empresas WHERE nif = :nif AND deleted_at IS NULL LIMIT 1');
      $stN->execute([':nif'=>$nif]);
      if ($stN->fetch()) throw new RuntimeException('Ya existe una empresa con ese NIF.');
    }

    // Valida que existen
    $inMarks = implode(',', array_fill(0, count($cursosIds), '?'));
    $stChkC  = $pdo->prepare("SELECT id FROM cursos WHERE id IN ($inMarks)");
    $stChkC->execute($cursosIds);
    $existentes = array_map('intval', $stChkC->fetchAll(PDO::FETCH_COLUMN));
    $cursosIds  = array_values(array_intersect($cursosIds, $existentes));
    if (count($cursosIds) === 0) throw new RuntimeException('Los cursos seleccionados no existen.');

    // Si es profesor: todos deben ser suyos
    if (!$isAdmin) {
      $stChkP = $pdo->prepare("
        SELECT curso_id
        FROM cursos_profesores
        WHERE profesor_id = :p AND curso_id IN ($inMarks)
      ");
      $stChkP->bindValue(':p', $profId, PDO::PARAM_INT);
      foreach ($cursosIds as $i => $val) $stChkP->bindValue($i + 1, $val, PDO::PARAM_INT);
      $stChkP->execute();
      $permisos = array_map('intval', $stChkP->fetchAll(PDO::FETCH_COLUMN));
      sort($permisos); sort($cursosIds);
      if ($permisos !== $cursosIds) throw new RuntimeException('No puedes seleccionar alguno de los cursos marcados.');
    }

    // Insert + relaciones
    $pdo->beginTransaction();
    try {
      $stIns = $pdo->prepare('
        INSERT INTO empresas
          (nombre, cif, nif, email, telefono, web, direccion, ciudad, provincia, codigo_postal, sector, activo)
        VALUES
          (:nombre, :cif, :nif, :email, :telefono, :web, :direccion, :ciudad, :provincia, :cp, :sector, :activo)
      ');
      $stIns->execute([
        ':nombre'=>$nombre,
        ':cif'=>($cif!==''?$cif:null),
        ':nif'=>($nif!==''?$nif:null),
        ':email'=>($email!==''?$email:null),
        ':telefono'=>($telefono!==''?$telefono:null),
        ':web'=>($web!==''?$web:null),
        ':direccion'=>($direccion!==''?$direccion:null),
        ':ciudad'=>($ciudad!==''?$ciudad:null),
        ':provincia'=>($provincia!==''?$provincia:null),
        ':cp'=>($cp!==''?$cp:null),
        ':sector'=>($sector!==''?$sector:null),
        ':activo'=>$activo
      ]);
      $empresaId = (int)$pdo->lastInsertId();

      $pdo->prepare("DELETE FROM empresa_cursos WHERE empresa_id = :e")->execute([':e'=>$empresaId]);
      $stRel = $pdo->prepare("INSERT INTO empresa_cursos (empresa_id, curso_id) VALUES (:e, :c)");
      foreach ($cursosIds as $cid) {
        $stRel->execute([':e'=>$empresaId, ':c'=>$cid]);
      }
      $pdo->commit();
    } catch (Throwable $e) {
      $pdo->rollBack();
      throw $e;
    }

    header('Location: ./index.php');
    exit;

  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Nueva empresa — Practicalia</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
  <?php require_once __DIR__ . '/../partials/menu.php'; ?>
  <main class="max-w-3xl mx-auto p-4">
    <h1 class="text-xl font-semibold mb-4">Nueva empresa</h1>

    <?php if ($error): ?>
      <div class="mb-3 bg-red-50 text-red-700 p-3 rounded"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" class="bg-white p-6 rounded-2xl shadow space-y-4">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium">Nombre *</label>
          <input name="nombre" required class="mt-1 w-full border rounded-xl p-2" value="<?= h($_POST['nombre'] ?? '') ?>">
        </div>
        <div>
          <label class="block text-sm font-medium">Sector</label>
          <input name="sector" class="mt-1 w-full border rounded-xl p-2" value="<?= h($_POST['sector'] ?? '') ?>">
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
          <label class="block text-sm font-medium">CIF</label>
          <input name="cif" class="mt-1 w-full border rounded-xl p-2" value="<?= h($_POST['cif'] ?? '') ?>">
        </div>
        <div>
          <label class="block text-sm font-medium">NIF</label>
          <input name="nif" class="mt-1 w-full border rounded-xl p-2" placeholder="Único si se informa" value="<?= h($_POST['nif'] ?? '') ?>">
        </div>
        <div>
          <label class="block text-sm font-medium">Teléfono</label>
          <input name="telefono" class="mt-1 w-full border rounded-xl p-2" value="<?= h($_POST['telefono'] ?? '') ?>">
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
          <label class="block text-sm font-medium">Email</label>
          <input name="email" type="email" class="mt-1 w-full border rounded-xl p-2" value="<?= h($_POST['email'] ?? '') ?>">
        </div>
        <div>
          <label class="block text-sm font-medium">Web</label>
          <input name="web" class="mt-1 w-full border rounded-xl p-2" placeholder="https://..." value="<?= h($_POST['web'] ?? '') ?>">
        </div>
        <div>
          <label class="block text-sm font-medium">Código postal</label>
          <input name="codigo_postal" class="mt-1 w-full border rounded-xl p-2" value="<?= h($_POST['codigo_postal'] ?? '') ?>">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium">Dirección</label>
        <input name="direccion" class="mt-1 w-full border rounded-xl p-2" value="<?= h($_POST['direccion'] ?? '') ?>">
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium">Ciudad</label>
          <input name="ciudad" class="mt-1 w-full border rounded-xl p-2" value="<?= h($_POST['ciudad'] ?? '') ?>">
        </div>
        <div>
          <label class="block text-sm font-medium">Provincia</label>
          <input name="provincia" class="mt-1 w-full border rounded-xl p-2" value="<?= h($_POST['provincia'] ?? '') ?>">
        </div>
      </div>

      <!-- ✅ Checkboxes para seleccionar varios cursos -->
      <div>
        <div class="flex items-center justify-between mb-1">
          <label class="block text-sm font-medium">Cursos/Grados asociados *</label>
          <div class="text-xs">
            <button type="button" class="underline mr-2" onclick="marcarTodos(true)">Marcar todos</button>
            <button type="button" class="underline" onclick="marcarTodos(false)">Desmarcar</button>
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-64 overflow-auto p-2 border rounded-xl">
          <?php foreach ($cursos as $c): $cid=(int)$c['id']; ?>
            <label class="inline-flex items-center gap-2">
              <input type="checkbox" name="cursos_ids[]" value="<?= $cid ?>" <?= in_array($cid, $postCursos, true) ? 'checked' : '' ?>>
              <span class="text-sm"><?= h($c['nombre']) ?></span>
            </label>
          <?php endforeach; ?>
          <?php if (!$cursos): ?>
            <div class="text-sm text-gray-500">No hay cursos disponibles</div>
          <?php endif; ?>
        </div>
        <p class="text-xs text-gray-500 mt-1">Puedes seleccionar uno o varios.</p>
      </div>

      <div class="flex items-center gap-2">
        <input type="hidden" name="activo" value="0">
        <input type="checkbox" name="activo" id="activo" value="1" <?= (($_POST['activo'] ?? '1') === '1') ? 'checked' : '' ?>>
        <label for="activo" class="text-sm">Empresa activa</label>
      </div>

      <div class="flex gap-2">
        <button class="rounded-xl bg-black text-white px-4 py-2">Guardar</button>
        <a href="./index.php" class="rounded-xl px-4 py-2 border">Cancelar</a>
      </div>
    </form>
  </main>

  <script>
  function marcarTodos(valor) {
    document.querySelectorAll('input[name="cursos_ids[]"]').forEach(cb => { cb.checked = valor; });
  }
  </script>
</body>
</html>

