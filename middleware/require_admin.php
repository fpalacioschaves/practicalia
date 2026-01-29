<?php
// practicalia/middleware/require_admin.php
declare(strict_types=1);

require_once __DIR__ . '/require_auth.php'; // ya carga auth.php y comprueba sesión

if (!require_role('admin')) {
    http_response_code(403);
    exit('Acceso restringido: se requiere rol administrador.');
}
