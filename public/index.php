<?php
// practicalia/public/index.php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/require_auth.php';
require_once __DIR__ . '/../lib/auth.php';

$user = current_user();
$isAdmin = require_role('admin');
$profId = (int) ($user['id'] ?? 0);


$dashboardService = new \App\Services\DashboardService($pdo);

$stats = $dashboardService->getStats($isAdmin, $profId);

$alumnos = $stats['alumnos'];
$empresas = $stats['empresas'];
$usuarios = $stats['usuarios'];
$cursos = $stats['cursos'];
$centros = $stats['centros'];
$asignaturas = $stats['asignaturas'];
$ras = $stats['ras'];
$pageTitle = 'Inicio';
require_once __DIR__ . '/partials/_header.php';
?>
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
<?php require_once __DIR__ . '/partials/_footer.php'; ?>