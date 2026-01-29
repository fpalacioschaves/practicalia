<?php
// practicalia/public/alumnos/create.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

$user = current_user();
$isAdmin = require_role('admin');
$profId  = (int)($user['id'] ?? 0);

$error = '';

// Cursos disponibles (según rol)
if ($isAdmin) {
  $cursos = $pdo->query("SELECT id, nombre FROM cursos ORDER BY nombre")->fetchAll();
} else {
  $st = $pdo->prepare("
    SELECT c.id, c.nombre
    FROM cursos c
    JOIN cursos_profesores cp ON cp.curso_id = c.id AND cp.profesor_id = :pid
    ORDER BY c.nombre
  ");
  $st->execute([':pid' => $profId]);
  $cursos = $st->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $nombre    = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $activo    = (isset($_POST['activo']) && $_POST['activo'] === '1') ? 1 : 0;
    $cursoId   = (int)($_POST['curso_id'] ?? 0); // curso único (opcional)
    $fnac      = trim($_POST['fecha_nacimiento'] ?? '');
    $notas     = trim($_POST['notas'] ?? '');

    if ($nombre === '' || $apellidos === '') throw new RuntimeException('Nombre y apellidos son obligatorios.');
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email no válido.');
    if ($fnac !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fnac)) throw new RuntimeException('Fecha de nacimiento inválida (YYYY-MM-DD).');

    // Email único (la tabla tiene UNIQUE en email)
    if ($email !== '') {
      $stChk = $pdo->prepare("SELECT 1 FROM alumnos WHERE email = :e AND deleted_at IS NULL LIMIT 1");
      $stChk->execute([':e' => $email]);
      if ($stChk->fetch()) throw new RuntimeException('Ya existe un alumno con ese email.');
    }

    // Validar permiso sobre el curso (si no es admin)
    if ($cursoId > 0 && !$isAdmin) {
      $st2 = $pdo->prepare("SELECT 1 FROM cursos_profesores WHERE curso_id = :c AND profesor_id = :p LIMIT 1");
      $st2->execute([':c'=>$cursoId, ':p'=>$profId]);
      if (!$st2->fetch()) throw new RuntimeException('No puedes asignar ese curso.');
    }

    // Insert alumno (incluye fecha_nacimiento y notas)
    $stIns = $pdo->prepare("
      INSERT INTO alumnos (nombre, apellidos, email, telefono, activo, fecha_nacimiento, notas)
      VALUES (:n,:a,:e,:t,:ac,:fn,:no)
    ");
    $stIns->execute([
      ':n'=>$nombre,
      ':a'=>$apellidos,
      ':e'=>($email!=='' ? $email : null),
      ':t'=>($telefono!=='' ? $telefono : null),
      ':ac'=>$activo,
      ':fn'=>($fnac!=='' ? $fnac : null),
      ':no'=>($notas!=='' ? $notas : null),
    ]);
    $alumnoId = (int)$pdo->lastInsertId();

    // Vincular curso único (si se eligió); alumnos_cursos requiere fecha_inicio NOT NULL
    // Grabamos fecha_inicio = CURDATE() y estado por defecto "matriculado"
    if ($cursoId > 0) {
      $stAC = $pdo->prepare("
        INSERT INTO alumnos_cursos (alumno_id, curso_id, fecha_inicio, estado)
        VALUES (:al,:cu, CURDATE(), 'matriculado')
      ");
      $stAC->execute([':al'=>$alumnoId, ':cu'=>$cursoId]);
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
  <title>Nuevo alumno — Practicalia</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
  <?php require_once __DIR__ . '/../partials/menu.php'; ?>

  <main class="max-w-xl mx-auto p-4">
    <h1 class="text-xl font-semibold mb-4">Nuevo alumno</h1>

    <?php if ($error): ?>
      <div class="mb-3 bg-red-50 text-red-700 p-3 rounded"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" class="bg-white p-6 rounded-2xl shadow space-y-4">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium">Nombre *</label>
          <input name="nombre" required class="mt-1 w-full border rounded-xl p-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Apellidos *</label>
          <input name="apellidos" required class="mt-1 w-full border rounded-xl p-2">
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium">Email</label>
          <input name="email" type="email" class="mt-1 w-full border rounded-xl p-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Teléfono</label>
          <input name="telefono" class="mt-1 w-full border rounded-xl p-2">
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium">Fecha de nacimiento</label>
          <input name="fecha_nacimiento" type="date" class="mt-1 w-full border rounded-xl p-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Curso</label>
          <select name="curso_id" class="mt-1 w-full border rounded-xl p-2">
            <option value="0">— Sin curso —</option>
            <?php foreach ($cursos as $c): ?>
              <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium">Notas</label>
        <textarea name="notas" rows="4" class="mt-1 w-full border rounded-xl p-2" placeholder="Observaciones, necesidades, etc."></textarea>
      </div>

      <div class="flex items-center gap-2">
        <input type="hidden" name="activo" value="0">
        <input type="checkbox" name="activo" id="activo" value="1" checked>
        <label for="activo" class="text-sm">Alumno activo</label>
      </div>

      <div class="flex gap-2">
        <button class="rounded-xl bg-black text-white px-4 py-2">Guardar</button>
        <a href="./index.php" class="rounded-xl px-4 py-2 border">Cancelar</a>
      </div>
    </form>
  </main>
</body>
</html>
