<?php
// practicalia/public/asignaturas/create.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

$user = current_user();
$isAdmin = require_role('admin');           // true si tiene rol admin
$profId = (int) ($user['id'] ?? 0);

$error = '';
$okMsg = '';

/** Cargar cursos disponibles según rol */
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

/** Helpers locales */
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
  return str_replace(',', '.', $v); // admitir coma decimal
}

/** Valores por defecto del formulario */
$data = [
  'cursos' => [],   // múltiple
  'nombre' => '',
  'codigo' => '',
  'ects' => '',
  'horas' => '',
  'semestre' => '',
  'nivel' => '1',
  'descripcion' => '',
  'activo' => '1',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['accion'] ?? '') === 'crear_asignatura')) {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $data['cursos'] = array_values(array_unique(array_map('intval', $_POST['cursos'] ?? [])));
    $data['nombre'] = trim($_POST['nombre'] ?? '');
    $data['codigo'] = trim($_POST['codigo'] ?? '');
    $data['ects'] = normalize_decimal($_POST['ects'] ?? '');
    $data['horas'] = trim($_POST['horas'] ?? '');
    $data['semestre'] = trim($_POST['semestre'] ?? '');
    $data['nivel'] = trim($_POST['nivel'] ?? '1');
    $data['descripcion'] = trim($_POST['descripcion'] ?? '');
    $data['activo'] = (isset($_POST['activo']) && $_POST['activo'] === '1') ? '1' : '0';

    if (!$data['cursos']) {
      throw new RuntimeException('Selecciona al menos un grado.');
    }
    if ($data['nombre'] === '') {
      throw new RuntimeException('El nombre es obligatorio.');
    }

    // Si es profesor, comprobar que TODOS los grados elegidos pertenecen al profe
    if (!$isAdmin) {
      $marks = implode(',', array_fill(0, count($data['cursos']), '?'));
      $chk = $pdo->prepare("SELECT COUNT(*) c FROM cursos_profesores WHERE profesor_id = ? AND curso_id IN ($marks)");
      $chk->execute(array_merge([$profId], $data['cursos']));
      $cnt = (int) $chk->fetchColumn();
      if ($cnt !== count($data['cursos'])) {
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
    if ($data['semestre'] !== '' && !in_array($data['semestre'], ['1', '2', '3', '4'], true)) {
      throw new RuntimeException('Semestre inválido.');
    }
    if ($data['nivel'] !== '' && (!ctype_digit($data['nivel']) || (int) $data['nivel'] > 10)) {
      throw new RuntimeException('Nivel (curso) inválido.');
    }

    // Comprobación de duplicados por cada grado seleccionado.
    // Usar placeholders distintos (:cid1, :cid2) para evitar HY093.
    foreach ($data['cursos'] as $cid) {
      if ($data['codigo'] !== '') {
        $stDupCode = $pdo->prepare("
          SELECT 1
          FROM asignaturas a
          LEFT JOIN asignatura_cursos ac ON ac.asignatura_id = a.id
          WHERE (a.curso_id = :cid1 OR ac.curso_id = :cid2)
            AND a.codigo = :codigo
          LIMIT 1
        ");
        $stDupCode->execute([':cid1' => $cid, ':cid2' => $cid, ':codigo' => $data['codigo']]);
        if ($stDupCode->fetch()) {
          throw new RuntimeException('Ya existe una asignatura con ese código en el grado seleccionado.');
        }
      }

      $stDupName = $pdo->prepare("
        SELECT 1
        FROM asignaturas a
        LEFT JOIN asignatura_cursos ac ON ac.asignatura_id = a.id
        WHERE (a.curso_id = :cid1 OR ac.curso_id = :cid2)
          AND a.nombre = :nombre
        LIMIT 1
      ");
      $stDupName->execute([':cid1' => $cid, ':cid2' => $cid, ':nombre' => $data['nombre']]);
      if ($stDupName->fetch()) {
        throw new RuntimeException('Ya existe una asignatura con ese nombre en el grado seleccionado.');
      }
    }

    // Insert principal en asignaturas
    $cursoPrincipal = (int) $data['cursos'][0];

    $st = $pdo->prepare("
      INSERT INTO asignaturas
        (curso_id, nivel, nombre, codigo, ects, horas, semestre, descripcion, activo)
      VALUES
        (:curso_id, :nivel, :nombre, :codigo, :ects, :horas, :semestre, :descripcion, :activo)
    ");
    $st->execute([
      ':curso_id' => $cursoPrincipal,
      ':nivel' => ($data['nivel'] !== '' ? (int) $data['nivel'] : 1),
      ':nombre' => $data['nombre'],
      ':codigo' => ($data['codigo'] !== '' ? $data['codigo'] : null),
      ':ects' => ($data['ects'] !== '' ? $data['ects'] : null),
      ':horas' => ($data['horas'] !== '' ? (int) $data['horas'] : null),
      ':semestre' => ($data['semestre'] !== '' ? (int) $data['semestre'] : null),
      ':descripcion' => ($data['descripcion'] !== '' ? $data['descripcion'] : null),
      ':activo' => (int) $data['activo'],
    ]);
    $asigId = (int) $pdo->lastInsertId();

    // Tabla puente N:M
    $insAC = $pdo->prepare("INSERT IGNORE INTO asignatura_cursos (asignatura_id, curso_id) VALUES (:a,:c)");
    foreach ($data['cursos'] as $cid) {
      $insAC->execute([':a' => $asigId, ':c' => (int) $cid]);
    }

    header('Location: ./index.php?created=1');
    exit;

  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}
$pageTitle = 'Nueva asignatura';
$mainClass = 'max-w-4xl';
require_once __DIR__ . '/../partials/_header.php';
?>
<h1 class="text-xl font-semibold mb-4">Nueva asignatura</h1>

<?php if ($error): ?>
  <div class="mb-3 bg-red-50 text-red-700 p-3 rounded"><?= h($error) ?></div>
<?php endif; ?>

<form method="post" class="bg-white p-6 rounded-2xl shadow space-y-4">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
  <input type="hidden" name="accion" value="crear_asignatura">

  <div>
    <label class="block text-sm font-medium mb-1">Grados *</label>
    <select name="cursos[]" multiple required class="mt-1 w-full border rounded-xl p-2" size="6">
      <?php foreach ($cursos as $c):
        $cid = (int) $c['id']; ?>
        <option value="<?= $cid ?>" <?= in_array($cid, array_map('intval', $data['cursos']), true) ? 'selected' : '' ?>>
          <?= h($c['nombre']) ?>
        </option>
      <?php endforeach; ?>
      <?php if (!$cursos): ?>
        <option value="" disabled>No hay grados disponibles</option>
      <?php endif; ?>
    </select>
    <p class="text-xs text-gray-500 mt-1">Mantén pulsado Ctrl/Cmd para seleccionar varios.</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
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
      <input type="number" name="semestre" min="1" max="10" value="<?= h($data['semestre']) ?>"
        class="mt-1 w-full border rounded-xl p-2">
    </div>
    <div>
      <label class="block text-sm font-medium">Nivel (Curso) *</label>
      <input type="number" name="nivel" min="1" max="10" required value="<?= h($data['nivel']) ?>"
        class="mt-1 w-full border rounded-xl p-2" placeholder="p.ej. 1 para 1º, 2 para 2º...">
    </div>
  </div>

  <div>
    <label class="block text-sm font-medium">Descripción</label>
    <textarea name="descripcion" rows="4" class="mt-1 w-full border rounded-xl p-2"
      placeholder="Breve descripción..."><?= h($data['descripcion']) ?></textarea>
  </div>

  <div class="flex items-center gap-2">
    <input type="hidden" name="activo" value="0">
    <input type="checkbox" id="activo" name="activo" value="1" <?= ($data['activo'] === '1' ? 'checked' : '') ?>>
    <label for="activo" class="text-sm">Asignatura activa</label>
  </div>

  <div class="flex gap-2">
    <button class="rounded-xl bg-black text-white px-4 py-2">Crear</button>
    <a href="./index.php" class="rounded-xl px-4 py-2 border">Cancelar</a>
  </div>
</form>
</main>
<?php require_once __DIR__ . '/../partials/_footer.php'; ?>