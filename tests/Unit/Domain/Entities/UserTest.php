<?php

namespace Tests\Unit\Domain\Entities;

use PHPUnit\Framework\TestCase;
use App\Domain\Entities\User;

class UserTest extends TestCase
{
    public function testUserCreation(): void
    {
        $user = new User(
            1,
            'testuser',
            password_hash('password123', PASSWORD_BCRYPT),
            new \DateTime()
        );

        $this->assertEquals(1, $user->getId());
        $this->assertEquals('testuser', $user->getUsername());
    }

    public function testPasswordVerification(): void
    {
        $password = 'password123';
        $hash = password_hash($password, PASSWORD_BCRYPT);
        
        $user = new User(1, 'testuser', $hash, new \DateTime());
        
        $this->assertTrue($user->verifyPassword($password));
        $this->assertFalse($user->verifyPassword('wrongpassword'));
    }

    public function testToArray(): void
    {
        $user = new User(
            1,
            'testuser',
            password_hash('password123', PASSWORD_BCRYPT),
            new \DateTime('2024-01-01 00:00:00')
        );

        $array = $user->toArray();
        
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('username', $array);
        $this->assertArrayNotHasKey('password_hash', $array);
    }
}
