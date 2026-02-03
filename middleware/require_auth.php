<?php
// practicalia/middleware/require_auth.php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';

if (!is_authenticated()) {
  // Si es una petición AJAX/Fetch, no redirigir, devolver 401
  $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

  if ($isAjax) {
    http_response_code(401);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Sesión expirada o no autenticado']));
  }

  // Calcula automáticamente la URL correcta del login
  $publicDir = realpath(__DIR__ . '/../public');
  $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
  $publicUrl = '/public';
  if ($publicDir && $docRoot && str_starts_with($publicDir, $docRoot)) {
    $publicUrl = str_replace('\\', '/', substr($publicDir, strlen($docRoot)));
    if ($publicUrl === '') {
      $publicUrl = '/public';
    }
  }
  header('Location: ' . rtrim($publicUrl, '/') . '/login.php');
  exit;
}
