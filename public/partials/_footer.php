<?php
// practicalia/public/partials/_footer.php
declare(strict_types=1);
?>
</main>
<footer class="mt-12 py-6 border-t border-gray-100 text-center text-sm text-gray-500">
    &copy;
    <?= date('Y') ?> Practicalia — Gestión de Prácticas
</footer>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.1/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('select:not([disabled])').forEach(function (el) {
            if (el.tomselect) return; // Evitar doble inicialización
            new TomSelect(el, {
                plugins: el.multiple ? ['remove_button'] : [],
                copyAttributesToRoot: true,
                controlInput: el.multiple ? null : '<input type="text" class="ts-control-input">',
            });
        });
    });
</script>
</body>

</html>