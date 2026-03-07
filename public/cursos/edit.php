<?php
// practicalia/public/cursos/edit.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_admin.php';
require_once __DIR__ . '/../../lib/auth.php';

$idGet = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$idPost = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$id = $idPost ?: $idGet;

if (!$id || $id <= 0) {
  http_response_code(400);
  exit('ID inválido');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $nombre = trim($_POST['nombre'] ?? '');
    $codigo = trim($_POST['codigo'] ?? '');
    $anyo = trim($_POST['anyo'] ?? '');
    $activo = (isset($_POST['activo']) && $_POST['activo'] === '1') ? 1 : 0;
    $profes = (array) ($_POST['profesores'] ?? []);

    if ($nombre === '')
      throw new RuntimeException('El nombre es obligatorio.');
    $anyoVal = ($anyo !== '') ? (int) $anyo : null;

    // Update curso
    $st = $pdo->prepare('UPDATE cursos SET nombre=:n, codigo=:c, anyo=:a, activo=:ac WHERE id=:id');
    $st->execute([':n' => $nombre, ':c' => ($codigo !== '' ? $codigo : null), ':a' => $anyoVal, ':ac' => $activo, ':id' => $id]);

    // Reset profes asignados
    $pdo->prepare('DELETE FROM cursos_profesores WHERE curso_id = :id')->execute([':id' => $id]);
    if ($profes) {
      $ins = $pdo->prepare('INSERT IGNORE INTO cursos_profesores (curso_id, profesor_id) VALUES (:c,:p)');
      foreach ($profes as $pid) {
        $pid = (int) $pid;
        if ($pid > 0)
          $ins->execute([':c' => $id, ':p' => $pid]);
      }
    }

    header('Location: ./index.php');
    exit;

  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}

// Cargar curso
$stC = $pdo->prepare('SELECT id, nombre, codigo, anyo, COALESCE(activo,1) AS activo FROM cursos WHERE id=:id LIMIT 1');
$stC->execute([':id' => $id]);
$curso = $stC->fetch();
if (!$curso) {
  http_response_code(404);
  exit('Curso no encontrado');
}
$cursoActivo = (int) $curso['activo'];

// Cargar profesores del sistema
$sqlProfs = "
SELECT u.id, u.nombre, u.apellidos
FROM usuarios u
JOIN usuarios_roles ur ON ur.usuario_id = u.id
JOIN roles r ON r.id = ur.rol_id AND r.codigo = 'profesor'
WHERE u.deleted_at IS NULL AND u.activo = 1
ORDER BY u.apellidos, u.nombre
";
$profesores = $pdo->query($sqlProfs)->fetchAll();

// Profesores ya asignados al curso
$stPC = $pdo->prepare('SELECT profesor_id FROM cursos_profesores WHERE curso_id = :id');
$stPC->execute([':id' => $id]);
$profesAsignados = array_map(fn($r) => (int) $r['profesor_id'], $stPC->fetchAll());
$pageTitle = 'Editar curso';
$mainClass = 'max-w-xl';
require_once __DIR__ . '/../partials/_header.php';
?>
<h1 class="text-xl font-semibold mb-4">Editar curso #<?= (int) $curso['id'] ?></h1>

<?php if ($error): ?>
  <div class="mb-3 bg-red-50 text-red-700 p-3 rounded"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" class="bg-white p-6 rounded-2xl shadow space-y-4">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
  <input type="hidden" name="id" value="<?= (int) $curso['id'] ?>">

  <div>
    <label class="block text-sm font-medium">Nombre *</label>
    <input name="nombre" value="<?= htmlspecialchars($curso['nombre']) ?>" required
      class="mt-1 w-full border rounded-xl p-2">
  </div>

  <div class="grid grid-cols-2 gap-3">
    <div>
      <label class="block text-sm font-medium">Código</label>
      <input name="codigo" value="<?= htmlspecialchars($curso['codigo'] ?? '') ?>"
        class="mt-1 w-full border rounded-xl p-2">
    </div>
    <div>
      <label class="block text-sm font-medium">Año</label>
      <input name="anyo" type="number" value="<?= htmlspecialchars((string) ($curso['anyo'] ?? '')) ?>"
        class="mt-1 w-full border rounded-xl p-2">
    </div>
  </div>

  <div>
    <label class="block text-sm font-medium">Profesores</label>
    <div class="mt-1 grid grid-cols-1 sm:grid-cols-2 gap-2">
      <?php foreach ($profesores as $p):
        $pid = (int) $p['id']; ?>
        <label class="inline-flex items-center gap-2">
          <input type="checkbox" name="profesores[]" value="<?= $pid ?>" <?= in_array($pid, $profesAsignados, true) ? 'checked' : '' ?>>
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
    <input type="checkbox" name="activo" id="activo" value="1" <?= ($cursoActivo === 1) ? 'checked' : '' ?>>
    <label for="activo" class="text-sm">Curso activo</label>
  </div>

  <div class="flex gap-2">
    <button class="btn-save">Guardar cambios</button>
    <a href="./index.php" class="btn-secondary">Volver</a>
  </div>
</form>
</main>
<?php require_once __DIR__ . '/../partials/_footer.php'; ?>