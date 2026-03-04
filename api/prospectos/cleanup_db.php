<?php
// api/prospectos/cleanup_db.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../config/db.php';

if (!require_role('admin')) {
    die('Acceso denegado: se requiere rol de administrador.');
}

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DROP TABLE IF EXISTS empresas_prospectos");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "<h1>Limpieza de Base de Datos</h1>";
    echo "<p style='color:green'>La tabla <code>empresas_prospectos</code> ha sido eliminada correctamente.</p>";
    echo "<p>Puedes borrar este archivo ahora.</p>";
} catch (PDOException $e) {
    echo "<h1>Error en la limpieza</h1>";
    echo "<p style='color:red'>" . $e->getMessage() . "</p>";
}
