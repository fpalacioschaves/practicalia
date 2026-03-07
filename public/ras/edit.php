<?php
// practicalia/public/ras/edit.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

$user    = current_user();
$isAdmin = require_role('admin');
$profId  = (int)($user['id'] ?? 0);

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$idGet  = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$idPost = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$id     = $idPost ?: $idGet;
if (!$id || $id <= 0) { http_response_code(400); exit('ID inválido'); }

$error = '';

/** Cargar RA con su asignatura y grado */
$st = $pdo->prepare("
  SELECT ra.*, a.id AS asig_id, a.nombre AS asig_nombre, a.curso_id,
         c.nombre AS curso_nombre
  FROM asignatura_ras ra
  JOIN asignaturas a ON a.id = ra.asignatura_id
  JOIN cursos c ON c.id = a.curso_id
  WHERE ra.id = :id AND ra.deleted_at IS NULL AND a.deleted_at IS NULL
  LIMIT 1
");
$st->execute([':id'=>$id]);
$ra = $st->fetch(PDO::FETCH_ASSOC);
if (!$ra) { http_response_code(404); exit('RA no encontrado'); }

/** Seguridad: si es profesor, debe impartir el grado al que pertenece este RA */
if (!$isAdmin) {
  $chk = $pdo->prepare("
    SELECT 1
    FROM cursos_profesores
    WHERE curso_id = :c AND profesor_id = :p
    LIMIT 1
  ");
  $chk->execute([':c'=>(int)$ra['curso_id'], ':p'=>$profId]);
  if (!$chk->fetch()) { http_response_code(403); exit('No tienes acceso a este RA.'); }
}

/** Cursos disponibles según rol (para mover el RA si procede) */
if ($isAdmin) {
  $cursos = $pdo->query("SELECT id, nombre FROM cursos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
} else {
  $stC = $pdo->prepare("
    SELECT c.id, c.nombre
    FROM cursos c
    JOIN cursos_profesores cp ON cp.curso_id = c.id AND cp.profesor_id = :pid
    ORDER BY c.nombre
  ");
  $stC->execute([':pid'=>$profId]);
  $cursos = $stC->fetchAll(PDO::FETCH_ASSOC);
}

/** Asignaturas dependientes del curso seleccionado */
$cursoSel = (int)$ra['curso_id'];
$asignaturas = [];
$stA = $pdo->prepare("
  SELECT a.id, a.nombre
  FROM asignaturas a
  WHERE a.deleted_at IS NULL AND a.curso_id = :c
  ORDER BY a.nombre
");
$stA->execute([':c'=>$cursoSel]);
$asignaturas = $stA->fetchAll(PDO::FETCH_ASSOC);

/** Datos del formulario (prefill) */
$data = [
  'curso_id'      => (string)$ra['curso_id'],
  'asignatura_id' => (string)$ra['asignatura_id'],
  'codigo'        => (string)$ra['codigo'],
  'titulo'        => (string)$ra['titulo'],
  'descripcion'   => (string)($ra['descripcion'] ?? ''),
  'orden'         => $ra['orden'] !== null ? (string)$ra['orden'] : '',
  'activo'        => (string)((int)$ra['activo'] ?? 1),
];

/** Cambio de curso vía POST ligero para refrescar combo de asignaturas (sin guardar) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['accion'] ?? '') === 'cambiar_curso')) {
  try {
    csrf_check($_POST['csrf'] ?? null);
    $data['curso_id'] = (string)((int)($_POST['curso_id'] ?? 0));
    $cursoSel = (int)$data['curso_id'];

    // Si es profesor, validar que el curso pertenece al profesor
    if (!$isAdmin && $cursoSel > 0) {
      $chk2 = $pdo->prepare("SELECT 1 FROM cursos_profesores WHERE curso_id=:c AND profesor_id=:p LIMIT 1");
      $chk2->execute([':c'=>$cursoSel, ':p'=>$profId]);
      if (!$chk2->fetch()) throw new RuntimeException('No puedes seleccionar ese grado.');
    }

    // Recargar asignaturas del curso seleccionado
    if ($cursoSel > 0) {
      $stA->execute([':c'=>$cursoSel]);
      $asignaturas = $stA->fetchAll(PDO::FETCH_ASSOC);
    } else {
      $asignaturas = [];
    }
    // Mantener el resto de campos
    $data['asignatura_id'] = '';
    $data['codigo']        = trim($_POST['codigo'] ?? $data['codigo']);
    $data['titulo']        = trim($_POST['titulo'] ?? $data['titulo']);
    $data['descripcion']   = trim($_POST['descripcion'] ?? $data['descripcion']);
    $data['orden']         = trim($_POST['orden'] ?? $data['orden']);
    $data['activo']        = (isset($_POST['activo']) && $_POST['activo']==='1') ? '1' : '0';
  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}

/** Guardar cambios */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['accion'] ?? '') === 'actualizar_ra')) {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $data['curso_id']      = (string)($_POST['curso_id'] ?? $data['curso_id']);
    $data['asignatura_id'] = (string)($_POST['asignatura_id'] ?? $data['asignatura_id']);
    $data['codigo']        = trim($_POST['codigo'] ?? $data['codigo']);
    $data['titulo']        = trim($_POST['titulo'] ?? $data['titulo']);
    $data['descripcion']   = trim($_POST['descripcion'] ?? $data['descripcion']);
    $data['orden']         = trim($_POST['orden'] ?? $data['orden']);
    $data['activo']        = (isset($_POST['activo']) && $_POST['activo']==='1') ? '1' : '0';

    $cursoId = (int)$data['curso_id'];
    $asigId  = (int)$data['asignatura_id'];

    if ($cursoId <= 0)  throw new RuntimeException('Selecciona el grado.');
    if ($asigId  <= 0)  throw new RuntimeException('Selecciona la asignatura.');
    if ($data['codigo'] === '') throw new RuntimeException('El código es obligatorio.');
    if ($data['titulo'] === '') throw new RuntimeException('El título es obligatorio.');
    if ($data['orden'] !== '' && (!ctype_digit($data['orden']) || (int)$data['orden'] > 10000)) {
      throw new RuntimeException('Orden inválido (0–10000).');
    }

    // Coherencia: la asignatura debe pertenecer al grado escogido
    $stChk = $pdo->prepare("SELECT 1 FROM asignaturas WHERE id=:a AND curso_id=:c AND deleted_at IS NULL LIMIT 1");
    $stChk->execute([':a'=>$asigId, ':c'=>$cursoId]);
    if (!$stChk->fetch()) throw new RuntimeException('La asignatura no pertenece al grado seleccionado.');

    // Si es profesor: validar que imparte ese grado
    if (!$isAdmin) {
      $stP = $pdo->prepare("SELECT 1 FROM cursos_profesores WHERE curso_id=:c AND profesor_id=:p LIMIT 1");
      $stP->execute([':c'=>$cursoId, ':p'=>$profId]);
      if (!$stP->fetch()) throw new RuntimeException('No puedes mover el RA a un grado que no impartes.');
    }

    // Duplicados amistosos
    $dup = $pdo->prepare("
      SELECT 1 FROM asignatura_ras
      WHERE id <> :id AND asignatura_id=:a AND (codigo=:co OR titulo=:ti) AND deleted_at IS NULL
      LIMIT 1
    ");
    $dup->execute([':id'=>$id, ':a'=>$asigId, ':co'=>$data['codigo'], ':ti'=>$data['titulo']]);
    if ($dup->fetch()) throw new RuntimeException('Ya existe un RA con ese código o título en esta asignatura.');

    // UPDATE
    $stU = $pdo->prepare("
      UPDATE asignatura_ras SET
        asignatura_id = :a,
        codigo = :co,
        titulo = :ti,
        descripcion = :de,
        orden = :orx,
        activo = :ac
      WHERE id = :id AND deleted_at IS NULL
    ");
    $stU->execute([
      ':id'=>$id,
      ':a'=>$asigId,
      ':co'=>$data['codigo'],
      ':ti'=>$data['titulo'],
      ':de'=>($data['descripcion'] !== '' ? $data['descripcion'] : null),
      ':orx'=>($data['orden'] !== '' ? (int)$data['orden'] : null),
      ':ac'=>(int)$data['activo'],
    ]);

    // Redirige al índice conservando filtros
    $qs = ['curso_id'=>$cursoId, 'asignatura_id'=>$asigId, 'updated'=>1];
    header('Location: ./index.php?'.http_build_query($qs));
    exit;

  } catch (Throwable $e) {
    $error = $e->getMessage();
    // Si cambió el curso en el submit, recargar combo de asignaturas
    $cursoSel = (int)$data['curso_id'];
    $stA->execute([':c'=>$cursoSel]);
    $asignaturas = $stA->fetchAll(PDO::FETCH_ASSOC);
  }
}
$pageTitle = 'Editar RA';
$mainClass = 'max-w-3xl';
require_once __DIR__ . '/../partials/_header.php';
?>
    <h1 class="text-xl font-semibold mb-4">Editar Resultado de Aprendizaje</h1>

    <?php if ($error): ?>
      <div class="mb-3 bg-red-50 text-red-700 p-3 rounded"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" class="bg-white p-6 rounded-2xl shadow space-y-4">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="id" value="<?= (int)$id ?>">

      <!-- selector grado + submit liviano para refrescar asignaturas -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium">Grado *</label>
          <select name="curso_id" required class="form-control" onchange="this.form.accion.value='cambiar_curso'; this.form.submit();">
            <?php foreach ($cursos as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= ((string)$c['id'] === (string)$data['curso_id']) ? 'selected' : '' ?>>
                <?= h($c['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <input type="hidden" name="accion" value="actualizar_ra">
          <p class="text-xs text-gray-500 mt-1">Al cambiar el grado, se actualizará el listado de asignaturas.</p>
        </div>

        <div>
          <label class="block text-sm font-medium">Asignatura *</label>
          <select name="asignatura_id" required class="form-control">
            <?php foreach ($asignaturas as $a): ?>
              <option value="<?= (int)$a['id'] ?>" <?= ((string)$a['id'] === (string)$data['asignatura_id']) ? 'selected' : '' ?>>
                <?= h($a['nombre']) ?>
              </option>
            <?php endforeach; ?>
            <?php if (!$asignaturas): ?>
              <option value="" disabled>No hay asignaturas en este grado</option>
            <?php endif; ?>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium">Código *</label>
          <input name="codigo" value="<?= h($data['codigo']) ?>" required class="form-control">
        </div>
        <div>
          <label class="block text-sm font-medium">Orden</label>
          <input name="orden" type="number" min="0" max="10000" value="<?= h($data['orden']) ?>" class="form-control" placeholder="p.ej. 1">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium">Título *</label>
        <input name="titulo" value="<?= h($data['titulo']) ?>" required class="form-control">
      </div>

      <div>
        <label class="block text-sm font-medium">Descripción</label>
        <textarea name="descripcion" rows="5" class="form-control"><?= h($data['descripcion']) ?></textarea>
      </div>

      <div class="flex items-center gap-2">
        <input type="hidden" name="activo" value="0">
        <input type="checkbox" id="activo" name="activo" value="1" <?= ($data['activo']==='1'?'checked':'') ?>>
        <label for="activo" class="text-sm">RA activo</label>
      </div>

      <div class="flex gap-2">
        <button class="btn-save">Guardar cambios</button>
        <a href="./index.php" class="btn-secondary">Cancelar</a>
      </div>
    </form>
  </main>
<?php require_once __DIR__ . '/../partials/_footer.php'; ?>
