<?php
// api/empresas/migration_fields.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../config/db.php';

if (!require_role('admin')) {
    die('Acceso denegado: se requiere rol de administrador.');
}

try {
    $pdo->exec("
        ALTER TABLE empresas 
        ADD COLUMN horario_practicas VARCHAR(255) DEFAULT NULL AFTER sector,
        ADD COLUMN tutor_nif VARCHAR(20) DEFAULT NULL AFTER responsable_telefono,
        ADD COLUMN tutor_departamento VARCHAR(100) DEFAULT NULL AFTER tutor_nif,
        ADD COLUMN rep_legal_nombre VARCHAR(150) DEFAULT NULL AFTER tutor_departamento,
        ADD COLUMN rep_legal_nif VARCHAR(20) DEFAULT NULL AFTER rep_legal_nombre,
        ADD COLUMN rep_legal_email VARCHAR(190) DEFAULT NULL AFTER rep_legal_nif
    ");
    echo "<h1>Migración de Empresas</h1>";
    echo "<p style='color:green'>Las columnas de horario, tutor y representante legal han sido añadidas correctamente.</p>";
    echo "<p>Puedes borrar este archivo ahora.</p>";
} catch (PDOException $e) {
    echo "<h1>Error en la migración</h1>";
    echo "<p style='color:red'>" . $e->getMessage() . "</p>";
}
