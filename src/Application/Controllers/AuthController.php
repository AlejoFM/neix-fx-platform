<?php

namespace App\Application\Controllers;

use App\Application\Services\AuthService;
use App\Infrastructure\Logger\LoggerFactory;

class AuthController
{
    private AuthService $authService;
    private $logger;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
        $this->logger = LoggerFactory::getLogger('api');
    }

    public function login(): void
    {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['username']) || !isset($input['password'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Usuario y contraseña son requeridos',
                ]);
                return;
            }

            $user = $this->authService->authenticate(
                $input['username'],
                $input['password']
            );

            if (!$user) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'Credenciales inválidas',
                ]);
                return;
            }

            // Iniciar sesión
            session_start();
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['username'] = $user->getUsername();

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'user' => $user->toArray(),
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error en login', [
                'error' => $e->getMessage(),
            ]);
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error interno del servidor',
            ]);
        }
    }

    public function logout(): void
    {
        header('Content-Type: application/json');
        session_start();
        session_destroy();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Sesión cerrada',
        ]);
    }

    public function getCurrentUser(): void
    {
        header('Content-Type: application/json');
        session_start();

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'No autenticado',
            ]);
            return;
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
            ],
        ]);
    }
}
