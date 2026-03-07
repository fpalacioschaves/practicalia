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
$mainClass = 'max-w-4xl';
require_once __DIR__ . '/../partials/_header.php';
?>
    <h1 class="text-xl font-semibold mb-4">Nueva empresa</h1>

    <?php if ($error): ?>
      <div class="mb-3 bg-red-50 text-red-700 p-3 rounded"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" class="bg-white p-6 rounded-2xl shadow space-y-4">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

      <!-- SECCIÓN 1: DATOS GENERALES -->
      <div class="bg-gray-50 p-4 rounded-xl border space-y-4">
        <h3 class="font-bold text-gray-800 border-b pb-2">1. Datos Generales</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div class="md:col-span-3">
            <label class="block text-sm font-medium">Nombre de la empresa *</label>
            <input name="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required
              class="form-control">
          </div>

          <div>
            <label class="block text-sm font-medium">CIF / NIF Empresa</label>
            <input name="cif" value="<?= htmlspecialchars($_POST['cif'] ?? '') ?>"
              class="form-control" placeholder="CIF de la empresa">
          </div>
          <div>
            <label class="block text-sm font-medium">Actividad / Sector</label>
            <input name="sector" value="<?= htmlspecialchars($_POST['sector'] ?? '') ?>"
              class="form-control">
          </div>
          <div>
            <label class="block text-sm font-medium">Email general</label>
            <input name="email" type="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
              class="form-control">
          </div>

          <div>
            <label class="block text-sm font-medium">Teléfono</label>
            <input name="telefono" value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>"
              class="form-control">
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm font-medium">Web</label>
            <input name="web" type="url" value="<?= htmlspecialchars($_POST['web'] ?? '') ?>"
              class="form-control" placeholder="https://...">
          </div>

          <div class="md:col-span-3">
            <label class="block text-sm font-medium">Dirección</label>
            <input name="direccion" value="<?= htmlspecialchars($_POST['direccion'] ?? '') ?>"
              class="form-control">
          </div>

          <div>
            <label class="block text-sm font-medium">Código Postal</label>
            <input name="codigo_postal" value="<?= htmlspecialchars($_POST['codigo_postal'] ?? '') ?>"
              class="form-control">
          </div>
          <div>
            <label class="block text-sm font-medium">Ciudad</label>
            <input name="ciudad" value="<?= htmlspecialchars($_POST['ciudad'] ?? '') ?>"
              class="form-control">
          </div>
          <div>
            <label class="block text-sm font-medium">Provincia</label>
            <input name="provincia" value="<?= htmlspecialchars($_POST['provincia'] ?? '') ?>"
              class="form-control">
          </div>

          <div class="md:col-span-3">
            <label class="block text-sm font-medium">Horario de realización de las prácticas/dualización</label>
            <input name="horario_practicas" value="<?= htmlspecialchars($_POST['horario_practicas'] ?? '') ?>"
              class="form-control" placeholder="Ej: Lunes a Viernes de 08:00 a 14:00">
          </div>
        </div>
      </div>

      <!-- SECCIÓN 2: TUTOR DE PRÁCTICAS -->
      <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100 space-y-4">
        <h3 class="font-bold text-blue-900 border-b border-blue-100 pb-2">2. Datos de Tutor de prácticas en la empresa</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-blue-800">Nombre del Tutor</label>
            <input name="responsable_nombre" value="<?= htmlspecialchars($_POST['responsable_nombre'] ?? '') ?>"
              class="form-control border-blue-200 bg-white">
          </div>
          <div>
            <label class="block text-sm font-medium text-blue-800">NIF del Tutor</label>
            <input name="tutor_nif" value="<?= htmlspecialchars($_POST['tutor_nif'] ?? '') ?>"
              class="form-control border-blue-200 bg-white">
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-blue-800">Email del Tutor</label>
            <input name="responsable_email" type="email" value="<?= htmlspecialchars($_POST['responsable_email'] ?? '') ?>"
              class="form-control border-blue-200 bg-white">
          </div>
          <div>
            <label class="block text-sm font-medium text-blue-800">Teléfono</label>
            <input name="responsable_telefono" value="<?= htmlspecialchars($_POST['responsable_telefono'] ?? '') ?>"
              class="form-control border-blue-200 bg-white">
          </div>
          <div class="md:col-span-3">
            <label class="block text-sm font-medium text-blue-800">Departamento donde realizan las prácticas</label>
            <input name="tutor_departamento" value="<?= htmlspecialchars($_POST['tutor_departamento'] ?? '') ?>"
              class="form-control border-blue-200 bg-white" placeholder="Ej: IT, Recursos Humanos, Contabilidad...">
          </div>
        </div>
      </div>

      <!-- SECCIÓN 3: REPRESENTANTE LEGAL -->
      <div class="bg-gray-50/50 p-4 rounded-xl border space-y-4">
        <h3 class="font-bold text-gray-800 border-b pb-2">3. Datos del representante legal de la empresa</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div class="md:col-span-3">
            <label class="block text-sm font-medium">Nombre Completo</label>
            <input name="rep_legal_nombre" value="<?= htmlspecialchars($_POST['rep_legal_nombre'] ?? '') ?>"
              class="form-control">
          </div>
          <div>
            <label class="block text-sm font-medium">NIF</label>
            <input name="rep_legal_nif" value="<?= htmlspecialchars($_POST['rep_legal_nif'] ?? '') ?>"
              class="form-control">
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm font-medium">Email</label>
            <input name="rep_legal_email" type="email" value="<?= htmlspecialchars($_POST['rep_legal_email'] ?? '') ?>"
              class="form-control">
          </div>
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
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 max-h-48 overflow-y-auto p-1 border rounded-lg bg-gray-50">
          <?php foreach ($cursos as $c):
            $cid = (int) $c['id']; ?>
            <label class="flex items-center gap-2 p-1 hover:bg-gray-100 rounded cursor-pointer">
              <input type="checkbox" name="cursos_ids[]" value="<?= $cid ?>" <?= in_array($cid, $postCursos) ? 'checked' : '' ?>>
              <span class="text-sm truncate"><?= htmlspecialchars($c['nombre']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="flex gap-2">
        <button class="btn-add">Crear empresa</button>
        <a href="./index.php" class="btn-secondary">Cancelar</a>
      </div>
    </form>
<?php require_once __DIR__ . '/../partials/_footer.php'; ?>