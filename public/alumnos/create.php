<?php
// practicalia/public/alumnos/create.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

$user = current_user();
$isAdmin = require_role('admin');
$profId = (int) ($user['id'] ?? 0);

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


$alumnoService = new \App\Services\AlumnoService($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $cursoId = (int) ($_POST['curso_id'] ?? 0);
    $alumnoId = $alumnoService->create($_POST, $cursoId, $isAdmin, $profId);

    header('Location: ./index.php');
    exit;

  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}
$pageTitle = 'Nuevo alumno';
$mainClass = 'max-w-xl';
require_once __DIR__ . '/../partials/_header.php';
?>
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

  <div class="grid grid-cols-3 gap-3">
    <div>
      <label class="block text-sm font-medium">DNI</label>
      <input name="dni" class="mt-1 w-full border rounded-xl p-2">
    </div>
    <div>
      <label class="block text-sm font-medium">Seg Social</label>
      <input name="seg_social" class="mt-1 w-full border rounded-xl p-2">
    </div>
    <div>
      <label class="block text-sm font-medium">Provincia/Localidad</label>
      <input name="provincia_localidad" class="mt-1 w-full border rounded-xl p-2">
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
          <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div>
    <label class="block text-sm font-medium">Notas</label>
    <textarea name="notas" rows="4" class="mt-1 w-full border rounded-xl p-2"
      placeholder="Observaciones, necesidades, etc."></textarea>
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
<?php require_once __DIR__ . '/../partials/_footer.php'; ?>