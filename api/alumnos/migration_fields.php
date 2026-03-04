<?php
// api/alumnos/migration_fields.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../config/db.php';

if (!require_role('admin')) {
    die('Acceso denegado: se requiere rol de administrador.');
}

try {
    $pdo->exec("
        ALTER TABLE alumnos 
        ADD COLUMN dni VARCHAR(20) DEFAULT NULL AFTER apellidos,
        ADD COLUMN seg_social VARCHAR(20) DEFAULT NULL AFTER dni,
        ADD COLUMN provincia_localidad VARCHAR(100) DEFAULT NULL AFTER seg_social
    ");
    echo "<h1>Migración de Alumnos</h1>";
    echo "<p style='color:green'>Las columnas <code>dni</code>, <code>seg_social</code> y <code>provincia_localidad</code> han sido añadidas correctamente.</p>";
    echo "<p>Puedes borrar este archivo ahora.</p>";
} catch (PDOException $e) {
    echo "<h1>Error en la migración</h1>";
    echo "<p style='color:red'>" . $e->getMessage() . "</p>";
}
