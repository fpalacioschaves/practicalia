<?php
// practicalia/public/alumnos/edit.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

$user = current_user();
$isAdmin = require_role('admin');
$profId = (int) ($user['id'] ?? 0);

$idGet = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$idPost = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$id = $idPost ?: $idGet;
if (!$id || $id <= 0) {
  http_response_code(400);
  exit('ID inválido');
}

$error = '';
$okMsg = '';

$alumnoService = new \App\Services\AlumnoService($pdo);

if (!$alumnoService->checkAccess($id, $isAdmin, $profId)) {
  http_response_code(403);
  exit('No tienes acceso a este alumno.');
}

/* ============================================================
   ACCIONES (POST)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);
    $accion = $_POST['accion'] ?? '';

    switch ($accion) {
      case 'guardar_matricula':
        $alumnoService->setEnrolledAsignaturas($id, array_map('intval', $_POST['matricula'] ?? []));
        header('Location: ./edit.php?id=' . $id . '&ok=1#matricula');
        exit;

      case 'guardar_alumno':
        $alumnoService->update($id, $_POST, (int) ($_POST['curso_id'] ?? 0), $isAdmin, $profId);
        break;

      case 'asignar_empresa':
        $alumnoService->asignarEmpresa($id, $_POST, $isAdmin, $profId);
        break;

      case 'actualizar_asignacion':
        $alumnoService->actualizarAsignacion($id, $_POST);
        break;

      case 'cerrar_asignacion':
        $alumnoService->cerrarAsignacion($id, (int) $_POST['ea_id'], $_POST['fecha_fin']);
        break;

      case 'eliminar_asignacion':
        $alumnoService->eliminarAsignacion($id, (int) $_POST['ea_id'], $isAdmin);
        break;

      case 'guardar_ras_alumno':
          $empresaId = (int)($_POST['empresa_id'] ?? 0);
          $asigId = (int)($_POST['asignatura_id'] ?? 0);
          $rasSeleccionados = array_map('intval', $_POST['ras'] ?? []);
          
          if ($empresaId > 0 && $asigId > 0) {
              // 1. Borrar RAs antiguos de esta asignatura para este alumno/empresa
              $stDel = $pdo->prepare("
                  DELETE ear FROM empresa_alumno_ras ear
                  JOIN asignatura_ras ar ON ar.id = ear.ra_id
                  WHERE ear.empresa_id = :e 
                    AND ear.alumno_id = :a 
                    AND ar.asignatura_id = :asig
              ");
              $stDel->execute([':e' => $empresaId, ':a' => $id, ':asig' => $asigId]);
              
              // 2. Insertar nuevos
              if ($rasSeleccionados) {
                  $stIns = $pdo->prepare("INSERT INTO empresa_alumno_ras (empresa_id, alumno_id, ra_id) VALUES (:e, :a, :r)");
                  foreach ($rasSeleccionados as $rId) {
                      $stIns->execute([':e' => $empresaId, ':a' => $id, ':r' => $rId]);
                  }
              }
              header('Location: ./edit.php?id=' . $id . '&ok=1#dual'); // Mantener en la sección
              exit;
          }
          break;

    }

    header('Location: ./edit.php?id=' . $id . '&ok=1' . ($accion !== 'guardar_alumno' ? '#dual' : ''));
    exit;

  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}

/* ============================================================
   CARGA DE DATOS (VISTA)
   ============================================================ */
$al = $alumnoService->getById($id);
if (!$al) {
  http_response_code(404);
  exit('Alumno no encontrado');
}

$cursoActual = $alumnoService->getCursoActual($id);
$cursos = $alumnoService->getAvailableCursos($isAdmin, $profId);
$asignaturasCurso = $alumnoService->getAsignaturasByCurso($cursoActual);
$empresasDisponibles = $alumnoService->getAvailableEmpresas($isAdmin, $profId);
$totalAsignaturasAgrupadas = $alumnoService->getAvailableAsignaturasGrouped($isAdmin, $profId, $cursoActual);
$matriculaActual = $alumnoService->getEnrolledAsignaturas($id);
$asignaciones = $alumnoService->getAsignaciones($id);

