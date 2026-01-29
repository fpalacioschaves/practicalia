<?php
// practicalia/public/register.php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';

$user = current_user();
if ($user) {
  header('Location: ./index.php');
}

$error = '';

/** Cargar centros para el select */
$centros = $pdo->query("SELECT id, nombre, ciudad FROM centros WHERE deleted_at IS NULL ORDER BY nombre")->fetchAll();

/** Rol profesor (lo buscamos por codigo) */
$stRol = $pdo->prepare("SELECT id FROM roles WHERE codigo = 'profesor' LIMIT 1");
$stRol->execute();
$rolProfesor = (int)($stRol->fetch()['id'] ?? 0);
if ($rolProfesor === 0) {
  $error = 'No está configurado el rol profesor en la base de datos.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $nombre    = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $password  = (string)($_POST['password'] ?? '');
    $password2 = (string)($_POST['password2'] ?? '');
    $centroId  = (int)($_POST['centro_id'] ?? 0);

    if ($nombre === '' || $email === '' || $password === '' || $password2 === '') {
      throw new RuntimeException('Nombre, email y contraseña son obligatorios.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      throw new RuntimeException('Email no válido.');
    }
    if ($password !== $password2) {
      throw new RuntimeException('Las contraseñas no coinciden.');
    }
    if ($centroId <= 0) {
      throw new RuntimeException('Debes seleccionar un centro.');
    }

    // Valida que el centro exista y no esté borrado
    $stC = $pdo->prepare("SELECT 1 FROM centros WHERE id = :id AND deleted_at IS NULL LIMIT 1");
    $stC->execute([':id'=>$centroId]);
    if (!$stC->fetch()) {
      throw new RuntimeException('El centro seleccionado no es válido.');
    }

    // Email único
    $stE = $pdo->prepare("SELECT 1 FROM usuarios WHERE email = :e AND deleted_at IS NULL LIMIT 1");
    $stE->execute([':e'=>$email]);
    if ($stE->fetch()) {
      throw new RuntimeException('Ya existe un usuario con ese email.');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Crear usuario (activo por defecto)
    $st = $pdo->prepare('
      INSERT INTO usuarios (nombre, apellidos, email, password_hash, telefono, centro_id, activo)
      VALUES (:n,:a,:e,:p,:t,:c,1)
    ');
    $st->execute([
      ':n'=>$nombre,
      ':a'=>($apellidos!=='' ? $apellidos : null),
      ':e'=>$email,
      ':p'=>$hash,
      ':t'=>($telefono!=='' ? $telefono : null),
      ':c'=>$centroId
    ]);
    $uid = (int)$pdo->lastInsertId();

    // Asignar rol profesor
    $stUR = $pdo->prepare('INSERT INTO usuarios_roles (usuario_id, rol_id) VALUES (:u,:r)');
    $stUR->execute([':u'=>$uid, ':r'=>$rolProfesor]);

    // Autologin opcional
    $_SESSION['user'] = [
      'id' => $uid,
      'nombre' => $nombre,
      'email' => $email,
    ];

    // Redirige a inicio
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
  <title>Registro — Practicalia</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen grid place-items-center">
  <main class="w-full max-w-md p-6">
    <h1 class="text-xl font-semibold mb-4">Crear cuenta (Profesor)</h1>

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

      <div>
        <label class="block text-sm font-medium">Email *</label>
        <input name="email" type="email" required class="mt-1 w-full border rounded-xl p-2" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>

      <div>
        <label class="block text-sm font-medium">Teléfono</label>
        <input name="telefono" class="mt-1 w-full border rounded-xl p-2" value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Centro *</label>
        <select name="centro_id" required class="mt-1 w-full border rounded-xl p-2">
          <option value="">— Selecciona centro —</option>
          <?php foreach ($centros as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ((int)($_POST['centro_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['nombre'] . (isset($c['ciudad']) && $c['ciudad'] ? ' — '.$c['ciudad'] : '')) ?>
            </option>
          <?php endforeach; ?>
        </select>
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

      <button class="rounded-xl bg-black text-white px-4 py-2 w-full">Crear cuenta</button>
      <div class="text-center text-sm">
        <a class="underline" href="./login.php">¿Ya tienes cuenta? Inicia sesión</a>
      </div>
    </form>
  </main>
</body>
</html>
