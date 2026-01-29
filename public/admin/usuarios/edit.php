<?php
// practicalia/public/admin/usuarios/edit.php
declare(strict_types=1);

require_once __DIR__ . '/../../../middleware/require_admin.php';
require_once __DIR__ . '/../../../lib/auth.php';

$idGet  = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$idPost = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$id     = $idPost ?: $idGet;
if (!$id || $id <= 0) { http_response_code(400); exit('ID inválido'); }

$error   = '';
$roles   = $pdo->query("SELECT id, codigo, nombre FROM roles ORDER BY id")->fetchAll();
$centros = $pdo->query("SELECT id, nombre, ciudad FROM centros WHERE deleted_at IS NULL ORDER BY nombre")->fetchAll();
/* cursos: SIN deleted_at */
$cursos  = $pdo->query("SELECT id, nombre FROM cursos ORDER BY nombre")->fetchAll();

// Usuario
$stU = $pdo->prepare('SELECT id, nombre, apellidos, email, telefono, activo, centro_id FROM usuarios WHERE id=:id AND deleted_at IS NULL LIMIT 1');
$stU->execute([':id'=>$id]);
$usuario = $stU->fetch();
if (!$usuario) { http_response_code(404); exit('Usuario no encontrado'); }

// Roles actuales
$stUR = $pdo->prepare('SELECT rol_id FROM usuarios_roles WHERE usuario_id = :id');
$stUR->execute([':id'=>$id]);
$rolesUser = array_map('intval', array_column($stUR->fetchAll(), 'rol_id'));

