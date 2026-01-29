<?php
// practicalia/public/alumnos/edit.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

$user    = current_user();
$isAdmin = require_role('admin');
$profId  = (int)($user['id'] ?? 0);

$idGet  = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$idPost = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$id     = $idPost ?: $idGet;
if (!$id || $id <= 0) { http_response_code(400); exit('ID inválido'); }

$error = '';
$okMsg = '';

/** Verifica acceso (profe solo ve alumnos de sus cursos) */
if (!$isAdmin) {
  $stChk = $pdo->prepare("
    SELECT 1
    FROM alumnos a
    JOIN alumnos_cursos ac ON ac.alumno_id = a.id
    JOIN cursos_profesores cp ON cp.curso_id = ac.curso_id AND cp.profesor_id = :pid
    WHERE a.id = :id AND a.deleted_at IS NULL
    LIMIT 1
  ");
  $stChk->execute([':pid'=>$profId, ':id'=>$id]);
  if (!$stChk->fetch()) { http_response_code(403); exit('No tienes acceso a este alumno.'); }
}

/* ============================================================
   ACCIÓN: GUARDAR DATOS DEL ALUMNO
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['accion'] ?? '') === 'guardar_alumno')) {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $nombre    = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $activo    = (isset($_POST['activo']) && $_POST['activo'] === '1') ? 1 : 0;
    $cursoId   = (int)($_POST['curso_id'] ?? 0);
    $fnac      = trim($_POST['fecha_nacimiento'] ?? '');
    $notas     = trim($_POST['notas'] ?? '');

    if ($nombre === '' || $apellidos === '') throw new RuntimeException('Nombre y apellidos son obligatorios.');
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email no válido.');
    if ($fnac !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fnac)) throw new RuntimeException('Fecha de nacimiento inválida (YYYY-MM-DD).');

    if ($email !== '') {
      $stChk = $pdo->prepare("SELECT 1 FROM alumnos WHERE email = :e AND id <> :id AND deleted_at IS NULL LIMIT 1");
      $stChk->execute([':e'=>$email, ':id'=>$id]);
      if ($stChk->fetch()) throw new RuntimeException('Ya existe otro alumno con ese email.');
    }

    if ($cursoId > 0 && !$isAdmin) {
      $st2 = $pdo->prepare("SELECT 1 FROM cursos_profesores WHERE curso_id = :c AND profesor_id = :p LIMIT 1");
      $st2->execute([':c'=>$cursoId, ':p'=>$profId]);
      if (!$st2->fetch()) throw new RuntimeException('No puedes asignar ese curso.');
    }

    $st = $pdo->prepare("
      UPDATE alumnos
      SET nombre=:n, apellidos=:a, email=:e, telefono=:t, activo=:ac, fecha_nacimiento=:fn, notas=:no
      WHERE id=:id
    ");
    $st->execute([
      ':n'=>$nombre, ':a'=>$apellidos,
      ':e'=>($email!=='' ? $email : null),
      ':t'=>($telefono!=='' ? $telefono : null),
      ':ac'=>$activo,
      ':fn'=>($fnac!=='' ? $fnac : null),
      ':no'=>($notas!=='' ? $notas : null),
      ':id'=>$id
    ]);

    $pdo->prepare('DELETE FROM alumnos_cursos WHERE alumno_id = :id')->execute([':id'=>$id]);
    if ($cursoId > 0) {
      $ins = $pdo->prepare("
        INSERT INTO alumnos_cursos (alumno_id, curso_id, fecha_inicio, estado)
        VALUES (:al,:cu, CURDATE(), 'matriculado')
      ");
      $ins->execute([':al'=>$id, ':cu'=>$cursoId]);
    }

    header('Location: ./edit.php?id='.$id.'&ok=1'); exit;

  } catch (Throwable $e) { $error = $e->getMessage(); }
}

/* ============================================================
   ASIGNACIÓN DE EMPRESA (única por alumno)
   ============================================================ */

/* Alta (sólo si NO hay ninguna asignación previa) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['accion'] ?? '') === 'asignar_empresa')) {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $empresaId   = (int)($_POST['empresa_id'] ?? 0);
    $tipo        = trim($_POST['tipo'] ?? 'dual');
    $fechaInicio = trim($_POST['fecha_inicio'] ?? '');
    $fechaFin    = trim($_POST['fecha_fin'] ?? '');
    $horasPrev   = trim($_POST['horas_previstas'] ?? '');
    $tutorNom    = trim($_POST['tutor_nombre'] ?? '');
    $tutorEmail  = trim($_POST['tutor_email'] ?? '');
    $tutorTel    = trim($_POST['tutor_telefono'] ?? '');
    $obs         = trim($_POST['observaciones'] ?? '');
    $asigSel     = array_values(array_unique(array_filter(array_map('intval', $_POST['asignaturas'] ?? []))));

    if ($empresaId <= 0) throw new RuntimeException('Selecciona una empresa.');
    if (!in_array($tipo, ['dual','fct','practicas','otros'], true)) $tipo = 'dual';
    if ($fechaInicio === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)) throw new RuntimeException('Fecha de inicio inválida (YYYY-MM-DD).');
    if ($fechaFin !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) throw new RuntimeException('Fecha de fin inválida (YYYY-MM-DD).');
    if ($tutorEmail !== '' && !filter_var($tutorEmail, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email del tutor no válido.');

    if (!$isAdmin) {
      $st = $pdo->prepare("
        SELECT 1
        FROM empresa_cursos ec
        JOIN cursos_profesores cp ON cp.curso_id = ec.curso_id AND cp.profesor_id = :pid
        WHERE ec.empresa_id = :eid
        LIMIT 1
      ");
      $st->execute([':pid'=>$profId, ':eid'=>$empresaId]);
      if (!$st->fetch()) throw new RuntimeException('No puedes asignar empresas fuera de tus cursos.');
    }

    // *** Regla nueva: si ya hay UNA asignación (da igual estado), no se puede crear otra
    $dupe = $pdo->prepare("SELECT 1 FROM empresa_alumnos WHERE alumno_id = :a LIMIT 1");
    $dupe->execute([':a'=>$id]);
    if ($dupe->fetch()) throw new RuntimeException('Este alumno ya tiene una empresa asignada.');

    $stCur = $pdo->prepare('SELECT curso_id FROM alumnos_cursos WHERE alumno_id = :id ORDER BY id DESC LIMIT 1');
    $stCur->execute([':id'=>$id]);
    $cursoAsociado = (int)($stCur->fetch()['curso_id'] ?? 0) ?: null;

    if (!empty($asigSel)) {
      if (!$cursoAsociado) throw new RuntimeException('El alumno no tiene curso asignado, no se pueden seleccionar asignaturas.');
      $inPlace = implode(',', array_fill(0, count($asigSel), '?'));
      $sqlVal = "
        SELECT COUNT(DISTINCT a.id)
        FROM asignaturas a
        LEFT JOIN asignatura_cursos ac ON ac.asignatura_id = a.id
        WHERE (a.curso_id = ? OR ac.curso_id = ?)
          AND a.id IN ($inPlace)
      ";
      $paramsVal = array_merge([$cursoAsociado, $cursoAsociado], $asigSel);
      $stVal = $pdo->prepare($sqlVal);
      $stVal->execute($paramsVal);
      if ((int)$stVal->fetchColumn() !== count($asigSel)) {
        throw new RuntimeException('Alguna asignatura seleccionada no pertenece al curso del alumno.');
      }
    }

    $ins = $pdo->prepare("
      INSERT INTO empresa_alumnos
        (empresa_id, alumno_id, curso_id, tipo, fecha_inicio, fecha_fin, horas_previstas, horas_realizadas,
         estado, tutor_nombre, tutor_email, tutor_telefono, observaciones)
      VALUES
        (:e, :a, :c, :t, :fi, :ff, :hp, NULL, :est, :tn, :te, :tt, :obs)
    ");
    $ins->execute([
      ':e'=>$empresaId, ':a'=>$id, ':c'=>$cursoAsociado, ':t'=>$tipo,
      ':fi'=>$fechaInicio, ':ff'=>($fechaFin!==''?$fechaFin:null),
      ':hp'=>($horasPrev!==''?(int)$horasPrev:null),
      ':est'=> ($fechaFin!=='' ? 'finalizada' : 'activa'),
      ':tn'=>($tutorNom!==''?$tutorNom:null),
      ':te'=>($tutorEmail!==''?$tutorEmail:null),
      ':tt'=>($tutorTel!==''?$tutorTel:null),
      ':obs'=>($obs!==''?$obs:null),
    ]);

    if (!empty($asigSel)) {
      $rows   = implode(',', array_fill(0, count($asigSel), '(?,?,?)'));
      $params = [];
      foreach ($asigSel as $asid) { $params[] = $empresaId; $params[] = $id; $params[] = (int)$asid; }
      $pdo->prepare("INSERT INTO empresa_alumnos_asignaturas (empresa_id, alumno_id, asignatura_id) VALUES $rows")->execute($params);
    }

    header('Location: ./edit.php?id='.$id.'&ok=1#dual'); exit;

  } catch (Throwable $e) { $error = $e->getMessage(); }
}

/* Actualizar los datos y asignaturas de la (única) asignación existente */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['accion'] ?? '') === 'actualizar_asignacion')) {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $eaId        = (int)($_POST['ea_id'] ?? 0);
    $empresaId   = (int)($_POST['empresa_id'] ?? 0);
    $tipo        = trim($_POST['tipo'] ?? 'dual');
    $fechaInicio = trim($_POST['fecha_inicio'] ?? '');
    $fechaFin    = trim($_POST['fecha_fin'] ?? '');
    $horasPrev   = trim($_POST['horas_previstas'] ?? '');
    $tutorNom    = trim($_POST['tutor_nombre'] ?? '');
    $tutorEmail  = trim($_POST['tutor_email'] ?? '');
    $tutorTel    = trim($_POST['tutor_telefono'] ?? '');
    $obs         = trim($_POST['observaciones'] ?? '');
    $asigSel     = array_values(array_unique(array_filter(array_map('intval', $_POST['asignaturas'] ?? []))));

    if ($eaId <= 0 || $empresaId <= 0) throw new RuntimeException('Asignación inválida.');
    if (!in_array($tipo, ['dual','fct','practicas','otros'], true)) $tipo = 'dual';
    if ($fechaInicio === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)) throw new RuntimeException('Fecha de inicio inválida (YYYY-MM-DD).');
    if ($fechaFin !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) throw new RuntimeException('Fecha de fin inválida (YYYY-MM-DD).');
    if ($tutorEmail !== '' && !filter_var($tutorEmail, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email del tutor no válido.');

    $stEA = $pdo->prepare("SELECT empresa_id FROM empresa_alumnos WHERE id=:id AND alumno_id=:al LIMIT 1");
    $stEA->execute([':id'=>$eaId, ':al'=>$id]);
    $rowEA = $stEA->fetch(PDO::FETCH_ASSOC);
    if (!$rowEA) throw new RuntimeException('No se encuentra la asignación.');
    if ((int)$rowEA['empresa_id'] !== $empresaId) throw new RuntimeException('Empresa inconsistente.');

    $pdo->prepare("
      UPDATE empresa_alumnos
      SET tipo=:t, fecha_inicio=:fi, fecha_fin=:ff, horas_previstas=:hp,
          estado=:est, tutor_nombre=:tn, tutor_email=:te, tutor_telefono=:tt, observaciones=:obs
      WHERE id=:id
    ")->execute([
      ':t'=>$tipo,
      ':fi'=>$fechaInicio,
      ':ff'=>($fechaFin!==''?$fechaFin:null),
      ':hp'=>($horasPrev!==''?(int)$horasPrev:null),
      ':est'=> ($fechaFin!=='' ? 'finalizada' : 'activa'),
      ':tn'=>($tutorNom!==''?$tutorNom:null),
      ':te'=>($tutorEmail!==''?$tutorEmail:null),
      ':tt'=>($tutorTel!==''?$tutorTel:null),
      ':obs'=>($obs!==''?$obs:null),
      ':id'=>$eaId
    ]);

    // Reemplazar asignaturas
    $pdo->prepare("DELETE FROM empresa_alumnos_asignaturas WHERE empresa_id=:e AND alumno_id=:a")
        ->execute([':e'=>$empresaId, ':a'=>$id]);

    if (!empty($asigSel)) {
      $rows   = implode(',', array_fill(0, count($asigSel), '(?,?,?)'));
      $params = [];
      foreach ($asigSel as $asid) { $params[] = $empresaId; $params[] = $id; $params[] = (int)$asid; }
      $pdo->prepare("INSERT INTO empresa_alumnos_asignaturas (empresa_id, alumno_id, asignatura_id) VALUES $rows")->execute($params);
    }

    header('Location: ./edit.php?id='.$id.'&ok=1#dual'); exit;

  } catch (Throwable $e) { $error = $e->getMessage(); }
}

