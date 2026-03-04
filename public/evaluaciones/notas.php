<?php
// practicalia/public/evaluaciones/notas.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$conv = $_GET['conv'] ?? 'Enero';

if (!$id || !in_array($conv, ['Enero', 'Mayo', 'Junio'])) {
    header('Location: ./index.php');
    exit;
}

// Cargar asignatura
$st = $pdo->prepare("SELECT nombre FROM asignaturas WHERE id = :id");
$st->execute([':id' => $id]);
$asig = $st->fetch();
if (!$asig) {
    exit('Asignatura no encontrada');
}

$service = new \App\Services\EvaluacionService($pdo);
$error = '';
$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notas'])) {
    try {
        csrf_check($_POST['csrf'] ?? '');
        $service->saveNotasBatch($id, $conv, $_POST['notas']);
        $ok = 'Notas guardadas correctamente.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$config = $service->getConfig($id, $conv);
$notas = $service->getNotas($id, $conv);

$pageTitle = 'Calificar: ' . $asig['nombre'];
require_once __DIR__ . '/../partials/_header.php';
?>
    <div class="mb-8 flex flex-col lg:flex-row lg:items-end justify-between gap-6">
        <div class="space-y-1">
            <div class="flex items-center gap-2 text-sm font-medium text-blue-600 mb-1">
                <a href="index.php" class="hover:underline flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Volver al listado
                </a>
            </div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight"><?= htmlspecialchars($asig['nombre']) ?></h1>
            <div class="flex items-center gap-3">
                <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-800 text-xs font-bold uppercase tracking-wider"><?= $conv ?></span>
                <span class="text-gray-400">|</span>
                <span class="text-gray-500 text-sm">Gestionando <?= count($notas) ?> alumnos matriculados</span>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            <a href="export_excel.php?id=<?= $id ?>&conv=<?= $conv ?>" 
               class="inline-flex items-center gap-2 bg-white border border-gray-200 text-gray-700 px-5 py-2.5 rounded-2xl text-sm font-bold shadow-sm hover:bg-gray-50 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Exportar Excel
            </a>
            <a href="config.php?id=<?= $id ?>&conv=<?= $conv ?>" 
               class="inline-flex items-center gap-2 bg-white border border-gray-200 text-gray-700 px-5 py-2.5 rounded-2xl text-sm font-bold shadow-sm hover:bg-gray-50 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                </svg>
                Configurar Pesos
            </a>
        </div>
    </div>

    <!-- Panel de Pesos resumido -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <?php 
        $pesos_config = [
            ['label' => 'Foro', 'value' => (int)$config['peso_foro'], 'color' => 'bg-blue-500'],
            ['label' => 'Actividades', 'value' => (int)$config['peso_actividades'], 'color' => 'bg-indigo-500'],
            ['label' => 'Examen', 'value' => (int)$config['peso_examen'], 'color' => 'bg-purple-500'],
            ['label' => 'Dualización', 'value' => (int)$config['peso_dualizacion'], 'color' => 'bg-pink-500'],
        ];
        foreach ($pesos_config as $p):
        ?>
            <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"><?= $p['label'] ?></p>
                    <p class="text-xl font-black text-gray-800"><?= $p['value'] ?><span class="text-sm font-normal text-gray-400">%</span></p>
                </div>
                <div class="w-1.5 h-10 rounded-full <?= $p['color'] ?>"></div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 text-red-700 p-4 rounded-2xl border border-red-100 mb-6 flex items-center gap-3">
             <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            <span class="font-medium"><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>
    <?php if ($ok): ?>
        <div class="bg-green-50 text-green-700 p-4 rounded-2xl border border-green-100 mb-6 flex items-center gap-3 animate-pulse">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span class="font-medium"><?= htmlspecialchars($ok) ?></span>
        </div>
    <?php endif; ?>

    <form method="post" class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="text-left p-6 font-bold text-gray-400 uppercase tracking-widest text-[10px]">Alumno</th>
                        <th class="text-center p-6 font-bold text-gray-400 uppercase tracking-widest text-[10px]">Foro</th>
                        <th class="text-center p-6 font-bold text-gray-400 uppercase tracking-widest text-[10px]">Activ.</th>
                        <th class="text-center p-6 font-bold text-gray-400 uppercase tracking-widest text-[10px]">Examen</th>
                        <th class="text-center p-6 font-bold text-gray-400 uppercase tracking-widest text-[10px]">Dual.</th>
                        <th class="text-center p-6 font-bold text-gray-400 uppercase tracking-widest text-[10px] bg-blue-50/50">Nota Final</th>
                        <th class="text-left p-6 font-bold text-gray-400 uppercase tracking-widest text-[10px]">Observaciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($notas as $n): $aid = (int)$n['id']; ?>
                        <tr class="hover:bg-gray-50/80 transition-colors group">
                            <td class="p-6">
                                <div class="font-bold text-gray-700 group-hover:text-blue-600 transition-colors"><?= htmlspecialchars($n['apellidos'] . ', ' . $n['nombre']) ?></div>
                                <div class="text-[10px] text-gray-400 font-mono mt-0.5">ID: <?= $aid ?></div>
                            </td>
                            <td class="p-4 text-center">
                                <input type="number" name="notas[<?= $aid ?>][nota_foro]" step="0.01" min="0" max="10"
                                       value="<?= $n['nota_foro'] ?>" 
                                       placeholder="-"
                                       class="w-16 border-0 bg-gray-50 rounded-xl p-2.5 text-center font-bold text-gray-700 focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none grade-input transition-all"
                                       data-peso="<?= $config['peso_foro'] ?>">
                            </td>
                            <td class="p-4 text-center">
                                <input type="number" name="notas[<?= $aid ?>][nota_actividades]" step="0.01" min="0" max="10"
                                       value="<?= $n['nota_actividades'] ?>" 
                                       placeholder="-"
                                       class="w-16 border-0 bg-gray-50 rounded-xl p-2.5 text-center font-bold text-gray-700 focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none grade-input transition-all"
                                       data-peso="<?= $config['peso_actividades'] ?>">
                            </td>
                            <td class="p-4 text-center">
                                <input type="number" name="notas[<?= $aid ?>][nota_examen]" step="0.01" min="0" max="10"
                                       value="<?= $n['nota_examen'] ?>" 
                                       placeholder="-"
                                       class="w-16 border-0 bg-gray-50 rounded-xl p-2.5 text-center font-bold text-gray-700 focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none grade-input transition-all"
                                       data-peso="<?= $config['peso_examen'] ?>">
                            </td>
                            <td class="p-4 text-center">
                                <input type="number" name="notas[<?= $aid ?>][nota_dualizacion]" step="0.01" min="0" max="10"
                                       value="<?= $n['nota_dualizacion'] ?>" 
                                       placeholder="-"
                                       class="w-16 border-0 bg-gray-50 rounded-xl p-2.5 text-center font-bold text-gray-700 focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none grade-input transition-all"
                                       data-peso="<?= $config['peso_dualizacion'] ?>">
                            </td>
                            <td class="p-4 text-center bg-blue-50/20">
                                <span class="text-lg font-black final-cell"><?= $n['nota_final'] ?? '—' ?></span>
                                <div class="status-indicator h-1 w-8 mx-auto rounded-full mt-1"></div>
                            </td>
                            <td class="p-4">
                                <textarea name="notas[<?= $aid ?>][observaciones]" rows="1" 
                                          placeholder="Nota interna..."
                                          class="w-full border-0 bg-gray-50 rounded-xl p-2 text-xs focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none transition-all"><?= htmlspecialchars($n['observaciones'] ?? '') ?></textarea>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($notas)): ?>
                        <tr>
                            <td colspan="7" class="p-20 text-center">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-50 text-gray-300 mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                                <p class="text-gray-400 font-medium italic">Sin alumnos matriculados.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($notas)): ?>
            <div class="p-8 bg-gray-50/50 border-t border-gray-100 flex justify-end">
                <button class="bg-gradient-to-br from-gray-800 to-black text-white px-10 py-4 rounded-2xl font-black shadow-lg hover:shadow-xl active:scale-95 transition-all">
                    Guardar Todas las Notas
                </button>
            </div>
        <?php endif; ?>
    </form>

    <script>
        document.querySelectorAll('tr').forEach(row => {
            const inputs = row.querySelectorAll('.grade-input');
            const finalCell = row.querySelector('.final-cell');
            const indicator = row.querySelector('.status-indicator');
            if (!finalCell) return;

            function calculateRowFinal() {
                let total = 0;
                let hasValue = false;
                inputs.forEach(i => {
                    const val = parseFloat(i.value);
                    const peso = parseFloat(i.dataset.peso);
                    if (!isNaN(val)) {
                        hasValue = true;
                        total += val * (peso / 100);
                    }
                });
                
                const final = hasValue ? total.toFixed(2) : '—';
                finalCell.textContent = final;
                
                if (hasValue) {
                    if (total < 5) {
                        finalCell.className = "text-lg font-black text-red-500 final-cell";
                        indicator.className = "status-indicator h-1 w-8 mx-auto rounded-full mt-1 bg-red-400";
                    } else if (total < 7) {
                        finalCell.className = "text-lg font-black text-amber-500 final-cell";
                        indicator.className = "status-indicator h-1 w-8 mx-auto rounded-full mt-1 bg-amber-400";
                    } else {
                        finalCell.className = "text-lg font-black text-emerald-500 final-cell";
                        indicator.className = "status-indicator h-1 w-8 mx-auto rounded-full mt-1 bg-emerald-400";
                    }
                } else {
                    finalCell.className = "text-lg font-black text-gray-300 final-cell";
                    indicator.className = "status-indicator h-1 w-8 mx-auto rounded-full mt-1 bg-transparent";
                }
            }

            inputs.forEach(i => i.addEventListener('input', calculateRowFinal));
            calculateRowFinal();
        });
    </script>
<?php require_once __DIR__ . '/../partials/_footer.php'; ?>