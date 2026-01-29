<?php
// practicalia/public/prospectos/edit.php
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
function dtlocal(?string $mysqlDt): string
{
  if (!$mysqlDt)
    return '';
  return str_replace(' ', 'T', substr($mysqlDt, 0, 16));
}

$idGet = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$idPost = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$id = $idPost ?: $idGet;

$error = '';

/** Cursos disponibles (según rol, coherente con empresas/edit.php) */
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

/* ===============================
   ACCIONES: GUARDAR PROSPECTO
   =============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'guardar') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $nombre = trim($_POST['nombre'] ?? '');
    $sector = trim($_POST['sector'] ?? '');
    $cnae = trim($_POST['cnae'] ?? '');
    $web = trim($_POST['web'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');

    $responsable_nombre = trim($_POST['responsable_nombre'] ?? '');
    $responsable_cargo = trim($_POST['responsable_cargo'] ?? '');
    $responsable_email = trim($_POST['responsable_email'] ?? '');
    $responsable_telefono = trim($_POST['responsable_telefono'] ?? '');

    $ciudad = trim($_POST['ciudad'] ?? '');
    $provincia = trim($_POST['provincia'] ?? '');
    $notas = trim($_POST['notas'] ?? '');

    $estado = trim($_POST['estado'] ?? 'nuevo');
    $origen = trim($_POST['origen'] ?? 'manual');
    $fuente = trim($_POST['fuente_url'] ?? '');
    $tags = trim($_POST['prospecto_etiquetas'] ?? '');

    $cursoId = (int) ($_POST['curso_id'] ?? 0);
    $asignado = $isAdmin ? (int) ($_POST['asignado_profesor_id'] ?? $profId) : $profId;

    if ($nombre === '')
      throw new RuntimeException('El nombre es obligatorio.');
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))
      throw new RuntimeException('Email no válido.');
    if ($responsable_email !== '' && !filter_var($responsable_email, FILTER_VALIDATE_EMAIL))
      throw new RuntimeException('Email del responsable no válido.');
    if (!in_array($estado, ['nuevo', 'pendiente', 'contactada', 'interesada', 'descartada'], true))
      $estado = 'nuevo';
    if (!in_array($origen, ['manual', 'busqueda', 'import'], true))
      $origen = 'manual';

    if ($id) {
      // update
      $st = $pdo->prepare("
        UPDATE empresas_prospectos SET
          nombre=:nombre, sector=:sector, cnae=:cnae, web=:web, email=:email, telefono=:telefono,
          responsable_nombre=:rnom, responsable_cargo=:rcargo, responsable_email=:remail, responsable_telefono=:rtel,
          ciudad=:ciudad, provincia=:provincia, notas=:notas,
          estado=:estado, origen=:origen, fuente_url=:fuente_url, prospecto_etiquetas=:tags,
          curso_id=:curso, asignado_profesor_id=:prof
        WHERE id=:id AND deleted_at IS NULL
      ");
      $st->execute([
        ':id' => $id,
        ':nombre' => $nombre,
        ':sector' => $sector,
        ':cnae' => $cnae,
        ':web' => ($web !== '' ? $web : null),
        ':email' => ($email !== '' ? $email : null),
        ':telefono' => ($telefono !== '' ? $telefono : null),
        ':rnom' => ($responsable_nombre !== '' ? $responsable_nombre : null),
        ':rcargo' => ($responsable_cargo !== '' ? $responsable_cargo : null),
        ':remail' => ($responsable_email !== '' ? $responsable_email : null),
        ':rtel' => ($responsable_telefono !== '' ? $responsable_telefono : null),
        ':ciudad' => ($ciudad !== '' ? $ciudad : null),
        ':provincia' => ($provincia !== '' ? $provincia : null),
        ':notas' => ($notas !== '' ? $notas : null),
        ':estado' => $estado,
        ':origen' => $origen,
        ':fuente_url' => ($fuente !== '' ? $fuente : null),
        ':tags' => ($tags !== '' ? $tags : null),
        ':curso' => ($cursoId > 0 ? $cursoId : null),
        ':prof' => ($asignado > 0 ? $asignado : null),
      ]);
    } else {
      // insert
      $st = $pdo->prepare("
        INSERT INTO empresas_prospectos
          (nombre, sector, cnae, web, email, telefono,
           responsable_nombre, responsable_cargo, responsable_email, responsable_telefono,
           ciudad, provincia, notas, estado, origen, fuente_url, prospecto_etiquetas, curso_id, asignado_profesor_id)
        VALUES
          (:nombre,:sector,:cnae,:web,:email,:telefono,
           :rnom,:rcargo,:remail,:rtel,
           :ciudad,:provincia,:notas,:estado,:origen,:fuente_url,:tags,:curso,:prof)
      ");
      $st->execute([
        ':nombre' => $nombre,
        ':sector' => $sector,
        ':cnae' => $cnae,
        ':web' => ($web !== '' ? $web : null),
        ':email' => ($email !== '' ? $email : null),
        ':telefono' => ($telefono !== '' ? $telefono : null),
        ':rnom' => ($responsable_nombre !== '' ? $responsable_nombre : null),
        ':rcargo' => ($responsable_cargo !== '' ? $responsable_cargo : null),
        ':remail' => ($responsable_email !== '' ? $responsable_email : null),
        ':rtel' => ($responsable_telefono !== '' ? $responsable_telefono : null),
        ':ciudad' => ($ciudad !== '' ? $ciudad : null),
        ':provincia' => ($provincia !== '' ? $provincia : null),
        ':notas' => ($notas !== '' ? $notas : null),
        ':estado' => $estado,
        ':origen' => $origen,
        ':fuente_url' => ($fuente !== '' ? $fuente : null),
        ':tags' => ($tags !== '' ? $tags : null),
        ':curso' => ($cursoId > 0 ? $cursoId : null),
        ':prof' => ($asignado > 0 ? $asignado : null),
      ]);
      $id = (int) $pdo->lastInsertId();
    }

    header('Location: ./edit.php?id=' . $id . '&ok=1');
    exit;

  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}

/* ===============================
   ACCIONES: CONTACTOS
   =============================== */
