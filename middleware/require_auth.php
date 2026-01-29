<?php
// practicalia/middleware/require_auth.php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';

if (!is_authenticated()) {
  // Calcula automáticamente la URL correcta del login
  $publicDir = realpath(__DIR__ . '/../public');
  $docRoot   = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
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
