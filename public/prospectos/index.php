<?php
// public/prospectos/index.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../lib/auth.php';

$user = current_user();
$isAdmin = require_role('admin');
$profId = (int) ($user['id'] ?? 0);

// Fetch prospects
$q = "SELECT * FROM empresas_prospectos WHERE deleted_at IS NULL";
$params = [];
if (!$isAdmin) {
    $q .= " AND asignado_profesor_id = ?";
    $params[] = $profId;
}
$q .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($q);
$stmt->execute($params);
$prospects = $stmt->fetchAll();

$pageTitle = 'Gestionar Prospectos';
require_once __DIR__ . '/../partials/_header.php';
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold">Prospectos (Leads)</h1>
        <p class="text-gray-600">Empresas detectadas automáticamente que aún no son colaboradoras.</p>
    </div>
    <a href="../empresas/descubrir.php" class="bg-black text-white px-4 py-2 rounded-xl">🔍 Buscar más</a>
</div>

<div class="bg-white rounded-2xl shadow overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="p-3 text-left">Empresa</th>
                <th class="p-3 text-left">Ciudad</th>
                <th class="p-3 text-left">Sector</th>
                <th class="p-3 text-left">Contacto</th>
                <th class="p-3 text-left">Estado</th>
                <th class="p-3 text-left">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($prospects as $p): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-3">
                        <div class="font-medium text-gray-900">
                            <?= htmlspecialchars($p['nombre']) ?>
                        </div>
                        <?php if ($p['web']): ?>
                            <a href="<?= htmlspecialchars($p['web']) ?>" target="_blank"
                                class="text-xs text-blue-600 hover:underline">
                                <?= htmlspecialchars($p['web']) ?>
                            </a>
                        <?php endif; ?>
                    </td>
                    <td class="p-3">
                        <?= htmlspecialchars($p['ciudad'] ?? '-') ?>
                    </td>
                    <td class="p-3 text-xs">
                        <?= htmlspecialchars($p['sector'] ?? '-') ?>
                    </td>
                    <td class="p-3 text-xs">
                        <div>
                            <?= htmlspecialchars($p['email'] ?? 'Sin email') ?>
                        </div>
                        <div class="text-gray-500">
                            <?= htmlspecialchars($p['telefono'] ?? '') ?>
                        </div>
                    </td>
                    <td class="p-3">
                        <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 border text-gray-700">
                            <?= strtoupper($p['estado']) ?>
                        </span>
                    </td>
                    <td class="p-3">
                        <div class="flex gap-2">
                            <?php if ($p['email']): ?>
                                <button
                                    onclick="openEmailModal(<?= $p['id'] ?>, '<?= addslashes($p['nombre']) ?>', '<?= addslashes($p['email']) ?>')"
                                    class="text-blue-600 hover:underline">📩 Contactar</button>
                            <?php endif; ?>
                            <button onclick="convertToCompany(<?= $p['id'] ?>)" class="text-green-600 hover:underline">✨
                                Graduar</button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$prospects): ?>
                <tr>
                    <td colspan="6" class="p-8 text-center text-gray-500">No hay prospectos asignados. Utiliza el buscador
                        automático para encontrar empresas.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Contacto Simple -->
<div id="contactModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-lg p-6 w-full max-w-lg">
        <h2 class="text-xl font-bold mb-4">Contactar con <span id="modalCompanyName"></span></h2>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Para:</label>
            <input type="text" id="modalTargetEmail" readonly class="w-full bg-gray-50 border rounded p-2 text-sm">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Asunto:</label>
            <input type="text" id="modalSubject" value="Propuesta de colaboración - SAFA"
                class="w-full border rounded p-2 text-sm">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Mensaje:</label>
            <textarea id="modalMessage" rows="5" class="w-full border rounded p-2 text-sm"></textarea>
        </div>
        <div class="flex justify-end gap-2">
            <button onclick="document.getElementById('contactModal').classList.add('hidden')"
                class="px-3 py-1.5 border rounded">Cancelar</button>
            <button id="sendBtn" onclick="sendProposal()" class="px-3 py-1.5 bg-black text-white rounded">Enviar
                Email</button>
        </div>
    </div>
</div>

<script>
    let currentProspectId = null;

    function openEmailModal(id, name, email) {
        currentProspectId = id;
        document.getElementById('modalCompanyName').textContent = name;
        document.getElementById('modalTargetEmail').value = email;
        document.getElementById('contactModal').classList.remove('hidden');

        // Sugerencia de mensaje por defecto
        document.getElementById('modalMessage').value = `Hola,\n\nSomos el centro SAFA y le escribimos porque estamos buscando plazas de prácticas para nuestros alumnos de Informática.\n\nHe visto que su empresa ${name} tiene una trayectoria interesante y nos gustaría saber si estarían abiertos a colaborar.\n\nUn saludo.`;
    }

    async function sendProposal() {
        const btn = document.getElementById('sendBtn');
        btn.disabled = true;
        btn.textContent = 'Enviando...';

        try {
            const res = await fetch((window.PRACTICALIA_API || '/api') + '/prospectos/contactar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({
                    id: currentProspectId,
                    asunto: document.getElementById('modalSubject').value,
                    mensaje: document.getElementById('modalMessage').value
                })
            });
            const json = await res.json();
            if (json.ok) {
                alert('Propuesta enviada correctamente');
                document.getElementById('contactModal').classList.add('hidden');
                location.reload();
            } else {
                alert('Error: ' + json.error);
            }
        } catch (e) {
            alert('Error al enviar el email.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Enviar Email';
        }
    }

    async function convertToCompany(id) {
        if (!confirm('¿Quieres graduar este prospecto a Empresa oficial?')) return;

        try {
            const res = await fetch((window.PRACTICALIA_API || '/api') + '/prospectos/convertir.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ id: id })
            });
            const json = await res.json();
            if (json.ok) {
                alert('¡Graduado con éxito! Ya puedes ver la empresa en el listado oficial.');
                location.reload();
            } else {
                alert('Error: ' + json.error);
            }
        } catch (e) {
            alert('Error en el servidor.');
        }
    }
</script>

<?php require_once __DIR__ . '/../partials/_footer.php'; ?>