$canalesPermitidos = ['llamada', 'email', 'visita', 'reunión', 'whatsapp', 'otros'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'guardar_contacto') {
  try {
    csrf_check($_POST['csrf'] ?? null);
    $prospectoId = (int) ($_POST['prospecto_id'] ?? 0);
    if ($prospectoId <= 0)
      throw new RuntimeException('Prospecto inválido.');
    if ($id && $prospectoId !== $id)
      throw new RuntimeException('IDs no coinciden.');

    $fechaRaw = trim($_POST['fecha'] ?? '');
    $fecha = $fechaRaw !== '' ? str_replace('T', ' ', $fechaRaw) . (strlen($fechaRaw) === 16 ? ':00' : '') : null;

    $canal = trim($_POST['canal'] ?? '');
    $asunto = trim($_POST['asunto'] ?? '');
    $resumen = trim($_POST['resumen'] ?? '');
    $resultado = trim($_POST['resultado'] ?? '');
    $prox = trim($_POST['proxima_accion'] ?? '');
    $confid = (isset($_POST['confidencial']) && $_POST['confidencial'] === '1') ? 1 : 0;

    if (!in_array($canal, $canalesPermitidos, true))
      $canal = 'otros';
    if ($asunto === '')
      throw new RuntimeException('El asunto es obligatorio.');

    $st = $pdo->prepare("
      INSERT INTO contactos_prospecto
        (prospecto_id, usuario_id, fecha, canal, asunto, resumen, resultado, proxima_accion, confidencial)
      VALUES
        (:p, :u, COALESCE(:f, NOW()), :c, :a, :r, :res, :pa, :conf)
    ");
    $st->execute([
      ':p' => $prospectoId,
      ':u' => $profId,
      ':f' => $fecha,
      ':c' => $canal,
      ':a' => $asunto,
      ':r' => ($resumen !== '' ? $resumen : null),
      ':res' => ($resultado !== '' ? $resultado : null),
      ':pa' => ($prox !== '' ? $prox : null),
      ':conf' => $confid,
    ]);

    header('Location: ./edit.php?id=' . $prospectoId . '&ok=1#contactos');
    exit;

  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}

/* ===============================
   ACCIONES: BORRADO LÓGICO
   =============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar') {
  try {
    csrf_check($_POST['csrf'] ?? null);
    $prospectoId = (int) ($_POST['id'] ?? 0);
    if ($prospectoId <= 0)
      throw new RuntimeException('ID inválido.');

    $pdo->prepare("UPDATE empresas_prospectos SET deleted_at = NOW() WHERE id=:id AND deleted_at IS NULL")
      ->execute([':id' => $prospectoId]);

    header('Location: ./index.php?ok=1');
    exit;

  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}

/* ===============================
   CARGA DE DATOS
   =============================== */
$prospecto = null;
if ($id) {
  $st = $pdo->prepare("
    SELECT p.*, u.nombre AS profesor_nombre
    FROM empresas_prospectos p
    LEFT JOIN usuarios u ON u.id = p.asignado_profesor_id
    WHERE p.id = :id AND p.deleted_at IS NULL
    LIMIT 1
  ");
  $st->execute([':id' => $id]);
  $prospecto = $st->fetch(PDO::FETCH_ASSOC);
  if (!$prospecto) {
    http_response_code(404);
    exit('Prospecto no encontrado');
  }
}

$contactos = [];
if ($id) {
  $stC = $pdo->prepare("
    SELECT cp.*, u.nombre AS autor_nombre
    FROM contactos_prospecto cp
    LEFT JOIN usuarios u ON u.id = cp.usuario_id
    WHERE cp.prospecto_id = :p
    ORDER BY cp.fecha DESC, cp.id DESC
    LIMIT 50
  ");
  $stC->execute([':p' => $id]);
  $contactos = $stC->fetchAll(PDO::FETCH_ASSOC);
}
$pageTitle = ($id ? 'Editar prospecto' : 'Nuevo prospecto');
require_once __DIR__ . '/../partials/_header.php';
?>
<div class="flex items-center justify-between">
  <h1 class="text-xl font-semibold"><?= $id ? 'Editar prospecto #' . (int) $id : 'Nuevo prospecto' ?></h1>
  <div class="flex gap-2">
    <?php if ($id): ?>
      <form method="post" onsubmit="return confirm('¿Eliminar este prospecto?');">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int) $id ?>">
        <input type="hidden" name="accion" value="eliminar">
        <button class="px-3 py-2 rounded-xl border" type="submit">Eliminar</button>
      </form>
      <form method="post" action="./promote.php" onsubmit="return confirm('¿Convertir a empresa real?');">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int) $id ?>">
        <button class="px-3 py-2 rounded-xl border bg-emerald-50" type="submit">Convertir</button>
      </form>
    <?php endif; ?>
    <a class="px-3 py-2 rounded-xl border" href="./index.php">Volver</a>
  </div>
</div>

<?php if ($error): ?>
  <div class="mt-3 bg-red-50 text-red-700 p-3 rounded"><?= h($error) ?></div>
<?php elseif (isset($_GET['ok'])): ?>
  <div class="mt-3 bg-green-50 text-green-700 p-3 rounded">Guardado correctamente.</div>
<?php endif; ?>

<form method="post" class="bg-white p-6 rounded-2xl shadow space-y-4 mt-4">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
  <input type="hidden" name="accion" value="guardar">
  <?php if ($id): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
    <div>
      <label class="block text-sm font-medium">Nombre *</label>
      <input name="nombre" required value="<?= h($prospecto['nombre'] ?? '') ?>"
        class="mt-1 w-full border rounded-xl p-2">
    </div>
    <div>
      <label class="block text-sm font-medium">Sector</label>
      <input name="sector" value="<?= h($prospecto['sector'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2">
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
    <div>
      <label class="block text-sm font-medium">CNAE</label>
      <input name="cnae" value="<?= h($prospecto['cnae'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2"
        placeholder="6201, 6311...">
    </div>
    <div>
      <label class="block text-sm font-medium">Web</label>
      <input name="web" value="<?= h($prospecto['web'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2"
        placeholder="https://...">
    </div>
    <div>
      <label class="block text-sm font-medium">Email</label>
      <input type="email" name="email" value="<?= h($prospecto['email'] ?? '') ?>"
        class="mt-1 w-full border rounded-xl p-2">
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
    <div>
      <label class="block text-sm font-medium">Teléfono</label>
      <input name="telefono" value="<?= h($prospecto['telefono'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2">
    </div>
    <div>
      <label class="block text-sm font-medium">Ciudad</label>
      <input name="ciudad" value="<?= h($prospecto['ciudad'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2">
    </div>
    <div>
      <label class="block text-sm font-medium">Provincia</label>
      <input name="provincia" value="<?= h($prospecto['provincia'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2">
    </div>
  </div>

  <div class="pt-4 border-t">
    <h3 class="font-semibold mb-2">Persona de contacto</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div>
        <label class="block text-sm font-medium">Nombre y apellidos</label>
        <input name="responsable_nombre" value="<?= h($prospecto['responsable_nombre'] ?? '') ?>"
          class="mt-1 w-full border rounded-xl p-2">
      </div>
      <div>
        <label class="block text-sm font-medium">Cargo</label>
        <input name="responsable_cargo" value="<?= h($prospecto['responsable_cargo'] ?? '') ?>"
          class="mt-1 w-full border rounded-xl p-2">
      </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
      <div>
        <label class="block text-sm font-medium">Email</label>
        <input type="email" name="responsable_email" value="<?= h($prospecto['responsable_email'] ?? '') ?>"
          class="mt-1 w-full border rounded-xl p-2">
      </div>
      <div>
        <label class="block text-sm font-medium">Teléfono</label>
        <input name="responsable_telefono" value="<?= h($prospecto['responsable_telefono'] ?? '') ?>"
          class="mt-1 w-full border rounded-xl p-2">
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
    <div>
      <label class="block text-sm font-medium">Estado</label>
      <?php $est = $prospecto['estado'] ?? 'nuevo'; ?>
      <select name="estado" class="mt-1 w-full border rounded-xl p-2">
        <?php foreach (['nuevo', 'pendiente', 'contactada', 'interesada', 'descartada'] as $opt): ?>
          <option value="<?= $opt ?>" <?= $est === $opt ? 'selected' : '' ?>><?= ucfirst($opt) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium">Curso objetivo</label>
      <select name="curso_id" class="mt-1 w-full border rounded-xl p-2">
        <option value="">—</option>
        <?php foreach ($cursos as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= isset($prospecto['curso_id']) && (int) $prospecto['curso_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= h($c['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium">Etiquetas (coma)</label>
      <input name="prospecto_etiquetas" value="<?= h($prospecto['prospecto_etiquetas'] ?? '') ?>"
        class="mt-1 w-full border rounded-xl p-2" placeholder="DAW, DAM, front, backend...">
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
    <div>
      <label class="block text-sm font-medium">Origen</label>
      <?php $org = $prospecto['origen'] ?? 'manual'; ?>
      <select name="origen" class="mt-1 w-full border rounded-xl p-2">
        <?php foreach (['manual', 'busqueda', 'import'] as $opt): ?>
          <option value="<?= $opt ?>" <?= $org === $opt ? 'selected' : '' ?>><?= ucfirst($opt) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="md:col-span-2">
      <label class="block text-sm font-medium">Fuente (URL)</label>
      <input name="fuente_url" value="<?= h($prospecto['fuente_url'] ?? '') ?>"
        class="mt-1 w-full border rounded-xl p-2" placeholder="https://resultados-busqueda/...">
    </div>
  </div>

  <div>
    <label class="block text-sm font-medium">Notas</label>
    <textarea name="notas" rows="4"
      class="mt-1 w-full border rounded-xl p-2"><?= h($prospecto['notas'] ?? '') ?></textarea>
  </div>

  <?php if ($isAdmin): ?>
    <div>
      <label class="block text-sm font-medium">Asignado a (profesor)</label>
      <?php
      $profs = $pdo->query("SELECT id, nombre FROM usuarios WHERE rol IN ('admin','profesor') ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
      $asig = $prospecto['asignado_profesor_id'] ?? $profId;
      ?>
      <select name="asignado_profesor_id" class="mt-1 w-full border rounded-xl p-2">
        <?php foreach ($profs as $p): ?>
          <option value="<?= (int) $p['id'] ?>" <?= (int) $asig === (int) $p['id'] ? 'selected' : '' ?>><?= h($p['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  <?php endif; ?>

  <div class="flex gap-2">
    <button class="rounded-xl bg-black text-white px-4 py-2">Guardar</button>
    <a href="./index.php" class="rounded-xl px-4 py-2 border">Volver</a>
  </div>
</form>

<?php if ($id): ?>
  <section id="contactos" class="bg-white p-6 rounded-2xl shadow mt-8">
    <h2 class="font-semibold mb-3">Histórico de contactos</h2>

    <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-6">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="accion" value="guardar_contacto">
      <input type="hidden" name="prospecto_id" value="<?= (int) $id ?>">

      <div>
        <label class="block text-sm font-medium">Fecha</label>
        <input type="datetime-local" name="fecha" class="mt-1 w-full border rounded-xl p-2">
      </div>

      <div>
        <label class="block text-sm font-medium">Canal</label>
        <select name="canal" class="mt-1 w-full border rounded-xl p-2">
          <?php foreach (['llamada', 'email', 'visita', 'reunión', 'whatsapp', 'otros'] as $opt): ?>
            <option value="<?= h($opt) ?>"><?= ucfirst($opt) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm font-medium">Asunto *</label>
        <input name="asunto" required class="mt-1 w-full border rounded-xl p-2">
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm font-medium">Resumen</label>
        <textarea name="resumen" rows="3" class="mt-1 w-full border rounded-xl p-2"></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium">Resultado</label>
        <input name="resultado" class="mt-1 w-full border rounded-xl p-2">
      </div>

      <div>
        <label class="block text-sm font-medium">Próxima acción</label>
        <input name="proxima_accion" class="mt-1 w-full border rounded-xl p-2">
      </div>

      <div class="flex items-center gap-2 md:col-span-2">
        <input type="hidden" name="confidencial" value="0">
        <input type="checkbox" id="confidencial" name="confidencial" value="1">
        <label for="confidencial" class="text-sm">Confidencial</label>
      </div>

      <div class="md:col-span-2">
        <button class="rounded-xl bg-black text-white px-4 py-2">Añadir contacto</button>
      </div>
    </form>

    <div class="overflow-x-auto border rounded-2xl">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="text-left p-3">Fecha</th>
            <th class="text-left p-3">Canal</th>
            <th class="text-left p-3">Asunto</th>
            <th class="text-left p-3">Resultado</th>
            <th class="text-left p-3">Próx. acción</th>
            <th class="text-left p-3">Autor</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$contactos): ?>
            <tr>
              <td colspan="6" class="p-3 text-gray-500">Sin contactos todavía.</td>
            </tr>
          <?php else:
            foreach ($contactos as $c): ?>
              <tr class="border-t">
                <td class="p-3"><?= h($c['fecha']) ?></td>
                <td class="p-3"><?= h($c['canal']) ?></td>
                <td class="p-3">
                  <div class="font-medium"><?= h($c['asunto']) ?></div>
                  <?php if (!empty($c['resumen'])): ?>
                    <div class="text-gray-600"><?= nl2br(h($c['resumen'])) ?></div>
                  <?php endif; ?>
                  <?php if ((int) $c['confidencial'] === 1): ?>
                    <span class="inline-block mt-1 text-xs bg-amber-100 text-amber-800 px-2 py-0.5 rounded">Confidencial</span>
                  <?php endif; ?>
                </td>
                <td class="p-3"><?= h($c['resultado'] ?? '') ?></td>
                <td class="p-3"><?= h($c['proxima_accion'] ?? '') ?></td>
                <td class="p-3"><?= h($c['autor_nombre'] ?? '—') ?></td>
              </tr>
            <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php endif; ?>
</main>
<?php require_once __DIR__ . '/../partials/_footer.php'; ?>