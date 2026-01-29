<?php
// practicalia/public/ras/create.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

$user    = current_user();
$isAdmin = require_role('admin');
$profId  = (int)($user['id'] ?? 0);

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Prefills por querystring (opcional)
$qCursoId = filter_input(INPUT_GET, 'curso_id', FILTER_VALIDATE_INT) ?: null;
$qAsigId  = filter_input(INPUT_GET, 'asignatura_id', FILTER_VALIDATE_INT) ?: null;

$error = '';
$data = [
  'curso_id'      => $qCursoId ? (string)$qCursoId : '',
  'asignatura_id' => $qAsigId  ? (string)$qAsigId  : '',
  'codigo'        => '',
  'titulo'        => '',
  'descripcion'   => '',
  'orden'         => '',
  'activo'        => '1',
];

/* Cursos visibles según rol */
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

/* Asignaturas dependientes del curso seleccionado (si hay) y visibles por rol */
$asignaturas = [];
$cursoSel = (int)($data['curso_id'] ?: 0);
if ($cursoSel > 0) {
  if ($isAdmin) {
    $st = $pdo->prepare("
      SELECT id, nombre FROM asignaturas
      WHERE deleted_at IS NULL AND curso_id = :c
      ORDER BY nombre
    ");
    $st->execute([':c' => $cursoSel]);
  } else {
    $st = $pdo->prepare("
      SELECT a.id, a.nombre
      FROM asignaturas a
      JOIN cursos_profesores cp ON cp.curso_id = a.curso_id AND cp.profesor_id = :pid
      WHERE a.deleted_at IS NULL AND a.curso_id = :c
      ORDER BY a.nombre
    ");
    $st->execute([':pid' => $profId, ':c' => $cursoSel]);
  }
  $asignaturas = $st->fetchAll(PDO::FETCH_ASSOC);
}

/* POST: crear RA */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['accion'] ?? '') === 'crear_ra')) {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $data['curso_id']      = (string)($_POST['curso_id'] ?? '');
    $data['asignatura_id'] = (string)($_POST['asignatura_id'] ?? '');
    $data['codigo']        = trim($_POST['codigo'] ?? '');
    $data['titulo']        = trim($_POST['titulo'] ?? '');
    $data['descripcion']   = trim($_POST['descripcion'] ?? '');
    $data['orden']         = trim($_POST['orden'] ?? '');
    $data['activo']        = (isset($_POST['activo']) && $_POST['activo'] === '1') ? '1' : '0';

    $cursoId = (int)$data['curso_id'];
    $asigId  = (int)$data['asignatura_id'];

    if ($cursoId <= 0)  throw new RuntimeException('Selecciona el grado.');
    if ($asigId  <= 0)  throw new RuntimeException('Selecciona la asignatura.');
    if ($data['codigo'] === '') throw new RuntimeException('El código es obligatorio.');
    if ($data['titulo'] === '') throw new RuntimeException('El título es obligatorio.');
    if ($data['orden'] !== '' && (!ctype_digit($data['orden']) || (int)$data['orden'] > 10000)) {
      throw new RuntimeException('Orden inválido (0–10000).');
    }

    // Coherencia: la asignatura debe pertenecer al curso escogido
    $stChk = $pdo->prepare("SELECT 1 FROM asignaturas WHERE id=:a AND curso_id=:c AND deleted_at IS NULL LIMIT 1");
    $stChk->execute([':a'=>$asigId, ':c'=>$cursoId]);
    if (!$stChk->fetch()) throw new RuntimeException('La asignatura no pertenece al grado seleccionado.');

    // Si es profesor: validar que imparte ese grado
    if (!$isAdmin) {
      $stP = $pdo->prepare("SELECT 1 FROM cursos_profesores WHERE curso_id=:c AND profesor_id=:p LIMIT 1");
      $stP->execute([':c'=>$cursoId, ':p'=>$profId]);
      if (!$stP->fetch()) throw new RuntimeException('No puedes crear RAs en un grado que no impartes.');
    }

    // Duplicados amistosos antes del insert
    $dup = $pdo->prepare("SELECT 1 FROM asignatura_ras WHERE asignatura_id=:a AND (codigo=:co OR titulo=:ti) AND deleted_at IS NULL LIMIT 1");
    $dup->execute([':a'=>$asigId, ':co'=>$data['codigo'], ':ti'=>$data['titulo']]);
    if ($dup->fetch()) throw new RuntimeException('Ya existe un RA con ese código o título en esta asignatura.');

    // Insert
    $stIns = $pdo->prepare("
      INSERT INTO asignatura_ras
        (asignatura_id, codigo, titulo, descripcion, orden, activo)
      VALUES
        (:asig, :cod, :tit, :desc, :ord, :act)
    ");
    $stIns->execute([
      ':asig' => $asigId,
      ':cod'  => $data['codigo'],
      ':tit'  => $data['titulo'],
      ':desc' => ($data['descripcion'] !== '' ? $data['descripcion'] : null),
      ':ord'  => ($data['orden'] !== '' ? (int)$data['orden'] : null),
      ':act'  => (int)$data['activo'],
    ]);

    // Redirigir al índice manteniendo filtros por curso/asignatura
    $qs = [];
    if ($cursoId) $qs['curso_id'] = $cursoId;
    if ($asigId)  $qs['asignatura_id'] = $asigId;
    $qs['created'] = 1;
    $redir = './index.php' . ($qs ? ('?'.http_build_query($qs)) : '');
    header('Location: ' . $redir);
    exit;

  } catch (Throwable $e) {
    $msg = $e->getMessage();
    if (str_contains($msg, 'uq_ra_asig_codigo')) $msg = 'Código duplicado en esta asignatura.';
    if (str_contains($msg, 'uq_ra_asig_titulo')) $msg = 'Título duplicado en esta asignatura.';
    $error = $msg;

    // Reconstruir combos si el usuario cambió de curso
    $cursoSel = (int)$data['curso_id'];
    if ($cursoSel > 0) {
      if ($isAdmin) {
        $st = $pdo->prepare("SELECT id, nombre FROM asignaturas WHERE deleted_at IS NULL AND curso_id=:c ORDER BY nombre");
        $st->execute([':c'=>$cursoSel]);
      } else {
        $st = $pdo->prepare("
          SELECT a.id, a.nombre
          FROM asignaturas a
          JOIN cursos_profesores cp ON cp.curso_id = a.curso_id AND cp.profesor_id = :pid
          WHERE a.deleted_at IS NULL AND a.curso_id = :c
          ORDER BY a.nombre
        ");
        $st->execute([':pid'=>$profId, ':c'=>$cursoSel]);
      }
      $asignaturas = $st->fetchAll(PDO::FETCH_ASSOC);
    }
  }
}
$pageTitle = 'Nuevo RA';
$mainClass = 'max-w-3xl';
require_once __DIR__ . '/../partials/_header.php';
?>
    <h1 class="text-xl font-semibold mb-4">Nuevo Resultado de Aprendizaje</h1>

    <?php if ($error): ?>
      <div class="mb-3 bg-red-50 text-red-700 p-3 rounded"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" class="bg-white p-6 rounded-2xl shadow space-y-4">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="accion" value="crear_ra">

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium">Grado *</label>
          <select name="curso_id" required class="mt-1 w-full border rounded-xl p-2" onchange="this.form.submit()">
            <option value="">— Selecciona —</option>
            <?php foreach ($cursos as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= ((string)$c['id'] === (string)$data['curso_id']) ? 'selected' : '' ?>>
                <?= h($c['nombre']) ?>
              </option>
            <?php endforeach; ?>
            <?php if (!$cursos): ?>
              <option value="" disabled>No hay grados disponibles</option>
            <?php endif; ?>
          </select>
          <p class="text-xs text-gray-500 mt-1">Tras elegir grado, el formulario se actualizará para listar sus asignaturas.</p>
        </div>

        <div>
          <label class="block text-sm font-medium">Asignatura *</label>
          <select name="asignatura_id" required class="mt-1 w-full border rounded-xl p-2">
            <option value="">— Selecciona —</option>
            <?php foreach ($asignaturas as $a): ?>
              <option value="<?= (int)$a['id'] ?>" <?= ((string)$a['id'] === (string)$data['asignatura_id']) ? 'selected' : '' ?>>
                <?= h($a['nombre']) ?>
              </option>
            <?php endforeach; ?>
            <?php if ($cursoSel && !$asignaturas): ?>
              <option value="" disabled>No hay asignaturas en este grado</option>
            <?php endif; ?>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium">Código *</label>
          <input name="codigo" value="<?= h($data['codigo']) ?>" required class="mt-1 w-full border rounded-xl p-2" placeholder="p.ej. RA1">
        </div>
        <div>
          <label class="block text-sm font-medium">Orden</label>
          <input name="orden" type="number" min="0" max="10000" value="<?= h($data['orden']) ?>" class="mt-1 w-full border rounded-xl p-2" placeholder="p.ej. 1">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium">Título *</label>
        <input name="titulo" value="<?= h($data['titulo']) ?>" required class="mt-1 w-full border rounded-xl p-2" placeholder="Enunciado resumido del RA">
      </div>

      <div>
        <label class="block text-sm font-medium">Descripción</label>
        <textarea name="descripcion" rows="5" class="mt-1 w-full border rounded-xl p-2" placeholder="Detalle del resultado de aprendizaje..."><?= h($data['descripcion']) ?></textarea>
      </div>

      <div class="flex items-center gap-2">
        <input type="hidden" name="activo" value="0">
        <input type="checkbox" id="activo" name="activo" value="1" <?= ($data['activo']==='1'?'checked':'') ?>>
        <label for="activo" class="text-sm">RA activo</label>
      </div>

      <div class="flex gap-2">
        <button class="rounded-xl bg-black text-white px-4 py-2">Crear</button>
        <a href="./index.php" class="rounded-xl px-4 py-2 border">Cancelar</a>
      </div>
    </form>
  </main>
<?php require_once __DIR__ . '/../partials/_footer.php'; ?>