$actual = $asignaciones[0] ?? null;
$asigIdsActual = [];
if ($actual) {
  $st = $pdo->prepare("SELECT asignatura_id FROM empresa_alumnos_asignaturas WHERE empresa_id=:e AND alumno_id=:a");
  $st->execute([':e' => (int) $actual['empresa_id'], ':a' => $id]);
  $asigIdsActual = $st->fetchAll(PDO::FETCH_COLUMN);
}

$asignaturasPorEA = [];
foreach ($asignaciones as $asig) {
  $st = $pdo->prepare("
    SELECT a.nombre FROM asignaturas a
    JOIN empresa_alumnos_asignaturas eaa ON eaa.asignatura_id = a.id
    WHERE eaa.empresa_id = :e AND eaa.alumno_id = :a
  ");
  $st->execute([':e' => (int) $asig['empresa_id'], ':a' => $id]);
  $asignaturasPorEA[(int) $asig['id']] = $st->fetchAll(PDO::FETCH_COLUMN);
}

function h(?string $s): string
{
  return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

$pageTitle = 'Editar alumno';
require_once __DIR__ . '/../partials/_header.php';
?>
<h1 class="text-xl font-semibold mb-4">Editar alumno #<?= (int) $al['id'] ?> —
  <?= h($al['nombre'] . ' ' . $al['apellidos']) ?>
</h1>

<?php if ($error): ?>
  <div class="mb-3 bg-red-50 text-red-700 p-3 rounded"><?= h($error) ?></div>
<?php elseif (isset($_GET['ok'])): ?>
  <div class="mb-3 bg-green-50 text-green-700 p-3 rounded">Operación realizada correctamente.</div>
<?php endif; ?>

<!-- FORM ALUMNO -->
<form method="post" class="bg-white p-6 rounded-2xl shadow space-y-4 mb-8">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
  <input type="hidden" name="id" value="<?= (int) $al['id'] ?>">
  <input type="hidden" name="accion" value="guardar_alumno">

  <div class="grid grid-cols-2 gap-3">
    <div>
      <label class="block text-sm font-medium">Nombre *</label>
      <input name="nombre" value="<?= h($al['nombre']) ?>" required class="mt-1 w-full border rounded-xl p-2">
    </div>
    <div>
      <label class="block text-sm font-medium">Apellidos *</label>
      <input name="apellidos" value="<?= h($al['apellidos']) ?>" required class="mt-1 w-full border rounded-xl p-2">
    </div>
  </div>

  <div class="grid grid-cols-2 gap-3">
    <div>
      <label class="block text-sm font-medium">Email</label>
      <input name="email" type="email" value="<?= h($al['email'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2">
    </div>
    <div>
      <label class="block text-sm font-medium">Teléfono</label>
      <input name="telefono" value="<?= h($al['telefono'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2">
    </div>
  </div>

  <div class="grid grid-cols-3 gap-3">
    <div>
      <label class="block text-sm font-medium">DNI</label>
      <input name="dni" value="<?= h($al['dni'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2">
    </div>
    <div>
      <label class="block text-sm font-medium">Seg Social</label>
      <input name="seg_social" value="<?= h($al['seg_social'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2">
    </div>
    <div>
      <label class="block text-sm font-medium">Provincia/Localidad</label>
      <input name="provincia_localidad" value="<?= h($al['provincia_localidad'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2">
    </div>
  </div>

  <div class="grid grid-cols-2 gap-3">
    <div>
      <label class="block text-sm font-medium">Fecha de nacimiento</label>
      <input name="fecha_nacimiento" type="date" value="<?= h($al['fecha_nacimiento'] ?? '') ?>"
        class="mt-1 w-full border rounded-xl p-2">
    </div>
    <div>
      <label class="block text-sm font-medium">Curso</label>
      <select name="curso_id" class="mt-1 w-full border rounded-xl p-2">
        <option value="0">— Sin curso —</option>
        <?php foreach ($cursos as $c):
          $cid = (int) $c['id']; ?>
          <option value="<?= $cid ?>" <?= ($cid === $cursoActual) ? 'selected' : '' ?>><?= h($c['nombre'] ?? '') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div>
    <label class="block text-sm font-medium">Notas</label>
    <textarea name="notas" rows="4" class="mt-1 w-full border rounded-xl p-2"><?= h($al['notas'] ?? '') ?></textarea>
  </div>

  <div class="flex items-center gap-2">
    <input type="hidden" name="activo" value="0">
    <input type="checkbox" name="activo" id="activo" value="1" <?= ((int) $al['activo'] === 1) ? 'checked' : '' ?>>
    <label for="activo" class="text-sm">Alumno activo</label>
  </div>

  <div class="flex gap-2">
    <button class="rounded-xl bg-black text-white px-4 py-2">Guardar</button>
    <a href="./index.php" class="rounded-xl px-4 py-2 border">Cancelar</a>
  </div>
</form>

<!-- MATRÍCULA EN ASIGNATURAS -->
<section id="matricula" class="bg-white p-6 rounded-2xl shadow mb-8">
  <h2 class="font-semibold mb-4">Matrícula en Asignaturas</h2>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= (int) $id ?>">
    <input type="hidden" name="accion" value="guardar_matricula">

    <div class="space-y-6">
      <?php foreach ($totalAsignaturasAgrupadas as $cid => $cursoInfo): ?>
        <div class="border rounded-xl p-4 bg-gray-50 mb-4">
          <h3 class="font-medium text-blue-800 mb-3 border-b pb-2"><?= h($cursoInfo['nombre']) ?></h3>
          
          <?php foreach ($cursoInfo['niveles'] as $nivelLabel => $asignaturas): ?>
            <div class="mb-4 last:mb-0">
              <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2"><?= h($nivelLabel) ?></h4>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                <?php foreach ($asignaturas as $asig): 
                  $asid = (int)$asig['id'];
                  $isEnrolled = in_array($asid, $matriculaActual);
                ?>
                  <label class="flex items-start gap-2 p-2 bg-white rounded-lg border hover:border-black cursor-pointer transition-colors">
                    <input type="checkbox" name="matricula[]" value="<?= $asid ?>" <?= $isEnrolled ? 'checked' : '' ?> class="mt-1">
                    <span class="text-sm"><?= h($asig['nombre']) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
      <?php if (!$totalAsignaturasAgrupadas): ?>
        <p class="text-sm text-gray-500 italic">No hay asignaturas disponibles para tus cursos.</p>
      <?php endif; ?>
    </div>

    <div class="mt-6">
      <button class="rounded-xl bg-black text-white px-4 py-2">Guardar Matrícula</button>
    </div>
  </form>
</section>

<!-- ASIGNACIÓN EN EMPRESA -->
<section id="dual" class="bg-white p-6 rounded-2xl shadow mb-8">
  <div class="flex items-center justify-between mb-3">
    <h2 class="font-semibold">Formación en empresa</h2>
  </div>

  <?php if ($actual): ?>
    <!-- EDITAR la empresa asignada (siempre que exista, esté o no finalizada) -->
    <form method="post" class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="id" value="<?= (int) $al['id'] ?>">
      <input type="hidden" name="ea_id" value="<?= (int) $actual['id'] ?>">
      <input type="hidden" name="accion" value="actualizar_asignacion">

      <div>
        <label class="block text-sm font-medium">Empresa *</label>
        <select class="mt-1 w-full border rounded-xl p-2 bg-gray-50 text-gray-600" disabled>
          <option><?= h($actual['empresa_nombre'] ?? '') ?></option>
        </select>
        <input type="hidden" name="empresa_id" value="<?= (int) $actual['empresa_id'] ?>">
      </div>

      <div>
        <label class="block text-sm font-medium">Tipo</label>
        <select name="tipo" class="mt-1 w-full border rounded-xl p-2">
          <?php foreach (['dual', 'fct', 'practicas', 'otros'] as $opt): ?>
            <option value="<?= $opt ?>" <?= ($opt === $actual['tipo']) ? 'selected' : '' ?>><?= ucfirst($opt) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium">Fecha inicio *</label>
        <input type="date" name="fecha_inicio" value="<?= h($actual['fecha_inicio']) ?>"
          class="mt-1 w-full border rounded-xl p-2" required>
      </div>

      <div>
        <label class="block text-sm font-medium">Fecha fin</label>
        <input type="date" name="fecha_fin" value="<?= h($actual['fecha_fin'] ?? '') ?>"
          class="mt-1 w-full border rounded-xl p-2">
      </div>

      <div>
        <label class="block text-sm font-medium">Horas previstas</label>
        <input type="number" name="horas_previstas" min="0" max="2000"
          value="<?= h((string) ($actual['horas_previstas'] ?? '')) ?>" class="mt-1 w-full border rounded-xl p-2">
      </div>

      <div class="md:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
          <label class="block text-sm font-medium">Tutor (empresa)</label>
          <input name="tutor_nombre" value="<?= h($actual['tutor_nombre'] ?? '') ?>"
            class="mt-1 w-full border rounded-xl p-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Email tutor</label>
          <input name="tutor_email" type="email" value="<?= h($actual['tutor_email'] ?? '') ?>"
            class="mt-1 w-full border rounded-xl p-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Teléfono tutor</label>
          <input name="tutor_telefono" value="<?= h($actual['tutor_telefono'] ?? '') ?>"
            class="mt-1 w-full border rounded-xl p-2">
        </div>
      </div>

      <div class="md:col-span-3">
        <label class="block text-sm font-medium">Observaciones</label>
        <textarea name="observaciones" rows="3"
          class="mt-1 w-full border rounded-xl p-2"><?= h($actual['observaciones'] ?? '') ?></textarea>
      </div>

      <div class="md:col-span-3">
        <label class="block text-sm font-medium">Asignaturas a dualizar</label>
        <?php if ($cursoActual > 0 && $asignaturasCurso): ?>
          <select name="asignaturas[]" multiple size="6" class="mt-1 w-full border rounded-xl p-2">
            <?php foreach ($asignaturasCurso as $as):
              $aid = (int) $as['id']; ?>
              <option value="<?= $aid ?>" <?= in_array($aid, $asigIdsActual) ? 'selected' : '' ?>>
                <?= h($as['nombre'] ?? '') ?>
              </option>
            <?php endforeach; ?>
          </select>
          <p class="text-xs text-gray-500 mt-1">Mantén Ctrl/Cmd para seleccionar varias.</p>
        <?php else: ?>
          <select disabled class="mt-1 w-full border rounded-xl p-2">
            <option>
              <?= $cursoActual > 0 ? 'No hay asignaturas asociadas a este curso' : 'Asigna primero un curso al alumno' ?>
            </option>
          </select>
        <?php endif; ?>
      </div>

      <div class="md:col-span-3 flex flex-wrap gap-2">
        <button class="rounded-xl bg-black text-white px-4 py-2">Guardar cambios</button>
    </form>
    <!-- Cerrar -->
    <form method="post" onsubmit="return confirm('¿Cerrar esta asignación?');" class="inline">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="id" value="<?= (int) $al['id'] ?>">
      <input type="hidden" name="ea_id" value="<?= (int) $actual['id'] ?>">
      <input type="hidden" name="accion" value="cerrar_asignacion">
      <input type="date" name="fecha_fin" value="<?= date('Y-m-d') ?>" class="border rounded px-2 text-xs">
      <button class="px-3 py-2 rounded border text-xs align-middle" type="submit">Cerrar</button>
    </form>

    <!-- Eliminar -->
    <form method="post" onsubmit="return confirm('¿Eliminar esta asignación?');" class="inline">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="id" value="<?= (int) $al['id'] ?>">
      <input type="hidden" name="ea_id" value="<?= (int) $actual['id'] ?>">
      <input type="hidden" name="accion" value="eliminar_asignacion">
      <button class="px-3 py-2 rounded border text-xs" type="submit">Eliminar</button>
    </form>
    </div>

  <?php else: ?>
    <!-- Alta sólo si no hay ninguna empresa asignada -->
    <form method="post" class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="id" value="<?= (int) $al['id'] ?>">
      <input type="hidden" name="accion" value="asignar_empresa">

      <div>
        <label class="block text-sm font-medium">Empresa *</label>
        <select name="empresa_id" class="mt-1 w-full border rounded-xl p-2" required>
          <option value="">— Selecciona —</option>
          <?php foreach ($empresasDisponibles as $e): ?>
            <option value="<?= (int) $e['id'] ?>"><?= h($e['nombre'] ?? '') ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium">Tipo</label>
        <select name="tipo" class="mt-1 w-full border rounded-xl p-2">
          <option value="dual">Dual</option>
          <option value="fct">FCT</option>
          <option value="practicas">Prácticas</option>
          <option value="otros">Otros</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium">Fecha inicio *</label>
        <input type="date" name="fecha_inicio" class="mt-1 w-full border rounded-xl p-2" required>
      </div>

      <div>
        <label class="block text-sm font-medium">Fecha fin</label>
        <input type="date" name="fecha_fin" class="mt-1 w-full border rounded-xl p-2">
      </div>

      <div>
        <label class="block text-sm font-medium">Horas previstas</label>
        <input type="number" name="horas_previstas" min="0" max="2000" class="mt-1 w-full border rounded-xl p-2">
      </div>

      <div class="md:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
          <label class="block text-sm font-medium">Tutor (empresa)</label>
          <input name="tutor_nombre" class="mt-1 w-full border rounded-xl p-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Email tutor</label>
          <input name="tutor_email" type="email" class="mt-1 w-full border rounded-xl p-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Teléfono tutor</label>
          <input name="tutor_telefono" class="mt-1 w-full border rounded-xl p-2">
        </div>
      </div>

      <div class="md:col-span-3">
        <label class="block text-sm font-medium">Observaciones</label>
        <textarea name="observaciones" rows="3" class="mt-1 w-full border rounded-xl p-2"></textarea>
      </div>

      <div class="md:col-span-3">
        <label class="block text-sm font-medium">Asignaturas a dualizar</label>
        <?php if ($cursoActual > 0 && $asignaturasCurso): ?>
          <select name="asignaturas[]" multiple size="6" class="mt-1 w-full border rounded-xl p-2">
            <?php foreach ($asignaturasCurso as $as): ?>
              <option value="<?= (int) $as['id'] ?>"><?= h($as['nombre'] ?? '') ?></option>
            <?php endforeach; ?>
          </select>
          <p class="text-xs text-gray-500 mt-1">Mantén Ctrl/Cmd para seleccionar varias.</p>
        <?php elseif ($cursoActual > 0): ?>
          <select disabled class="mt-1 w-full border rounded-xl p-2">
            <option>No hay asignaturas asociadas a este curso</option>
          </select>
        <?php else: ?>
          <select disabled class="mt-1 w-full border rounded-xl p-2">
            <option>Asigna primero un curso al alumno</option>
          </select>
        <?php endif; ?>
      </div>

      <div class="md:col-span-3">
        <button class="rounded-xl bg-black text-white px-4 py-2">Asignar</button>
      </div>
    </form>
  <?php endif; ?>

  <!-- >>> NUEVO: GESTIÓN DE RAs (Panel replicado de empresas/edit.php) -->
  <?php if ($actual && $asigIdsActual):
    $empresaId = (int) $actual['empresa_id'];

    // Consultas para RAs
    $stRAs = $pdo->prepare("
          SELECT ar.id, ar.codigo, ar.titulo
          FROM asignatura_ras ar
          WHERE ar.asignatura_id = :asig
          ORDER BY COALESCE(ar.orden, ar.id)
      ");
    $stMarcados = $pdo->prepare("
          SELECT ear.ra_id
          FROM empresa_alumno_ras ear
          WHERE ear.empresa_id = :e AND ear.alumno_id = :a
      ");
    $stMarcados->execute([':e' => $empresaId, ':a' => $id]);
    $marcados = $stMarcados->fetchAll(PDO::FETCH_COLUMN, 0);
    $mapMarcados = array_fill_keys($marcados, true);
    ?>
    <div class="mt-8 border-t pt-6">
      <h3 class="font-semibold text-lg mb-4">Resultados de Aprendizaje (Dual)</h3>
      <p class="text-sm text-gray-500 mb-4">Selecciona los RAs que se trabajarán en esta empresa para cada asignatura
        asignada.</p>

      <div class="space-y-6">
        <?php foreach ($asigIdsActual as $asigId):
          $asigName = '';
          foreach ($asignaturasCurso as $ac) {
            if ((int) $ac['id'] === (int) $asigId) {
              $asigName = $ac['nombre'];
              break;
            }
          }
          $stRAs->execute([':asig' => $asigId]);
          $ras = $stRAs->fetchAll(PDO::FETCH_ASSOC);
          ?>
          <div class="rounded-xl border bg-gray-50">
            <div class="px-4 py-3 border-b flex items-center justify-between">
              <h4 class="font-medium"><?= h($asigName) ?></h4>
              <span class="text-xs text-gray-500"><?= count($ras) ?> RAs disponibles</span>
            </div>
            <div class="p-4 bg-white rounded-b-xl">
              <?php if (!$ras): ?>
                <p class="text-gray-500 text-sm">No hay RAs definidos para esta asignatura.</p>
              <?php else: ?>
                <form method="post">
                  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                  <input type="hidden" name="id" value="<?= (int) $id ?>">
                  <input type="hidden" name="accion" value="guardar_ras_alumno">
                  <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
                  <input type="hidden" name="asignatura_id" value="<?= $asigId ?>">

                  <div class="overflow-x-auto border rounded-xl mb-3">
                    <table class="min-w-full text-sm">
                      <thead class="bg-gray-50">
                        <tr>
                          <th class="text-left p-2 w-24">Código</th>
                          <th class="text-left p-2">Resultado de Aprendizaje</th>
                          <th class="text-center p-2 w-16">Dual</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($ras as $ra):
                          $checked = isset($mapMarcados[(int) $ra['id']]);
                          ?>
                          <tr class="border-t hover:bg-gray-50">
                            <td class="p-2 font-mono text-xs"><?= h($ra['codigo']) ?></td>
                            <td class="p-2"><?= h($ra['titulo']) ?></td>
                            <td class="p-2 text-center">
                              <input type="checkbox" name="ras[]" value="<?= (int) $ra['id'] ?>" <?= $checked ? 'checked' : '' ?>>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                  <div class="text-right">
                    <button class="text-sm bg-black text-white px-3 py-1.5 rounded-lg">Guardar RAs para
                      <?= h($asigName) ?></button>
                  </div>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
  <!-- <<< FIN NUEVO -->

  <!-- HISTÓRICO DE ASIGNACIONES (Si hay más de una) -->
  <?php if (count($asignaciones) > 1): ?>
    <section class="bg-white p-6 rounded-2xl shadow mb-8">
      <h2 class="font-semibold mb-3">Histórico de asignaciones</h2>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="text-left p-2">Empresa</th>
              <th class="text-left p-2">Tipo</th>
              <th class="text-left p-2">Inicio</th>
              <th class="text-left p-2">Fin</th>
              <th class="text-left p-2">Estado</th>
              <th class="text-left p-2">Asignaturas</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($asignaciones as $idx => $asig):
              if ($idx === 0)
                continue; ?>
              <tr class="border-t">
                <td class="p-2"><?= h($asig['empresa_nombre'] ?? '') ?></td>
                <td class="p-2"><?= h($asig['tipo'] ?? '') ?></td>
                <td class="p-2"><?= h($asig['fecha_inicio'] ?? '') ?></td>
                <td class="p-2"><?= h($asig['fecha_fin'] ?? '—') ?></td>
                <td class="p-2"><?= h($asig['estado'] ?? '') ?></td>
                <td class="p-2 text-xs">
                  <?= h(implode(', ', $asignaturasPorEA[(int) $asig['id']] ?? [])) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endif; ?>

  <!-- DIARIO DE CONTACTOS -->
  <?php require __DIR__ . '/_edit_contactos.php'; ?>
  <?php require_once __DIR__ . '/../partials/_footer.php'; ?>