/* Cerrar asignación */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['accion'] ?? '') === 'cerrar_asignacion')) {
  try {
    csrf_check($_POST['csrf'] ?? null);
    $eaId = (int)($_POST['ea_id'] ?? 0);
    $fFin = trim($_POST['fecha_fin'] ?? '');
    if ($eaId <= 0) throw new RuntimeException('Asignación inválida.');
    if ($fFin === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fFin)) throw new RuntimeException('Fecha de fin inválida.');

    $st = $pdo->prepare("SELECT id FROM empresa_alumnos WHERE id=:id AND alumno_id=:al LIMIT 1");
    $st->execute([':id'=>$eaId, ':al'=>$id]);
    if (!$st->fetch()) throw new RuntimeException('No se encuentra la asignación.');

    $pdo->prepare("UPDATE empresa_alumnos SET fecha_fin=:ff, estado='finalizada' WHERE id=:id")
        ->execute([':ff'=>$fFin, ':id'=>$eaId]);

    header('Location: ./edit.php?id='.$id.'&ok=1#dual'); exit;

  } catch (Throwable $e) { $error = $e->getMessage(); }
}

/* Eliminar asignación */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['accion'] ?? '') === 'eliminar_asignacion')) {
  try {
    csrf_check($_POST['csrf'] ?? null);
    if (!$isAdmin) throw new RuntimeException('Solo el administrador puede eliminar asignaciones.');
    $eaId = (int)($_POST['ea_id'] ?? 0);
    if ($eaId <= 0) throw new RuntimeException('Asignación inválida.');

    $st = $pdo->prepare("SELECT empresa_id FROM empresa_alumnos WHERE id=:id AND alumno_id=:al LIMIT 1");
    $st->execute([':id'=>$eaId, ':al'=>$id]);
    $rowEA = $st->fetch(PDO::FETCH_ASSOC);
    if (!$rowEA) throw new RuntimeException('No se encuentra la asignación.');
    $empresaId = (int)$rowEA['empresa_id'];

    $pdo->prepare("DELETE FROM empresa_alumnos_asignaturas WHERE empresa_id = :e AND alumno_id = :a")
        ->execute([':e'=>$empresaId, ':a'=>$id]);

    $pdo->prepare("DELETE FROM empresa_alumnos WHERE id=:id")->execute([':id'=>$eaId]);

    header('Location: ./edit.php?id='.$id.'&ok=1#dual'); exit;

  } catch (Throwable $e) { $error = $e->getMessage(); }
}

