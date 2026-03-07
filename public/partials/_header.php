<?php
// practicalia/public/partials/_header.php
declare(strict_types=1);
/**
 * @var string|null $pageTitle Título de la página (opcional)
 * @var string|null $mainClass Clase CSS para el <main> (opcional, por defecto max-w-6xl)
 */
$pageTitle = $pageTitle ?? 'Practicalia';
$mainClass = $mainClass ?? 'max-w-7xl';

// Detecta base URL según entorno (local vs hosting)
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$publicPos = strpos($scriptName, '/public/');
$projectRoot = ($publicPos !== false) ? substr($scriptName, 0, $publicPos) : '';
$base = $projectRoot . '/public';
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>
        <?= htmlspecialchars($pageTitle) ?> — Practicalia
    </title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.4.1/dist/css/tom-select.css" rel="stylesheet">
    <link href="<?= $base ?>/css/tailwind.css?v=<?= filemtime(__DIR__ . '/../css/tailwind.css') ?>" rel="stylesheet">
</head>

<body class="bg-gray-50 min-h-screen text-gray-900">
    <?php require_once __DIR__ . '/menu.php'; ?>
    <main class="<?= htmlspecialchars($mainClass) ?> mx-auto p-4 md:p-6">