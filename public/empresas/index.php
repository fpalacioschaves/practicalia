<?php
// practicalia/public/empresas/index.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function urlWith(array $merge): string {
  $cur = $_GET;
  foreach ($merge as $k=>$v) { if ($v === null) unset($cur[$k]); else $cur[$k] = $v; }
  $q = http_build_query($cur);
  return $q ? ('?'.$q) : './index.php';
}

$user = current_user();
$isAdmin = require_role('admin');
$profId  = (int)($user['id'] ?? 0);

/** Parámetros de filtrado/paginación */
$search    = trim($_GET['q'] ?? '');
$hasSearch = ($search !== '');
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 10;
$offset    = ($page - 1) * $perPage;

/** WHERE + params (evitar HY093 usando placeholders distintos) */
$params = [];
$where  = "e.deleted_at IS NULL";
if ($hasSearch) {
  $where .= " AND (
      e.nombre         LIKE :q1 OR
      e.ciudad         LIKE :q2 OR
      e.provincia      LIKE :q3 OR
      e.codigo_postal  LIKE :q4
    )";
  $like = "%{$search}%";
  $params[':q1'] = $like;
  $params[':q2'] = $like;
  $params[':q3'] = $like;
  $params[':q4'] = $like;
}

/** ------- TOTAL ------- */
if ($isAdmin) {
  $sqlCount = "SELECT COUNT(*) c FROM empresas e WHERE $where";
  $stC = $pdo->prepare($sqlCount);
  foreach ($params as $k=>$v) $stC->bindValue($k, $v, PDO::PARAM_STR);
  $stC->execute();
  $total = (int)$stC->fetch()['c'];
} else {
  $sqlCount = "
    SELECT COUNT(DISTINCT e.id) c
    FROM empresas e
    JOIN empresa_cursos ec ON ec.empresa_id = e.id
    JOIN cursos_profesores cp ON cp.curso_id = ec.curso_id AND cp.profesor_id = :pid
    WHERE $where
  ";
  $stC = $pdo->prepare($sqlCount);
  foreach ($params as $k=>$v) $stC->bindValue($k, $v, PDO::PARAM_STR);
  $stC->bindValue(':pid', $profId, PDO::PARAM_INT);
  $stC->execute();
  $total = (int)$stC->fetch()['c'];
}

/** Ajuste por si la página pedida se sale de rango */
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) {
  $page = $totalPages;
  $offset = ($page - 1) * $perPage;
}

/** ------- LISTA ------- */
if ($isAdmin) {
  $sql = "
    SELECT e.id, e.nombre, e.telefono, e.email, e.web, e.ciudad, e.provincia, e.codigo_postal
    FROM empresas e
    WHERE $where
    ORDER BY e.nombre ASC
    LIMIT :limit OFFSET :offset
  ";
  $st = $pdo->prepare($sql);
  foreach ($params as $k=>$v) $st->bindValue($k, $v, PDO::PARAM_STR);
} else {
  $sql = "
    SELECT DISTINCT e.id, e.nombre, e.telefono, e.email, e.web, e.ciudad, e.provincia, e.codigo_postal
    FROM empresas e
    JOIN empresa_cursos ec ON ec.empresa_id = e.id
    JOIN cursos_profesores cp ON cp.curso_id = ec.curso_id AND cp.profesor_id = :pid
    WHERE $where
    ORDER BY e.id DESC
    LIMIT :limit OFFSET :offset
  ";
  $st = $pdo->prepare($sql);
  foreach ($params as $k=>$v) $st->bindValue($k, $v, PDO::PARAM_STR);
  $st->bindValue(':pid', $profId, PDO::PARAM_INT);
}
$st->bindValue(':limit', $perPage, PDO::PARAM_INT);
$st->bindValue(':offset', $offset, PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

/** Chips con cursos por empresa */
$cursoNombres = [];
if ($rows) {
  $ids = implode(',', array_map('intval', array_column($rows, 'id')));
  $q = $pdo->query("
    SELECT ec.empresa_id, c.nombre
    FROM empresa_cursos ec
    JOIN cursos c ON c.id = ec.curso_id
    WHERE ec.empresa_id IN ($ids)
    ORDER BY c.nombre
  ");
  foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $cursoNombres[(int)$r['empresa_id']][] = $r['nombre'];
  }
}

/** Rango mostrado */
$from = ($total === 0) ? 0 : ($offset + 1);
$to   = min($offset + $perPage, $total);

/** Ventana de paginación compacta */
$window = 2;
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
  <title>Empresas — Practicalia</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
  <?php require_once __DIR__ . '/../partials/menu.php'; ?>

  <main class="max-w-6xl mx-auto p-4">
    <div class="mb-4 flex items-center justify-between">
      <form class="flex gap-2" method="get">
        <input name="q" value="<?= h($search) ?>" class="rounded-xl border p-2" placeholder="Buscar (nombre, ciudad, provincia, CP)">
        <button class="rounded-xl bg-black text-white px-4">Buscar</button>
        <?php if ($hasSearch): ?>
          <a href="./index.php" class="rounded-xl border px-3 py-2 text-sm">Limpiar</a>
        <?php endif; ?>
      </form>
      <a href="./create.php" class="rounded-xl bg-black text-white px-4 py-2">Nueva empresa</a>
    </div>

    <div class="mb-2 text-sm text-gray-600">
      Mostrando <span class="font-medium"><?= (int)$from ?></span>–<span class="font-medium"><?= (int)$to ?></span> de
      <span class="font-medium"><?= (int)$total ?></span> empresa(s)
      <?= $hasSearch ? ' · búsqueda «'.h($search).'»' : '' ?>
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
            <th class="text-left p-3">Email</th>
            <th class="text-left p-3">Cursos</th>
            <th class="text-left p-3">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): $id=(int)$r['id']; ?>
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
              <td class="p-3"><?= h($r['codigo_postal'] ?? '') ?></td>
              <td class="p-3"><?= h($r['email'] ?? '') ?></td>
              <td class="p-3">
                <?php foreach (($cursoNombres[$id] ?? []) as $cn): ?>
                  <span class="inline-block text-xs bg-gray-100 border px-2 py-0.5 rounded-full mr-1 mb-1"><?= h($cn) ?></span>
                <?php endforeach; ?>
              </td>
              <td class="p-3 flex items-center gap-2">
                <a class="underline" href="./edit.php?id=<?= $id ?>">Editar</a>
                <form method="post" action="./delete.php" onsubmit="return confirm('¿Eliminar esta empresa?');">
                  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                  <input type="hidden" name="id" value="<?= $id ?>">
                  <button class="text-red-600 underline">Eliminar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <tr><td class="p-3" colspan="8">Sin resultados.</td></tr>
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
