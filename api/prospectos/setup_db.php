<?php
// api/prospectos/setup_db.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';
require_once __DIR__ . '/../../config/db.php';

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS `empresas_prospectos` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `nombre` varchar(255) NOT NULL,
      `sector` varchar(100) DEFAULT NULL,
      `ciudad` varchar(100) DEFAULT NULL,
      `provincia` varchar(100) DEFAULT NULL,
      `web` varchar(255) DEFAULT NULL,
      `email` varchar(150) DEFAULT NULL,
      `telefono` varchar(50) DEFAULT NULL,
      `asignado_profesor_id` int(11) DEFAULT NULL,
      `origen` varchar(50) DEFAULT 'busqueda',
      `estado` enum('nuevo','contactado','interesado','descartado','convertido') DEFAULT 'nuevo',
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      `deleted_at` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `idx_prospect_prof` (`asignado_profesor_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql);
    echo "<h1>Tablas creadas con éxito</h1><p>Ya puedes usar el módulo de Prospectos.</p><a href='../../public/empresas/descubrir.php'>Volver al buscador</a>";

} catch (Throwable $e) {
    echo "<h1>Error al crear tablas</h1><p>" . $e->getMessage() . "</p>";
}
