<?php
// practicalia/public/centros/index.php
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

$search = trim($_GET['q'] ?? '');
$hasSearch = ($search !== '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$whereSql = 'WHERE deleted_at IS NULL';
if ($hasSearch) {
  $whereSql .= ' AND (nombre LIKE :q OR ciudad LIKE :q OR provincia LIKE :q OR cp LIKE :q)';
}
$like = "%{$search}%";

/** total */
$sqlCount = "SELECT COUNT(*) AS c FROM centros $whereSql";
$stC = $pdo->prepare($sqlCount);
if ($hasSearch) {
  $stC->bindValue(':q', $like, PDO::PARAM_STR);
}
$stC->execute();
$total = (int) ($stC->fetch()['c'] ?? 0);

/** Ajuste de página si nos salimos de rango */
$totalPages = max(1, (int) ceil($total / $perPage));
if ($page > $totalPages) {
  $page = $totalPages;
  $offset = ($page - 1) * $perPage;
}

/** listado */
$sql = "
  SELECT id, nombre, telefono, email, web, direccion, ciudad, provincia, cp
  FROM centros
  $whereSql
  ORDER BY nombre ASC
  LIMIT :limit OFFSET :offset
";
$st = $pdo->prepare($sql);
if ($hasSearch) {
  $st->bindValue(':q', $like, PDO::PARAM_STR);
}
$st->bindValue(':limit', $perPage, PDO::PARAM_INT);
$st->bindValue(':offset', $offset, PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll();

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
$pageTitle = 'Centros';
require_once __DIR__ . '/../partials/_header.php';
?>
<div class="mb-4 flex items-center justify-between">
  <form class="flex gap-2" method="get">
    <input name="q" value="<?= h($search) ?>" class="rounded-xl border border-gray-300 p-2"
      placeholder="Buscar (nombre, ciudad, provincia, CP)">
    <button class="rounded-xl bg-black text-white px-4">Buscar</button>
    <?php if ($hasSearch): ?>
      <a href="./index.php" class="btn-secondary px-3">Limpiar</a>
    <?php endif; ?>
  </form>
  <a href="./create.php" class="btn-add">Nuevo centro</a>
</div>

<div class="mb-2 text-sm text-gray-600">
  Mostrando <span class="font-medium"><?= (int) $from ?></span>–<span class="font-medium"><?= (int) $to ?></span> de
  <span class="font-medium"><?= (int) $total ?></span> centro(s)
  <?= $hasSearch ? ' · búsqueda «' . h($search) . '»' : '' ?>
</div>

<div class="bg-white rounded-2xl shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-gray-100">
      <tr>
        <th class="text-left p-3">ID</th>
        <th class="text-left p-3">Nombre</th>
        <th class="text-left p-3">Ciudad</th>
        <th class="text-left p-3">Provincia</th>
        <th class="text-left p-3">CP</th>
        <th class="text-left p-3">Teléfono</th>
        <th class="text-left p-3">Email</th>
        <th class="text-left p-3">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r):
        $id = (int) $r['id']; ?>
        <tr class="border-t hover:bg-gray-50">
          <td class="p-3"><?= $id ?></td>
          <td class="p-3">
            <a href="./edit.php?id=<?= $id ?>" class="text-gray-900 hover:underline">
              <?= h($r['nombre']) ?>
            </a>
            <?php if (!empty($r['web'])): ?>
              <div class="text-xs text-gray-500"><?= h($r['web']) ?></div>
            <?php endif; ?>
          </td>
          <td class="p-3"><?= h($r['ciudad'] ?? '') ?></td>
          <td class="p-3"><?= h($r['provincia'] ?? '') ?></td>
          <td class="p-3"><?= h($r['cp'] ?? '') ?></td>
          <td class="p-3"><?= h($r['telefono'] ?? '') ?></td>
          <td class="p-3"><?= h($r['email'] ?? '') ?></td>
          <td class="p-3">
            <div class="flex items-center gap-2">
              <a class="btn-edit" href="./edit.php?id=<?= $id ?>">Editar</a>
              <form method="post" action="./delete.php" onsubmit="return confirm('¿Eliminar este centro?');">
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
</main>
<?php require_once __DIR__ . '/../partials/_footer.php'; ?>