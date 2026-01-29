<?php
// practicalia/public/partials/_header.php
declare(strict_types=1);
/**
 * @var string|null $pageTitle Título de la página (opcional)
 * @var string|null $mainClass Clase CSS para el <main> (opcional, por defecto max-w-6xl)
 */
$pageTitle = $pageTitle ?? 'Practicalia';
$mainClass = $mainClass ?? 'max-w-6xl';
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>
        <?= htmlspecialchars($pageTitle) ?> — Practicalia
    </title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Estilos globales suaves */
        .btn-primary {
            @apply rounded-xl bg-black text-white px-4 py-2 hover:bg-gray-800 transition-colors;
        }

        .btn-secondary {
            @apply rounded-xl border border-gray-300 px-4 py-2 hover:bg-gray-50 transition-colors;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen text-gray-900">
    <?php require_once __DIR__ . '/menu.php'; ?>
    <main class="<?= htmlspecialchars($mainClass) ?> mx-auto p-4 md:p-6">