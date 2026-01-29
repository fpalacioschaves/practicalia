<?php
// practicalia/public/logout.php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';

// Cerrar sesión
logout();

// Calcular la URL correcta hacia /public/login.php según dónde esté instalada la app
$publicDir = realpath(__DIR__);                  // .../practicalia/public
$docRoot   = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$publicUrl = '/public';

if ($publicDir && $docRoot && str_starts_with($publicDir, $docRoot)) {
    // Construye la parte /practicalia/public eliminando el doc root
    $publicUrl = str_replace('\\', '/', substr($publicDir, strlen($docRoot)));
    if ($publicUrl === '') { $publicUrl = '/public'; }
}

// Redirigir al login dentro de la carpeta correcta
header('Location: ' . rtrim($publicUrl, '/') . '/login.php');
exit;
