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
$alumnosActivos = $stats['alumnos_activos'];
$asignacionesActivas = $stats['asignaciones_activas'];
$ultimasEmpresas = $stats['ultimas_empresas'];
$ultimosAlumnos = $stats['ultimos_alumnos'];

$pageTitle = 'Inicio';
require_once __DIR__ . '/partials/_header.php';
?>

<!-- HERO: Saludo + acciones rápidas -->
<div
  class="mb-8 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
  <div>
    <h1 class="text-2xl font-bold text-gray-900">
      Hola, <?= htmlspecialchars($user['nombre'] ?? 'usuario') ?> 👋
    </h1>
    <p class="text-gray-500 mt-1 text-sm">
      <?= $isAdmin ? 'Acceso de <strong>Administrador</strong>' : 'Acceso de <strong>Profesor</strong>' ?>
    </p>
  </div>
  <div class="flex gap-3 flex-wrap">
    <a href="./alumnos/create.php" class="btn-add">+ Nuevo alumno</a>
    <a href="./empresas/create.php" class="btn-secondary">+ Nueva empresa</a>
    <a href="./evaluaciones/index.php" class="btn-secondary">📊 Evaluaciones</a>
  </div>
</div>

<!-- KPI CARDS -->
<section class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">

  <!-- Alumnos -->
  <a href="./alumnos/index.php"
    class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:border-blue-200 hover:shadow-md transition-all">
    <div class="flex items-start justify-between">
      <div>
        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Alumnos</div>
        <div class="text-3xl font-bold text-gray-900"><?= number_format($alumnos, 0, ',', '.') ?></div>
        <div class="text-xs text-blue-600 mt-1 font-medium"><?= $alumnosActivos ?> en prácticas</div>
      </div>
      <div
        class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center text-blue-500 group-hover:bg-blue-100 transition-colors flex-shrink-0">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
      </div>
    </div>
  </a>

  <!-- Empresas -->
  <a href="./empresas/index.php"
    class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:border-emerald-200 hover:shadow-md transition-all">
    <div class="flex items-start justify-between">
      <div>
        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Empresas</div>
        <div class="text-3xl font-bold text-gray-900"><?= number_format($empresas, 0, ',', '.') ?></div>
        <div class="text-xs text-emerald-600 mt-1 font-medium"><?= $asignacionesActivas ?> asignaciones activas</div>
      </div>
      <div
        class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-500 group-hover:bg-emerald-100 transition-colors flex-shrink-0">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
        </svg>
      </div>
    </div>
  </a>

  <!-- Asignaturas -->
  <a href="./asignaturas/index.php"
    class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:border-indigo-200 hover:shadow-md transition-all">
    <div class="flex items-start justify-between">
      <div>
        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Asignaturas</div>
        <div class="text-3xl font-bold text-gray-900"><?= number_format($asignaturas, 0, ',', '.') ?></div>
        <div class="text-xs text-indigo-600 mt-1 font-medium"><?= number_format($ras, 0, ',', '.') ?> RAs</div>
      </div>
      <div
        class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-500 group-hover:bg-indigo-100 transition-colors flex-shrink-0">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
      </div>
    </div>
  </a>

  <!-- Grados -->
  <?php if ($isAdmin): ?>
    <a href="./cursos/index.php"
      class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:border-purple-200 hover:shadow-md transition-all">
    <?php else: ?>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
      <?php endif; ?>
      <div class="flex items-start justify-between">
        <div>
          <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Grados</div>
          <div class="text-3xl font-bold text-gray-900"><?= number_format($cursos, 0, ',', '.') ?></div>
          <div class="text-xs text-purple-600 mt-1 font-medium"><?= number_format($centros, 0, ',', '.') ?> centros
          </div>
        </div>
        <div
          class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center text-purple-500 <?= $isAdmin ? 'group-hover:bg-purple-100' : '' ?> transition-colors flex-shrink-0">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l9-5-9-5-9 5 9 5z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
          </svg>
        </div>
      </div>
      <?php if ($isAdmin): ?>
    </a>
  <?php else: ?>
    </div>
  <?php endif; ?>

  <!-- RAs -->
  <a href="./ras/index.php"
    class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:border-orange-200 hover:shadow-md transition-all">
    <div class="flex items-start justify-between">
      <div>
        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">RAs</div>
        <div class="text-3xl font-bold text-gray-900"><?= number_format($ras, 0, ',', '.') ?></div>
        <div class="text-xs text-orange-600 mt-1 font-medium">Resultados de Aprendizaje</div>
      </div>
      <div
        class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center text-orange-500 group-hover:bg-orange-100 transition-colors flex-shrink-0">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
    </div>
  </a>

  <!-- Usuarios -->
  <?php if ($isAdmin): ?>
    <a href="./admin/usuarios/index.php"
      class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:border-teal-200 hover:shadow-md transition-all">
    <?php else: ?>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
      <?php endif; ?>
      <div class="flex items-start justify-between">
        <div>
          <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Usuarios</div>
          <div class="text-3xl font-bold text-gray-900"><?= number_format($usuarios, 0, ',', '.') ?></div>
          <div class="text-xs text-teal-600 mt-1 font-medium"><?= $isAdmin ? 'Gestionar usuarios' : 'Solo lectura' ?>
          </div>
        </div>
        <div
          class="w-10 h-10 bg-teal-50 rounded-xl flex items-center justify-center text-teal-500 <?= $isAdmin ? 'group-hover:bg-teal-100' : '' ?> transition-colors flex-shrink-0">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>
      <?php if ($isAdmin): ?>
    </a>
  <?php else: ?>
    </div>
  <?php endif; ?>

  <!-- Centros -->
  <?php if ($isAdmin): ?>
    <a href="./centros/index.php"
      class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:border-pink-200 hover:shadow-md transition-all">
    <?php else: ?>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
      <?php endif; ?>
      <div class="flex items-start justify-between">
        <div>
          <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Centros</div>
          <div class="text-3xl font-bold text-gray-900"><?= number_format($centros, 0, ',', '.') ?></div>
          <div class="text-xs text-pink-600 mt-1 font-medium">Centros educativos</div>
        </div>
        <div
          class="w-10 h-10 bg-pink-50 rounded-xl flex items-center justify-center text-pink-500 <?= $isAdmin ? 'group-hover:bg-pink-100' : '' ?> transition-colors flex-shrink-0">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
          </svg>
        </div>
      </div>
      <?php if ($isAdmin): ?>
    </a>
  <?php else: ?>
    </div>
  <?php endif; ?>

