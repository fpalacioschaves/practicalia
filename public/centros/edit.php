<?php
// practicalia/public/centros/edit.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

$idGet = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$idPost = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$id = $idPost ?: $idGet;
if (!$id || $id <= 0) {
  http_response_code(400);
  exit('ID inválido');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['accion'] ?? '') === 'guardar_centro')) {
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
      UPDATE centros SET
        nombre=:nombre, telefono=:telefono, email=:email, web=:web,
        direccion=:direccion, ciudad=:ciudad, provincia=:provincia, cp=:cp
      WHERE id=:id
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
      ':id' => $id
    ]);

    header('Location: ./edit.php?id=' . $id);
    exit;

  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}

/** Cargar centro */
$stE = $pdo->prepare('SELECT * FROM centros WHERE id = :id AND deleted_at IS NULL LIMIT 1');
$stE->execute([':id' => $id]);
$centro = $stE->fetch();
if (!$centro) {
  http_response_code(404);
  exit('Centro no encontrado');
}

// Preparar mapa (igual estilo que empresas)
$addrParts = array_filter([
  $centro['direccion'] ?? '',
  $centro['cp'] ?? '',
  $centro['ciudad'] ?? '',
  $centro['provincia'] ?? '',
]);
$direccionCompleta = implode(', ', $addrParts);
$mapSrc = $direccionCompleta !== ''
  ? 'https://www.google.com/maps?q=' . urlencode($direccionCompleta) . '&output=embed'
  : null;
$mapLink = $direccionCompleta !== ''
  ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($direccionCompleta)
  : null;
$pageTitle = 'Editar centro';
require_once __DIR__ . '/../partials/_header.php';
?>
<h1 class="text-xl font-semibold mb-4">
  Editar centro #<?= (int) $centro['id'] ?> — <?= htmlspecialchars($centro['nombre']) ?>
</h1>

<?php if ($error): ?>
  <div class="mb-3 bg-red-50 text-red-700 p-3 rounded"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- FORM DATOS CENTRO -->
<form method="post" class="bg-white p-6 rounded-2xl shadow space-y-4 mb-8">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
  <input type="hidden" name="id" value="<?= (int) $centro['id'] ?>">
  <input type="hidden" name="accion" value="guardar_centro">

  <div>
    <label class="block text-sm font-medium">Nombre *</label>
    <input name="nombre" value="<?= htmlspecialchars($centro['nombre']) ?>" required
      class="mt-1 w-full border rounded-xl p-2">
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
    <div>
      <label class="block text-sm font-medium">Teléfono</label>
      <input name="telefono" value="<?= htmlspecialchars($centro['telefono'] ?? '') ?>"
        class="mt-1 w-full border rounded-xl p-2">
    </div>
    <div>
      <label class="block text-sm font-medium">Email</label>
      <input name="email" type="email" value="<?= htmlspecialchars($centro['email'] ?? '') ?>"
        class="mt-1 w-full border rounded-xl p-2">
    </div>
  </div>

  <div>
    <label class="block text-sm font-medium">Web</label>
    <input name="web" value="<?= htmlspecialchars($centro['web'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2"
      placeholder="https://...">
  </div>

  <div>
    <label class="block text-sm font-medium">Dirección</label>
    <input name="direccion" value="<?= htmlspecialchars($centro['direccion'] ?? '') ?>"
      class="mt-1 w-full border rounded-xl p-2">
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
    <div>
      <label class="block text-sm font-medium">Ciudad</label>
      <input name="ciudad" value="<?= htmlspecialchars($centro['ciudad'] ?? '') ?>"
        class="mt-1 w-full border rounded-xl p-2">
    </div>
    <div>
      <label class="block text-sm font-medium">Provincia</label>
      <input name="provincia" value="<?= htmlspecialchars($centro['provincia'] ?? '') ?>"
        class="mt-1 w-full border rounded-xl p-2">
    </div>
    <div>
      <label class="block text-sm font-medium">CP</label>
      <input name="cp" value="<?= htmlspecialchars($centro['cp'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2">
    </div>
  </div>

  <div class="flex gap-2">
    <button class="rounded-xl bg-black text-white px-4 py-2">Guardar</button>
    <a href="./index.php" class="rounded-xl px-4 py-2 border">Volver</a>
  </div>
</form>

<!-- MAPA -->
<section class="bg-white p-6 rounded-2xl shadow">
  <h2 class="font-semibold mb-3">Mapa</h2>
  <?php if ($mapSrc): ?>
    <div class="aspect-video w-full overflow-hidden rounded-2xl border">
      <iframe src="<?= htmlspecialchars($mapSrc) ?>" class="w-full h-full" style="border:0;" loading="lazy"
        referrerpolicy="no-referrer-when-downgrade" allowfullscreen>
      </iframe>
    </div>
    <div class="mt-2 text-sm">
      <span class="text-gray-600"><?= htmlspecialchars($direccionCompleta) ?></span>
      ·
      <a href="<?= htmlspecialchars($mapLink) ?>" target="_blank" class="underline">Abrir en Google Maps</a>
    </div>
  <?php else: ?>
    <p class="text-sm text-gray-600">Añade dirección, CP, ciudad y provincia para ver el mapa aquí.</p>
  <?php endif; ?>
</section>
</main>
<?php require_once __DIR__ . '/../partials/_footer.php'; ?>