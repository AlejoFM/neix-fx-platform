<?php

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;

require_once __DIR__ . '/../../../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Infrastructure\Repositories\UserRepository;
use App\Infrastructure\Repositories\UserConfigurationRepository;
use App\Infrastructure\Repositories\InstrumentRepository;
use App\Application\Services\UserConfigurationService;
use App\Application\Services\NotificationService;
use App\Infrastructure\Logger\LoggerFactory;

$root = __DIR__ . '/../../../';
try {
    Dotenv::createImmutable($root, '.env')->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    Dotenv::createImmutable($root . '.env', '.env')->load();
}

class ConfigWebSocketHandler implements MessageComponentInterface
{
    protected $clients;
    private UserConfigurationService $configService;
    private NotificationService $notificationService;
    private $logger;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        
        $userRepository = new UserRepository();
        $instrumentRepository = new InstrumentRepository();
        $configRepository = new UserConfigurationRepository();
        $notificationRepository = new \App\Infrastructure\Repositories\NotificationRepository();
        
        $this->configService = new UserConfigurationService($configRepository, $instrumentRepository);
        $this->notificationService = new NotificationService($notificationRepository);
        $this->logger = LoggerFactory::getLogger('websocket');
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        $this->logger->info('Cliente conectado al canal de configuraciones', [
            'connection_id' => $conn->resourceId,
        ]);

        // Enviar mensaje de bienvenida
        $conn->send(json_encode([
            'type' => 'connection',
            'status' => 'connected',
            'message' => 'Conectado al canal de configuraciones',
        ]));
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $this->logger->debug('Mensaje recibido en canal de configuraciones', [
            'connection_id' => $from->resourceId,
            'message' => $msg,
        ]);

        try {
            $data = json_decode($msg, true);

            if (!$data) {
                throw new \InvalidArgumentException('Mensaje JSON inválido');
            }

            // Validar que tenga usuario y configuraciones
            if (!isset($data['user_id']) || !isset($data['configurations'])) {
                throw new \InvalidArgumentException('user_id y configurations son requeridos');
            }

            $userId = (int) $data['user_id'];
            $configurations = $data['configurations'];
            $timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');

            // Validar y guardar configuraciones
            $result = $this->configService->saveMultipleConfigurations($userId, $configurations);

            // Crear notificación
            if (empty($result['errors'])) {
                $this->notificationService->createNotification(
                    $userId,
                    'success',
                    'Configuraciones enviadas',
                    'Todas las configuraciones se enviaron correctamente vía WebSocket'
                );

                $response = [
                    'type' => 'success',
                    'timestamp' => $timestamp,
                    'message' => 'Configuraciones procesadas correctamente',
                    'data' => $result,
                ];
            } else {
                $this->notificationService->createNotification(
                    $userId,
                    'warning',
                    'Configuraciones parcialmente enviadas',
                    count($result['errors']) . ' configuraciones tuvieron errores'
                );

                $response = [
                    'type' => 'warning',
                    'timestamp' => $timestamp,
                    'message' => 'Algunas configuraciones tuvieron errores',
                    'data' => $result,
                ];
            }

            // Enviar respuesta al cliente
            $from->send(json_encode($response));

            // Broadcast a otros clientes del mismo usuario (si hay múltiples conexiones)
            foreach ($this->clients as $client) {
                if ($client !== $from) {
                    $client->send(json_encode([
                        'type' => 'notification',
                        'user_id' => $userId,
                        'message' => 'Configuraciones actualizadas',
                    ]));
                }
            }

            $this->logger->info('Configuraciones procesadas vía WebSocket', [
                'user_id' => $userId,
                'success_count' => count($result['success']),
                'error_count' => count($result['errors']),
            ]);

        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Error de validación en WebSocket', [
                'error' => $e->getMessage(),
            ]);

            $from->send(json_encode([
                'type' => 'error',
                'message' => $e->getMessage(),
            ]));

        } catch (\Exception $e) {
            $this->logger->error('Error al procesar configuraciones vía WebSocket', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $from->send(json_encode([
                'type' => 'error',
                'message' => 'Error interno del servidor',
            ]));
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        $this->logger->info('Cliente desconectado del canal de configuraciones', [
            'connection_id' => $conn->resourceId,
        ]);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->logger->error('Error en conexión WebSocket de configuraciones', [
            'connection_id' => $conn->resourceId,
            'error' => $e->getMessage(),
        ]);
        $conn->close();
    }
}

$port = $_ENV['WS_CONFIG_PORT'] ?? 8082;
$logger = LoggerFactory::getLogger('websocket');

$logger->info("Iniciando servidor WebSocket de configuraciones en puerto $port");

$loop = Factory::create();
$handler = new ConfigWebSocketHandler();

$server = IoServer::factory(
    new HttpServer(
        new WsServer($handler)
    ),
    $port,
    '0.0.0.0'
);

$logger->info("Servidor WebSocket de configuraciones iniciado en puerto $port");
$server->run();