/* ============================================================
   CARGAS DE DATOS
   ============================================================ */
// Alumno
$stA = $pdo->prepare('
  SELECT id, nombre, apellidos, email, telefono, COALESCE(activo,1) AS activo, fecha_nacimiento, notas
  FROM alumnos WHERE id=:id AND deleted_at IS NULL LIMIT 1
');
$stA->execute([':id'=>$id]);
$al = $stA->fetch();
if (!$al) { http_response_code(404); exit('Alumno no encontrado'); }
$alActivo = (int)$al['activo'];

// Curso actual
$stAC = $pdo->prepare('SELECT curso_id FROM alumnos_cursos WHERE alumno_id = :id ORDER BY id DESC LIMIT 1');
$stAC->execute([':id'=>$id]);
$cursoActual = (int)($stAC->fetch()['curso_id'] ?? 0);

// Cursos disponibles (según rol)
if ($isAdmin) {
  $cursos = $pdo->query("SELECT id, nombre FROM cursos ORDER BY nombre")->fetchAll();
} else {
  $stC = $pdo->prepare("
    SELECT c.id, c.nombre
    FROM cursos c
    JOIN cursos_profesores cp ON cp.curso_id = c.id AND cp.profesor_id = :pid
    ORDER BY c.nombre
  ");
  $stC->execute([':pid'=>$profId]);
  $cursos = $stC->fetchAll();
}

// Asignaturas del curso (para el select múltiple)
$asignaturasCurso = [];
if ($cursoActual > 0) {
  $stAs = $pdo->prepare("
    SELECT DISTINCT a.id, a.nombre
    FROM asignaturas a
    LEFT JOIN asignatura_cursos ac ON ac.asignatura_id = a.id
    WHERE a.deleted_at IS NULL AND (a.curso_id = :c1 OR ac.curso_id = :c2)
    ORDER BY a.nombre
  ");
  $stAs->execute([':c1'=>$cursoActual, ':c2'=>$cursoActual]);
  $asignaturasCurso = $stAs->fetchAll(PDO::FETCH_ASSOC);
}

// Empresas disponibles (según rol)
if ($isAdmin) {
  $stEmp = $pdo->query("
    SELECT e.id, e.nombre
    FROM empresas e
    WHERE e.deleted_at IS NULL AND COALESCE(e.activo,1)=1
    ORDER BY e.nombre
  ");
  $empresasDisponibles = $stEmp->fetchAll(PDO::FETCH_ASSOC);
} else {
  $stEmp = $pdo->prepare("
    SELECT DISTINCT e.id, e.nombre
    FROM empresas e
    JOIN empresa_cursos ec ON ec.empresa_id = e.id
    JOIN cursos_profesores cp ON cp.curso_id = ec.curso_id AND cp.profesor_id = :pid
    WHERE e.deleted_at IS NULL AND COALESCE(e.activo,1)=1
    ORDER BY e.nombre
  ");
  $stEmp->execute([':pid'=>$profId]);
  $empresasDisponibles = $stEmp->fetchAll(PDO::FETCH_ASSOC);
}

// Asignaciones del alumno (todas) — la primera será la “actual” (activa primero, si no hay, la más reciente)
$stEA = $pdo->prepare("
  SELECT ea.*, e.nombre AS empresa_nombre
  FROM empresa_alumnos ea
  JOIN empresas e ON e.id = ea.empresa_id
  WHERE ea.alumno_id = :al
  ORDER BY (ea.fecha_fin IS NULL) DESC, ea.fecha_inicio DESC, ea.id DESC
");
$stEA->execute([':al'=>$id]);
$asignaciones = $stEA->fetchAll(PDO::FETCH_ASSOC);

// *** CAMBIO CLAVE: usamos SIEMPRE la primera como “empresa del alumno” (aunque esté finalizada)
$actual = $asignaciones[0] ?? null;

// Asignaturas seleccionadas de la “actual”
$asigIdsActual = [];
if ($actual) {
  $stSel = $pdo->prepare("
    SELECT asignatura_id
    FROM empresa_alumnos_asignaturas
    WHERE empresa_id=:e AND alumno_id=:a
    ORDER BY asignatura_id
  ");
  $stSel->execute([':e'=>(int)$actual['empresa_id'], ':a'=>$id]);
  $asigIdsActual = array_map('intval', $stSel->fetchAll(PDO::FETCH_COLUMN));
}

// Para mostrar nombres de asignaturas en histórico
$asignaturasPorEA = [];
if ($asignaciones) {
  $stMap = $pdo->prepare("
    SELECT ea.id AS ea_id, a.nombre
    FROM empresa_alumnos ea
    JOIN empresa_alumnos_asignaturas eaa
      ON eaa.empresa_id = ea.empresa_id
     AND eaa.alumno_id  = ea.alumno_id
    JOIN asignaturas a ON a.id = eaa.asignatura_id
    WHERE ea.alumno_id = :al
    ORDER BY a.nombre
  ");
  $stMap->execute([':al'=>$id]);
  while ($row = $stMap->fetch(PDO::FETCH_ASSOC)) {
    $asignaturasPorEA[(int)$row['ea_id']][] = $row['nombre'];
  }
}

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Editar alumno — Practicalia</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
  <?php require_once __DIR__ . '/../partials/menu.php'; ?>

  <main class="max-w-6xl mx-auto p-4">
    <h1 class="text-xl font-semibold mb-4">Editar alumno #<?= (int)$al['id'] ?> — <?= h($al['nombre'] . ' ' . $al['apellidos']) ?></h1>

    <?php if ($error): ?>
      <div class="mb-3 bg-red-50 text-red-700 p-3 rounded"><?= h($error) ?></div>
    <?php elseif(isset($_GET['ok'])): ?>
      <div class="mb-3 bg-green-50 text-green-700 p-3 rounded">Operación realizada correctamente.</div>
    <?php endif; ?>

    <!-- FORM ALUMNO -->
    <form method="post" class="bg-white p-6 rounded-2xl shadow space-y-4 mb-8">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="id" value="<?= (int)$al['id'] ?>">
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

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium">Fecha de nacimiento</label>
          <input name="fecha_nacimiento" type="date" value="<?= h($al['fecha_nacimiento'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Curso</label>
          <select name="curso_id" class="mt-1 w-full border rounded-xl p-2">
            <option value="0">— Sin curso —</option>
            <?php foreach ($cursos as $c): $cid=(int)$c['id']; ?>
              <option value="<?= $cid ?>" <?= ($cid === $cursoActual) ? 'selected' : '' ?>><?= h($c['nombre']) ?></option>
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
        <input type="checkbox" name="activo" id="activo" value="1" <?= ((int)$al['activo']===1)?'checked':'' ?>>
        <label for="activo" class="text-sm">Alumno activo</label>
      </div>

      <div class="flex gap-2">
        <button class="rounded-xl bg-black text-white px-4 py-2">Guardar</button>
        <a href="./index.php" class="rounded-xl px-4 py-2 border">Cancelar</a>
      </div>
    </form>

    <!-- ASIGNACIÓN EN EMPRESA -->
    <section id="dual" class="bg-white p-6 rounded-2xl shadow mb-8">
      <div class="flex items-center justify-between mb-3">
        <h2 class="font-semibold">Formación en empresa</h2>
      </div>

      <?php if ($actual): ?>
        <!-- EDITAR la empresa asignada (siempre que exista, esté o no finalizada) -->
        <form method="post" class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
          <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= (int)$al['id'] ?>">
          <input type="hidden" name="ea_id" value="<?= (int)$actual['id'] ?>">
          <input type="hidden" name="accion" value="actualizar_asignacion">

          <div>
            <label class="block text-sm font-medium">Empresa *</label>
            <select class="mt-1 w-full border rounded-xl p-2 bg-gray-50 text-gray-600" disabled>
              <option><?= h($actual['empresa_nombre']) ?></option>
            </select>
            <input type="hidden" name="empresa_id" value="<?= (int)$actual['empresa_id'] ?>">
          </div>

          <div>
            <label class="block text-sm font-medium">Tipo</label>
            <select name="tipo" class="mt-1 w-full border rounded-xl p-2">
              <?php foreach (['dual','fct','practicas','otros'] as $opt): ?>
                <option value="<?= $opt ?>" <?= ($opt===$actual['tipo'])?'selected':'' ?>><?= ucfirst($opt) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium">Fecha inicio *</label>
            <input type="date" name="fecha_inicio" value="<?= h($actual['fecha_inicio']) ?>" class="mt-1 w-full border rounded-xl p-2" required>
          </div>

          <div>
            <label class="block text-sm font-medium">Fecha fin</label>
            <input type="date" name="fecha_fin" value="<?= h($actual['fecha_fin'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2">
          </div>

          <div>
            <label class="block text-sm font-medium">Horas previstas</label>
            <input type="number" name="horas_previstas" min="0" max="2000" value="<?= h((string)($actual['horas_previstas'] ?? '')) ?>" class="mt-1 w-full border rounded-xl p-2">
          </div>

          <div class="md:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
              <label class="block text-sm font-medium">Tutor (empresa)</label>
              <input name="tutor_nombre" value="<?= h($actual['tutor_nombre'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2">
            </div>
            <div>
              <label class="block text-sm font-medium">Email tutor</label>
              <input name="tutor_email" type="email" value="<?= h($actual['tutor_email'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2">
            </div>
            <div>
              <label class="block text-sm font-medium">Teléfono tutor</label>
              <input name="tutor_telefono" value="<?= h($actual['tutor_telefono'] ?? '') ?>" class="mt-1 w-full border rounded-xl p-2">
            </div>
          </div>

          <div class="md:col-span-3">
            <label class="block text-sm font-medium">Observaciones</label>
            <textarea name="observaciones" rows="3" class="mt-1 w-full border rounded-xl p-2"><?= h($actual['observaciones'] ?? '') ?></textarea>
          </div>

          <div class="md:col-span-3">
            <label class="block text-sm font-medium">Asignaturas a dualizar</label>
            <?php if ($cursoActual > 0 && $asignaturasCurso): ?>
              <select name="asignaturas[]" multiple size="6" class="mt-1 w-full border rounded-xl p-2">
                <?php foreach ($asignaturasCurso as $as): $aid=(int)$as['id']; ?>
                  <option value="<?= $aid ?>" <?= in_array($aid, $asigIdsActual, true) ? 'selected' : '' ?>><?= h($as['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
              <p class="text-xs text-gray-500 mt-1">Mantén Ctrl/Cmd para seleccionar varias.</p>
            <?php else: ?>
              <select disabled class="mt-1 w-full border rounded-xl p-2">
                <option><?= $cursoActual>0 ? 'No hay asignaturas asociadas a este curso' : 'Asigna primero un curso al alumno' ?></option>
              </select>
            <?php endif; ?>
          </div>

          <div class="md:col-span-3 flex flex-wrap gap-2">
            <button class="rounded-xl bg-black text-white px-4 py-2">Guardar cambios</button>
            </form>
            <!-- Cerrar -->
            <form method="post" onsubmit="return confirm('¿Cerrar esta asignación?');" class="inline">
              <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$al['id'] ?>">
              <input type="hidden" name="ea_id" value="<?= (int)$actual['id'] ?>">
              <input type="hidden" name="accion" value="cerrar_asignacion">
              <button class="px-3 py-2 rounded border text-xs align-middle" type="submit">Cerrar</button>
            </form>

            <!-- Eliminar -->
            <form method="post" onsubmit="return confirm('¿Eliminar esta asignación?');" class="inline">
              <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$al['id'] ?>">
              <input type="hidden" name="ea_id" value="<?= (int)$actual['id'] ?>">
              <input type="hidden" name="accion" value="eliminar_asignacion">
              <button class="px-3 py-2 rounded border text-xs" type="submit">Eliminar</button>
            </form>
          </div>
        
      <?php else: ?>
        <!-- Alta sólo si no hay ninguna empresa asignada -->
        <form method="post" class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
          <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= (int)$al['id'] ?>">
          <input type="hidden" name="accion" value="asignar_empresa">

          <div>
            <label class="block text-sm font-medium">Empresa *</label>
            <select name="empresa_id" class="mt-1 w-full border rounded-xl p-2" required>
              <option value="">— Selecciona —</option>
              <?php foreach ($empresasDisponibles as $e): ?>
                <option value="<?= (int)$e['id'] ?>"><?= h($e['nombre']) ?></option>
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
                  <option value="<?= (int)$as['id'] ?>"><?= h($as['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
              <p class="text-xs text-gray-500 mt-1">Mantén Ctrl/Cmd para seleccionar varias.</p>
            <?php elseif ($cursoActual > 0): ?>
              <select disabled class="mt-1 w-full border rounded-xl p-2"><option>No hay asignaturas asociadas a este curso</option></select>
            <?php else: ?>
              <select disabled class="mt-1 w-full border rounded-xl p-2"><option>Asigna primero un curso al alumno</option></select>
            <?php endif; ?>
          </div>

          <div class="md:col-span-3">
            <button class="rounded-xl bg-black text-white px-4 py-2">Asignar</button>
          </div>
        </form>
      <?php endif; ?>

    </section>

    <!-- DIARIO DE CONTACTOS -->
    <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white p-6 rounded-2xl shadow">
        <h2 class="font-semibold mb-3">Diario de contactos</h2>
        <ul class="space-y-3">
          <?php
          $stCt = $pdo->prepare('
            SELECT ac.id, ac.fecha, ac.tipo, ac.resumen, ac.notas,
                   u.id AS prof_id, u.nombre AS prof_nombre, u.apellidos AS prof_apellidos
            FROM alumno_contactos ac
            JOIN usuarios u ON u.id = ac.profesor_id
            WHERE ac.alumno_id = :id
            ORDER BY ac.fecha DESC, ac.id DESC
          ');
          $stCt->execute([':id'=>$id]);
          $contactos = $stCt->fetchAll();
          ?>
          <?php foreach ($contactos as $c): ?>
            <li class="border rounded-xl p-3">
              <div class="flex items-center justify-between text-sm">
                <div>
                  <span class="font-medium"><?= h($c['prof_apellidos'] . ', ' . $c['prof_nombre']) ?></span>
                  · <span class="text-gray-500"><?= h($c['tipo']) ?></span>
                  · <span class="text-gray-500"><?= h($c['fecha']) ?></span>
                </div>
                <?php if ($isAdmin || (int)$c['prof_id'] === $profId): ?>
                  <form method="post" action="./contacto_delete.php" onsubmit="return confirm('¿Eliminar contacto?');">
                    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="alumno_id" value="<?= (int)$al['id'] ?>">
                    <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                    <button class="text-red-600 underline text-xs">Eliminar</button>
                  </form>
                <?php endif; ?>
              </div>
              <div class="mt-2 font-medium"><?= h($c['resumen']) ?></div>
              <?php if (!empty($c['notas'])): ?>
                <div class="mt-1 text-sm text-gray-700 whitespace-pre-line"><?= h($c['notas']) ?></div>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
          <?php if (!$contactos): ?>
            <li class="text-sm text-gray-500">Aún no hay contactos.</li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="bg-white p-6 rounded-2xl shadow">
        <h2 class="font-semibold mb-3">Añadir contacto</h2>
        <form method="post" action="./contacto_create.php" class="space-y-3">
          <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="alumno_id" value="<?= (int)$al['id'] ?>">

          <div>
            <label class="block text-sm font-medium">Tipo</label>
            <select name="tipo" class="mt-1 w-full border rounded-xl p-2">
              <option value="llamada">Llamada</option>
              <option value="email">Email</option>
              <option value="tutoria">Tutoría</option>
              <option value="visita">Visita</option>
              <option value="otro">Otro</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium">Resumen *</label>
            <input name="resumen" required class="mt-1 w-full border rounded-xl p-2" placeholder="Asunto breve">
          </div>

          <div>
            <label class="block text-sm font-medium">Notas</label>
            <textarea name="notas" rows="4" class="mt-1 w-full border rounded-xl p-2" placeholder="Detalles..."></textarea>
          </div>

          <button class="rounded-xl bg-black text-white px-4 py-2">Guardar contacto</button>
        </form>
      </div>
    </section>
  </main>
</body>
</html>
