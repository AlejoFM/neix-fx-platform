<?php

namespace App\Application\Controllers;

use App\Application\Services\NotificationService;

class NotificationController
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function getNotifications(): void
    {
        header('Content-Type: application/json');
        session_start();

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            return;
        }

        try {
            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
            $notifications = $this->notificationService->getUserNotifications(
                $_SESSION['user_id'],
                $limit
            );
            $data = array_map(fn($notif) => $notif->toArray(), $notifications);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error al obtener notificaciones',
            ]);
        }
    }

    public function markAsRead(): void
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

            if (!isset($input['notification_id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'notification_id es requerido',
                ]);
                return;
            }

            $success = $this->notificationService->markAsRead($input['notification_id']);

            http_response_code(200);
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Notificación marcada como leída' : 'Error al marcar notificación',
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error interno del servidor',
            ]);
        }
    }

    public function getUnreadCount(): void
    {
        header('Content-Type: application/json');
        session_start();

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
            return;
        }

        try {
            $count = $this->notificationService->getUnreadCount($_SESSION['user_id']);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'count' => $count,
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error al obtener conteo',
            ]);
        }
    }
}
