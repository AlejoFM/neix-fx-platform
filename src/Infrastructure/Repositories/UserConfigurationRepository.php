<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\UserConfiguration;
use App\Domain\Interfaces\UserConfigurationRepositoryInterface;
use App\Infrastructure\Database\DatabaseConnection;
use PDO;

class UserConfigurationRepository implements UserConfigurationRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseConnection::getInstance();
    }

    public function findByUserId(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, user_id, instrument_id, target_price, operation_type, is_active, created_at, updated_at 
             FROM user_configurations 
             WHERE user_id = :user_id AND is_active = 1'
        );
        $stmt->execute(['user_id' => $userId]);
        $data = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $data);
    }

    public function findByUserIdAndInstrumentId(int $userId, int $instrumentId): ?UserConfiguration
    {
        $stmt = $this->db->prepare(
            'SELECT id, user_id, instrument_id, target_price, operation_type, is_active, created_at, updated_at 
             FROM user_configurations 
             WHERE user_id = :user_id AND instrument_id = :instrument_id AND is_active = 1'
        );
        $stmt->execute([
            'user_id' => $userId,
            'instrument_id' => $instrumentId,
        ]);
        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    public function findAllActiveWithTargetPrice(): array
    {
        $stmt = $this->db->query(
            'SELECT id, user_id, instrument_id, target_price, operation_type, is_active, created_at, updated_at 
             FROM user_configurations 
             WHERE is_active = 1 AND target_price IS NOT NULL'
        );
        $data = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $data);
    }

    public function save(UserConfiguration $configuration): UserConfiguration
    {
        $existing = $this->findByUserIdAndInstrumentId(
            $configuration->getUserId(),
            $configuration->getInstrumentId()
        );

        if ($existing) {
            return $this->update($configuration, $existing->getId());
        }

        return $this->insert($configuration);
    }

    private function insert(UserConfiguration $configuration): UserConfiguration
    {
        $stmt = $this->db->prepare(
            'INSERT INTO user_configurations (user_id, instrument_id, target_price, operation_type, is_active) 
             VALUES (:user_id, :instrument_id, :target_price, :operation_type, :is_active)'
        );
        $stmt->execute([
            'user_id' => $configuration->getUserId(),
            'instrument_id' => $configuration->getInstrumentId(),
            'target_price' => $configuration->getTargetPrice(),
            'operation_type' => $configuration->getOperationType(),
            'is_active' => $configuration->isActive() ? 1 : 0,
        ]);

        $id = (int) $this->db->lastInsertId();
        if ($id <= 0) {
            throw new \RuntimeException('No se pudo obtener el ID de la configuración insertada');
        }
        $saved = $this->findById($id);
        if ($saved === null) {
            throw new \RuntimeException('Configuración no encontrada tras insertar');
        }
        return $saved;
    }

    private function update(UserConfiguration $configuration, int $id): UserConfiguration
    {
        $stmt = $this->db->prepare(
            'UPDATE user_configurations 
             SET target_price = :target_price, 
                 operation_type = :operation_type, 
                 is_active = :is_active,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            'target_price' => $configuration->getTargetPrice(),
            'operation_type' => $configuration->getOperationType(),
            'is_active' => $configuration->isActive() ? 1 : 0,
            'id' => $id,
        ]);

        $saved = $this->findById($id);
        if ($saved === null) {
            throw new \RuntimeException('Configuración no encontrada tras actualizar');
        }
        return $saved;
    }

    public function delete(int $configurationId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE user_configurations 
             SET is_active = 0 
             WHERE id = :id'
        );
        return $stmt->execute(['id' => $configurationId]);
    }

    public function deleteByUserId(int $userId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE user_configurations 
             SET is_active = 0 
             WHERE user_id = :user_id'
        );
        return $stmt->execute(['user_id' => $userId]);
    }

    private function findById(int $id): ?UserConfiguration
    {
        $stmt = $this->db->prepare(
            'SELECT id, user_id, instrument_id, target_price, operation_type, is_active, created_at, updated_at 
             FROM user_configurations 
             WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    private function hydrate(array $data): UserConfiguration
    {
        return new UserConfiguration(
            (int) $data['id'],
            (int) $data['user_id'],
            (int) $data['instrument_id'],
            $data['target_price'] !== null ? (float) $data['target_price'] : null,
            $data['operation_type'],
            (bool) $data['is_active'],
            new \DateTime($data['created_at']),
            $data['updated_at'] ? new \DateTime($data['updated_at']) : null
        );
    }
}
