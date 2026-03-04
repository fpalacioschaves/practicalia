<?php
// practicalia/public/evaluaciones/index.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

$user = current_user();
$isAdmin = require_role('admin');
$profId = (int) ($user['id'] ?? 0);

$alumnoService = new \App\Services\AlumnoService($pdo);
$grouped = $alumnoService->getAvailableAsignaturasGrouped($isAdmin, $profId);

$pageTitle = 'Evaluaciones';
require_once __DIR__ . '/../partials/_header.php';
?>
<div class="mb-8">
    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Evaluaciones y Notas</h1>
    <p class="text-gray-500 mt-1">Gestión integral de calificaciones y convocatorias por asignatura.</p>
</div>

<div class="space-y-10">
    <?php foreach ($grouped as $curso): ?>
        <div>
            <div class="flex items-center gap-3 mb-5">
                <div class="h-8 w-1.5 bg-blue-600 rounded-full"></div>
                <h2 class="text-xl font-bold text-gray-800 tracking-tight"><?= htmlspecialchars($curso['nombre']) ?></h2>
            </div>
            <div class="space-y-8">
                <?php foreach ($curso['niveles'] as $nivel => $asignaturas): ?>
                    <div>
                        <h3 class="flex items-center gap-2 text-sm font-bold text-gray-400 uppercase tracking-widest mb-4">
                            <span class="w-2 h-2 rounded-full bg-gray-200"></span>
                            <?= $nivel ?>
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($asignaturas as $asig): ?>
                                <div
                                    class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                                    <h4 class="text-lg font-bold text-gray-900 mb-6 min-h-[3rem] line-clamp-2">
                                        <?= htmlspecialchars($asig['nombre']) ?></h4>

                                    <div class="space-y-4">
                                        <?php
                                        $convs = [
                                            'Enero' => 'from-blue-500 to-blue-600',
                                            'Mayo' => 'from-indigo-500 to-indigo-600',
                                            'Junio' => 'from-purple-500 to-purple-600'
                                        ];
                                        foreach ($convs as $conv => $gradient):
                                            ?>
                                            <div
                                                class="flex items-center justify-between gap-3 p-3 rounded-2xl bg-gray-50 group/item hover:bg-white hover:ring-1 hover:ring-gray-200 transition-all">
                                                <span class="text-sm font-semibold text-gray-700"><?= $conv ?></span>
                                                <div class="flex items-center gap-2">
                                                    <a href="notas.php?id=<?= (int) $asig['id'] ?>&conv=<?= $conv ?>"
                                                        class="bg-gradient-to-br <?= $gradient ?> text-white text-xs font-bold px-4 py-2 rounded-xl shadow-sm hover:brightness-110 active:scale-95 transition-all">
                                                        Notas
                                                    </a>
                                                    <a href="config.php?id=<?= (int) $asig['id'] ?>&conv=<?= $conv ?>"
                                                        title="Configurar Pesos"
                                                        class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        </svg>
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (empty($grouped)): ?>
    <div class="bg-gray-50 border border-dashed border-gray-300 p-12 rounded-3xl text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 text-gray-400 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900">No hay asignaturas disponibles</h3>
        <p class="text-gray-500 max-w-xs mx-auto mt-2">No tienes asignaturas vinculadas a tu perfil o no hay grados activos
            actualmente.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../partials/_footer.php'; ?>