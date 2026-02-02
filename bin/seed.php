<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Infrastructure\Database\DatabaseConnection;
use App\Application\Services\AuthService;
use App\Infrastructure\Repositories\UserRepository;

$root = __DIR__ . '/../';
try {
    Dotenv::createImmutable($root, '.env')->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    Dotenv::createImmutable($root . '.env', '.env')->load();
}

echo "Creando usuarios de prueba...\n";

try {
    $db = DatabaseConnection::getInstance();
    $userRepository = new UserRepository();
    $authService = new AuthService($userRepository);

    // Usuario 1
    $user1 = $userRepository->findByUsername('user1');
    if (!$user1) {
        $passwordHash = $authService->hashPassword('password123');
        $user1 = new \App\Domain\Entities\User(
            0,
            'user1',
            $passwordHash,
            new \DateTime()
        );
        $user1 = $userRepository->create($user1);
        echo "✓ Usuario 'user1' creado (password: password123)\n";
    } else {
        echo "⚠ Usuario 'user1' ya existe\n";
    }

    // Usuario 2
    $user2 = $userRepository->findByUsername('user2');
    if (!$user2) {
        $passwordHash = $authService->hashPassword('password123');
        $user2 = new \App\Domain\Entities\User(
            0,
            'user2',
            $passwordHash,
            new \DateTime()
        );
        $user2 = $userRepository->create($user2);
        echo "✓ Usuario 'user2' creado (password: password123)\n";
    } else {
        echo "⚠ Usuario 'user2' ya existe\n";
    }

    echo "✓ Seed completado\n";
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
