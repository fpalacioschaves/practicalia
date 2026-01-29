<?php
// practicalia/public/admin/usuarios/delete.php
declare(strict_types=1);

require_once __DIR__ . '/../../../middleware/require_admin.php';
require_once __DIR__ . '/../../../lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido');
}

try {
    csrf_check($_POST['csrf'] ?? null);
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) throw new RuntimeException('ID inválido');

    $me = current_user();
    if ($me && (int)$me['id'] === $id) {
        throw new RuntimeException('No puedes eliminar tu propia cuenta.');
    }

    // Opción 1: borrado duro (DELETE)
    // $pdo->prepare('DELETE FROM usuarios WHERE id = :id')->execute([':id'=>$id]);

    // Opción 2: soft delete
    $pdo->prepare('UPDATE usuarios SET deleted_at = NOW() WHERE id = :id')->execute([':id'=>$id]);
    $pdo->prepare('DELETE FROM usuarios_roles WHERE usuario_id = :id')->execute([':id'=>$id]);

    header('Location: index.php');
    exit;

} catch (Throwable $e) {
    http_response_code(400);
    exit('Error: ' . $e->getMessage());
}
