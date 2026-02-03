<?php
// practicalia/public/api/templates.php
require_once __DIR__ . '/../middleware/require_staff.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../config/db.php';


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

$method = $_SERVER['REQUEST_METHOD'];
$userId = $user['id'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM email_templates WHERE profesor_id = ? ORDER BY titulo ASC");
    $stmt->execute([$userId]);
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($templates);
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $titulo = $input['titulo'] ?? '';
    $asunto = $input['asunto'] ?? '';
    $cuerpo = $input['cuerpo'] ?? '';

    if (empty($titulo) || empty($asunto)) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos incompletos']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO email_templates (profesor_id, titulo, asunto, cuerpo) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $titulo, $asunto, $cuerpo]);
        echo json_encode(['id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'DB Error: ' . $e->getMessage()]);
    }
} elseif ($method === 'DELETE') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM email_templates WHERE id = ? AND profesor_id = ?");
    $stmt->execute([$id, $userId]);
    echo json_encode(['success' => true]);
}
