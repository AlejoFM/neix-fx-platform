<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Infrastructure\Repositories\UserRepository;
use App\Application\Services\AuthService;
use App\Domain\Entities\User;
use App\Infrastructure\Database\DatabaseConnection;

/**
 * @group integration
 */
class AuthServiceTest extends TestCase
{
    private AuthService $authService;
    private UserRepository $userRepository;
    private ?string $testUsername = null;

    protected function setUp(): void
    {
        try {
            DatabaseConnection::reset();
            $this->userRepository = new UserRepository();
            $this->authService = new AuthService($this->userRepository);
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        DatabaseConnection::reset();
    }

    public function testAuthenticateWithValidCredentials(): void
    {
        $this->testUsername = 'testuser_' . uniqid();
        $passwordHash = $this->authService->hashPassword('testpassword');
        $user = new User(0, $this->testUsername, $passwordHash, new \DateTime());
        $user = $this->userRepository->create($user);

        $authenticated = $this->authService->authenticate($this->testUsername, 'testpassword');

        $this->assertNotNull($authenticated);
        $this->assertEquals($user->getId(), $authenticated->getId());
    }

    public function testAuthenticateWithInvalidCredentials(): void
    {
        $authenticated = $this->authService->authenticate('nonexistent_' . uniqid(), 'password');
        $this->assertNull($authenticated);
    }
}
