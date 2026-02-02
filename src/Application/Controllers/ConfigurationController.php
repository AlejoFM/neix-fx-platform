<?php

namespace App\Application\Controllers;

use App\Application\Services\UserConfigurationService;
use App\Application\Services\NotificationService;
use App\Infrastructure\Logger\LoggerFactory;

class ConfigurationController
{
    private UserConfigurationService $configService;
    private NotificationService $notificationService;
    private $logger;

    public function __construct(
        UserConfigurationService $configService,
        NotificationService $notificationService
    ) {
        $this->configService = $configService;
        $this->notificationService = $notificationService;
        $this->logger = LoggerFactory::getLogger('api');
    }

    public function getConfigurations(): void
    {
        header('Content-Type: application/json');
        session_start();

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            return;
        }

        try {
            $configurations = $this->configService->getUserConfigurations($_SESSION['user_id']);
            $data = array_map(fn($config) => $config->toArray(), $configurations);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error al obtener configuraciones',
            ]);
        }
    }

    public function saveConfiguration(): void
    {
        header('Content-Type: application/json');
        session_start();

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if ($input === null || $input === false) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Cuerpo JSON inválido o vacío',
                ]);
                return;
            }

            if (!isset($input['instrument_id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'instrument_id es requerido',
                ]);
                return;
            }

            $result = $this->configService->saveConfiguration(
                $_SESSION['user_id'],
                $input['instrument_id'],
                $input['target_price'] ?? null,
                $input['operation_type'] ?? 'buy'
            );

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            $this->logThrowable($e, 'Error al guardar configuración');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error interno del servidor',
            ]);
        }
    }

    public function sendConfigurations(): void
    {
        header('Content-Type: application/json');
        session_start();

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['configurations']) || !is_array($input['configurations'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'configurations debe ser un array',
                ]);
                return;
            }

            // Guardar configuraciones
            $result = $this->configService->saveMultipleConfigurations(
                $_SESSION['user_id'],
                $input['configurations']
            );

            // Crear notificación de éxito/error
            if (empty($result['errors'])) {
                $this->notificationService->createNotification(
                    $_SESSION['user_id'],
                    'success',
                    'Configuraciones enviadas',
                    'Todas las configuraciones se enviaron correctamente'
                );
            } else {
                $this->notificationService->createNotification(
                    $_SESSION['user_id'],
                    'warning',
                    'Configuraciones parcialmente enviadas',
                    count($result['errors']) . ' configuraciones tuvieron errores'
                );
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $result,
                'message' => 'Configuraciones procesadas',
            ]);

        } catch (\Throwable $e) {
            $this->logThrowable($e, 'Error al enviar configuraciones');

            if (isset($_SESSION['user_id'])) {
                $this->notificationService->createNotification(
                    $_SESSION['user_id'],
                    'error',
                    'Error al enviar configuraciones',
                    'Ocurrió un error al procesar las configuraciones'
                );
            }

            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error interno del servidor',
            ]);
        }
    }

    private function logThrowable(\Throwable $e, string $message): void
    {
        $context = [
            'exception_class' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ];
        if ($e->getPrevious() !== null) {
            $context['previous'] = [
                'class' => get_class($e->getPrevious()),
                'message' => $e->getPrevious()->getMessage(),
                'trace' => $e->getPrevious()->getTraceAsString(),
            ];
        }
        $this->logger->error($message . ': ' . get_class($e) . ' - ' . $e->getMessage(), $context);
    }
}
