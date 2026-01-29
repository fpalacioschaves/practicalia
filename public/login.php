<?php
// practicalia/public/login.php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';

// Si ya hay sesión, manda al panel (evita re-login)
if (is_authenticated()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check($_POST['csrf'] ?? null);
        $email    = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        attempt_login($pdo, $email, $password);

        // Redirección relativa, NO a /public en la raíz del servidor
        header('Location: index.php');
        exit;

    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Practicalia — Acceso</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
  <main class="w-full max-w-md bg-white rounded-2xl shadow p-8">
    <h1 class="text-2xl font-semibold text-gray-900 mb-2">Acceso</h1>
    <p class="text-sm text-gray-600 mb-6">Entra con tu cuenta para gestionar Practicalia.</p>
    

    <?php if ($error): ?>
      <div class="mb-4 rounded-lg bg-red-50 text-red-700 p-3 text-sm">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

      <div>
        <label class="block text-sm font-medium text-gray-700">Email</label>
        <input name="email" type="email" required autocomplete="email"
               class="mt-1 w-full rounded-xl border border-gray-300 p-3 focus:outline-none focus:ring-2 focus:ring-black">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Contraseña</label>
        <input name="password" type="password" required autocomplete="current-password"
               class="mt-1 w-full rounded-xl border border-gray-300 p-3 focus:outline-none focus:ring-2 focus:ring-black">
      </div>

      <button type="submit"
              class="w-full rounded-xl bg-black text-white py-3 font-medium hover:opacity-90">
        Entrar
      </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
      ¿No tienes cuenta? <a class="text-gray-900 underline" href="register.php">Crear cuenta</a>
    </p>
  </main>
</body>
</html>
