<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

// Cargar variables de entorno
$root = __DIR__ . '/../../';
try {
    Dotenv::createImmutable($root, '.env')->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    Dotenv::createImmutable($root . '.env', '.env')->load();
}

// Configurar manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', $_ENV['APP_DEBUG'] ?? '0');

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Inicializar dependencias
use App\Infrastructure\Repositories\UserRepository;
use App\Infrastructure\Repositories\InstrumentRepository;
use App\Infrastructure\Repositories\NotificationRepository;
use App\Infrastructure\Repositories\UserConfigurationRepository;
use App\Application\Services\AuthService;
use App\Application\Services\InstrumentService;
use App\Application\Services\UserConfigurationService;
use App\Application\Services\NotificationService;
use App\Application\Controllers\AuthController;
use App\Application\Controllers\InstrumentController;
use App\Application\Controllers\ConfigurationController;
use App\Application\Controllers\NotificationController;
use App\Infrastructure\Logger\LoggerFactory;

$userRepository = new UserRepository();
$instrumentRepository = new InstrumentRepository();
$notificationRepository = new NotificationRepository();
$configRepository = new UserConfigurationRepository();

$authService = new AuthService($userRepository);
$instrumentService = new InstrumentService($instrumentRepository);
$notificationService = new NotificationService($notificationRepository);
$configService = new UserConfigurationService($configRepository, $instrumentRepository);

$authController = new AuthController($authService);
$instrumentController = new InstrumentController($instrumentService);
$configController = new ConfigurationController($configService, $notificationService);
$notificationController = new NotificationController($notificationService);

// Routing simple
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remover /api del path
$path = str_replace('/api', '', $requestUri);
$path = trim($path, '/');

// Debug (remover en producción)
// error_log("Request URI: " . $requestUri);
// error_log("Path: " . $path);
// error_log("Method: " . $requestMethod);

try {
    // Auth routes
    if ($path === 'auth/login' && $requestMethod === 'POST') {
        $authController->login();
    } elseif ($path === 'auth/logout' && $requestMethod === 'POST') {
        $authController->logout();
    } elseif ($path === 'auth/me' && $requestMethod === 'GET') {
        $authController->getCurrentUser();
    }
    // Instrument routes
    elseif ($path === 'instruments' && $requestMethod === 'GET') {
        $instrumentController->getAll();
    }
    // Configuration routes
    elseif ($path === 'configurations' && $requestMethod === 'GET') {
        $configController->getConfigurations();
    } elseif ($path === 'configurations' && $requestMethod === 'POST') {
        $configController->saveConfiguration();
    } elseif ($path === 'configurations/send' && $requestMethod === 'POST') {
        $configController->sendConfigurations();
    }
    // Notification routes
    elseif ($path === 'notifications' && $requestMethod === 'GET') {
        $notificationController->getNotifications();
    } elseif ($path === 'notifications/read' && $requestMethod === 'POST') {
        $notificationController->markAsRead();
    } elseif ($path === 'notifications/unread-count' && $requestMethod === 'GET') {
        $notificationController->getUnreadCount();
    }
    // 404
    else {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Endpoint no encontrado',
        ]);
    }
} catch (\Throwable $e) {
    $logMessage = sprintf(
        '[API] %s en %s:%d - %s',
        get_class($e),
        $e->getFile(),
        $e->getLine(),
        $e->getMessage()
    );
    $logContext = [
        'exception_class' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ];
    try {
        LoggerFactory::getLogger('api')->error($logMessage, $logContext);
    } catch (\Throwable $logError) {
        error_log($logMessage . "\n" . $e->getTraceAsString());
        error_log('Fallback log failed: ' . $logError->getMessage());
    }
    error_log($logMessage . "\nStack trace:\n" . $e->getTraceAsString());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor',
    ]);
}
