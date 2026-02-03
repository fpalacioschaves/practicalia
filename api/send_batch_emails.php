<?php
// practicalia/public/api/send_batch_emails.php
require_once __DIR__ . '/../middleware/require_staff.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/Services/EmailService.php';

use App\Services\EmailService;

header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['company_ids']) || !is_array($input['company_ids'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan IDs de empresas']);
    exit;
}

$subjectTemplate = $input['subject'] ?? '';
$bodyTemplate = $input['body'] ?? '';

if (empty($subjectTemplate) || empty($bodyTemplate)) {
    http_response_code(400);
    echo json_encode(['error' => 'Asunto y cuerpo son obligatorios']);
    exit;
}

$emailService = new EmailService($pdo);
$companyIds = array_map('intval', $input['company_ids']);

if (empty($companyIds)) {
    echo json_encode(['success' => 0, 'failed' => 0]);
    exit;
}

// Fetch companies
$inQuery = implode(',', $companyIds);
$stmt = $pdo->query("SELECT * FROM empresas WHERE id IN ($inQuery)");
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$successCount = 0;
$failedCount = 0;

foreach ($companies as $company) {
    $email = $company['email'];
    if (empty($email)) {
        // Try responsible email if company email is empty?
        $email = $company['responsable_email'];
    }

    if (empty($email)) {
        $failedCount++;
        continue; // Log skipping?
    }

    // Personalize
    $subject = str_replace(
        ['{empresa}', '{responsable}', '{ciudad}'],
        [$company['nombre'], $company['responsable_nombre'] ?? 'Responsable', $company['ciudad'] ?? ''],
        $subjectTemplate
    );

    $body = str_replace(
        ['{empresa}', '{responsable}', '{ciudad}'],
        [$company['nombre'], $company['responsable_nombre'] ?? 'Responsable', $company['ciudad'] ?? ''],
        $bodyTemplate
    );

    // Send
    $teacherEmail = $user['email'] ?? null;
    $teacherName = $user['nombre'] ?? '';

    $result = $emailService->sendEmail($email, $subject, $body, [], $teacherEmail, $teacherName);
    $sent = $result['success'];
    $errorMsg = $result['error'];

    // Log
    $emailService->logEmailContact($company['id'], $user['id'], $subject, $body, $sent);

    if ($sent) {
        $successCount++;
    } else {
        $failedCount++;
        // Optionally log errorMsg to a temp array to return to client
        $errors[] = "Empresa ID {$company['id']}: $errorMsg";
    }
}

echo json_encode([
    'success' => $successCount,
    'failed' => $failedCount,
    'errors' => $errors ?? []
]);
