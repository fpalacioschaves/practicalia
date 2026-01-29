<?php
// lib/auth.php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

// === Config rápida ===
// Si no quieres permitir auto-registro, pon aquí un código secreto.
// Si se deja vacío, cualquier usuario podrá registrarse.
const INVITE_CODE = '';

/**
 * Genera o recupera el token CSRF de la sesión
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/**
 * Comprueba el token CSRF recibido
 */
function csrf_check(?string $token): void
{
    if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('Token CSRF inválido o caducado');
    }
}

/**
 * Devuelve el usuario actual de sesión (o null)
 */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Comprueba si hay sesión activa
 */
function is_authenticated(): bool
{
    return current_user() !== null;
}

/**
 * Comprueba si el usuario actual tiene un rol concreto
 */
function require_role(string $codigoRol): bool
{
    $user = current_user();
    if (!$user) return false;
    return in_array($codigoRol, $user['roles_codigos'] ?? [], true);
}

/**
 * Busca un usuario por email
 */
function get_user_by_email(PDO $pdo, string $email): ?array
{
    $st = $pdo->prepare('SELECT * FROM usuarios WHERE email = :email AND deleted_at IS NULL LIMIT 1');
    $st->execute([':email' => $email]);
    $u = $st->fetch();
    return $u ?: null;
}

/**
 * Devuelve los roles asociados a un usuario
 */
function get_roles_for_user(PDO $pdo, int $userId): array
{
    $st = $pdo->prepare('SELECT r.codigo FROM roles r JOIN usuarios_roles ur ON ur.rol_id = r.id WHERE ur.usuario_id = :id');
    $st->execute([':id' => $userId]);
    return array_map(fn($r) => $r['codigo'], $st->fetchAll());
}

/**
 * Crea los roles básicos si no existen
 */
function ensure_default_roles(PDO $pdo): void
{
    $pdo->exec("INSERT IGNORE INTO roles (codigo, nombre) VALUES ('admin','Administrador'),('profesor','Profesor')");
}

/**
 * Asigna el rol admin al primer usuario creado
 */
function first_user_is_admin(PDO $pdo, int $userId): void
{
    $count = (int)$pdo->query('SELECT COUNT(*) AS c FROM usuarios')->fetch()['c'];
    if ($count === 1) {
        $st = $pdo->prepare("INSERT IGNORE INTO usuarios_roles (usuario_id, rol_id)
                             SELECT :uid, id FROM roles WHERE codigo = 'admin'");
        $st->execute([':uid' => $userId]);
    }
}

/**
 * Actualiza la sesión con los roles del usuario
 */
function attach_roles_to_session(PDO $pdo, int $userId): void
{
    $_SESSION['user']['roles_codigos'] = get_roles_for_user($pdo, $userId);
}

/**
 * Registro de usuario nuevo (por defecto: profesor)
 */
function register_user(PDO $pdo, string $nombre, string $apellidos, string $email, string $password, string $invite = ''): int
{
    ensure_default_roles($pdo);

    if (INVITE_CODE !== '' && $invite !== INVITE_CODE) {
        throw new RuntimeException('Código de invitación incorrecto');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Email no válido');
    }
    if (strlen($password) < 8) {
        throw new RuntimeException('La contraseña debe tener 8 caracteres o más');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $st = $pdo->prepare('INSERT INTO usuarios (nombre, apellidos, email, password_hash, activo)
                         VALUES (:n,:a,:e,:h,1)');
    $st->execute([':n' => $nombre, ':a' => $apellidos, ':e' => $email, ':h' => $hash]);
    $userId = (int)$pdo->lastInsertId();

    // Rol por defecto: profesor
    $st2 = $pdo->prepare("INSERT IGNORE INTO usuarios_roles (usuario_id, rol_id)
                          SELECT :uid, id FROM roles WHERE codigo = 'profesor'");
    $st2->execute([':uid' => $userId]);

    // Si es el primer usuario => admin
    first_user_is_admin($pdo, $userId);

    return $userId;
}

/**
 * Intenta iniciar sesión con email y contraseña
 */
function attempt_login(PDO $pdo, string $email, string $password): array
{
    $u = get_user_by_email($pdo, $email);
    if (!$u || (int)$u['activo'] !== 1) {
        throw new RuntimeException('Credenciales inválidas');
    }
    if (!password_verify($password, $u['password_hash'])) {
        throw new RuntimeException('Credenciales inválidas');
    }

    $roles = get_roles_for_user($pdo, (int)$u['id']);

    $_SESSION['user'] = [
        'id' => (int)$u['id'],
        'nombre' => $u['nombre'],
        'apellidos' => $u['apellidos'],
        'email' => $u['email'],
        'roles_codigos' => $roles,
    ];

    return $_SESSION['user'];
}

/**
 * Cierra sesión y limpia cookies
 */
function logout(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