// Curso actual (si es profesor)
$stCP = $pdo->prepare("SELECT curso_id FROM cursos_profesores WHERE profesor_id = :p ORDER BY curso_id LIMIT 1");
$stCP->execute([':p'=>$id]);
$cursoActual = (int)($stCP->fetch()['curso_id'] ?? 0);

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
    $cursoId    = (int)($_POST['curso_id'] ?? 0);

    if ($nombre === '' || $email === '') throw new RuntimeException('Nombre y email son obligatorios.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email no válido.');
    if (!$rolesSel) throw new RuntimeException('Selecciona al menos un rol.');

    // ¿será profesor?
    $esProfesor = false;
    foreach ($rolesSel as $rid) {
      foreach ($roles as $r) {
        if ((int)$r['id'] === $rid && $r['codigo'] === 'profesor') { $esProfesor = true; break 2; }
      }
    }

    // Centro
    if ($esProfesor && $centroId <= 0) throw new RuntimeException('Para el rol profesor debes seleccionar un centro.');
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

    // Email único (permitiendo el actual)
    $stChk = $pdo->prepare("SELECT 1 FROM usuarios WHERE email = :e AND id <> :id AND deleted_at IS NULL LIMIT 1");
    $stChk->execute([':e'=>$email, ':id'=>$id]);
    if ($stChk->fetch()) throw new RuntimeException('Ya existe otro usuario con ese email.');

    // Update
    $st = $pdo->prepare('UPDATE usuarios SET nombre=:n, apellidos=:a, email=:e, telefono=:t, activo=:ac, centro_id=:c WHERE id=:id');
    $st->execute([
      ':n'=>$nombre,
      ':a'=>($apellidos!==''?$apellidos:null),
      ':e'=>$email,
      ':t'=>($telefono!==''?$telefono:null),
      ':ac'=>$activo,
      ':c'=>($centroId>0?$centroId:null),
      ':id'=>$id
    ]);

    // Password (opcional)
    if ($password !== '' || $password2 !== '') {
      if ($password !== $password2) throw new RuntimeException('Las contraseñas no coinciden.');
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $pdo->prepare('UPDATE usuarios SET password_hash = :p WHERE id = :id')->execute([':p'=>$hash, ':id'=>$id]);
    }

    // Roles
    $pdo->prepare('DELETE FROM usuarios_roles WHERE usuario_id = :id')->execute([':id'=>$id]);
    $insR = $pdo->prepare('INSERT INTO usuarios_roles (usuario_id, rol_id) VALUES (:u,:r)');
    foreach ($rolesSel as $rid) { $insR->execute([':u'=>$id, ':r'=>$rid]); }

    // Curso único si es profesor
    $pdo->prepare("DELETE FROM cursos_profesores WHERE profesor_id = :p")->execute([':p'=>$id]);
    if ($esProfesor) {
      $pdo->prepare("INSERT INTO cursos_profesores (curso_id, profesor_id) VALUES (:c,:p)")
          ->execute([':c'=>$cursoId, ':p'=>$id]);
      $cursoActual = $cursoId;
    } else {
      $cursoActual = 0;
    }

    header('Location: ./index.php'); exit;

  } catch (Throwable $e) {
    $error = $e->getMessage();
    // Reconstrucción de estado
    $rolesUser            = array_map('intval', $_POST['roles'] ?? $rolesUser);
    $usuario['nombre']    = $nombre   ?? $usuario['nombre'];
    $usuario['apellidos'] = $apellidos?? $usuario['apellidos'];
    $usuario['email']     = $email    ?? $usuario['email'];
    $usuario['telefono']  = $telefono ?? $usuario['telefono'];
    $usuario['activo']    = $activo   ?? $usuario['activo'];
    $usuario['centro_id'] = $centroId ?? $usuario['centro_id'];
    $cursoActual          = $cursoId  ?? $cursoActual;
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Editar usuario — Practicalia</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
<?php require_once __DIR__ . '/../../partials/menu.php'; ?>

<main class="max-w-xl mx-auto p-4">
  <h1 class="text-xl font-semibold mb-4">Editar usuario #<?= (int)$usuario['id'] ?></h1>

  <?php if ($error): ?>
    <div class="mb-3 bg-red-50 text-red-700 p-3 rounded"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" class="bg-white p-6 rounded-2xl shadow space-y-4">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= (int)$usuario['id'] ?>">

    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-sm font-medium">Nombre *</label>
        <input name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required class="mt-1 w-full border rounded-xl p-2">
      </div>
      <div>
        <label class="block text-sm font-medium">Apellidos</label>
        <input name="apellidos" value="<?= htmlspecialchars($usuario['apellidos'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2">
      </div>
    </div>

    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-sm font-medium">Email *</label>
        <input name="email" type="email" value="<?= htmlspecialchars($usuario['email']) ?>" required class="mt-1 w-full border rounded-xl p-2">
      </div>
      <div>
        <label class="block text-sm font-medium">Teléfono</label>
        <input name="telefono" value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2">
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Centro (obligatorio si es profesor)</label>
      <select name="centro_id" class="mt-1 w-full border rounded-xl p-2">
        <option value="0">— Sin centro —</option>
        <?php foreach ($centros as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= ((int)($usuario['centro_id'] ?? 0)===(int)$c['id'])?'selected':'' ?>>
            <?= htmlspecialchars($c['nombre'] . (!empty($c['ciudad']) ? ' — '.$c['ciudad'] : '')) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Curso / familia (obligatorio si es profesor)</label>
      <select name="curso_id" class="mt-1 w-full border rounded-xl p-2">
        <option value="0">— Sin curso —</option>
        <?php foreach ($cursos as $c): $cid=(int)$c['id']; ?>
          <option value="<?= $cid ?>" <?= ($cid === (int)$cursoActual) ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-sm font-medium">Nueva contraseña (opcional)</label>
        <input name="password" type="password" class="mt-1 w-full border rounded-xl p-2">
      </div>
      <div>
        <label class="block text-sm font-medium">Repite nueva contraseña</label>
        <input name="password2" type="password" class="mt-1 w-full border rounded-xl p-2">
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Roles *</label>
      <div class="flex flex-wrap gap-3">
        <?php foreach ($roles as $r): $rid=(int)$r['id']; ?>
          <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="roles[]" value="<?= $rid ?>" <?= in_array($rid, $rolesUser, true) ? 'checked' : '' ?>>
            <span><?= htmlspecialchars($r['nombre']) ?> <span class="text-xs text-gray-500">(<?= htmlspecialchars($r['codigo']) ?>)</span></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <input type="hidden" name="activo" value="0">
      <input type="checkbox" name="activo" id="activo" value="1" <?= ((int)$usuario['activo']===1)?'checked':'' ?>>
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
