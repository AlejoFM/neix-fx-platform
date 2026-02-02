<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\User;
use App\Domain\Interfaces\UserRepositoryInterface;
use App\Infrastructure\Database\DatabaseConnection;
use PDO;

class UserRepository implements UserRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseConnection::getInstance();
    }

    public function findByUsername(string $username): ?User
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, password_hash, created_at, updated_at 
             FROM users 
             WHERE username = :username'
        );
        $stmt->execute(['username' => $username]);
        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, password_hash, created_at, updated_at 
             FROM users 
             WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    public function create(User $user): User
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, password_hash) 
             VALUES (:username, :password_hash)'
        );
        $stmt->execute([
            'username' => $user->getUsername(),
            'password_hash' => $user->getPasswordHash(),
        ]);

        $id = (int) $this->db->lastInsertId();
        return $this->findById($id);
    }

    private function hydrate(array $data): User
    {
        return new User(
            (int) $data['id'],
            $data['username'],
            $data['password_hash'],
            new \DateTime($data['created_at']),
            $data['updated_at'] ? new \DateTime($data['updated_at']) : null
        );
    }
}
