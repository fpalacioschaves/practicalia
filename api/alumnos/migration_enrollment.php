<?php
// api/alumnos/migration_enrollment.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../config/db.php';

if (!require_role('admin')) {
    die('Acceso denegado: se requiere rol de administrador.');
}

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS alumnos_asignaturas (
            alumno_id INT(10) UNSIGNED NOT NULL,
            asignatura_id INT(10) UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (alumno_id, asignatura_id),
            CONSTRAINT fk_enroll_alumno FOREIGN KEY (alumno_id) REFERENCES alumnos(id) ON DELETE CASCADE,
            CONSTRAINT fk_enroll_asignatura FOREIGN KEY (asignatura_id) REFERENCES asignaturas(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "<h1>Migración: Matrícula de Alumnos</h1>";
    echo "<p style='color:green'>La tabla <code>alumnos_asignaturas</code> ha sido creada correctamente.</p>";
    echo "<p>Puedes borrar este archivo ahora.</p>";
} catch (PDOException $e) {
    echo "<h1>Error en la migración</h1>";
    echo "<p style='color:red'>" . $e->getMessage() . "</p>";
}
