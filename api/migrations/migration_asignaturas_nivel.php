<?php
// practicalia/api/migrations/migration_asignaturas_nivel.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/auth.php';

// Solo admin
if (!require_role('admin')) {
    exit('Acceso denegado');
}

try {
    // 1. Añadir columna nivel
    $pdo->exec("ALTER TABLE asignaturas ADD COLUMN nivel TINYINT UNSIGNED DEFAULT 1 AFTER curso_id");
    echo "Columna 'nivel' añadida correctamente.<br>";

    // 2. Población inicial basada en semestre
    // semestre 1-2 -> nivel 1
    // semestre 3-4 -> nivel 2
    // semestre 5-6 -> nivel 3, etc.
    $pdo->exec("UPDATE asignaturas SET nivel = CEIL(semestre / 2) WHERE semestre IS NOT NULL AND semestre > 0");
    echo "Población inicial de 'nivel' completada basándose en semestres.<br>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
