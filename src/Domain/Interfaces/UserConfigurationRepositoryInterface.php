<?php

namespace App\Domain\Interfaces;

use App\Domain\Entities\UserConfiguration;

interface UserConfigurationRepositoryInterface
{
    public function findByUserId(int $userId): array;
    public function findByUserIdAndInstrumentId(int $userId, int $instrumentId): ?UserConfiguration;
    public function findAllActiveWithTargetPrice(): array;
    public function save(UserConfiguration $configuration): UserConfiguration;
    public function delete(int $configurationId): bool;
    public function deleteByUserId(int $userId): bool;
}
