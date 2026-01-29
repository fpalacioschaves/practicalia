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
            $stCt->execute([':id' => $id]);
            $contactos = $stCt->fetchAll();
            ?>
            <?php foreach ($contactos as $c): ?>
                <li class="border rounded-xl p-3">
                    <div class="flex items-center justify-between text-sm">
                        <div>
                            <span class="font-medium">
                                <?= h(($c['prof_apellidos'] ?? '') . ', ' . ($c['prof_nombre'] ?? '')) ?>
                            </span>
                            · <span class="text-gray-500">
                                <?= h($c['tipo'] ?? '') ?>
                            </span>
                            · <span class="text-gray-500">
                                <?= h($c['fecha'] ?? '') ?>
                            </span>
                        </div>
                        <?php if ($isAdmin || (int) ($c['prof_id'] ?? 0) === $profId): ?>
                            <form method="post" action="./contacto_delete.php"
                                onsubmit="return confirm('¿Eliminar contacto?');">
                                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="alumno_id" value="<?= (int) $al['id'] ?>">
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <button class="text-red-600 underline text-xs">Eliminar</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="mt-2 font-medium">
                        <?= h($c['resumen'] ?? '') ?>
                    </div>
                    <?php if (!empty($c['notas'])): ?>
                        <div class="mt-1 text-sm text-gray-700 whitespace-pre-line">
                            <?= h($c['notas'] ?? '') ?>
                        </div>
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
            <input type="hidden" name="alumno_id" value="<?= (int) $al['id'] ?>">

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
                <textarea name="notas" rows="4" class="mt-1 w-full border rounded-xl p-2"
                    placeholder="Detalles..."></textarea>
            </div>

            <button class="rounded-xl bg-black text-white px-4 py-2">Guardar contacto</button>
        </form>
    </div>
</section>