<?php
// practicalia/public/index.php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/require_auth.php';
require_once __DIR__ . '/../lib/auth.php';

$user = current_user();
$isAdmin = require_role('admin');
$profId  = (int)($user['id'] ?? 0);

/** ---------- Alumnos ---------- */
if ($isAdmin) {
  $stAl = $pdo->query("SELECT COUNT(*) AS c FROM alumnos WHERE deleted_at IS NULL");
  $alumnos = (int)($stAl->fetch()['c'] ?? 0);
} else {
  $stAl = $pdo->prepare("
    SELECT COUNT(DISTINCT a.id) AS c
    FROM alumnos a
    JOIN alumnos_cursos ac ON ac.alumno_id = a.id
    JOIN cursos_profesores cp ON cp.curso_id = ac.curso_id AND cp.profesor_id = :pid
    WHERE a.deleted_at IS NULL
  ");
  $stAl->execute([':pid' => $profId]);
  $alumnos = (int)($stAl->fetch()['c'] ?? 0);
}

/** ---------- Empresas ---------- */
$stEm = $pdo->query("SELECT COUNT(*) AS c FROM empresas WHERE deleted_at IS NULL");
$empresas = (int)($stEm->fetch()['c'] ?? 0);

/** ---------- Usuarios ---------- */
$stUs = $pdo->query("SELECT COUNT(*) AS c FROM usuarios WHERE deleted_at IS NULL");
$usuarios = (int)($stUs->fetch()['c'] ?? 0);

/** ---------- Cursos ---------- */
$stCu = $pdo->query("SELECT COUNT(*) AS c FROM cursos");
$cursos = (int)($stCu->fetch()['c'] ?? 0);

/** ---------- Centros ---------- */
$stCe = $pdo->query("SELECT COUNT(*) AS c FROM centros");
$centros = (int)($stCe->fetch()['c'] ?? 0);

/** ---------- Asignaturas ---------- */
if ($isAdmin) {
  $stAs = $pdo->query("SELECT COUNT(*) AS c FROM asignaturas WHERE deleted_at IS NULL");
} else {
  $stAs = $pdo->prepare("
    SELECT COUNT(DISTINCT a.id) AS c
    FROM asignaturas a
    JOIN cursos_profesores cp ON cp.curso_id = a.curso_id AND cp.profesor_id = :pid
    WHERE a.deleted_at IS NULL
  ");
  $stAs->execute([':pid' => $profId]);
}
$asignaturas = (int)($stAs->fetch()['c'] ?? 0);

/** ---------- Resultados de Aprendizaje (RAs) ---------- */
if ($isAdmin) {
  $stRa = $pdo->query("SELECT COUNT(*) AS c FROM asignatura_ras WHERE deleted_at IS NULL");
} else {
  $stRa = $pdo->prepare("
    SELECT COUNT(DISTINCT ra.id) AS c
    FROM asignatura_ras ra
    JOIN asignaturas a ON a.id = ra.asignatura_id
    JOIN cursos_profesores cp ON cp.curso_id = a.curso_id AND cp.profesor_id = :pid
    WHERE ra.deleted_at IS NULL AND a.deleted_at IS NULL
  ");
  $stRa->execute([':pid' => $profId]);
}
$ras = (int)($stRa->fetch()['c'] ?? 0);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Inicio — Practicalia</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
  <?php require_once __DIR__ . '/partials/menu.php'; ?>

  <main class="max-w-6xl mx-auto p-4">
    <header class="mb-6">
      <h1 class="text-2xl font-semibold text-gray-900">Inicio</h1>
      <p class="text-sm text-gray-600">
        Bienvenido<?= $user && isset($user['nombre']) ? ', ' . htmlspecialchars($user['nombre']) : '' ?>.
        <?= $isAdmin ? 'Tienes acceso de administrador.' : 'Tienes acceso de profesor.' ?>
      </p>
    </header>

    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Alumnos -->
      <div class="bg-white rounded-2xl shadow p-5">
        <div class="text-sm text-gray-500 mb-1">Alumnos</div>
        <div class="text-3xl font-semibold"><?= number_format($alumnos, 0, ',', '.') ?></div>
        <div class="mt-3">
          <a href="./alumnos/index.php" class="text-sm text-gray-700 hover:underline">Ver listado</a>
        </div>
        <div class="mt-2 text-xs text-gray-400">
          <?= $isAdmin ? 'Total en el sistema' : 'Solo los de tus cursos' ?>
        </div>
      </div>

      <!-- Empresas -->
      <div class="bg-white rounded-2xl shadow p-5">
        <div class="text-sm text-gray-500 mb-1">Empresas</div>
        <div class="text-3xl font-semibold"><?= number_format($empresas, 0, ',', '.') ?></div>
        <div class="mt-3">
          <a href="./empresas/index.php" class="text-sm text-gray-700 hover:underline">Ver listado</a>
        </div>
      </div>

      <!-- Usuarios -->
      <div class="bg-white rounded-2xl shadow p-5">
        <div class="text-sm text-gray-500 mb-1">Usuarios</div>
        <div class="text-3xl font-semibold"><?= number_format($usuarios, 0, ',', '.') ?></div>
        <div class="mt-3">
          <?php if ($isAdmin): ?>
            <a href="./admin/usuarios/index.php" class="text-sm text-gray-700 hover:underline">Gestionar usuarios</a>
          <?php else: ?>
            <span class="text-sm text-gray-400">Solo visible</span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Cursos -->
      <div class="bg-white rounded-2xl shadow p-5">
        <div class="text-sm text-gray-500 mb-1">Grados</div>
        <div class="text-3xl font-semibold"><?= number_format($cursos, 0, ',', '.') ?></div>
        <div class="mt-3">
          <?php if ($isAdmin): ?>
            <a href="./cursos/index.php" class="text-sm text-gray-700 hover:underline">Ver grados</a>
          <?php else: ?>
            <span class="text-sm text-gray-400">Solo visible</span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Asignaturas -->
      <div class="bg-white rounded-2xl shadow p-5">
        <div class="text-sm text-gray-500 mb-1">Asignaturas</div>
        <div class="text-3xl font-semibold"><?= number_format($asignaturas, 0, ',', '.') ?></div>
        <div class="mt-3">
          <a href="./asignaturas/index.php" class="text-sm text-gray-700 hover:underline">Ver asignaturas</a>
        </div>
        <div class="mt-2 text-xs text-gray-400">
          <?= $isAdmin ? 'Total en el sistema' : 'Solo tus grados' ?>
        </div>
      </div>

      <!-- Resultados de Aprendizaje -->
      <div class="bg-white rounded-2xl shadow p-5">
        <div class="text-sm text-gray-500 mb-1">Resultados de Aprendizaje (RAs)</div>
        <div class="text-3xl font-semibold"><?= number_format($ras, 0, ',', '.') ?></div>
        <div class="mt-3">
          <a href="./ras/index.php" class="text-sm text-gray-700 hover:underline">Ver RAs</a>
        </div>
        <div class="mt-2 text-xs text-gray-400">
          <?= $isAdmin ? 'Total en el sistema' : 'Solo los de tus grados' ?>
        </div>
      </div>

      <!-- Centros -->
      <div class="bg-white rounded-2xl shadow p-5">
        <div class="text-sm text-gray-500 mb-1">Centros</div>
        <div class="text-3xl font-semibold"><?= number_format($centros, 0, ',', '.') ?></div>
        <div class="mt-3">
          <?php if ($isAdmin): ?>
            <a href="./centros/index.php" class="text-sm text-gray-700 hover:underline">Ver centros</a>
          <?php else: ?>
            <span class="text-sm text-gray-400">Solo visible</span>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </main>
</body>
</html>
