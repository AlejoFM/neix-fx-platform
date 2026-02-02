<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$root = __DIR__ . '/../';
try {
    Dotenv::createImmutable($root, '.env')->safeLoad();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    Dotenv::createImmutable($root . '.env', '.env')->safeLoad();
}

error_reporting(E_ALL);
ini_set('display_errors', $_ENV['APP_DEBUG'] ?? '0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/auth.php';

use App\Infrastructure\Repositories\UserRepository;
use App\Application\Services\AuthService;

try {
    $userRepository = new UserRepository();
    $authService = new AuthService($userRepository);
} catch (Throwable $e) {
    error_log('Bootstrap error: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error</title></head><body>';
    echo '<h1>Error del servidor</h1><p>No se pudo conectar a la base de datos. Comprueba que los contenedores estén en marcha.</p>';
    echo '<p><a href="/">Intentar de nuevo</a></p></body></html>';
    exit;
}

// Ruta: nginx pasa url en query (try_files ... /index.php?url=$uri&$args)
$path = isset($_GET['url']) ? trim((string) $_GET['url'], '/') : '';
if ($path === 'index.php') {
    $path = '';
}

// POST /login — procesar formulario de login
if ($path === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $_SESSION['login_error'] = 'Usuario y contraseña son requeridos';
        header('Location: /login');
        exit;
    }

    $user = $authService->authenticate($username, $password);
    if (!$user) {
        $_SESSION['login_error'] = 'Credenciales inválidas';
        header('Location: /login');
        exit;
    }

    $_SESSION['user_id'] = $user->getId();
    $_SESSION['username'] = $user->getUsername();
    header('Location: /platform');
    exit;
}

// GET/POST /logout
if ($path === 'logout') {
    session_destroy();
    header('Location: /login');
    exit;
}

// GET / o /login — vista de login (solo si no está autenticado)
if ($path === '' || $path === 'login') {
    requireGuest();
    $loginError = getLoginError();
    require __DIR__ . '/views/login.php';
    exit;
}

// GET /platform — vista de plataforma (protegida)
if ($path === 'platform') {
    requireAuth();
    $currentUser = getCurrentUser();
    require __DIR__ . '/views/platform.php';
    exit;
}

// 404
http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>404</title></head><body><h1>Página no encontrada</h1><p><a href="/">Ir al inicio</a></p></body></html>';
