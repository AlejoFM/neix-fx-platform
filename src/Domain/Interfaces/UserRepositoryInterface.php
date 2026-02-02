<?php

namespace App\Domain\Interfaces;

use App\Domain\Entities\User;

interface UserRepositoryInterface
{
    public function findByUsername(string $username): ?User;
    public function findById(int $id): ?User;
    public function create(User $user): User;
}
