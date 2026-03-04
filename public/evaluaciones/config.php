<?php
// practicalia/public/evaluaciones/config.php
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check($_POST['csrf'] ?? '');
        $data = [
            'peso_foro' => (float) $_POST['peso_foro'],
            'peso_actividades' => (float) $_POST['peso_actividades'],
            'peso_examen' => (float) $_POST['peso_examen'],
            'peso_dualizacion' => (float) $_POST['peso_dualizacion'],
        ];

        if (array_sum($data) != 100) {
            throw new Exception("La suma de los porcentajes debe ser exactamente 100%. Actual: " . array_sum($data) . "%");
        }

        $service->saveConfig($id, $conv, $data);
        $ok = 'Configuración guardada correctamente.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$config = $service->getConfig($id, $conv);

$pageTitle = 'Configurar Pesos - ' . $asig['nombre'];
require_once __DIR__ . '/../partials/_header.php';
?>
<div class="max-w-2xl mx-auto py-6">
    <div class="mb-8">
        <a href="notas.php?id=<?= $id ?>&conv=<?= $conv ?>"
            class="text-blue-600 hover:underline text-sm font-medium mb-2 inline-block">← Volver a las notas</a>
        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Configuración de Pesos</h1>
        <p class="text-gray-500 mt-1"><?= htmlspecialchars($asig['nombre']) ?> — Convocatoria de <?= $conv ?></p>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 text-red-700 p-4 rounded-2xl border border-red-100 mb-6 flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                    clip-rule="evenodd" />
            </svg>
            <span class="font-medium"><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>
    <?php if ($ok): ?>
        <div class="bg-green-50 text-green-700 p-4 rounded-2xl border border-green-100 mb-6 flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd" />
            </svg>
            <span class="font-medium"><?= htmlspecialchars($ok) ?></span>
        </div>
    <?php endif; ?>

    <form method="post" class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 space-y-8">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

        <div class="bg-blue-50/50 p-4 rounded-2xl border border-blue-50 border-dashed">
            <p class="text-sm text-blue-700 leading-relaxed italic">
                Ajusta los porcentajes de cada componente. Recuerda que la suma total debe ser exactamente
                <strong>100%</strong> para poder guardar.
            </p>
        </div>

        <div class="space-y-6">
            <?php
            $fields = [
                ['name' => 'peso_foro', 'label' => 'Participación en Foro', 'color' => 'bg-blue-500'],
                ['name' => 'peso_actividades', 'label' => 'Actividades Prácticas', 'color' => 'bg-indigo-500'],
                ['name' => 'peso_examen', 'label' => 'Examen Teórico', 'color' => 'bg-purple-500'],
                ['name' => 'peso_dualizacion', 'label' => 'Empresa / Dualización', 'color' => 'bg-pink-500'],
            ];
            foreach ($fields as $f):
                ?>
                <div class="group">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-bold text-gray-700"><?= $f['label'] ?></label>
                        <div class="flex items-center gap-2">
                            <input type="number" name="<?= $f['name'] ?>" step="1" min="0" max="100" required
                                value="<?= (int) $config[$f['name']] ?>"
                                class="w-20 border-0 bg-gray-50 rounded-xl p-3 text-right font-black text-gray-800 focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none transition-all weight-input">
                            <span class="text-gray-400 font-bold">%</span>
                        </div>
                    </div>
                    <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full <?= $f['color'] ?> transition-all duration-500 bar-indicator"
                            style="width: <?= (int) $config[$f['name']] ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="pt-8 border-t border-gray-100">
            <div
                class="flex justify-between items-center mb-10 p-5 bg-gray-50 rounded-2xl border border-gray-100 border-dashed">
                <div>
                    <span class="text-xs font-black text-gray-400 uppercase tracking-widest">Total Acumulado</span>
                    <p id="total-text" class="text-sm text-gray-500 mt-1">Debe sumar 100%</p>
                </div>
                <span id="total-pct" class="text-4xl font-black text-gray-900 transition-all">100%</span>
            </div>

            <button id="submit-btn"
                class="w-full bg-gradient-to-br from-gray-800 to-black text-white py-4 rounded-2xl font-black shadow-lg hover:shadow-xl active:scale-95 transition-all disabled:opacity-30 disabled:pointer-events-none">
                Guardar Configuración
            </button>
        </div>
    </form>
</div>

<script>
    const inputs = document.querySelectorAll('.weight-input');
    const bars = document.querySelectorAll('.bar-indicator');
    const totalSpan = document.getElementById('total-pct');
    const submitBtn = document.getElementById('submit-btn');
    const totalText = document.getElementById('total-text');

    function updateTotal() {
        let total = 0;
        inputs.forEach((input, index) => {
            const val = parseInt(input.value || 0);
            total += val;
            bars[index].style.width = val + '%';
        });

        totalSpan.textContent = total + '%';

        if (total !== 100) {
            totalSpan.classList.add('text-red-500');
            totalSpan.classList.remove('text-emerald-500');
            submitBtn.disabled = true;
            totalText.textContent = total < 100 ? 'Faltan ' + (100 - total) + '%' : 'Sobra un ' + (total - 100) + '%';
            totalText.className = "text-sm text-red-500 mt-1 font-bold";
        } else {
            totalSpan.classList.remove('text-red-500');
            totalSpan.classList.add('text-emerald-500');
            submitBtn.disabled = false;
            totalText.textContent = '¡Perfecto! Suma exacta.';
            totalText.className = "text-sm text-emerald-500 mt-1 font-bold";
        }
    }

    inputs.forEach(i => i.addEventListener('input', updateTotal));
    updateTotal();
</script>

<?php require_once __DIR__ . '/../partials/_footer.php'; ?>