<?php
// practicalia/public/evaluaciones/export_excel.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_staff.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$conv = $_GET['conv'] ?? 'Enero';

if (!$id || !in_array($conv, ['Enero', 'Mayo', 'Junio'])) {
    die('Parámetros inválidos');
}

// Cargar asignatura
$st = $pdo->prepare("SELECT nombre FROM asignaturas WHERE id = :id");
$st->execute([':id' => $id]);
$asig = $st->fetch();

$service = new \App\Services\EvaluacionService($pdo);
$notas = $service->getNotas($id, $conv);

$filename = "Notas_" . str_replace(' ', '_', $asig['nombre']) . "_" . $conv . "_" . date('Y-m-d') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// UTF-8 BOM para Excel
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Cabecera
fputcsv($output, [
    'Apellidos',
    'Nombre',
    'Foro',
    'Actividades',
    'Examen',
    'Dualización',
    'NOTA FINAL',
    'Observaciones'
]);

foreach ($notas as $n) {
    fputcsv($output, [
        $n['apellidos'],
        $n['nombre'],
        $n['nota_foro'] ?? '0',
        $n['nota_actividades'] ?? '0',
        $n['nota_examen'] ?? '0',
        $n['nota_dualizacion'] ?? '0',
        $n['nota_final'] ?? '0',
        $n['observaciones'] ?? ''
    ]);
}

fclose($output);
exit;
