<?php
// practicalia/public/empresas/index.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

function h(?string $s): string
{
  return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
function urlWith(array $merge): string
{
  $cur = $_GET;
  foreach ($merge as $k => $v) {
    if ($v === null)
      unset($cur[$k]);
    else
      $cur[$k] = $v;
  }
  $q = http_build_query($cur);
  return $q ? ('?' . $q) : './index.php';
}

$user = current_user();
$isAdmin = require_role('admin');
$profId = (int) ($user['id'] ?? 0);


$empresaService = new \App\Services\EmpresaService($pdo);

/** Parámetros de filtrado/paginación */
$search = trim($_GET['q'] ?? '');
$hasSearch = ($search !== '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

/** ------- TOTAL ------- */
$total = $empresaService->getCount($search, $isAdmin, $profId);

/** Ajuste por si la página pedida se sale de rango */
$totalPages = max(1, (int) ceil($total / $perPage));
if ($page > $totalPages)
  $page = $totalPages;
$offset = ($page - 1) * $perPage;

/** ------- LISTA ------- */
$rows = $empresaService->getList($search, $isAdmin, $profId, $perPage, $offset);

/** Chips con cursos por empresa */
$cursoNombres = $empresaService->getCursoNombresByEmpresaIds(array_column($rows, 'id'));

/** Rango mostrado */
$from = ($total === 0) ? 0 : ($offset + 1);
$to = min($offset + $perPage, $total);

/** Ventana de paginación compacta */
$window = 2;
$pagesToShow = [];
$pagesToShow[] = 1;
for ($i = $page - $window; $i <= $page + $window; $i++) {
  if ($i > 1 && $i < $totalPages)
    $pagesToShow[] = $i;
}
if ($totalPages > 1)
  $pagesToShow[] = $totalPages;
$pagesToShow = array_values(array_unique(array_filter($pagesToShow, fn($p) => $p >= 1 && $p <= $totalPages)));
sort($pagesToShow);

function renderPageLink(int $p, int $cur): string
{
  $isCur = ($p === $cur);
  $cls = 'px-3 py-1 rounded border text-sm ' . ($isCur ? 'bg-black text-white border-black' : 'bg-white');
  return '<a class="' . $cls . '" href="' . h(urlWith(['page' => $p])) . '">' . $p . '</a>';
}
$pageTitle = 'Empresas';
require_once __DIR__ . '/../partials/_header.php';
?>
<div class="mb-4 flex items-center justify-between">
  <form class="flex gap-2" method="get">
    <input name="q" value="<?= h($search) ?>" class="form-control" placeholder="Buscar (nombre, ciudad, provincia, CP)">
    <button class="btn-filter">Buscar</button>
    <?php if ($hasSearch): ?>
      <a href="./index.php" class="btn-secondary px-3">Limpiar</a>
    <?php endif; ?>
  </form>
  <a href="./create.php" class="btn-add">Nueva empresa</a>
  <button id="startBatchEmailBtn" class="rounded-xl border border-black text-black px-4 py-2 ml-2 hover:bg-gray-100">
    ✉ Enviar Email Masivo
  </button>
</div>

<!-- Modal Email -->
<div id="emailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
  <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-2xl max-h-screen overflow-y-auto">
    <h2 class="text-xl font-bold mb-4">Enviar Email a <span id="selectedCount">0</span> Empresas</h2>

    <div class="mb-4">
      <label class="block text-sm font-medium mb-1">Cargar Plantilla</label>
      <select id="templateSelect" class="w-full border rounded p-2">
        <option value="">-- Seleccionar --</option>
      </select>
    </div>

    <div class="mb-4">
      <label class="block text-sm font-medium mb-1">Asunto</label>
      <input type="text" id="emailSubject" class="w-full border rounded p-2" placeholder="Asunto del correo">
    </div>

    <div class="mb-4">
      <label class="block text-sm font-medium mb-1">Cuerpo del Mensaje</label>
      <p class="text-xs text-gray-500 mb-2">Variables: {empresa}, {responsable}, {ciudad}</p>
      <textarea id="emailBody" rows="6" class="w-full border rounded p-2"></textarea>
    </div>

    <div class="mb-4 border-t pt-4">
      <label class="block text-sm font-medium mb-1">Guardar como nueva plantilla</label>
      <div class="flex gap-2">
        <input type="text" id="newTemplateName" placeholder="Nombre de la plantilla" class="flex-1 border rounded p-2">
        <button id="saveTemplateBtn" class="bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded">Guardar</button>
      </div>
    </div>

    <div class="flex justify-end gap-2 mt-6">
      <button id="closeModalBtn" class="px-4 py-2 border rounded hover:bg-gray-50">Cancelar</button>
      <button id="sendBatchBtn" class="px-4 py-2 bg-black text-white rounded hover:bg-gray-800">Enviar Correos</button>
    </div>
  </div>
</div>
<script src="../js/email_sender.js"></script>

<div class="mb-2 text-sm text-gray-600">
  Mostrando <span class="font-medium"><?= (int) $from ?></span>–<span class="font-medium"><?= (int) $to ?></span> de
  <span class="font-medium"><?= (int) $total ?></span> empresa(s)
  <?= $hasSearch ? ' · búsqueda «' . h($search) . '»' : '' ?>
</div>

<div class="bg-white rounded-2xl shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-gray-100">
      <th class="text-left p-3"><input type="checkbox" id="selectAll"></th>
      <th class="text-left p-3">ID</th>
      <th class="text-left p-3">Nombre</th>
      <th class="text-left p-3">Ciudad</th>
      <th class="text-left p-3">Provincia</th>
      <th class="text-left p-3">CP</th>
      <th class="text-left p-3">Email</th>
      <th class="text-left p-3">Cursos</th>
      <th class="text-left p-3">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r):
        $id = (int) $r['id']; ?>
        <tr class="border-t hover:bg-gray-50">
          <td class="p-3"><input type="checkbox" class="company-checkbox" value="<?= $id ?>"></td>
          <td class="p-3"><?= $id ?></td>
          <td class="p-3">
            <a href="./edit.php?id=<?= $id ?>" class="text-gray-900 hover:underline">
              <?= h($r['nombre']) ?>
            </a>
            <?php if ((int) ($r['es_publica'] ?? 0) === 1): ?>
              <span class="inline-block ml-2 px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                Compartida
              </span>
            <?php endif; ?>
            <?php if (!empty($r['web'])): ?>
              <div class="text-xs text-gray-500"><?= h($r['web']) ?></div>
            <?php endif; ?>
          </td>
          <td class="p-3"><?= h($r['ciudad'] ?? '') ?></td>
          <td class="p-3"><?= h($r['provincia'] ?? '') ?></td>
          <td class="p-3"><?= h($r['codigo_postal'] ?? '') ?></td>
          <td class="p-3"><?= h($r['email'] ?? '') ?></td>
          <td class="p-3">
            <?php foreach (($cursoNombres[$id] ?? []) as $cn): ?>
              <span class="inline-block text-xs bg-gray-100 border px-2 py-0.5 rounded-full mr-1 mb-1"><?= h($cn) ?></span>
            <?php endforeach; ?>
          </td>
          <td class="p-3">
            <div class="flex items-center gap-2">
              <a class="btn-edit" href="./edit.php?id=<?= $id ?>">Editar</a>
              <form method="post" action="./delete.php" onsubmit="return confirm('¿Eliminar esta empresa?');">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= $id ?>">
                <button class="btn-delete">Eliminar</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr>
          <td class="p-3" colspan="8">Sin resultados.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php if ($totalPages > 1): ?>
  <nav class="mt-4 flex items-center gap-2" aria-label="Paginación">
    <!-- Primera / Anterior -->
    <a class="px-3 py-1 rounded border text-sm <?= $page === 1 ? 'pointer-events-none opacity-50' : '' ?>"
      href="<?= h(urlWith(['page' => 1])) ?>">« Primera</a>
    <a class="px-3 py-1 rounded border text-sm <?= $page === 1 ? 'pointer-events-none opacity-50' : '' ?>"
      href="<?= h(urlWith(['page' => max(1, $page - 1)])) ?>">‹ Anterior</a>

    <!-- Números con elipsis -->
    <?php
    $prev = 0;
    foreach ($pagesToShow as $p):
      if ($prev && $p > $prev + 1)
        echo '<span class="px-2 text-gray-400">…</span>';
      echo renderPageLink($p, $page);
      $prev = $p;
    endforeach;
    ?>

    <!-- Siguiente / Última -->
    <a class="px-3 py-1 rounded border text-sm <?= $page === $totalPages ? 'pointer-events-none opacity-50' : '' ?>"
      href="<?= h(urlWith(['page' => min($totalPages, $page + 1)])) ?>">Siguiente ›</a>
    <a class="px-3 py-1 rounded border text-sm <?= $page === $totalPages ? 'pointer-events-none opacity-50' : '' ?>"
      href="<?= h(urlWith(['page' => $totalPages])) ?>">Última »</a>
  </nav>
<?php endif; ?>
<?php require_once __DIR__ . '/../partials/_footer.php'; ?>