<?php
// practicalia/public/prospectos/index.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

$user = current_user();
$isAdmin = require_role('admin'); // true si admin
$profId  = (int)($user['id'] ?? 0);

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$q      = trim($_GET['q'] ?? '');
$estado = trim($_GET['estado'] ?? '');
$curso  = (int)($_GET['curso_id'] ?? 0);
$soloMios = isset($_GET['mios']) && $_GET['mios'] === '1';

$where = ["p.deleted_at IS NULL"];
$params = [];

if ($q !== '') {
  $where[] = "(p.nombre LIKE :q OR p.web LIKE :q OR p.email LIKE :q OR p.prospecto_etiquetas LIKE :q OR p.responsable_nombre LIKE :q OR p.ciudad LIKE :q OR p.provincia LIKE :q)";
  $params[':q'] = "%$q%";
}
if (in_array($estado, ['nuevo','pendiente','contactada','interesada','descartada'], true)) {
  $where[] = "p.estado = :estado";
  $params[':estado'] = $estado;
}
if ($curso > 0) {
  $where[] = "p.curso_id = :curso";
  $params[':curso'] = $curso;
}
if (!$isAdmin || $soloMios) {
  $where[] = "p.asignado_profesor_id = :prof";
  $params[':prof'] = $profId;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$cursos = $pdo->query("SELECT id, nombre FROM cursos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Paginación
$perPage = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$stCount = $pdo->prepare("SELECT COUNT(*) FROM empresas_prospectos p $whereSql");
$stCount->execute($params);
$total = (int)$stCount->fetchColumn();

$sql = "
  SELECT p.*, u.nombre AS profesor_nombre, c.nombre AS curso_nombre
  FROM empresas_prospectos p
  LEFT JOIN usuarios u ON u.id = p.asignado_profesor_id
  LEFT JOIN cursos c   ON c.id = p.curso_id
  $whereSql
  ORDER BY p.updated_at DESC, p.id DESC
  LIMIT :lim OFFSET :off
";
$st = $pdo->prepare($sql);
foreach ($params as $k=>$v) $st->bindValue($k, $v);
$st->bindValue(':lim', $perPage, PDO::PARAM_INT);
$st->bindValue(':off', $offset, PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

$pages = (int)ceil($total / $perPage);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Prospectos de empresas — Practicalia</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
  <?php require_once __DIR__ . '/../partials/menu.php'; ?>

  <main class="max-w-6xl mx-auto p-4 space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">Prospectos de empresas</h1>
      <div class="flex gap-2">
        <a href="./buscar.php" class="rounded-xl border px-4 py-2">Buscar empresas</a>
        <a href="./edit.php" class="rounded-xl bg-black text-white px-4 py-2">Añadir prospecto</a>
      </div>
    </div>

    <form class="bg-white p-4 rounded-2xl shadow grid grid-cols-1 md:grid-cols-5 gap-3">
      <input class="border rounded-xl p-2 md:col-span-2" name="q" placeholder="Buscar por nombre, web, email, etiquetas..." value="<?= h($q) ?>">
      <select class="border rounded-xl p-2" name="estado">
        <option value="">Estado (todos)</option>
        <?php foreach (['nuevo','pendiente','contactada','interesada','descartada'] as $opt): ?>
          <option value="<?= $opt ?>" <?= $estado===$opt?'selected':'' ?>><?= ucfirst($opt) ?></option>
        <?php endforeach; ?>
      </select>
      <select class="border rounded-xl p-2" name="curso_id">
        <option value="0">Curso objetivo (todos)</option>
        <?php foreach ($cursos as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= $curso===(int)$c['id']?'selected':'' ?>><?= h($c['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
      <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="mios" value="1" <?= (!$isAdmin || $soloMios)?'checked':''; ?>>
        Solo asignados a mí
      </label>
      <div class="md:col-span-5">
        <button class="rounded-xl bg-black text-white px-4 py-2">Filtrar</button>
        <a href="./index.php" class="ml-2 rounded-xl px-4 py-2 border">Limpiar</a>
      </div>
    </form>

    <div class="bg-white rounded-2xl shadow overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="text-left p-3">Prospecto</th>
            <th class="text-left p-3">Contacto</th>
            <th class="text-left p-3">Estado</th>
            <th class="text-left p-3">Curso</th>
            <th class="text-left p-3">Asignado</th>
            <th class="text-left p-3">Actualizado</th>
            <th class="text-left p-3">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="7" class="p-4 text-gray-500">Sin resultados.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr class="border-t">
              <td class="p-3">
                <div class="font-medium"><?= h($r['nombre']) ?></div>
                <div class="text-xs text-gray-600">
                  <?php if ($r['web']): ?><a class="underline" target="_blank" href="<?= h($r['web']) ?>"><?= h($r['web']) ?></a><?php endif; ?>
                  <?= $r['web'] && ($r['email']||$r['telefono']) ? ' · ' : '' ?>
                  <?php if ($r['email']): ?><a class="underline" href="mailto:<?= h($r['email']) ?>"><?= h($r['email']) ?></a><?php endif; ?>
                  <?= $r['email'] && $r['telefono'] ? ' · ' : '' ?>
                  <?php if ($r['telefono']): ?><span><?= h($r['telefono']) ?></span><?php endif; ?>
                </div>
                <?php if ($r['prospecto_etiquetas']): ?>
                  <div class="text-xs text-gray-500 mt-1">Etiquetas: <?= h($r['prospecto_etiquetas']) ?></div>
                <?php endif; ?>
              </td>
              <td class="p-3">
                <?php if ($r['responsable_nombre']): ?>
                  <div><?= h($r['responsable_nombre']) ?><?= $r['responsable_cargo'] ? ' — '.h($r['responsable_cargo']) : '' ?></div>
                  <div class="text-xs text-gray-600">
                    <?php if ($r['responsable_email']): ?><a class="underline" href="mailto:<?= h($r['responsable_email']) ?>"><?= h($r['responsable_email']) ?></a><?php endif; ?>
                    <?= ($r['responsable_email'] && $r['responsable_telefono']) ? ' · ' : '' ?>
                    <?php if ($r['responsable_telefono']): ?><span><?= h($r['responsable_telefono']) ?></span><?php endif; ?>
                  </div>
                <?php else: ?>
                  <span class="text-gray-400">—</span>
                <?php endif; ?>
              </td>
              <td class="p-3"><?= h(ucfirst($r['estado'])) ?></td>
              <td class="p-3"><?= h($r['curso_nombre'] ?? '—') ?></td>
              <td class="p-3"><?= h($r['profesor_nombre'] ?? '—') ?></td>
              <td class="p-3"><?= h($r['updated_at']) ?></td>
              <td class="p-3">
                <div class="flex gap-2">
                  <a class="px-3 py-1 rounded border" href="./edit.php?id=<?= (int)$r['id'] ?>">Editar</a>
                  <form method="post" action="./promote.php" onsubmit="return confirm('¿Convertir este prospecto en empresa?');">
                    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="px-3 py-1 rounded border bg-emerald-50" type="submit">Convertir</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pages > 1): ?>
      <div class="flex gap-2 justify-center">
        <?php for ($i=1; $i<=$pages; $i++): ?>
          <a class="px-3 py-1 rounded border <?= $i===$page?'bg-black text-white':'' ?>"
             href="?<?= http_build_query(array_merge($_GET, ['page'=>$i])) ?>"><?= $i ?></a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>
