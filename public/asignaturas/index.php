<?php
// practicalia/public/asignaturas/index.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

$user = current_user();
$isAdmin = require_role('admin');
$profId = (int) ($user['id'] ?? 0);

/** @var PDO $pdo */
function h(?string $s): string
{
  return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

// Parámetros de filtro/paginación
$q = trim((string) ($_GET['q'] ?? ''));
$cursoIdF = filter_input(INPUT_GET, 'curso_id', FILTER_VALIDATE_INT);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Cursos disponibles según rol (para filtro)
if ($isAdmin) {
  $cursos = $pdo->query("SELECT id, nombre FROM cursos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
} else {
  $stC = $pdo->prepare("
    SELECT c.id, c.nombre
    FROM cursos c
    JOIN cursos_profesores cp ON cp.curso_id = c.id AND cp.profesor_id = :pid
    ORDER BY c.nombre
  ");
  $stC->execute([':pid' => $profId]);
  $cursos = $stC->fetchAll(PDO::FETCH_ASSOC);
}

// WHERE dinámico
$where = [];
$params = [];
$where[] = "a.deleted_at IS NULL";

// Si es profesor: debe estar vinculada a alguno de sus cursos (principal o N:M)
if (!$isAdmin) {
  $where[] = "EXISTS (
    SELECT 1
    FROM cursos_profesores cp2
    WHERE cp2.profesor_id = :pid
      AND (cp2.curso_id = a.curso_id
           OR cp2.curso_id IN (SELECT acx.curso_id FROM asignatura_cursos acx WHERE acx.asignatura_id = a.id))
  )";
  $params[':pid'] = $profId;
}

// Filtro por curso (principal o N:M)
if ($cursoIdF && $cursoIdF > 0) {
  $where[] = "(a.curso_id = :c1 OR EXISTS (
                 SELECT 1 FROM asignatura_cursos acf
                 WHERE acf.asignatura_id = a.id AND acf.curso_id = :c2
               ))";
  $params[':c1'] = $cursoIdF;
  $params[':c2'] = $cursoIdF;
}

