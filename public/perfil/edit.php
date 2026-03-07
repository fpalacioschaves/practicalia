<?php
// practicalia/public/perfil/edit.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_auth.php';
require_once __DIR__ . '/../../lib/auth.php';

$user = current_user();
$id = (int) $user['id'];

$error = '';
$success = '';

/** Cargar datos actuales (incl. centro_id) desde BD */
$stU = $pdo->prepare('SELECT id, nombre, email, centro_id FROM usuarios WHERE id = :id LIMIT 1');
$stU->execute([':id' => $id]);
$dbUser = $stU->fetch();
if (!$dbUser) {
  http_response_code(404);
  exit('Usuario no encontrado');
}

/** Listas para los selects */
$centros = $pdo->query("SELECT id, nombre, ciudad FROM centros ORDER BY nombre")->fetchAll();
/* cursos SIN deleted_at */
$cursos = $pdo->query("SELECT id, nombre FROM cursos ORDER BY nombre")->fetchAll();

/** Curso actual (si existe relación) */
$stCP = $pdo->prepare("SELECT curso_id FROM cursos_profesores WHERE profesor_id = :p ORDER BY curso_id LIMIT 1");
$stCP->execute([':p' => $id]);
$cursoActual = (int) ($stCP->fetch()['curso_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = trim($_POST['password'] ?? '');
    $centroId = (int) ($_POST['centro_id'] ?? 0);
    $cursoId = (int) ($_POST['curso_id'] ?? 0);

    if ($nombre === '' || $email === '') {
      throw new RuntimeException('El nombre y el email son obligatorios.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      throw new RuntimeException('El email no es válido.');
    }

    // Validaciones simples de centro/curso si vienen informados
    if ($centroId > 0) {
      $st = $pdo->prepare("SELECT 1 FROM centros WHERE id = :id LIMIT 1");
      $st->execute([':id' => $centroId]);
      if (!$st->fetch())
        throw new RuntimeException('Centro no válido.');
    }
    if ($cursoId > 0) {
      $st = $pdo->prepare("SELECT 1 FROM cursos WHERE id = :id LIMIT 1");
      $st->execute([':id' => $cursoId]);
      if (!$st->fetch())
        throw new RuntimeException('Curso no válido.');
    }

    // Update de usuario (nombre, email, password opcional, centro)
    if ($pass !== '') {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $st = $pdo->prepare('UPDATE usuarios SET nombre=:n, email=:e, password_hash=:p, centro_id=:c WHERE id=:id');
      $st->execute([
        ':n' => $nombre,
        ':e' => $email,
        ':p' => $hash,
        ':c' => ($centroId > 0 ? $centroId : null),
        ':id' => $id
      ]);
    } else {
      $st = $pdo->prepare('UPDATE usuarios SET nombre=:n, email=:e, centro_id=:c WHERE id=:id');
      $st->execute([
        ':n' => $nombre,
        ':e' => $email,
        ':c' => ($centroId > 0 ? $centroId : null),
        ':id' => $id
      ]);
    }

    // Guardar curso único del profesor (si se elige; si 0, borrar relación)
    $pdo->prepare("DELETE FROM cursos_profesores WHERE profesor_id = :p")->execute([':p' => $id]);
    if ($cursoId > 0) {
      $pdo->prepare("INSERT INTO cursos_profesores (curso_id, profesor_id) VALUES (:c,:p)")
        ->execute([':c' => $cursoId, ':p' => $id]);
      $cursoActual = $cursoId;
    } else {
      $cursoActual = 0;
    }

    // Refrescar sesión visible
    $_SESSION['user']['nombre'] = $nombre;
    $_SESSION['user']['email'] = $email;

    // Refrescar variables para el form
    $dbUser['nombre'] = $nombre;
    $dbUser['email'] = $email;
    $dbUser['centro_id'] = ($centroId > 0 ? $centroId : null);

    $success = 'Perfil actualizado correctamente.';

  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}
$pageTitle = 'Mi perfil';
$mainClass = 'max-w-3xl mx-auto p-6';
require_once __DIR__ . '/../partials/_header.php';
?>
<h1 class="text-xl font-semibold mb-4">Mi perfil</h1>

<?php if ($error): ?>
  <div class="mb-3 bg-red-50 text-red-700 p-3 rounded"><?= htmlspecialchars($error) ?></div>
<?php elseif ($success): ?>
  <div class="mb-3 bg-green-50 text-green-700 p-3 rounded"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<form method="post" class="bg-white p-6 rounded-2xl shadow space-y-4">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="md:col-span-2">
      <label class="block text-sm font-medium">Nombre completo</label>
      <input name="nombre" value="<?= htmlspecialchars($dbUser['nombre'] ?? '') ?>" required class="form-control">
    </div>

    <div>
      <label class="block text-sm font-medium">Email</label>
      <input name="email" type="email" value="<?= htmlspecialchars($dbUser['email'] ?? '') ?>" required
        class="form-control">
    </div>

    <div>
      <label class="block text-sm font-medium">Contraseña <span class="text-gray-400 font-normal">(dejar en blanco para
          no cambiar)</span></label>
      <input name="password" type="password" class="form-control">
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium mb-1">Centro</label>
      <select name="centro_id" class="form-control">
        <option value="0">— Sin centro —</option>
        <?php foreach ($centros as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= ((int) ($dbUser['centro_id'] ?? 0) === (int) $c['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['nombre'] . (!empty($c['ciudad']) ? ' — ' . $c['ciudad'] : '')) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Curso / familia profesional</label>
      <select name="curso_id" class="form-control">
        <option value="0">— Sin curso —</option>
        <?php foreach ($cursos as $c):
          $cid = (int) $c['id']; ?>
          <option value="<?= $cid ?>" <?= ($cid === (int) $cursoActual) ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <p class="text-xs text-gray-500 mt-1">Esto influye en los alumnos y empresas que verás asociados.</p>
    </div>
  </div>

  <div class="flex gap-2">
    <button class="btn-primary">Guardar</button>
    <a href="<?= $base ?>/index.php" class="btn-secondary">Cancelar</a>
  </div>
</form>
</main>
<?php require_once __DIR__ . '/../partials/_footer.php'; ?>