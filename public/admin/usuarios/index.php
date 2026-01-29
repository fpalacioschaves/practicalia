<?php
// practicalia/public/admin/usuarios/index.php
declare(strict_types=1);

require_once __DIR__ . '/../../../middleware/require_admin.php';
require_once __DIR__ . '/../../../lib/auth.php';

$search    = trim($_GET['q'] ?? '');
$hasSearch = ($search !== '');
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 10;
$offset    = ($page - 1) * $perPage;

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function urlWith(array $merge): string {
  $cur = $_GET;
  foreach ($merge as $k=>$v) { if ($v === null) unset($cur[$k]); else $cur[$k] = $v; }
  $q = http_build_query($cur);
  return $q ? ('?'.$q) : './index.php';
}

$whereSql = 'WHERE u.deleted_at IS NULL';
if ($hasSearch) {
    // OJO: placeholders únicos (q1, q2, q3) — no repetir :q
    $whereSql .= ' AND (u.nombre LIKE :q1 OR u.apellidos LIKE :q2 OR u.email LIKE :q3)';
}
$like = "%{$search}%";

/** -------- Contar total -------- */
$sqlCount = "SELECT COUNT(*) AS c FROM usuarios u $whereSql";
$stCount  = $pdo->prepare($sqlCount);
if ($hasSearch) {
    $stCount->bindValue(':q1', $like, PDO::PARAM_STR);
    $stCount->bindValue(':q2', $like, PDO::PARAM_STR);
    $stCount->bindValue(':q3', $like, PDO::PARAM_STR);
}
$stCount->execute();
$total = (int)($stCount->fetch()['c'] ?? 0);

/** Ajuste de página si nos salimos de rango */
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) {
  $page = $totalPages;
  $offset = ($page - 1) * $perPage;
}

/** -------- Listado con paginación -------- */
$sqlList = "
SELECT 
  u.id, u.nombre, u.apellidos, u.email, u.activo,
  GROUP_CONCAT(r.codigo ORDER BY r.codigo SEPARATOR ',') AS roles
FROM usuarios u
LEFT JOIN usuarios_roles ur ON ur.usuario_id = u.id
LEFT JOIN roles r ON r.id = ur.rol_id
$whereSql
GROUP BY u.id, u.nombre, u.apellidos, u.email, u.activo
ORDER BY u.apellidos ASC
LIMIT :limit OFFSET :offset
";
$st = $pdo->prepare($sqlList);

if ($hasSearch) {
    $st->bindValue(':q1', $like, PDO::PARAM_STR);
    $st->bindValue(':q2', $like, PDO::PARAM_STR);
    $st->bindValue(':q3', $like, PDO::PARAM_STR);
}
$st->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$st->bindValue(':offset', $offset,  PDO::PARAM_INT);

$st->execute();
$rows = $st->fetchAll();

/** Cálculo del rango mostrado */
$from = ($total === 0) ? 0 : ($offset + 1);
$to   = min($offset + $perPage, $total);

/** Ventana de paginación (compacta) */
$window = 2; // páginas a cada lado de la actual
$pagesToShow = [];
$pagesToShow[] = 1;
for ($i = $page - $window; $i <= $page + $window; $i++) {
  if ($i > 1 && $i < $totalPages) $pagesToShow[] = $i;
}
if ($totalPages > 1) $pagesToShow[] = $totalPages;
$pagesToShow = array_values(array_unique(array_filter($pagesToShow, fn($p)=>$p>=1 && $p<=$totalPages)));
sort($pagesToShow);