</section>

<!-- ACTIVIDAD RECIENTE -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

  <!-- ÚLTIMOS ALUMNOS -->
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-50">
      <h2 class="font-semibold text-gray-800 text-sm">Alumnos recientes</h2>
      <a href="./alumnos/index.php" class="text-xs text-blue-600 hover:underline font-medium">Ver todos →</a>
    </div>
    <?php if (empty($ultimosAlumnos)): ?>
      <p class="text-sm text-gray-400 px-6 py-5">No hay alumnos todavía.</p>
    <?php else: ?>
      <ul class="divide-y divide-gray-50">
        <?php foreach ($ultimosAlumnos as $al): ?>
          <li class="flex items-center gap-3 px-6 py-3 hover:bg-gray-50 transition-colors">
            <div
              class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 text-xs font-bold flex items-center justify-center flex-shrink-0">
              <?= mb_strtoupper(mb_substr($al['nombre'], 0, 1) . mb_substr($al['apellidos'], 0, 1)) ?>
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-sm font-medium text-gray-900 truncate">
                <?= htmlspecialchars($al['apellidos'] . ', ' . $al['nombre']) ?></div>
              <?php if (!empty($al['email'])): ?>
                <div class="text-xs text-gray-400 truncate"><?= htmlspecialchars($al['email']) ?></div>
              <?php endif; ?>
            </div>
            <a href="./alumnos/edit.php?id=<?= (int) $al['id'] ?>" class="btn-edit flex-shrink-0">Ver</a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <!-- ÚLTIMAS EMPRESAS -->
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-50">
      <h2 class="font-semibold text-gray-800 text-sm">Empresas recientes</h2>
      <a href="./empresas/index.php" class="text-xs text-emerald-600 hover:underline font-medium">Ver todas →</a>
    </div>
    <?php if (empty($ultimasEmpresas)): ?>
      <p class="text-sm text-gray-400 px-6 py-5">No hay empresas todavía.</p>
    <?php else: ?>
      <ul class="divide-y divide-gray-50">
        <?php foreach ($ultimasEmpresas as $emp): ?>
          <li class="flex items-center gap-3 px-6 py-3 hover:bg-gray-50 transition-colors">
            <div
              class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 text-xs font-bold flex items-center justify-center flex-shrink-0">
              <?= mb_strtoupper(mb_substr($emp['nombre'], 0, 2)) ?>
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-sm font-medium text-gray-900 truncate"><?= htmlspecialchars($emp['nombre']) ?></div>
              <div class="text-xs text-gray-400 truncate">
                <?= htmlspecialchars(implode(' · ', array_filter([$emp['sector'] ?? null, $emp['ciudad'] ?? null]))) ?>
              </div>
            </div>
            <a href="./empresas/edit.php?id=<?= (int) $emp['id'] ?>" class="btn-edit flex-shrink-0">Ver</a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

</div>

<?php require_once __DIR__ . '/partials/_footer.php'; ?>