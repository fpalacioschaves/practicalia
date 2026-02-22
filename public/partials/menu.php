<?php
// practicalia/public/partials/menu.php
declare(strict_types=1);

require_once __DIR__ . '/../../lib/auth.php';

$user = current_user();
$isAdmin = require_role('admin');
$isProfesor = require_role('profesor');

// Detecta base URL según entorno (local vs hosting)
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$publicPos = strpos($scriptName, '/public/');
$projectRoot = ($publicPos !== false) ? substr($scriptName, 0, $publicPos) : '';

$base = $projectRoot . '/public';
$apiBase = $projectRoot . '/api';

$path = $_SERVER['SCRIPT_NAME'] ?? '';

function active(string $needle): string
{
  global $path;
  return str_contains($path, $needle) ? 'text-black font-semibold' : 'text-gray-600';
}

// Para JS:
?>
<script>
  window.PRACTICALIA_API = "<?= $apiBase ?>";
</script>
<nav class="bg-white shadow mb-6">
  <div class="max-w-6xl mx-auto px-4">
    <div class="flex justify-between h-14 items-center">
      <div class="flex items-center space-x-6">
        <a href="<?= $base ?>/index.php" class="font-semibold text-gray-800">Practicalia</a>

        <?php if ($isAdmin): ?>
          <a href="<?= $base ?>/admin/usuarios/index.php" class="<?= active('/admin/usuarios/') ?>">
            Usuarios
          </a>

          <a href="<?= $base ?>/cursos/index.php" class="<?= active('/cursos/') ?>">
            Grados
          </a>

          <a href="<?= $base ?>/asignaturas/index.php" class="<?= active('/asignaturas/') ?>">
            Asignaturas
          </a>

          <!-- RAs -->
          <a href="<?= $base ?>/ras/index.php" class="<?= active('/ras/') ?>">
            RAs
          </a>

          <a href="<?= $base ?>/alumnos/index.php" class="<?= active('/alumnos/') ?>">
            Alumnos
          </a>

          <a href="<?= $base ?>/empresas/index.php" class="<?= active('/empresas/') ?>">
            Empresas
          </a>

          <a href="<?= $base ?>/prospectos/index.php" class="<?= active('/prospectos/') ?>">
            Prospectos
          </a>


          <a href="<?= $base ?>/centros/index.php" class="<?= active('/centros/') ?>">
            Centros
          </a>

        <?php elseif ($isProfesor): ?>
          <a href="<?= $base ?>/alumnos/index.php" class="<?= active('/alumnos/') ?>">
            Alumnos
          </a>

          <a href="<?= $base ?>/asignaturas/index.php" class="<?= active('/asignaturas/') ?>">
            Asignaturas
          </a>

          <!-- RAs -->
          <a href="<?= $base ?>/ras/index.php" class="<?= active('/ras/') ?>">
            RAs
          </a>

          <a href="<?= $base ?>/empresas/index.php" class="<?= active('/empresas/') ?>">
            Empresas
          </a>

          <a href="<?= $base ?>/prospectos/index.php" class="<?= active('/prospectos/') ?>">
            Prospectos
          </a>

        <?php endif; ?>

        <a href="<?= $base ?>/perfil/edit.php" class="<?= active('/perfil/') ?>">
          Mi perfil
        </a>

        <a href="<?= $base ?>/ayuda.php" class="<?= active('/ayuda.php') ?>">
          Ayuda
        </a>
      </div>

      <div class="flex items-center gap-3 text-sm text-gray-700">
        <span><?= htmlspecialchars($user['nombre'] ?? '') ?></span>
        <a href="<?= $base ?>/logout.php" class="text-red-600 hover:underline">Salir</a>
      </div>
    </div>
  </div>
</nav>