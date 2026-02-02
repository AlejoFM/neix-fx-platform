<?php

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;

require_once __DIR__ . '/../../../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Application\Services\PriceService;
use App\Application\Services\PriceTargetMonitorService;
use App\Infrastructure\Repositories\UserConfigurationRepository;
use App\Infrastructure\Repositories\InstrumentRepository;
use App\Infrastructure\Repositories\NotificationRepository;
use App\Infrastructure\Logger\LoggerFactory;

$root = __DIR__ . '/../../../';
try {
    Dotenv::createImmutable($root, '.env')->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    Dotenv::createImmutable($root . '.env', '.env')->load();
}

class PriceWebSocketHandler implements MessageComponentInterface
{
    protected $clients;
    private PriceService $priceService;
    private PriceTargetMonitorService $targetMonitor;
    private $logger;
    private $loop;
    private $periodicTimer;

    public function __construct($loop = null)
    {
        $this->clients = new \SplObjectStorage;
        $this->priceService = new PriceService();
        $this->logger = LoggerFactory::getLogger('websocket');
        $this->loop = $loop;
        
        // Inicializar monitor de precios objetivo
        $configRepository = new UserConfigurationRepository();
        $instrumentRepository = new InstrumentRepository();
        $notificationRepository = new NotificationRepository();
        $this->targetMonitor = new PriceTargetMonitorService(
            $configRepository,
            $instrumentRepository,
            $notificationRepository
        );
    }
    
    public function setupTimers($loop): void
    {
        $this->loop = $loop;
        
        // Enviar precios cada 5 segundos y verificar objetivos
        $this->logger->info('Configurando timer periódico de 5 segundos');
        
        // Guardar referencia al timer como propiedad de la clase para asegurar que se ejecute
        $this->periodicTimer = $this->loop->addPeriodicTimer(5, function () {
            $this->logger->info('Timer periódico ejecutado - iniciando broadcast', [
                'clientes_conectados' => count($this->clients),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            $this->broadcastPrices();
        });
        
        $this->logger->info('Timer periódico configurado correctamente', [
            'timer_id' => spl_object_id($this->periodicTimer)
        ]);
        
        // También enviar inmediatamente al inicio (después de 1 segundo)
        $this->loop->addTimer(1, function () {
            $this->logger->info('Enviando precios iniciales (timer único)');
            $this->broadcastPrices();
        });
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        $this->logger->info('Cliente conectado al canal de precios', [
            'connection_id' => $conn->resourceId,
        ]);

        // Enviar precios inmediatamente al conectar
        $this->sendPricesToClient($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        // El canal de precios es unidireccional (solo envía)
        // No procesamos mensajes entrantes
        $this->logger->debug('Mensaje recibido en canal de precios (ignorado)', [
            'connection_id' => $from->resourceId,
            'message' => $msg,
        ]);
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        $this->logger->info('Cliente desconectado del canal de precios', [
            'connection_id' => $conn->resourceId,
        ]);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->logger->error('Error en conexión WebSocket de precios', [
            'connection_id' => $conn->resourceId,
            'error' => $e->getMessage(),
        ]);
        $conn->close();
    }

    private function broadcastPrices(): void
    {
        $clientCount = count($this->clients);
        
        $this->logger->debug("Broadcast iniciado", [
            'clientes_conectados' => $clientCount,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        if ($clientCount === 0) {
            $this->logger->debug('No hay clientes conectados, omitiendo broadcast');
            return;
        }

        $this->logger->info("Broadcasting precios a $clientCount clientes");

        try {
            $prices = $this->priceService->fetchPrices();
            $data = array_map(fn($price) => $price->toArray(), $prices);

            $this->logger->info('Precios obtenidos del generador', [
                'count' => count($data),
                'precios' => array_map(fn($p) => [
                    'instrument' => $p->getInstrumentSymbol(),
                    'price' => $p->getPrice()
                ], $prices)
            ]);

            // Verificar si algún precio objetivo fue alcanzado
            try {
                $notifications = $this->targetMonitor->checkPriceTargets($prices);
                
                // Enviar notificaciones si hay objetivos alcanzados
                if (!empty($notifications)) {
                    $this->logger->info('Objetivos alcanzados', ['count' => count($notifications)]);
                    foreach ($notifications as $notification) {
                        $notificationMessage = json_encode([
                            'type' => 'target_reached',
                            'notification' => $notification->toArray(),
                            'timestamp' => date('Y-m-d H:i:s'),
                        ]);
                        
                        // Enviar a todos los clientes (en producción, filtrar por usuario)
                        foreach ($this->clients as $client) {
                            $client->send($notificationMessage);
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error('Error al verificar objetivos', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $message = json_encode([
                'type' => 'prices',
                'timestamp' => date('Y-m-d H:i:s'),
                'data' => $data,
            ]);

            $this->logger->info('Mensaje JSON preparado', [
                'tamaño' => strlen($message),
                'preview' => substr($message, 0, 150)
            ]);

            $sentCount = 0;
            $errors = 0;
            foreach ($this->clients as $client) {
                try {
                    $client->send($message);
                    $sentCount++;
                    $this->logger->debug("Mensaje enviado a cliente", [
                        'connection_id' => $client->resourceId ?? 'unknown'
                    ]);
                } catch (\Exception $e) {
                    $errors++;
                    $this->logger->error('Error al enviar mensaje a cliente', [
                        'error' => $e->getMessage(),
                        'connection_id' => $client->resourceId ?? 'unknown',
                    ]);
                }
            }

            $this->logger->info("Broadcast completado", [
                'enviados' => $sentCount,
                'errores' => $errors,
                'total_clientes' => $clientCount
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error al obtener precios para WebSocket', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function sendPricesToClient(ConnectionInterface $conn): void
    {
        try {
            $prices = $this->priceService->fetchPrices();
            $data = array_map(fn($price) => $price->toArray(), $prices);

            $message = json_encode([
                'type' => 'prices',
                'timestamp' => date('Y-m-d H:i:s'),
                'data' => $data,
            ]);

            $conn->send($message);

        } catch (\Exception $e) {
            $this->logger->error('Error al enviar precios iniciales', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

$port = $_ENV['WS_PRICES_PORT'] ?? 8081;
$logger = LoggerFactory::getLogger('websocket');

$logger->info("Iniciando servidor WebSocket de precios en puerto $port");

// Crear el handler sin loop inicialmente
$handler = new PriceWebSocketHandler();

// Crear el servidor - IoServer::factory() crea su propio loop
$server = IoServer::factory(
    new HttpServer(
        new WsServer($handler)
    ),
    $port,
    '0.0.0.0'
);

// Obtener el loop del servidor y configurar los timers
$serverLoop = $server->loop;
$handler->setupTimers($serverLoop);

$logger->info("Servidor WebSocket de precios iniciado en puerto $port", [
    'loop_class' => get_class($serverLoop)
]);

$server->run();
