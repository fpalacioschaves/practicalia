<?php
// practicalia/api/evaluaciones/migration.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/auth.php';

if (!require_role('admin')) {
    die('Acceso denegado: se requiere rol de administrador.');
}

try {
    // 1. Tabla de configuración de pesos (por asignatura y convocatoria)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS evaluacion_config (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            asignatura_id INT UNSIGNED NOT NULL,
            convocatoria ENUM('Enero', 'Mayo', 'Junio') NOT NULL,
            peso_foro DECIMAL(5,2) DEFAULT 10.00,
            peso_actividades DECIMAL(5,2) DEFAULT 30.00,
            peso_examen DECIMAL(5,2) DEFAULT 40.00,
            peso_dualizacion DECIMAL(5,2) DEFAULT 20.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_asig_conv (asignatura_id, convocatoria),
            FOREIGN KEY (asignatura_id) REFERENCES asignaturas(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Tabla de notas por alumno
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS evaluaciones_notas (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            alumno_id INT UNSIGNED NOT NULL,
            asignatura_id INT UNSIGNED NOT NULL,
            convocatoria ENUM('Enero', 'Mayo', 'Junio') NOT NULL,
            nota_foro DECIMAL(4,2) DEFAULT NULL,
            nota_actividades DECIMAL(4,2) DEFAULT NULL,
            nota_examen DECIMAL(4,2) DEFAULT NULL,
            nota_dualizacion DECIMAL(4,2) DEFAULT NULL,
            nota_final DECIMAL(4,2) DEFAULT NULL,
            observaciones TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_alumno_asig_conv (alumno_id, asignatura_id, convocatoria),
            FOREIGN KEY (alumno_id) REFERENCES alumnos(id) ON DELETE CASCADE,
            FOREIGN KEY (asignatura_id) REFERENCES asignaturas(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo "Migración completada con éxito. Tablas 'evaluacion_config' y 'evaluaciones_notas' creadas.";

} catch (PDOException $e) {
    echo "Error en la migración: " . $e->getMessage();
}
