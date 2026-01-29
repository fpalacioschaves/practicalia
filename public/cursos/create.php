<?php
// practicalia/public/cursos/create.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_admin.php';
require_once __DIR__ . '/../../lib/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $nombre = trim($_POST['nombre'] ?? '');
    $codigo = trim($_POST['codigo'] ?? '');
    $anyo = trim($_POST['anyo'] ?? '');
    $activo = (isset($_POST['activo']) && $_POST['activo'] === '1') ? 1 : 0;
    $profes = (array) ($_POST['profesores'] ?? []); // ids de usuarios

    if ($nombre === '')
      throw new RuntimeException('El nombre es obligatorio.');
    $anyoVal = ($anyo !== '') ? (int) $anyo : null;

    // Insert curso
    $st = $pdo->prepare('INSERT INTO cursos (nombre, codigo, anyo, activo) VALUES (:n,:c,:a,:ac)');
    $st->execute([':n' => $nombre, ':c' => ($codigo !== '' ? $codigo : null), ':a' => $anyoVal, ':ac' => $activo]);
    $cursoId = (int) $pdo->lastInsertId();

    // Profes asignados
    if ($profes) {
      $ins = $pdo->prepare('INSERT IGNORE INTO cursos_profesores (curso_id, profesor_id) VALUES (:c,:p)');
      foreach ($profes as $pid) {
        $pid = (int) $pid;
        if ($pid > 0)
          $ins->execute([':c' => $cursoId, ':p' => $pid]);
      }
    }

    header('Location: ./index.php');
    exit;

  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}

// Cargar profesores (usuarios con rol 'profesor')
$sqlProfs = "
SELECT u.id, u.nombre, u.apellidos
FROM usuarios u
JOIN usuarios_roles ur ON ur.usuario_id = u.id
JOIN roles r ON r.id = ur.rol_id AND r.codigo = 'profesor'
WHERE u.deleted_at IS NULL AND u.activo = 1
ORDER BY u.apellidos, u.nombre
";
$profesores = $pdo->query($sqlProfs)->fetchAll();
$pageTitle = 'Nuevo curso';
$mainClass = 'max-w-xl';
require_once __DIR__ . '/../partials/_header.php';
?>
<h1 class="text-xl font-semibold mb-4">Nuevo curso</h1>

<?php if ($error): ?>
  <div class="mb-3 bg-red-50 text-red-700 p-3 rounded"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" class="bg-white p-6 rounded-2xl shadow space-y-4">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

  <div>
    <label class="block text-sm font-medium">Nombre *</label>
    <input name="nombre" required class="mt-1 w-full border rounded-xl p-2">
  </div>

  <div class="grid grid-cols-2 gap-3">
    <div>
      <label class="block text-sm font-medium">Código</label>
      <input name="codigo" class="mt-1 w-full border rounded-xl p-2" placeholder="Opcional (único)">
    </div>
    <div>
      <label class="block text-sm font-medium">Año</label>
      <input name="anyo" type="number" class="mt-1 w-full border rounded-xl p-2" placeholder="2025">
    </div>
  </div>

  <div>
    <label class="block text-sm font-medium">Profesores (opcional)</label>
    <div class="mt-1 grid grid-cols-1 sm:grid-cols-2 gap-2">
      <?php foreach ($profesores as $p): ?>
        <label class="inline-flex items-center gap-2">
          <input type="checkbox" name="profesores[]" value="<?= (int) $p['id'] ?>">
          <span><?= htmlspecialchars($p['apellidos'] . ', ' . $p['nombre']) ?></span>
        </label>
      <?php endforeach; ?>
      <?php if (!$profesores): ?>
        <span class="text-xs text-gray-500">No hay profesores activos.</span>
      <?php endif; ?>
    </div>
  </div>

  <div class="flex items-center gap-2">
    <input type="hidden" name="activo" value="0">
    <input type="checkbox" name="activo" id="activo" value="1" checked>
    <label for="activo" class="text-sm">Curso activo</label>
  </div>

  <div class="flex gap-2">
    <button class="rounded-xl bg-black text-white px-4 py-2">Guardar</button>
    <a href="./index.php" class="rounded-xl px-4 py-2 border">Cancelar</a>
  </div>
</form>
</main>
<?php require_once __DIR__ . '/../partials/_footer.php'; ?>