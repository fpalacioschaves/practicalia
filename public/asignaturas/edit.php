<?php
// practicalia/public/asignaturas/edit.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

$user = current_user();
$isAdmin = require_role('admin');
$profId = (int) ($user['id'] ?? 0);

function h(?string $s): string
{
  return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
function normalize_decimal(?string $v): ?string
{
  if ($v === null)
    return null;
  $v = trim($v);
  if ($v === '')
    return null;
  return str_replace(',', '.', $v);
}

$idGet = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$idPost = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$id = $idPost ?: $idGet;
if (!$id || $id <= 0) {
  http_response_code(400);
  exit('ID inválido');
}

$error = '';

/* Cargar asignatura */
$stA = $pdo->prepare("
  SELECT a.*
  FROM asignaturas a
  WHERE a.id = :id AND a.deleted_at IS NULL
  LIMIT 1
");
$stA->execute([':id' => $id]);
$asig = $stA->fetch(PDO::FETCH_ASSOC);
if (!$asig) {
  http_response_code(404);
  exit('Asignatura no encontrada');
}

/* Cargar grados asociados (histórico 1:N + N:M actual) */
$seleccionados = [];
if (!empty($asig['curso_id'])) {
  $seleccionados[(int) $asig['curso_id']] = true;
}
$stAC = $pdo->prepare("SELECT curso_id FROM asignatura_cursos WHERE asignatura_id = :a");
$stAC->execute([':a' => $id]);
foreach ($stAC->fetchAll(PDO::FETCH_COLUMN) as $cid) {
  $seleccionados[(int) $cid] = true;
}
$seleccionados = array_keys($seleccionados); // int únicos

/* Cursos disponibles para el selector (según rol) */
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

/* Seguridad: si es profesor, debe impartir al menos uno de los grados asociados actuales */
if (!$isAdmin) {
  if (empty($seleccionados)) {
    http_response_code(403);
    exit('No tienes acceso a esta asignatura.');
  }
  $marks = implode(',', array_fill(0, count($seleccionados), '?'));
  $params = array_merge([$profId], $seleccionados);
  $chk = $pdo->prepare("SELECT COUNT(*) c FROM cursos_profesores WHERE profesor_id = ? AND curso_id IN ($marks)");
  $chk->execute($params);
  if ((int) $chk->fetchColumn() === 0) {
    http_response_code(403);
    exit('No tienes acceso a esta asignatura.');
  }
}

/* Datos del form */
$data = [
  'cursos' => $seleccionados,
  'nombre' => (string) $asig['nombre'],
  'codigo' => (string) ($asig['codigo'] ?? ''),
  'ects' => $asig['ects'] !== null ? (string) $asig['ects'] : '',
  'horas' => $asig['horas'] !== null ? (string) $asig['horas'] : '',
  'semestre' => $asig['semestre'] !== null ? (string) $asig['semestre'] : '',
  'descripcion' => (string) ($asig['descripcion'] ?? ''),
  'activo' => (string) ((int) $asig['activo'] ?? 1),
];

/* Guardar cambios */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['accion'] ?? '') === 'actualizar_asignatura')) {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $data['cursos'] = array_values(array_unique(array_map('intval', $_POST['cursos'] ?? $data['cursos'])));
    $data['nombre'] = trim($_POST['nombre'] ?? $data['nombre']);
    $data['codigo'] = trim($_POST['codigo'] ?? $data['codigo']);
    $data['ects'] = normalize_decimal($_POST['ects'] ?? $data['ects']);
    $data['horas'] = trim($_POST['horas'] ?? $data['horas']);
    $data['semestre'] = trim($_POST['semestre'] ?? $data['semestre']);
    $data['descripcion'] = trim($_POST['descripcion'] ?? $data['descripcion']);
    $data['activo'] = (isset($_POST['activo']) && $_POST['activo'] === '1') ? '1' : '0';

    if (!$data['cursos'])
      throw new RuntimeException('Selecciona al menos un grado.');
    if ($data['nombre'] === '')
      throw new RuntimeException('El nombre es obligatorio.');

    // Si es profesor: validar que TODOS los grados elegidos los imparte
    if (!$isAdmin) {
      $marks = implode(',', array_fill(0, count($data['cursos']), '?'));
      $chk2 = $pdo->prepare("SELECT COUNT(*) c FROM cursos_profesores WHERE profesor_id = ? AND curso_id IN ($marks)");
      $chk2->execute(array_merge([$profId], $data['cursos']));
      if ((int) $chk2->fetchColumn() !== count($data['cursos'])) {
        throw new RuntimeException('Has seleccionado grados que no impartes.');
      }
    }

    // Validaciones suaves
    if ($data['ects'] !== null && $data['ects'] !== '' && !preg_match('/^\d{1,3}(\.\d)?$/', $data['ects'])) {
      throw new RuntimeException('ECTS inválidos (usa formato 6 o 6.0).');
    }
    if ($data['horas'] !== '' && (!ctype_digit($data['horas']) || (int) $data['horas'] > 2000)) {
      throw new RuntimeException('Horas inválidas (0–2000).');
    }
    if ($data['semestre'] !== '' && !in_array($data['semestre'], ['1', '2'], true)) {
      throw new RuntimeException('Semestre inválido (1 ó 2).');
    }

    // Duplicados (por grado). Usar :cid1 y :cid2 para evitar HY093.
    foreach ($data['cursos'] as $cid) {
      if ($data['codigo'] !== '') {
        $stDupCode = $pdo->prepare("
          SELECT 1
          FROM asignaturas a
          LEFT JOIN asignatura_cursos ac ON ac.asignatura_id = a.id
          WHERE a.id <> :id
            AND (a.curso_id = :cid1 OR ac.curso_id = :cid2)
            AND a.codigo = :codigo
          LIMIT 1
        ");
        $stDupCode->execute([':id' => $id, ':cid1' => $cid, ':cid2' => $cid, ':codigo' => $data['codigo']]);
        if ($stDupCode->fetch())
          throw new RuntimeException('Ya existe una asignatura con ese código en el grado seleccionado.');
      }

      $stDupName = $pdo->prepare("
        SELECT 1
        FROM asignaturas a
        LEFT JOIN asignatura_cursos ac ON ac.asignatura_id = a.id
        WHERE a.id <> :id
          AND (a.curso_id = :cid1 OR ac.curso_id = :cid2)
          AND a.nombre = :nombre
        LIMIT 1
      ");
      $stDupName->execute([':id' => $id, ':cid1' => $cid, ':cid2' => $cid, ':nombre' => $data['nombre']]);
      if ($stDupName->fetch())
        throw new RuntimeException('Ya existe una asignatura con ese nombre en el grado seleccionado.');
    }

    // Curso principal para compatibilidad: primer seleccionado
    $cursoPrincipal = (int) $data['cursos'][0];

    // UPDATE principal
    $stU = $pdo->prepare("
      UPDATE asignaturas SET
        curso_id=:curso_id, nombre=:nombre, codigo=:codigo, ects=:ects,
        horas=:horas, semestre=:semestre, descripcion=:descripcion, activo=:activo
      WHERE id=:id AND deleted_at IS NULL
    ");
    $stU->execute([
      ':id' => $id,
      ':curso_id' => $cursoPrincipal,
      ':nombre' => $data['nombre'],
      ':codigo' => ($data['codigo'] !== '' ? $data['codigo'] : null),
      ':ects' => ($data['ects'] !== '' ? $data['ects'] : null),
      ':horas' => ($data['horas'] !== '' ? (int) $data['horas'] : null),
      ':semestre' => ($data['semestre'] !== '' ? (int) $data['semestre'] : null),
      ':descripcion' => ($data['descripcion'] !== '' ? $data['descripcion'] : null),
      ':activo' => (int) $data['activo'],
    ]);

    // Resinc N:M
    $pdo->prepare("DELETE FROM asignatura_cursos WHERE asignatura_id = :a")->execute([':a' => $id]);
    $insAC = $pdo->prepare("INSERT IGNORE INTO asignatura_cursos (asignatura_id, curso_id) VALUES (:a,:c)");
    foreach ($data['cursos'] as $cid) {
      $insAC->execute([':a' => $id, ':c' => (int) $cid]);
    }

    header('Location: ./index.php?updated=1');
    exit;

  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}

/* Para pintar tras guardar/error */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // $data['cursos'] ya viene armado
} else {
  $data['cursos'] = $seleccionados;
}
$pageTitle = 'Editar asignatura';
$mainClass = 'max-w-4xl';
require_once __DIR__ . '/../partials/_header.php';
?>
<h1 class="text-xl font-semibold mb-4">Editar asignatura</h1>

<?php if ($error): ?>
  <div class="mb-3 bg-red-50 text-red-700 p-3 rounded"><?= h($error) ?></div>
<?php endif; ?>

<form method="post" class="bg-white p-6 rounded-2xl shadow space-y-4">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
  <input type="hidden" name="id" value="<?= (int) $asig['id'] ?>">
  <input type="hidden" name="accion" value="actualizar_asignatura">

  <div>
    <label class="block text-sm font-medium mb-1">Grados *</label>
    <select name="cursos[]" multiple required class="mt-1 w-full border rounded-xl p-2" size="6">
      <?php
      $selecs = array_map('intval', $data['cursos'] ?? []);
      foreach ($cursos as $c):
        $cid = (int) $c['id'];
        ?>
        <option value="<?= $cid ?>" <?= in_array($cid, $selecs, true) ? 'selected' : '' ?>>
          <?= h($c['nombre']) ?>
        </option>
      <?php endforeach; ?>
      <?php if (!$cursos): ?>
        <option value="" disabled>No hay grados disponibles</option>
      <?php endif; ?>
    </select>
    <p class="text-xs text-gray-500 mt-1">Mantén pulsado Ctrl/Cmd para seleccionar varios.</p>
  </div>

  <div class="grid grid-cols-1 md-grid-cols-2 md:grid-cols-2 gap-3">
    <div>
      <label class="block text-sm font-medium">Nombre *</label>
      <input name="nombre" value="<?= h($data['nombre']) ?>" required class="mt-1 w-full border rounded-xl p-2">
    </div>
    <div>
      <label class="block text-sm font-medium">Código</label>
      <input name="codigo" value="<?= h($data['codigo']) ?>" class="mt-1 w-full border rounded-xl p-2"
        placeholder="p.ej. SIGDAW2">
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
    <div>
      <label class="block text-sm font-medium">ECTS</label>
      <input name="ects" inputmode="decimal" value="<?= h($data['ects']) ?>" class="mt-1 w-full border rounded-xl p-2"
        placeholder="6 o 6.0">
    </div>
    <div>
      <label class="block text-sm font-medium">Horas</label>
      <input name="horas" type="number" min="0" max="2000" value="<?= h($data['horas']) ?>"
        class="mt-1 w-full border rounded-xl p-2">
    </div>
    <div>
      <label class="block text-sm font-medium">Semestre</label>
      <select name="semestre" class="mt-1 w-full border rounded-xl p-2">
        <option value="">—</option>
        <option value="1" <?= ($data['semestre'] === '1' ? 'selected' : '') ?>>1</option>
        <option value="2" <?= ($data['semestre'] === '2' ? 'selected' : '') ?>>2</option>
      </select>
    </div>
  </div>

  <div>
    <label class="block text-sm font-medium">Descripción</label>
    <textarea name="descripcion" rows="4"
      class="mt-1 w-full border rounded-xl p-2"><?= h($data['descripcion']) ?></textarea>
  </div>

  <div class="flex items-center gap-2">
    <input type="hidden" name="activo" value="0">
    <input type="checkbox" id="activo" name="activo" value="1" <?= ($data['activo'] === '1' ? 'checked' : '') ?>>
    <label for="activo" class="text-sm">Asignatura activa</label>
  </div>

  <div class="flex gap-2">
    <button class="rounded-xl bg-black text-white px-4 py-2">Guardar</button>
    <a href="./index.php" class="rounded-xl px-4 py-2 border">Volver</a>
  </div>
</form>
</main>
<?php require_once __DIR__ . '/../partials/_footer.php'; ?>