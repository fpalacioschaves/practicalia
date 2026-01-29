<?php
// practicalia/public/centros/create.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $web = trim($_POST['web'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $provincia = trim($_POST['provincia'] ?? '');
    $cp = trim($_POST['cp'] ?? '');

    if ($nombre === '')
      throw new RuntimeException('El nombre es obligatorio.');
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))
      throw new RuntimeException('Email no válido.');
    if ($cp !== '' && !preg_match('/^[0-9A-Za-z -]{3,10}$/', $cp))
      throw new RuntimeException('Código postal no válido.');

    $st = $pdo->prepare('
      INSERT INTO centros
        (nombre, telefono, email, web, direccion, ciudad, provincia, cp)
      VALUES
        (:nombre, :telefono, :email, :web, :direccion, :ciudad, :provincia, :cp)
    ');
    $st->execute([
      ':nombre' => $nombre,
      ':telefono' => ($telefono !== '' ? $telefono : null),
      ':email' => ($email !== '' ? $email : null),
      ':web' => ($web !== '' ? $web : null),
      ':direccion' => ($direccion !== '' ? $direccion : null),
      ':ciudad' => ($ciudad !== '' ? $ciudad : null),
      ':provincia' => ($provincia !== '' ? $provincia : null),
      ':cp' => ($cp !== '' ? $cp : null),
    ]);

    header('Location: ./index.php');
    exit;

  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}
$pageTitle = 'Nuevo centro';
require_once __DIR__ . '/../partials/_header.php';
?>
<h1 class="text-xl font-semibold mb-4">Nuevo centro</h1>

<?php if ($error): ?>
  <div class="mb-3 bg-red-50 text-red-700 p-3 rounded"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" class="bg-white p-6 rounded-2xl shadow space-y-4">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

  <div>
    <label class="block text-sm font-medium">Nombre *</label>
    <input name="nombre" required class="mt-1 w-full border rounded-xl p-2">
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
    <div>
      <label class="block text-sm font-medium">Teléfono</label>
      <input name="telefono" class="mt-1 w-full border rounded-xl p-2">
    </div>
    <div>
      <label class="block text-sm font-medium">Email</label>
      <input name="email" type="email" class="mt-1 w-full border rounded-xl p-2">
    </div>
  </div>

  <div>
    <label class="block text-sm font-medium">Web</label>
    <input name="web" class="mt-1 w-full border rounded-xl p-2" placeholder="https://...">
  </div>

  <div>
    <label class="block text-sm font-medium">Dirección</label>
    <input name="direccion" class="mt-1 w-full border rounded-xl p-2">
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
    <div>
      <label class="block text-sm font-medium">Ciudad</label>
      <input name="ciudad" class="mt-1 w-full border rounded-xl p-2">
    </div>
    <div>
      <label class="block text-sm font-medium">Provincia</label>
      <input name="provincia" class="mt-1 w-full border rounded-xl p-2">
    </div>
    <div>
      <label class="block text-sm font-medium">CP</label>
      <input name="cp" class="mt-1 w-full border rounded-xl p-2">
    </div>
  </div>

  <div class="flex gap-2">
    <button class="rounded-xl bg-black text-white px-4 py-2">Guardar</button>
    <a href="./index.php" class="rounded-xl px-4 py-2 border">Cancelar</a>
  </div>
</form>
</main>
<?php require_once __DIR__ . '/../partials/_footer.php'; ?>