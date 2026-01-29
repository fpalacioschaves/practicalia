<?php
// practicalia/public/empresas/edit.php
declare(strict_types=1);

/*
  Esta vista:
  - Muestra alumnos asociados a la empresa.
  - Para cada alumno, lista sus asignaturas dualizadas en la empresa.
  - Para cada asignatura, muestra una tabla de RAs con checkboxes para marcar qué RAs se dualizan en la empresa.
  - Guarda la selección en `empresa_alumno_ras (empresa_id, alumno_id, ra_id)`.
*/

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

$user = current_user();
$isAdmin = require_role('admin'); // <- devuelve true si el usuario tiene rol admin
$profId  = (int)($user['id'] ?? 0);

$idGet  = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$idPost = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$id     = $idPost ?: $idGet;
if (!$id || $id <= 0) { http_response_code(400); exit('ID inválido'); }

$error = '';
$okMsg = '';

/** Cursos disponibles (según rol) */
if ($isAdmin) {
  $cursos = $pdo->query("SELECT id, nombre FROM cursos ORDER BY nombre")->fetchAll();
} else {
  $st = $pdo->prepare("
    SELECT c.id, c.nombre
    FROM cursos c
    JOIN cursos_profesores cp ON cp.curso_id = c.id AND cp.profesor_id = :pid
    ORDER BY c.nombre
  ");
  $st->execute([':pid'=>$profId]);
  $cursos = $st->fetchAll();
}

/* ============================================================
   ACCIONES POST — EMPRESA
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'guardar_empresa') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $nombre    = trim($_POST['nombre'] ?? '');
    $cif       = trim($_POST['cif'] ?? '');
    $nif       = trim($_POST['nif'] ?? '');
    $sector    = trim($_POST['sector'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $web       = trim($_POST['web'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $ciudad    = trim($_POST['ciudad'] ?? '');
    $provincia = trim($_POST['provincia'] ?? '');
    $cp        = trim($_POST['codigo_postal'] ?? '');
    $activo    = (isset($_POST['activo']) && $_POST['activo'] === '1') ? 1 : 0;

    // NUEVO: persona de contacto de tutoría en empresa
    $responsable_nombre   = trim($_POST['responsable_nombre'] ?? '');
    $responsable_cargo    = trim($_POST['responsable_cargo'] ?? '');
    $responsable_email    = trim($_POST['responsable_email'] ?? '');
    $responsable_telefono = trim($_POST['responsable_telefono'] ?? '');

    // >>> NUEVO: varios cursos seleccionados
    $cursosIds = array_values(array_unique(array_filter(array_map('intval', $_POST['cursos_ids'] ?? []))));

    if ($nombre === '') throw new RuntimeException('El nombre es obligatorio.');
    if (count($cursosIds) === 0) throw new RuntimeException('Selecciona al menos un curso.');
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email no válido.');
    if ($responsable_email !== '' && !filter_var($responsable_email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email del responsable no es válido.');
    if ($cp !== '' && !preg_match('/^[0-9A-Za-z -]{3,10}$/', $cp)) throw new RuntimeException('Código postal no válido.');

    if ($nif !== '') {
      $stN = $pdo->prepare('SELECT 1 FROM empresas WHERE nif = :nif AND id <> :id AND deleted_at IS NULL LIMIT 1');
      $stN->execute([':nif'=>$nif, ':id'=>$id]);
      if ($stN->fetch()) throw new RuntimeException('Ya existe otra empresa con ese NIF.');
    }

    // Validar cursos existentes y permisos por rol
    $inMarks = implode(',', array_fill(0, count($cursosIds), '?'));
    $stChkC  = $pdo->prepare("SELECT id FROM cursos WHERE id IN ($inMarks)");
    $stChkC->execute($cursosIds);
    $existentes = array_map('intval', $stChkC->fetchAll(PDO::FETCH_COLUMN));
    $cursosIds  = array_values(array_intersect($cursosIds, $existentes));
    if (count($cursosIds) === 0) throw new RuntimeException('Los cursos seleccionados no existen.');

    if (!$isAdmin) {
      $stChkP = $pdo->prepare("
        SELECT curso_id
        FROM cursos_profesores
        WHERE profesor_id = ? AND curso_id IN ($inMarks)
      ");
      $stChkP->bindValue(1, $profId, PDO::PARAM_INT);
      foreach ($cursosIds as $i => $val) {
        $stChkP->bindValue($i + 2, $val, PDO::PARAM_INT);
      }
      $stChkP->execute();
      $permisos = array_map('intval', $stChkP->fetchAll(PDO::FETCH_COLUMN));
      sort($permisos); sort($cursosIds);
      if ($permisos !== $cursosIds) throw new RuntimeException('No puedes seleccionar alguno de los cursos marcados.');
    }

    $st = $pdo->prepare('
      UPDATE empresas SET
        nombre=:nombre, cif=:cif, nif=:nif, email=:email, telefono=:tel, web=:web,
        direccion=:dir, ciudad=:ciudad, provincia=:provincia, codigo_postal=:cp,
        sector=:sector,
        responsable_nombre=:rnom,
        responsable_cargo=:rcargo,
        responsable_email=:remail,
        responsable_telefono=:rtel,
        activo=:activo
      WHERE id=:id
    ');
    $st->execute([
      ':nombre'=>$nombre,
      ':cif'=>($cif!==''?$cif:null),
      ':nif'=>($nif!==''?$nif:null),
      ':email'=>($email!==''?$email:null),
      ':tel'=>($telefono!==''?$telefono:null),
      ':web'=>($web!==''?$web:null),
      ':dir'=>($direccion!==''?$direccion:null),
      ':ciudad'=>($ciudad!==''?$ciudad:null),
      ':provincia'=>($provincia!==''?$provincia:null),
      ':cp'=>($cp!==''?$cp:null),
      ':sector'=>($sector!==''?$sector:null),

      ':rnom'=>($responsable_nombre!==''?$responsable_nombre:null),
      ':rcargo'=>($responsable_cargo!==''?$responsable_cargo:null),
      ':remail'=>($responsable_email!==''?$responsable_email:null),
      ':rtel'=>($responsable_telefono!==''?$responsable_telefono:null),

      ':activo'=>$activo,
      ':id'=>$id
    ]);

    // >>> NUEVO: actualizar M:N con varios cursos
    $pdo->prepare('DELETE FROM empresa_cursos WHERE empresa_id = :e')->execute([':e'=>$id]);
    $stRel = $pdo->prepare('INSERT INTO empresa_cursos (empresa_id, curso_id) VALUES (:e,:c)');
    foreach ($cursosIds as $cid) {
      $stRel->execute([':e'=>$id, ':c'=>$cid]);
    }

    $okMsg = 'Empresa guardada.';
    header('Location: ./edit.php?id='.$id.'&ok=1');
    exit;

  } catch (Throwable $e) { $error = $e->getMessage(); }
}

/* ============================================================
   ACCIONES POST — CONTACTOS DE EMPRESA
   ============================================================ */
$canalesPermitidos = ['llamada','email','visita','reunión','whatsapp','otros'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'guardar_contacto') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $empresaIdForm = (int)($_POST['empresa_id'] ?? 0);
    if ($empresaIdForm !== $id) throw new RuntimeException('Empresa inválida.');

    $fechaRaw = trim($_POST['fecha'] ?? '');
    // acepta 'YYYY-MM-DDTHH:MM' de <input type="datetime-local">
    $fecha = $fechaRaw !== '' ? str_replace('T', ' ', $fechaRaw) . (strlen($fechaRaw) === 16 ? ':00' : '') : null;

    $canal  = trim($_POST['canal'] ?? '');
    $asunto = trim($_POST['asunto'] ?? '');
    $resumen = trim($_POST['resumen'] ?? '');
    $resultado = trim($_POST['resultado'] ?? '');
    $prox = trim($_POST['proxima_accion'] ?? '');
    $confidencial = (isset($_POST['confidencial']) && $_POST['confidencial'] === '1') ? 1 : 0;

    if (!in_array($canal, $canalesPermitidos, true)) $canal = 'otros';
    if ($asunto === '') throw new RuntimeException('El asunto es obligatorio.');

    $st = $pdo->prepare("
      INSERT INTO contactos_empresa
        (empresa_id, usuario_id, fecha, canal, asunto, resumen, resultado, proxima_accion, confidencial)
      VALUES
        (:e, :u, COALESCE(:f, NOW()), :c, :a, :r, :res, :p, :conf)
    ");
    $st->execute([
      ':e'=>$id, ':u'=>$profId,
      ':f'=>$fecha, ':c'=>$canal, ':a'=>$asunto,
      ':r'=>($resumen!==''?$resumen:null),
      ':res'=>($resultado!==''?$resultado:null),
      ':p'=>($prox!==''?$prox:null),
      ':conf'=>$confidencial
    ]);

    header('Location: ./edit.php?id='.$id.'&ok=1#contactos');
    exit;

  } catch (Throwable $e) { $error = $e->getMessage(); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar_contacto') {
  try {
    csrf_check($_POST['csrf'] ?? null);
    $contactoId = (int)($_POST['contacto_id'] ?? 0);
    if ($contactoId <= 0) throw new RuntimeException('Contacto inválido.');

    // Cargar para verificar permisos y pertenencia
    $st = $pdo->prepare("SELECT id, empresa_id, usuario_id FROM contactos_empresa WHERE id=:id LIMIT 1");
    $st->execute([':id'=>$contactoId]);
    $c = $st->fetch(PDO::FETCH_ASSOC);
    if (!$c || (int)$c['empresa_id'] !== $id) throw new RuntimeException('Contacto no encontrado.');

    $esAutor = ((int)$c['usuario_id'] === $profId);
    if (!$isAdmin && !$esAutor) throw new RuntimeException('No tienes permiso para borrar este contacto.');

    $pdo->prepare("DELETE FROM contactos_empresa WHERE id=:id")->execute([':id'=>$contactoId]);

    header('Location: ./edit.php?id='.$id.'&ok=1#contactos');
    exit;

  } catch (Throwable $e) { $error = $e->getMessage(); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'actualizar_contacto') {
  try {
    csrf_check($_POST['csrf'] ?? null);
    $contactoId = (int)($_POST['contacto_id'] ?? 0);
    if ($contactoId <= 0) throw new RuntimeException('Contacto inválido.');

    $st = $pdo->prepare("SELECT id, empresa_id, usuario_id FROM contactos_empresa WHERE id=:id LIMIT 1");
    $st->execute([':id'=>$contactoId]);
    $c = $st->fetch(PDO::FETCH_ASSOC);
    if (!$c || (int)$c['empresa_id'] !== $id) throw new RuntimeException('Contacto no encontrado.');

    $esAutor = ((int)$c['usuario_id'] === $profId);
    if (!$isAdmin && !$esAutor) throw new RuntimeException('No tienes permiso para editar este contacto.');

    $fechaRaw = trim($_POST['fecha'] ?? '');
    $fecha = $fechaRaw !== '' ? str_replace('T', ' ', $fechaRaw) . (strlen($fechaRaw) === 16 ? ':00' : '') : null;

    $canal  = trim($_POST['canal'] ?? '');
    $asunto = trim($_POST['asunto'] ?? '');
    $resumen = trim($_POST['resumen'] ?? '');
    $resultado = trim($_POST['resultado'] ?? '');
    $prox = trim($_POST['proxima_accion'] ?? '');
    $confidencial = (isset($_POST['confidencial']) && $_POST['confidencial'] === '1') ? 1 : 0;

    if (!in_array($canal, $canalesPermitidos, true)) $canal = 'otros';
    if ($asunto === '') throw new RuntimeException('El asunto es obligatorio.');

    $st = $pdo->prepare("
      UPDATE contactos_empresa SET
        fecha = COALESCE(:f, fecha),
        canal = :c,
        asunto = :a,
        resumen = :r,
        resultado = :res,
        proxima_accion = :p,
        confidencial = :conf
      WHERE id = :id
    ");
    $st->execute([
      ':id'=>$contactoId,
      ':f'=>$fecha, ':c'=>$canal, ':a'=>$asunto,
      ':r'=>($resumen!==''?$resumen:null),
      ':res'=>($resultado!==''?$resultado:null),
      ':p'=>($prox!==''?$prox:null),
      ':conf'=>$confidencial
    ]);

    header('Location: ./edit.php?id='.$id.'&ok=1#contactos');
    exit;

  } catch (Throwable $e) { $error = $e->getMessage(); }
}

/* ============================================================
   >>> NUEVO — GUARDAR RAs (por alumno y asignatura)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'guardar_ras') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $empresaIdForm    = (int)($_POST['empresa_id'] ?? 0);
    $alumnoIdForm     = (int)($_POST['alumno_id'] ?? 0);
    $asignaturaIdForm = (int)($_POST['asignatura_id'] ?? 0);
    $seleccion        = array_map('intval', array_filter((array)($_POST['ras'] ?? [])));

    if ($empresaIdForm !== $id) throw new RuntimeException('Empresa inválida.');
    if ($alumnoIdForm <= 0 || $asignaturaIdForm <= 0) throw new RuntimeException('Parámetros incompletos.');

    // comprobar relación empresa-alumno
    $stChk = $pdo->prepare("SELECT 1 FROM empresa_alumnos WHERE empresa_id=:e AND alumno_id=:a LIMIT 1");
    $stChk->execute([':e'=>$empresaIdForm, ':a'=>$alumnoIdForm]);
    if (!$stChk->fetch()) throw new RuntimeException('El alumno no pertenece a esta empresa.');

    // comprobar tabla de destino
    $hasEAR = (bool)$pdo->query("
      SELECT 1
      FROM information_schema.tables
      WHERE table_schema = DATABASE() AND table_name = 'empresa_alumno_ras'
      LIMIT 1
    ")->fetchColumn();
    if (!$hasEAR) throw new RuntimeException('Falta la tabla empresa_alumno_ras.');

    // borrar existentes de ESA ASIGNATURA (todos los ra_id cuyo RA pertenezca a la asignatura dada)
    $pdo->beginTransaction();
    $stDel = $pdo->prepare("
      DELETE ear
      FROM empresa_alumno_ras ear
      JOIN asignatura_ras ar ON ar.id = ear.ra_id
      WHERE ear.empresa_id=:e AND ear.alumno_id=:a AND ar.asignatura_id=:asig
    ");
    $stDel->execute([':e'=>$empresaIdForm, ':a'=>$alumnoIdForm, ':asig'=>$asignaturaIdForm]);

    // insertar los seleccionados
    if ($seleccion) {
      $stIns = $pdo->prepare("
        INSERT INTO empresa_alumno_ras (empresa_id, alumno_id, ra_id)
        VALUES (:e,:a,:ra)
      ");
      foreach ($seleccion as $raId) {
        $stIns->execute([':e'=>$empresaIdForm, ':a'=>$alumnoIdForm, ':ra'=>$raId]);
      }
    }

    $pdo->commit();
    header('Location: ./edit.php?id='.$id.'&ok=1#alumnos');
    exit;

  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $error = $e->getMessage();
  }
}

/** Cargar empresa */
$stE = $pdo->prepare('SELECT * FROM empresas WHERE id = :id AND deleted_at IS NULL LIMIT 1');
$stE->execute([':id'=>$id]);
$empresa = $stE->fetch();
if (!$empresa) { http_response_code(404); exit('Empresa no encontrada'); }

/** Cursos actualmente vinculados (pueden ser varios) */
$stEC = $pdo->prepare('SELECT curso_id FROM empresa_cursos WHERE empresa_id = :e ORDER BY curso_id');
$stEC->execute([':e'=>$id]);
$cursosActuales = array_map('intval', $stEC->fetchAll(PDO::FETCH_COLUMN));

/** Cargar contactos (últimos 50) */
$stC = $pdo->prepare("
  SELECT ce.*, u.nombre AS autor_nombre
  FROM contactos_empresa ce
  LEFT JOIN usuarios u ON u.id = ce.usuario_id
  WHERE ce.empresa_id = :e
  ORDER BY ce.fecha DESC, ce.id DESC
  LIMIT 50
");
$stC->execute([':e'=>$id]);
$contactos = $stC->fetchAll(PDO::FETCH_ASSOC);

/** Si se va a editar un contacto */
$editContactoId = filter_input(INPUT_GET, 'edit_contacto', FILTER_VALIDATE_INT);
$contactoEdit = null;
if ($editContactoId) {
  foreach ($contactos as $row) {
    if ((int)$row['id'] === (int)$editContactoId) { $contactoEdit = $row; break; }
  }
}

/* ============================================================
   >>> NUEVO — ALUMNOS + ASIGNATURAS + RAs
   ============================================================ */
$hasEAR = (bool)$pdo->query("
  SELECT 1
  FROM information_schema.tables
  WHERE table_schema = DATABASE() AND table_name = 'empresa_alumno_ras'
  LIMIT 1
")->fetchColumn();

/** Alumnos de la empresa */
$stA = $pdo->prepare("
  SELECT a.id, a.nombre, a.apellidos, a.email
  FROM empresa_alumnos ea
  JOIN alumnos a ON a.id = ea.alumno_id
  WHERE ea.empresa_id = :e
  ORDER BY a.apellidos, a.nombre
");
$stA->execute([':e' => $id]);
$alumnosEmpresa = $stA->fetchAll(PDO::FETCH_ASSOC);

/** Asignaturas dualizadas por alumno en esta empresa */
$stAsigs = $pdo->prepare("
  SELECT eaa.asignatura_id, asig.nombre
  FROM empresa_alumnos_asignaturas eaa
  JOIN asignaturas asig ON asig.id = eaa.asignatura_id
  WHERE eaa.empresa_id = :e AND eaa.alumno_id = :a
  ORDER BY asig.nombre
");

/** RAs por asignatura */
$stRAs = $pdo->prepare("
  SELECT ar.id, ar.codigo, ar.titulo
  FROM asignatura_ras ar
  WHERE ar.asignatura_id = :asig
  ORDER BY COALESCE(ar.orden, ar.id)
");

/** RAs ya marcados para (empresa, alumno, asignatura) -> devuelve ra_id */
$stMarcados = $hasEAR ? $pdo->prepare("
  SELECT ear.ra_id AS id
  FROM empresa_alumno_ras ear
  JOIN asignatura_ras ar ON ar.id = ear.ra_id
  WHERE ear.empresa_id = :e AND ear.alumno_id = :a AND ar.asignatura_id = :asig
") : null;

/** Pre-carga en arrays (para simplificar la vista) */
$asignaturasPorAlumno = [];
$rasPorAsignatura     = [];    // cache por asignatura_id
$marcadosClave        = [];    // clave: alumno_id|asignatura_id => set[id]=true

foreach ($alumnosEmpresa as $al) {
  $stAsigs->execute([':e'=>$id, ':a'=>$al['id']]);
  $asignaturas = $stAsigs->fetchAll(PDO::FETCH_ASSOC);
  $asignaturasPorAlumno[$al['id']] = $asignaturas;

  foreach ($asignaturas as $as) {
    $asigId = (int)$as['asignatura_id'];

    if (!isset($rasPorAsignatura[$asigId])) {
      $stRAs->execute([':asig'=>$asigId]);
      $rasPorAsignatura[$asigId] = $stRAs->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($hasEAR) {
      $stMarcados->execute([':e'=>$id, ':a'=>$al['id'], ':asig'=>$asigId]);
      $rows = $stMarcados->fetchAll(PDO::FETCH_COLUMN);
      $key = $al['id'].'|'.$asigId;
      $marcadosClave[$key] = [];
      foreach ($rows as $raId) $marcadosClave[$key][(int)$raId] = true;
    }
  }
}

/** Mapa */
$addrParts = array_filter([
  $empresa['direccion'] ?? '', $empresa['codigo_postal'] ?? '',
  $empresa['ciudad'] ?? '', $empresa['provincia'] ?? '',
]);
$direccionCompleta = implode(', ', $addrParts);
$mapSrc  = $direccionCompleta ? 'https://www.google.com/maps?q='.urlencode($direccionCompleta).'&output=embed' : null;
$mapLink = $direccionCompleta ? 'https://www.google.com/maps/search/?api=1&query='.urlencode($direccionCompleta) : null;

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function dtlocal(?string $mysqlDt): string {
  if (!$mysqlDt) return '';
  // mysql "YYYY-MM-DD HH:MM:SS" -> input datetime-local "YYYY-MM-DDTHH:MM"
  return str_replace(' ', 'T', substr($mysqlDt, 0, 16));
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Editar empresa — Practicalia</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
  <?php require_once __DIR__ . '/../partials/menu.php'; ?>

  <main class="max-w-6xl mx-auto p-4">
    <h1 class="text-xl font-semibold mb-2">
      Editar empresa #<?= (int)$empresa['id'] ?> — <?= h($empresa['nombre']) ?>
    </h1>

    <!-- Cabecera: contacto responsable (si existe) -->
    <?php if (!empty($empresa['responsable_nombre'])): ?>
      <div class="mb-4 text-sm text-gray-700">
        <span class="font-medium">Contacto:</span>
        <?= h($empresa['responsable_nombre']) ?>
        <?php if (!empty($empresa['responsable_cargo'])): ?>
          — <?= h($empresa['responsable_cargo']) ?>
        <?php endif; ?>
        <?php if (!empty($empresa['responsable_email'])): ?>
          · <a class="underline" href="mailto:<?= h($empresa['responsable_email']) ?>"><?= h($empresa['responsable_email']) ?></a>
        <?php endif; ?>
        <?php if (!empty($empresa['responsable_telefono'])): ?>
          · <span><?= h($empresa['responsable_telefono']) ?></span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="mb-3 bg-red-50 text-red-700 p-3 rounded"><?= h($error) ?></div>
    <?php elseif(isset($_GET['ok'])): ?>
      <div class="mb-3 bg-green-50 text-green-700 p-3 rounded">Guardado correctamente.</div>
    <?php endif; ?>

    <!-- FORM EMPRESA -->
    <form method="post" class="bg-white p-6 rounded-2xl shadow space-y-4 mb-8">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="id" value="<?= (int)$empresa['id'] ?>">
      <input type="hidden" name="accion" value="guardar_empresa">

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium">Nombre *</label>
          <input name="nombre" value="<?= h($empresa['nombre']) ?>" required class="mt-1 w-full border rounded-xl p-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Sector</label>
          <input name="sector" value="<?= h($empresa['sector'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2">
        </div>
      </div>

      <!-- NUEVO: Persona responsable de la tutoría -->
      <div class="pt-4 border-t" style="border:1px solid #ccc; padding: 10px; border-radius: 8px;">
        <h3 class="font-semibold mb-2">Persona de contacto (tutor/a en la empresa)</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium">Nombre y apellidos</label>
            <input name="responsable_nombre"
                   value="<?= h($empresa['responsable_nombre'] ?? '') ?>"
                   class="mt-1 w-full border rounded-xl p-2" placeholder="Ej.: Ana García Ruiz">
          </div>
          <div>
            <label class="block text-sm font-medium">Cargo</label>
            <input name="responsable_cargo"
                   value="<?= h($empresa['responsable_cargo'] ?? '') ?>"
                   class="mt-1 w-full border rounded-xl p-2" placeholder="Ej.: Responsable de RR.HH.">
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
          <div>
            <label class="block text-sm font-medium">Email</label>
            <input type="email" name="responsable_email"
                   value="<?= h($empresa['responsable_email'] ?? '') ?>"
                   class="mt-1 w-full border rounded-xl p-2" placeholder="ana.garcia@empresa.com">
          </div>
          <div>
            <label class="block text-sm font-medium">Teléfono</label>
            <input name="responsable_telefono"
                   value="<?= h($empresa['responsable_telefono'] ?? '') ?>"
                   class="mt-1 w-full border rounded-xl p-2" placeholder="+34 600 000 000">
          </div>
        </div>
      </div>
      <!-- /NUEVO -->

      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
          <label class="block text-sm font-medium">CIF</label>
          <input name="cif" value="<?= h($empresa['cif'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2">
        </div>
        <div>
          <label class="block text-sm font-medium">NIF</label>
          <input name="nif" value="<?= h($empresa['nif'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Teléfono</label>
          <input name="telefono" value="<?= h($empresa['telefono'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2">
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
          <label class="block text-sm font-medium">Email</label>
          <input name="email" type="email" value="<?= h($empresa['email'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Web</label>
          <input name="web" value="<?= h($empresa['web'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2" placeholder="https://...">
        </div>
        <div>
          <label class="block text-sm font-medium">Código postal</label>
          <input name="codigo_postal" value="<?= h($empresa['codigo_postal'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium">Dirección</label>
        <input name="direccion" value="<?= h($empresa['direccion'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2">
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium">Ciudad</label>
          <input name="ciudad" value="<?= h($empresa['ciudad'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Provincia</label>
          <input name="provincia" value="<?= h($empresa['provincia'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2">
        </div>
      </div>

      <!-- >>> NUEVO: Selección múltiple de cursos con checkboxes -->
      <div>
        <div class="flex items-center justify-between mb-1">
          <label class="block text-sm font-medium">Cursos/Grados asociados *</label>
          <div class="text-xs">
            <button type="button" class="underline mr-2" onclick="marcarTodos(true)">Marcar todos</button>
            <button type="button" class="underline" onclick="marcarTodos(false)">Desmarcar</button>
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-64 overflow-auto p-2 border rounded-xl">
          <?php foreach ($cursos as $c): $cid=(int)$c['id']; ?>
            <label class="inline-flex items-center gap-2">
              <input type="checkbox" name="cursos_ids[]" value="<?= $cid ?>"
                     <?= in_array($cid, $cursosActuales, true) ? 'checked' : '' ?>>
              <span class="text-sm"><?= h($c['nombre']) ?></span>
            </label>
          <?php endforeach; ?>
          <?php if (!$cursos): ?>
            <div class="text-sm text-gray-500">No hay cursos disponibles</div>
          <?php endif; ?>
        </div>
        <p class="text-xs text-gray-500 mt-1">Puedes seleccionar uno o varios.</p>
      </div>
      <!-- <<< FIN NUEVO -->

      <div class="flex items-center gap-2">
        <input type="hidden" name="activo" value="0">
        <input type="checkbox" name="activo" id="activo" value="1" <?= ((int)($empresa['activo'] ?? 1)===1)?'checked':'' ?>>
        <label for="activo" class="text-sm">Empresa activa</label>
      </div>

      <div class="flex gap-2">
        <button class="rounded-xl bg-black text-white px-4 py-2">Guardar</button>
        <a href="./index.php" class="rounded-xl px-4 py-2 border">Volver</a>
      </div>
    </form>

    <!-- >>> ALUMNOS + ASIGNATURAS + RAs -->
    <section id="alumnos" class="bg-white p-6 rounded-2xl shadow mb-8">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold">Alumnos asociados y RAs dualizados</h2>
        <div class="text-sm text-gray-600">
          <?= count($alumnosEmpresa) ?> alumno<?= count($alumnosEmpresa)===1?'':'s' ?>
        </div>
      </div>

      <?php if (!$alumnosEmpresa): ?>
        <p class="text-gray-500">No hay alumnos asociados.</p>
      <?php else: ?>
        <div class="space-y-6">
          <?php foreach ($alumnosEmpresa as $al): ?>
            <div class="border rounded-2xl">
              <div class="p-4 flex items-center justify-between bg-gray-50 rounded-t-2xl">
                <div>
                  <div class="font-medium"><?= h($al['apellidos'] . ', ' . $al['nombre']) ?></div>
                  <div class="text-xs text-gray-500 flex items-center gap-2">
                    <span>#<?= (int)$al['id'] ?></span>
                    <?php if (!empty($al['email'])): ?>
                      <a class="underline" href="mailto:<?= h($al['email']) ?>"><?= h($al['email']) ?></a>
                    <?php endif; ?>
                  </div>
                </div>
                <a class="px-3 py-1 rounded border" href="../alumnos/edit.php?id=<?= (int)$al['id'] ?>">Ver ficha</a>
              </div>

              <div class="p-4 space-y-5">
                <?php
                  $asignaturas = $asignaturasPorAlumno[$al['id']] ?? [];
                ?>
                <?php if (!$asignaturas): ?>
                  <p class="text-gray-500">Este alumno no tiene asignaturas dualizadas en esta empresa.</p>
                <?php else: foreach ($asignaturas as $as): ?>
                  <?php
                    $asigId  = (int)$as['asignatura_id'];
                    $ras     = $rasPorAsignatura[$asigId] ?? [];
                    $keyMarc = $al['id'].'|'.$asigId;
                    $marcados = $marcadosClave[$keyMarc] ?? [];
                  ?>
                  <div class="rounded-xl border">
                    <div class="px-4 py-3 bg-gray-50 rounded-t-xl flex items-center justify-between">
                      <h3 class="font-medium">Asignatura: <?= h($as['nombre']) ?></h3>
                      <?php if (!$hasEAR): ?>
                        <span class="text-xs text-amber-700 bg-amber-100 px-2 py-0.5 rounded">No existe la tabla empresa_alumno_ras</span>
                      <?php endif; ?>
                    </div>

                    <div class="p-4">
                      <?php if (!$ras): ?>
                        <p class="text-gray-500">Sin RAs definidos para esta asignatura.</p>
                      <?php else: ?>
                        <form method="post" class="space-y-3">
                          <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                          <input type="hidden" name="id" value="<?= (int)$empresa['id'] ?>">
                          <input type="hidden" name="empresa_id" value="<?= (int)$empresa['id'] ?>">
                          <input type="hidden" name="alumno_id" value="<?= (int)$al['id'] ?>">
                          <input type="hidden" name="asignatura_id" value="<?= $asigId ?>">
                          <input type="hidden" name="accion" value="guardar_ras">

                          <div class="overflow-x-auto border rounded-xl">
                            <table class="min-w-full text-sm">
                              <thead class="bg-gray-50">
                                <tr>
                                  <th class="text-left p-3">Código</th>
                                  <th class="text-left p-3">Resultado de aprendizaje</th>
                                  <th class="text-left p-3 w-28">Dualiza</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php foreach ($ras as $ra): $checked = isset($marcados[(int)$ra['id']]); ?>
                                  <tr class="border-t">
                                    <td class="p-3 whitespace-nowrap font-medium"><?= h($ra['codigo'] ?? ('RA '.(int)$ra['id'])) ?></td>
                                    <td class="p-3"><?= h($ra['titulo']) ?></td>
                                    <td class="p-3">
                                      <?php if ($hasEAR): ?>
                                        <input type="checkbox" name="ras[]" value="<?= (int)$ra['id'] ?>" <?= $checked ? 'checked' : '' ?>>
                                      <?php else: ?>
                                        <span class="text-gray-400">n/d</span>
                                      <?php endif; ?>
                                    </td>
                                  </tr>
                                <?php endforeach; ?>
                              </tbody>
                            </table>
                          </div>

                          <?php if ($hasEAR): ?>
                            <div>
                              <button class="rounded-xl bg-black text-white px-4 py-2">Guardar RAs</button>
                            </div>
                          <?php endif; ?>
                        </form>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
    <!-- <<< FIN NUEVO -->

    <!-- MAPA -->
    <section class="bg-white p-6 rounded-2xl shadow mb-8">
      <h2 class="font-semibold mb-3">Mapa</h2>
      <?php if ($mapSrc): ?>
        <div class="aspect-video w-full overflow-hidden rounded-2xl border">
          <iframe src="<?= h($mapSrc) ?>" class="w-full h-full" style="border:0;" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
        </div>
        <div class="mt-2 text-sm">
          <span class="text-gray-600"><?= h($direccionCompleta) ?></span>
          · <a href="<?= h($mapLink) ?>" target="_blank" class="underline">Abrir en Google Maps</a>
        </div>
      <?php else: ?>
        <p class="text-sm text-gray-600">Añade dirección, CP, ciudad y provincia para ver el mapa aquí.</p>
      <?php endif; ?>
    </section>

    <!-- HISTÓRICO DE CONTACTOS -->
    <section id="contactos" class="bg-white p-6 rounded-2xl shadow mb-8">
      <div class="flex items-center justify-between mb-3">
        <h2 class="font-semibold">Histórico de contactos</h2>
      </div>

      <!-- Formulario alta / edición -->
      <?php
        $isEdit = (bool)$contactoEdit;
        $formAction = $isEdit ? 'actualizar_contacto' : 'guardar_contacto';
      ?>
      <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-6">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int)$empresa['id'] ?>">
        <input type="hidden" name="empresa_id" value="<?= (int)$empresa['id'] ?>">
        <input type="hidden" name="accion" value="<?= $formAction ?>">
        <?php if ($isEdit): ?>
          <input type="hidden" name="contacto_id" value="<?= (int)$contactoEdit['id'] ?>">
        <?php endif; ?>

        <div>
          <label class="block text-sm font-medium">Fecha</label>
          <input type="datetime-local" name="fecha"
                 value="<?= h($isEdit ? dtlocal($contactoEdit['fecha']) : '') ?>"
                 class="mt-1 w-full border rounded-xl p-2">
        </div>

        <div>
          <label class="block text-sm font-medium">Canal</label>
          <select name="canal" class="mt-1 w-full border rounded-xl p-2">
            <?php
              $valCanal = $isEdit ? (string)$contactoEdit['canal'] : 'otros';
              foreach ($canalesPermitidos as $opt):
            ?>
              <option value="<?= h($opt) ?>" <?= $opt===$valCanal?'selected':'' ?>><?= h(ucfirst($opt)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium">Asunto *</label>
          <input name="asunto" required value="<?= h($isEdit ? $contactoEdit['asunto'] : '') ?>" class="mt-1 w-full border rounded-xl p-2">
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium">Resumen</label>
          <textarea name="resumen" rows="3" class="mt-1 w-full border rounded-xl p-2"><?= h($isEdit ? (string)$contactoEdit['resumen'] : '') ?></textarea>
        </div>

        <div>
          <label class="block text-sm font-medium">Resultado</label>
          <input name="resultado" value="<?= h($isEdit ? (string)$contactoEdit['resultado'] : '') ?>" class="mt-1 w-full border rounded-xl p-2">
        </div>

        <div>
          <label class="block text-sm font-medium">Próxima acción</label>
          <input name="proxima_accion" value="<?= h($isEdit ? (string)$contactoEdit['proxima_accion'] : '') ?>" class="mt-1 w-full border rounded-xl p-2">
        </div>

        <div class="flex items-center gap-2 md:col-span-2">
          <input type="hidden" name="confidencial" value="0">
          <input type="checkbox" id="confidencial" name="confidencial" value="1"
            <?= $isEdit && (int)$contactoEdit['confidencial'] === 1 ? 'checked' : '' ?>>
          <label for="confidencial" class="text-sm">Confidencial</label>
        </div>

        <div class="md:col-span-2 flex gap-2">
          <button class="rounded-xl bg-black text-white px-4 py-2"><?= $isEdit ? 'Guardar cambios' : 'Añadir contacto' ?></button>
          <?php if ($isEdit): ?>
            <a href="./edit.php?id=<?= (int)$empresa['id'] ?>#contactos" class="rounded-xl px-4 py-2 border">Cancelar</a>
          <?php endif; ?>
        </div>
      </form>

      <!-- Lista -->
      <div class="overflow-x-auto border rounded-2xl">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="text-left p-3">Fecha</th>
              <th class="text-left p-3">Canal</th>
              <th class="text-left p-3">Asunto</th>
              <th class="text-left p-3">Resultado</th>
              <th class="text-left p-3">Próxima acción</th>
              <th class="text-left p-3">Autor</th>
              <th class="text-left p-3">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$contactos): ?>
              <tr><td colspan="7" class="p-3 text-gray-500">Sin contactos todavía.</td></tr>
            <?php else:
              foreach ($contactos as $c):
                $canEdit = $isAdmin || ((int)$c['usuario_id'] === $profId);
            ?>
              <tr class="border-t">
                <td class="p-3"><?= h($c['fecha']) ?></td>
                <td class="p-3"><?= h($c['canal']) ?></td>
                <td class="p-3">
                  <div class="font-medium"><?= h($c['asunto']) ?></div>
                  <?php if (!empty($c['resumen'])): ?>
                    <div class="text-gray-600"><?= nl2br(h($c['resumen'])) ?></div>
                  <?php endif; ?>
                  <?php if ((int)$c['confidencial'] === 1): ?>
                    <span class="inline-block mt-1 text-xs bg-amber-100 text-amber-800 px-2 py-0.5 rounded">Confidencial</span>
                  <?php endif; ?>
                </td>
                <td class="p-3"><?= h($c['resultado'] ?? '') ?></td>
                <td class="p-3"><?= h($c['proxima_accion'] ?? '') ?></td>
                <td class="p-3"><?= h($c['autor_nombre'] ?? '—') ?></td>
                <td class="p-3">
                  <div class="flex gap-2">
                    <?php if ($canEdit): ?>
                      <a class="px-3 py-1 rounded border" href="./edit.php?id=<?= (int)$id ?>&edit_contacto=<?= (int)$c['id'] ?>#contactos">Editar</a>
                      <form method="post" onsubmit="return confirm('¿Eliminar este contacto?')">
                        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$id ?>">
                        <input type="hidden" name="contacto_id" value="<?= (int)$c['id'] ?>">
                        <input type="hidden" name="accion" value="eliminar_contacto">
                        <button class="px-3 py-1 rounded border" type="submit">Eliminar</button>
                      </form>
                    <?php else: ?>
                      <span class="text-gray-400">—</span>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </section>

  </main>

  <script>
  function marcarTodos(valor) {
    document.querySelectorAll('input[name="cursos_ids[]"]').forEach(cb => { cb.checked = valor; });
  }
  </script>
</body>
</html>