function renderPageLink(int $p, int $cur): string {
  $isCur = ($p === $cur);
  $cls = 'px-3 py-1 rounded border text-sm ' . ($isCur ? 'bg-black text-white border-black' : 'bg-white');
  return '<a class="'.$cls.'" href="'.h(urlWith(['page'=>$p])).'">'.$p.'</a>';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Usuarios — Practicalia</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">

  <?php require_once __DIR__ . '/../../partials/menu.php'; ?>

  <main class="max-w-6xl mx-auto p-4">
    <div class="mb-4 flex items-center justify-between">
      <form class="flex gap-2" method="get">
        <input name="q" value="<?= h($search) ?>"
               class="rounded-xl border border-gray-300 p-2" placeholder="Buscar (nombre, apellidos, email)">
        <button class="rounded-xl bg-black text-white px-4">Buscar</button>
        <?php if ($hasSearch): ?>
          <a href="./index.php" class="rounded-xl border px-3 py-2 text-sm">Limpiar</a>
        <?php endif; ?>
      </form>
      <a href="./create.php" class="rounded-xl bg-black text-white px-4 py-2">Nuevo usuario</a>
    </div>

    <div class="mb-2 text-sm text-gray-600">
      Mostrando <span class="font-medium"><?= (int)$from ?></span>–<span class="font-medium"><?= (int)$to ?></span> de
      <span class="font-medium"><?= (int)$total ?></span> usuario(s)
      <?= $hasSearch ? ' · búsqueda «'.h($search).'»' : '' ?>
    </div>

    <div class="bg-white rounded-2xl shadow overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-100">
          <tr>
            <th class="text-left p-3">ID</th>
            <th class="text-left p-3">Nombre</th>
            <th class="text-left p-3">Email</th>
            <th class="text-left p-3">Roles</th>
            <th class="text-left p-3">Activo</th>
            <th class="text-left p-3">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <?php $uid = (int)$r['id']; ?>
            <tr class="border-t">
              <td class="p-3"><?= $uid ?></td>
              <td class="p-3"><?= h(trim(($r['nombre'] ?? '').' '.($r['apellidos'] ?? ''))) ?></td>
              <td class="p-3"><?= h($r['email'] ?? '') ?></td>
              <td class="p-3"><?= h($r['roles'] ?? '') ?></td>
              <td class="p-3">
                <?php if ((int)($r['activo'] ?? 0) === 1): ?>
                  <span class="px-2 py-1 text-xs rounded bg-emerald-100 text-emerald-800">Sí</span>
                <?php else: ?>
                  <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-800">No</span>
                <?php endif; ?>
              </td>
              <td class="p-3">
                <div class="flex items-center gap-2">
                  <a class="underline" href="./edit.php?id=<?= $uid ?>">Editar</a>
                  <form method="post" action="./delete.php" onsubmit="return confirm('¿Eliminar este usuario?');">
                    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= $uid ?>">
                    <button class="text-red-600 underline">Eliminar</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <tr><td class="p-3" colspan="6">Sin resultados.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <nav class="mt-4 flex items-center gap-2" aria-label="Paginación">
        <!-- Primera / Anterior -->
        <a class="px-3 py-1 rounded border text-sm <?= $page===1?'pointer-events-none opacity-50':'' ?>"
           href="<?= h(urlWith(['page'=>1])) ?>">« Primera</a>
        <a class="px-3 py-1 rounded border text-sm <?= $page===1?'pointer-events-none opacity-50':'' ?>"
           href="<?= h(urlWith(['page'=>max(1,$page-1)])) ?>">‹ Anterior</a>

        <!-- Números con elipsis -->
        <?php
          $prev = 0;
          foreach ($pagesToShow as $p):
            if ($prev && $p > $prev+1) echo '<span class="px-2 text-gray-400">…</span>';
            echo renderPageLink($p, $page);
            $prev = $p;
          endforeach;
        ?>

        <!-- Siguiente / Última -->
        <a class="px-3 py-1 rounded border text-sm <?= $page===$totalPages?'pointer-events-none opacity-50':'' ?>"
           href="<?= h(urlWith(['page'=>min($totalPages,$page+1)])) ?>">Siguiente ›</a>
        <a class="px-3 py-1 rounded border text-sm <?= $page===$totalPages?'pointer-events-none opacity-50':'' ?>"
           href="<?= h(urlWith(['page'=>$totalPages])) ?>">Última »</a>
      </nav>
    <?php endif; ?>
  </main>
</body>
</html>
