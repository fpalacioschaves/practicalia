<?php
// practicalia/middleware/require_staff.php
declare(strict_types=1);

require_once __DIR__ . '/require_auth.php'; // ya carga auth.php
if (!require_role('admin') && !require_role('profesor')) {
    http_response_code(403);
    exit('Acceso restringido: requiere rol admin o profesor.');
}
