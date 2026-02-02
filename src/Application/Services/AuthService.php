<?php

namespace App\Application\Services;

use App\Domain\Entities\User;
use App\Domain\Interfaces\UserRepositoryInterface;
use App\Infrastructure\Logger\LoggerFactory;

class AuthService
{
    private UserRepositoryInterface $userRepository;
    private $logger;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
        $this->logger = LoggerFactory::getLogger('auth');
    }

    public function authenticate(string $username, string $password): ?User
    {
        $this->logger->info('Intento de autenticación', ['username' => $username]);

        $user = $this->userRepository->findByUsername($username);

        if (!$user) {
            $this->logger->warning('Usuario no encontrado', ['username' => $username]);
            return null;
        }

        if (!$user->verifyPassword($password)) {
            $this->logger->warning('Contraseña incorrecta', ['username' => $username]);
            return null;
        }

        $this->logger->info('Autenticación exitosa', ['user_id' => $user->getId()]);
        return $user;
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}