// Búsqueda por nombre/código  ✅ usar dos placeholders distintos
if ($q !== '') {
  $where[] = "(a.nombre LIKE :q1 OR a.codigo LIKE :q2)";
  $params[':q1'] = "%{$q}%";
  $params[':q2'] = "%{$q}%";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Total
$sqlCount = "
  SELECT COUNT(DISTINCT a.id)
  FROM asignaturas a
  $whereSql
";
$st = $pdo->prepare($sqlCount);
$st->execute($params);
$total = (int) $st->fetchColumn();

// Ajuste por si la página se sale
$pages = max(1, (int) ceil($total / $perPage));
if ($page > $pages) {
  $page = $pages;
  $offset = ($page - 1) * $perPage;
}

// LISTADO:
// - left join al curso principal (c0)
// - left join a la tabla puente (ac) + su curso (c1)
// - agrupamos por a.id (UNA fila por asignatura)
// - cursos_nombres se arma con DISTINCT para evitar duplicados
$sqlRows = "
  SELECT
    a.id,
    a.nombre,
    a.codigo,
    a.ects,
    a.horas,
    a.semestre,
    a.nivel,
    a.activo,
    IFNULL(
      GROUP_CONCAT(DISTINCT c1.nombre ORDER BY c1.nombre SEPARATOR ', '),
      c0.nombre
    ) AS cursos_nombres
  FROM asignaturas a
  LEFT JOIN cursos c0
         ON c0.id = a.curso_id
  LEFT JOIN asignatura_cursos ac
         ON ac.asignatura_id = a.id
  LEFT JOIN cursos c1
         ON c1.id = ac.curso_id
  $whereSql
  GROUP BY a.id, a.nombre, a.codigo, a.ects, a.horas, a.semestre, a.nivel, a.activo
  ORDER BY a.nombre ASC, a.id ASC
  LIMIT :limit OFFSET :offset
";
$st = $pdo->prepare($sqlRows);
foreach ($params as $k => $v) {
  $st->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$st->bindValue(':limit', $perPage, PDO::PARAM_INT);
$st->bindValue(':offset', $offset, PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

// Helper para mantener filtros en paginación
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

// Rango mostrado
$from = ($total === 0) ? 0 : ($offset + 1);
$to = min($offset + $perPage, $total);

// Ventana de paginación
$window = 2;
$pagesToShow = [];
$pagesToShow[] = 1;
for ($i = $page - $window; $i <= $page + $window; $i++) {
  if ($i > 1 && $i < $pages)
    $pagesToShow[] = $i;
}
if ($pages > 1)
  $pagesToShow[] = $pages;
$pagesToShow = array_values(array_unique(array_filter($pagesToShow, fn($p) => $p >= 1 && $p <= $pages)));
sort($pagesToShow);

function renderPageLink(int $p, int $cur): string
{
  $isCur = ($p === $cur);
  $cls = 'px-3 py-1 rounded border text-sm ' . ($isCur ? 'bg-black text-white border-black' : 'bg-white');
  return '<a class="' . $cls . '" href="' . h(urlWith(['page' => $p])) . '">' . $p . '</a>';
}
$pageTitle = 'Asignaturas';
require_once __DIR__ . '/../partials/_header.php';
?>
<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold">Asignaturas</h1>
  <a href="./create.php" class="btn-add">Nueva asignatura</a>
</div>

<!-- Filtros -->
<form method="get" class="bg-white p-4 rounded-2xl shadow mb-4 grid grid-cols-1 md:grid-cols-3 gap-3">
  <div>
    <label class="block text-sm font-medium mb-1">Buscar</label>
    <input name="q" value="<?= h($q) ?>" class="form-control w-full" placeholder="Nombre o código">
  </div>
  <div>
    <label class="block text-sm font-medium mb-1">Grado</label>
    <select name="curso_id" class="form-control w-full">
      <option value="">— Todos —</option>
      <?php foreach ($cursos as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= ($cursoIdF && (int) $c['id'] === $cursoIdF ? 'selected' : '') ?>>
          <?= h($c['nombre']) ?>
        </option>
      <?php endforeach; ?>
      <?php if (!$cursos): ?>
        <option value="" disabled>No hay grados disponibles</option>
      <?php endif; ?>
    </select>
  </div>
  <div class="flex items-end gap-2">
    <button class="btn-filter px-6">Filtrar</button>
    <a href="./index.php" class="btn-secondary px-6">Limpiar</a>
  </div>
</form>

<div class="mb-2 text-sm text-gray-600">
  Mostrando <span class="font-medium"><?= (int) $from ?></span>–<span class="font-medium"><?= (int) $to ?></span> de
  <span class="font-medium"><?= (int) $total ?></span> asignatura(s)
  <?php if ($q !== ''): ?> · búsqueda «<?= h($q) ?>»<?php endif; ?>
  <?php if ($cursoIdF): ?> · grado filtrado<?php endif; ?>
</div>

<!-- Tabla -->
<div class="overflow-x-auto bg-white rounded-2xl shadow border">
  <table class="min-w-full text-sm">
    <thead class="bg-gray-50">
      <tr>
        <th class="text-left p-3">Asignatura</th>
        <th class="text-left p-3">Código</th>
        <th class="text-left p-3">ECTS</th>
        <th class="text-left p-3">Horas</th>
        <th class="text-left p-3">Curso</th>
        <th class="text-left p-3">Sem.</th>
        <th class="text-left p-3">Grados</th>
        <th class="text-left p-3">Activa</th>
        <th class="text-left p-3">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr>
          <td colspan="8" class="p-4 text-gray-500">No hay asignaturas.</td>
        </tr>
      <?php else:
        foreach ($rows as $r): ?>
          <tr class="border-t">
            <td class="p-3"><?= h($r['nombre']) ?></td>
            <td class="p-3"><?= h($r['codigo'] ?? '') ?></td>
            <td class="p-3"><?= h($r['ects'] !== null ? (string) $r['ects'] : '—') ?></td>
            <td class="p-3"><?= h($r['horas'] !== null ? (string) $r['horas'] : '—') ?></td>
            <td class="p-3 font-bold"><?= h($r['nivel'] !== null ? $r['nivel'] . 'º' : '—') ?></td>
            <td class="p-3"><?= h($r['semestre'] !== null ? (string) $r['semestre'] : '—') ?></td>
            <td class="p-3"><?= h($r['cursos_nombres'] ?? '—') ?></td>
            <td class="p-3">
              <?php if ((int) $r['activo'] === 1): ?>
                <span class="inline-block text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-800">Sí</span>
              <?php else: ?>
                <span class="inline-block text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-700">No</span>
              <?php endif; ?>
            </td>
            <td class="p-3">
              <div class="flex gap-2">
                <a class="btn-edit" href="./edit.php?id=<?= (int) $r['id'] ?>">Editar</a>
                <form method="post" action="./delete.php" onsubmit="return confirm('¿Eliminar esta asignatura?');">
                  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                  <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                  <button class="btn-delete" type="submit">Eliminar</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<!-- Paginación -->
<?php if ($pages > 1): ?>
  <nav class="flex items-center gap-2 mt-3" aria-label="Paginación">
    <a class="px-3 py-1 rounded border text-sm <?= $page === 1 ? 'pointer-events-none opacity-50' : '' ?>"
      href="<?= h(urlWith(['page' => 1])) ?>">« Primera</a>
    <a class="px-3 py-1 rounded border text-sm <?= $page === 1 ? 'pointer-events-none opacity-50' : '' ?>"
      href="<?= h(urlWith(['page' => max(1, $page - 1)])) ?>">‹ Anterior</a>
    <?php
    $prev = 0;
    foreach ($pagesToShow as $p):
      if ($prev && $p > $prev + 1)
        echo '<span class="px-2 text-gray-400">…</span>';
      echo renderPageLink($p, $page);
      $prev = $p;
    endforeach;
    ?>
    <a class="px-3 py-1 rounded border text-sm <?= $page === $pages ? 'pointer-events-none opacity-50' : '' ?>"
      href="<?= h(urlWith(['page' => min($pages, $page + 1)])) ?>">Siguiente ›</a>
    <a class="px-3 py-1 rounded border text-sm <?= $page === $pages ? 'pointer-events-none opacity-50' : '' ?>"
      href="<?= h(urlWith(['page' => $pages])) ?>">Última »</a>
  </nav>
<?php endif; ?>
</main>
<?php require_once __DIR__ . '/../partials/_footer.php'; ?>