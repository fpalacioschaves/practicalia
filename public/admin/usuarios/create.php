<?php
// practicalia/public/admin/usuarios/create.php
declare(strict_types=1);

require_once __DIR__ . '/../../../middleware/require_admin.php';
require_once __DIR__ . '/../../../lib/auth.php';

$error  = '';
$roles   = $pdo->query("SELECT id, codigo, nombre FROM roles ORDER BY id")->fetchAll();
$centros = $pdo->query("SELECT id, nombre, ciudad FROM centros WHERE deleted_at IS NULL ORDER BY nombre")->fetchAll();
/* cursos: SIN deleted_at */
$cursos  = $pdo->query("SELECT id, nombre FROM cursos ORDER BY nombre")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $nombre     = trim($_POST['nombre'] ?? '');
    $apellidos  = trim($_POST['apellidos'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $telefono   = trim($_POST['telefono'] ?? '');
    $activo     = (isset($_POST['activo']) && $_POST['activo'] === '1') ? 1 : 0;
    $password   = (string)($_POST['password'] ?? '');
    $password2  = (string)($_POST['password2'] ?? '');
    $rolesSel   = array_map('intval', $_POST['roles'] ?? []);
    $centroId   = (int)($_POST['centro_id'] ?? 0);
    $cursoId    = (int)($_POST['curso_id'] ?? 0); // solo si es profesor

    if ($nombre === '' || $email === '') throw new RuntimeException('Nombre y email son obligatorios.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email no válido.');
    if ($password === '' || $password2 === '') throw new RuntimeException('Debes indicar y confirmar la contraseña.');
    if ($password !== $password2) throw new RuntimeException('Las contraseñas no coinciden.');
    if (!$rolesSel) throw new RuntimeException('Selecciona al menos un rol.');

    // ¿tiene rol profesor?
    $esProfesor = false;
    foreach ($rolesSel as $rid) {
      foreach ($roles as $r) {
        if ((int)$r['id'] === $rid && $r['codigo'] === 'profesor') { $esProfesor = true; break 2; }
      }
    }

    // Centro validaciones
    if ($esProfesor && $centroId <= 0) {
      throw new RuntimeException('Para el rol profesor debes seleccionar un centro.');
    }
    if ($centroId > 0) {
      $stC = $pdo->prepare("SELECT 1 FROM centros WHERE id=:id AND deleted_at IS NULL LIMIT 1");
      $stC->execute([':id'=>$centroId]);
      if (!$stC->fetch()) throw new RuntimeException('Centro no válido.');
    }

    // Curso (solo si profesor) — SIN deleted_at
    if ($esProfesor) {
      if ($cursoId <= 0) throw new RuntimeException('Para el rol profesor debes seleccionar un curso/familia.');
      $stCur = $pdo->prepare("SELECT 1 FROM cursos WHERE id=:id LIMIT 1");
      $stCur->execute([':id'=>$cursoId]);
      if (!$stCur->fetch()) throw new RuntimeException('Curso no válido.');
    }

    // Email único
    $stChk = $pdo->prepare("SELECT 1 FROM usuarios WHERE email = :e AND deleted_at IS NULL LIMIT 1");
    $stChk->execute([':e'=>$email]);
    if ($stChk->fetch()) throw new RuntimeException('Ya existe un usuario con ese email.');

    // Crear usuario
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $st = $pdo->prepare('
      INSERT INTO usuarios (nombre, apellidos, email, password_hash, telefono, centro_id, activo)
      VALUES (:n,:a,:e,:p,:t,:c,:ac)
    ');
    $st->execute([
      ':n'=>$nombre,
      ':a'=>($apellidos!==''?$apellidos:null),
      ':e'=>$email,
      ':p'=>$hash,
      ':t'=>($telefono!==''?$telefono:null),
      ':c'=>($centroId>0?$centroId:null),
      ':ac'=>$activo
    ]);
    $uid = (int)$pdo->lastInsertId();

    // Roles
    $insR = $pdo->prepare('INSERT INTO usuarios_roles (usuario_id, rol_id) VALUES (:u,:r)');
    foreach ($rolesSel as $rid) { $insR->execute([':u'=>$uid, ':r'=>$rid]); }

    // Curso único del profesor
    if ($esProfesor) {
      $pdo->prepare("DELETE FROM cursos_profesores WHERE profesor_id = :p")->execute([':p'=>$uid]);
      $pdo->prepare("INSERT INTO cursos_profesores (curso_id, profesor_id) VALUES (:c,:p)")
          ->execute([':c'=>$cursoId, ':p'=>$uid]);
    }

    header('Location: ./index.php'); exit;

  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Nuevo usuario — Practicalia</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
<?php require_once __DIR__ . '/../../partials/menu.php'; ?>

<main class="max-w-xl mx-auto p-4">
  <h1 class="text-xl font-semibold mb-4">Nuevo usuario</h1>

  <?php if ($error): ?>
    <div class="mb-3 bg-red-50 text-red-700 p-3 rounded"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" class="bg-white p-6 rounded-2xl shadow space-y-4">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-sm font-medium">Nombre *</label>
        <input name="nombre" required class="mt-1 w-full border rounded-xl p-2" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
      </div>
      <div>
        <label class="block text-sm font-medium">Apellidos</label>
        <input name="apellidos" class="mt-1 w-full border rounded-xl p-2" value="<?= htmlspecialchars($_POST['apellidos'] ?? '') ?>">
      </div>
    </div>

    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-sm font-medium">Email *</label>
        <input name="email" type="email" required class="mt-1 w-full border rounded-xl p-2" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div>
        <label class="block text-sm font-medium">Teléfono</label>
        <input name="telefono" class="mt-1 w-full border rounded-xl p-2" value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">
      </div>
    </div>

    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-sm font-medium">Contraseña *</label>
        <input name="password" type="password" required class="mt-1 w-full border rounded-xl p-2">
      </div>
      <div>
        <label class="block text-sm font-medium">Repite contraseña *</label>
        <input name="password2" type="password" required class="mt-1 w-full border rounded-xl p-2">
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Centro (obligatorio si es profesor)</label>
      <select name="centro_id" class="mt-1 w-full border rounded-xl p-2">
        <option value="0">— Sin centro —</option>
        <?php foreach ($centros as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= ((int)($_POST['centro_id'] ?? 0)===(int)$c['id'])?'selected':'' ?>>
            <?= htmlspecialchars($c['nombre'] . (!empty($c['ciudad']) ? ' — '.$c['ciudad'] : '')) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Curso / familia (obligatorio si es profesor)</label>
      <select name="curso_id" class="mt-1 w-full border rounded-xl p-2">
        <option value="0">— Sin curso —</option>
        <?php foreach ($cursos as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= ((int)($_POST['curso_id'] ?? 0)===(int)$c['id'])?'selected':'' ?>>
            <?= htmlspecialchars($c['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Roles *</label>
      <div class="flex flex-wrap gap-3">
        <?php foreach ($roles as $r): ?>
          <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="roles[]" value="<?= (int)$r['id'] ?>" <?= in_array((int)$r['id'], array_map('intval', $_POST['roles'] ?? []), true) ? 'checked' : '' ?>>
            <span><?= htmlspecialchars($r['nombre']) ?> <span class="text-xs text-gray-500">(<?= htmlspecialchars($r['codigo']) ?>)</span></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <input type="hidden" name="activo" value="0">
      <input type="checkbox" name="activo" id="activo" value="1" checked>
      <label for="activo" class="text-sm">Usuario activo</label>
    </div>

    <div class="flex gap-2">
      <button class="rounded-xl bg-black text-white px-4 py-2">Guardar</button>
      <a href="./index.php" class="rounded-xl px-4 py-2 border">Cancelar</a>
    </div>
  </form>
</main>
</body>
</html>
