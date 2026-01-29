<?php
// practicalia/public/ras/index.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

$user    = current_user();
$isAdmin = require_role('admin');
$profId  = (int)($user['id'] ?? 0);

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function urlWith(array $merge): string {
  $cur = $_GET;
  foreach ($merge as $k=>$v) { if ($v === null) unset($cur[$k]); else $cur[$k] = $v; }
  $q = http_build_query($cur);
  return $q ? ('?'.$q) : './index.php';
}

// ---------------- Parámetros ----------------
$q           = trim((string)($_GET['q'] ?? ''));
$cursoIdF    = filter_input(INPUT_GET, 'curso_id', FILTER_VALIDATE_INT) ?: null;
$asigIdF     = filter_input(INPUT_GET, 'asignatura_id', FILTER_VALIDATE_INT) ?: null;
$page        = max(1, (int)($_GET['page'] ?? 1));
$perPage     = 10;
$offset      = ($page - 1) * $perPage;

// ---------------- Cursos visibles según rol ----------------
if ($isAdmin) {
  $cursos = $pdo->query("SELECT id, nombre FROM cursos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
} else {
  $st = $pdo->prepare("
    SELECT c.id, c.nombre
    FROM cursos c
    JOIN cursos_profesores cp ON cp.curso_id = c.id AND cp.profesor_id = :pid
    ORDER BY c.nombre
  ");
  $st->execute([':pid' => $profId]);
  $cursos = $st->fetchAll(PDO::FETCH_ASSOC);
}

// ---------------- Asignaturas para el combo (dependen del curso y del rol) ----------------
$asignaturasFiltro = [];
if ($cursoIdF) {
  if ($isAdmin) {
    $st = $pdo->prepare("
      SELECT a.id, a.nombre
      FROM asignaturas a
      WHERE a.deleted_at IS NULL AND a.curso_id = :c
      ORDER BY a.nombre
    ");
    $st->execute([':c'=>$cursoIdF]);
  } else {
    $st = $pdo->prepare("
      SELECT a.id, a.nombre
      FROM asignaturas a
      JOIN cursos_profesores cp ON cp.curso_id = a.curso_id AND cp.profesor_id = :pid
      WHERE a.deleted_at IS NULL AND a.curso_id = :c
      ORDER BY a.nombre
    ");
    $st->execute([':pid'=>$profId, ':c'=>$cursoIdF]);
  }
  $asignaturasFiltro = $st->fetchAll(PDO::FETCH_ASSOC);
}

// ---------------- WHERE y JOINs ----------------
$params = [];
$joins  = "JOIN asignaturas a ON a.id = ra.asignatura_id
           JOIN cursos c ON c.id = a.curso_id";
$where  = ["ra.deleted_at IS NULL", "a.deleted_at IS NULL"]; // RA y asignatura no borradas

// Restringir por rol
if (!$isAdmin) {
  $joins .= " JOIN cursos_profesores cp ON cp.curso_id = c.id AND cp.profesor_id = :pid";
  $params[':pid'] = $profId;
}

// Filtro por curso
if ($cursoIdF) {
  $where[] = "c.id = :curso_id";
  $params[':curso_id'] = $cursoIdF;
}

// Filtro por asignatura
if ($asigIdF) {
  $where[] = "a.id = :asig_id";
  $params[':asig_id'] = $asigIdF;
}

// Búsqueda (codigo/titulo/descripcion)  ✅ placeholders distintos
if ($q !== '') {
  $where[] = "(ra.codigo LIKE :q1 OR ra.titulo LIKE :q2 OR ra.descripcion LIKE :q3)";
  $like = "%{$q}%";
  $params[':q1'] = $like;
  $params[':q2'] = $like;
  $params[':q3'] = $like;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ---------------- Total (con DISTINCT) ----------------
$sqlCount = "SELECT COUNT(DISTINCT ra.id)
             FROM asignatura_ras ra
             $joins
             $whereSql";
$st = $pdo->prepare($sqlCount);
$st->execute($params);
$total = (int)$st->fetchColumn();

// ---------------- Paginación: cálculo y ajuste ----------------
$perPage     = max(1, (int)$perPage);
$totalPages  = max(1, (int)ceil($total / $perPage));
$page        = min(max(1, (int)$page), $totalPages);
$offset      = ($page - 1) * $perPage;

// ---------------- Filas ----------------
$sqlRows = "SELECT ra.id, ra.codigo, ra.titulo, ra.orden, ra.activo,
                   a.id AS asig_id, a.nombre AS asig_nombre,
                   c.id AS curso_id, c.nombre AS curso_nombre
            FROM asignatura_ras ra
            $joins
            $whereSql
            GROUP BY ra.id, ra.codigo, ra.titulo, ra.orden, ra.activo, a.id, a.nombre, c.id, c.nombre
            ORDER BY c.nombre ASC, a.nombre ASC,
                     (ra.orden IS NULL) ASC, ra.orden ASC, ra.codigo ASC
            LIMIT :limit OFFSET :offset";
$st = $pdo->prepare($sqlRows);
foreach ($params as $k=>$v) {
  $st->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$st->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$st->bindValue(':offset', $offset,  PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

// ---------------- Rango mostrado ----------------
$from = ($total === 0) ? 0 : ($offset + 1);
$to   = min($offset + $perPage, $total);

// ---------------- Ventana de paginación (±2) ----------------
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
  <title>Resultados de Aprendizaje — Practicalia</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
  <?php require_once __DIR__ . '/../partials/menu.php'; ?>

  <main class="max-w-6xl mx-auto p-4">
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-xl font-semibold">Resultados de Aprendizaje (RA)</h1>
      <a href="./create.php<?= $asigIdF ? '?asignatura_id='.(int)$asigIdF : '' ?>" class="px-4 py-2 rounded-xl bg-black text-white">+ Nuevo RA</a>
    </div>

    <!-- Filtros -->
    <form method="get" class="bg-white p-4 rounded-2xl shadow mb-4 grid grid-cols-1 md:grid-cols-4 gap-3">
      <div>
        <label class="block text-sm font-medium">Buscar</label>
        <input name="q" value="<?= h($q) ?>" class="mt-1 w-full border rounded-xl p-2" placeholder="Código, título o texto">
      </div>
      <div>
        <label class="block text-sm font-medium">Grado</label>
        <select name="curso_id" class="mt-1 w-full border rounded-xl p-2" onchange="this.form.submit()">
          <option value="">— Todos —</option>
          <?php foreach ($cursos as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ($cursoIdF && (int)$c['id']===$cursoIdF ? 'selected' : '') ?>><?= h($c['nombre']) ?></option>
          <?php endforeach; ?>
          <?php if (!$cursos): ?>
            <option value="" disabled>No hay grados disponibles</option>
          <?php endif; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium">Asignatura</label>
        <select name="asignatura_id" class="mt-1 w-full border rounded-xl p-2">
          <option value="">— Todas —</option>
          <?php foreach ($asignaturasFiltro as $a): ?>
            <option value="<?= (int)$a['id'] ?>" <?= ($asigIdF && (int)$a['id']===$asigIdF ? 'selected' : '') ?>><?= h($a['nombre']) ?></option>
          <?php endforeach; ?>
          <?php if ($cursoIdF && !$asignaturasFiltro): ?>
            <option value="" disabled>No hay asignaturas en este grado</option>
          <?php endif; ?>
        </select>
      </div>
      <div class="flex items-end gap-2">
        <button class="px-4 py-2 rounded-xl border">Filtrar</button>
        <a href="./index.php" class="px-4 py-2 rounded-xl border">Limpiar</a>
      </div>
    </form>

    <div class="mb-2 text-sm text-gray-600">
      Mostrando <span class="font-medium"><?= (int)$from ?></span>–<span class="font-medium"><?= (int)$to ?></span> de
      <span class="font-medium"><?= (int)$total ?></span> RA(s)
      <?php if ($q !== ''): ?> · «<?= h($q) ?>»<?php endif; ?>
      <?php if ($cursoIdF): ?> · grado filtrado<?php endif; ?>
      <?php if ($asigIdF): ?> · asignatura filtrada<?php endif; ?>
    </div>

    <!-- Tabla -->
    <div class="overflow-x-auto bg-white rounded-2xl shadow border">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="text-left p-3">Código</th>
            <th class="text-left p-3">Título</th>
            <th class="text-left p-3">Asignatura</th>
            <th class="text-left p-3">Grado</th>
            <th class="text-left p-3">Orden</th>
            <th class="text-left p-3">Activo</th>
            <th class="text-left p-3">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="7" class="p-4 text-gray-500">No hay RAs.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr class="border-t">
              <td class="p-3 font-medium"><?= h($r['codigo']) ?></td>
              <td class="p-3"><?= h($r['titulo']) ?></td>
              <td class="p-3"><?= h($r['asig_nombre']) ?></td>
              <td class="p-3"><?= h($r['curso_nombre']) ?></td>
              <td class="p-3"><?= h($r['orden'] !== null ? (string)$r['orden'] : '—') ?></td>
              <td class="p-3">
                <?php if ((int)$r['activo'] === 1): ?>
                  <span class="inline-block text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-800">Sí</span>
                <?php else: ?>
                  <span class="inline-block text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-700">No</span>
                <?php endif; ?>
              </td>
              <td class="p-3">
                <div class="flex gap-2">
                  <a class="px-3 py-1 rounded border text-xs" href="./edit.php?id=<?= (int)$r['id'] ?>">Editar</a>
                  <form method="post" action="./delete.php" onsubmit="return confirm('¿Eliminar este RA?');">
                    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="px-3 py-1 rounded border text-xs" type="submit">Eliminar</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Paginación -->
    <?php if ($totalPages > 1): ?>
      <nav class="flex items-center gap-2 mt-3" aria-label="Paginación">
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
