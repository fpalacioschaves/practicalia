<?php
// practicalia/public/empresas/create.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

$user = current_user();
$isAdmin = require_role('admin');
$profId = (int) ($user['id'] ?? 0);

$error = '';

/** Cursos disponibles */
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

/** Para re-pintar selección tras error */
$postCursos = array_map('intval', $_POST['cursos_ids'] ?? []);


$empresaService = new \App\Services\EmpresaService($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $empresaId = $empresaService->create($_POST, $postCursos, $isAdmin, $profId);

    header('Location: ./index.php');
    exit;

  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}
$pageTitle = 'Nueva empresa';
$mainClass = 'max-w-3xl';
require_once __DIR__ . '/../partials/_header.php';
?>
    <h1 class="text-xl font-semibold mb-4">Nueva empresa</h1>

    <?php if ($error): ?>
      <div class="mb-3 bg-red-50 text-red-700 p-3 rounded"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" class="bg-white p-6 rounded-2xl shadow space-y-4">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div class="md:col-span-2">
          <label class="block text-sm font-medium">Nombre de la empresa *</label>
          <input name="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required
            class="mt-1 w-full border rounded-xl p-2">
        </div>

        <div>
          <label class="block text-sm font-medium">NIF</label>
          <input name="nif" value="<?= htmlspecialchars($_POST['nif'] ?? '') ?>"
            class="mt-1 w-full border rounded-xl p-2" placeholder="Opcional">
        </div>
        <div>
          <label class="block text-sm font-medium">Email general</label>
          <input name="email" type="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            class="mt-1 w-full border rounded-xl p-2">
        </div>

        <div>
          <label class="block text-sm font-medium">Teléfono</label>
          <input name="telefono" value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>"
            class="mt-1 w-full border rounded-xl p-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Web</label>
          <input name="web" type="url" value="<?= htmlspecialchars($_POST['web'] ?? '') ?>"
            class="mt-1 w-full border rounded-xl p-2" placeholder="https://...">
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="md:col-span-2">
          <label class="block text-sm font-medium">Dirección</label>
          <input name="direccion" value="<?= htmlspecialchars($_POST['direccion'] ?? '') ?>"
            class="mt-1 w-full border rounded-xl p-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Código Postal</label>
          <input name="codigo_postal" value="<?= htmlspecialchars($_POST['codigo_postal'] ?? '') ?>"
            class="mt-1 w-full border rounded-xl p-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Ciudad</label>
          <input name="ciudad" value="<?= htmlspecialchars($_POST['ciudad'] ?? '') ?>"
            class="mt-1 w-full border rounded-xl p-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Provincia</label>
          <input name="provincia" value="<?= htmlspecialchars($_POST['provincia'] ?? '') ?>"
            class="mt-1 w-full border rounded-xl p-2">
        </div>
        <div>
          <label class="block text-sm font-medium">País</label>
          <input name="pais" value="<?= htmlspecialchars($_POST['pais'] ?? 'España') ?>"
            class="mt-1 w-full border rounded-xl p-2">
        </div>
      </div>

      <div class="pt-4 border-t">
        <div class="flex items-center justify-between mb-2">
            <div>
                <h3 class="text-sm font-bold">Vincular Grados</h3>
                <p class="text-xs text-gray-500 mb-3">Selecciona los grados que tienen convenio o interés en esta empresa.</p>
            </div>
            <div class="flex items-center gap-2 bg-blue-50 px-3 py-2 rounded-lg border border-blue-100">
                <input type="checkbox" name="es_publica" id="es_publica" value="1" <?= isset($_POST['es_publica']) ? 'checked' : '' ?>>
                <label for="es_publica" class="text-sm font-medium text-blue-900 cursor-pointer">
                    Compartir con todos los centros
                </label>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto p-1 border rounded-lg bg-gray-50">
          <?php foreach ($cursos as $c):
            $cid = (int) $c['id']; ?>
            <label class="flex items-center gap-2 p-1 hover:bg-gray-100 rounded cursor-pointer">
              <input type="checkbox" name="cursos_ids[]" value="<?= $cid ?>" <?= in_array($cid, $postCursos) ? 'checked' : '' ?>>
              <span class="text-sm"><?= htmlspecialchars($c['nombre']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="flex gap-2 pt-4">
        <button class="rounded-xl bg-black text-white px-4 py-2">Crear empresa</button>
        <a href="./index.php" class="rounded-xl px-4 py-2 border">Cancelar</a>
      </div>
    </form>
<?php require_once __DIR__ . '/../partials/_footer.php'; ?>