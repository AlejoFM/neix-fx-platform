<?php

namespace App\Application\Services;

use App\Domain\Entities\UserConfiguration;
use App\Domain\Interfaces\UserConfigurationRepositoryInterface;
use App\Domain\Interfaces\InstrumentRepositoryInterface;
use App\Infrastructure\Logger\LoggerFactory;

class UserConfigurationService
{
    private UserConfigurationRepositoryInterface $configRepository;
    private InstrumentRepositoryInterface $instrumentRepository;
    private $logger;

    public function __construct(
        UserConfigurationRepositoryInterface $configRepository,
        InstrumentRepositoryInterface $instrumentRepository
    ) {
        $this->configRepository = $configRepository;
        $this->instrumentRepository = $instrumentRepository;
        $this->logger = LoggerFactory::getLogger('api');
    }

    public function getUserConfigurations(int $userId): array
    {
        return $this->configRepository->findByUserId($userId);
    }

    public function saveConfiguration(
        int $userId,
        int $instrumentId,
        ?float $targetPrice,
        string $operationType
    ): array {
        // Validar que el instrumento existe
        $instrument = $this->instrumentRepository->findById($instrumentId);
        if (!$instrument) {
            throw new \InvalidArgumentException("Instrumento no encontrado");
        }

        // Validar tipo de operación
        if (!in_array($operationType, ['buy', 'sell'])) {
            throw new \InvalidArgumentException("Tipo de operación inválido");
        }

        // Crear o actualizar configuración
        $configuration = new UserConfiguration(
            0, // ID temporal, se asignará al guardar
            $userId,
            $instrumentId,
            $targetPrice,
            $operationType,
            true,
            new \DateTime(),
            null
        );

        // Validar configuración
        $errors = $configuration->validate();
        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }

        $saved = $this->configRepository->save($configuration);
        $this->logger->info('Configuración guardada', [
            'user_id' => $userId,
            'instrument_id' => $instrumentId,
        ]);

        return $saved->toArray();
    }

    public function saveMultipleConfigurations(int $userId, array $configurations): array
    {
        $results = [];
        $errors = [];

        foreach ($configurations as $config) {
            try {
                $result = $this->saveConfiguration(
                    $userId,
                    $config['instrument_id'],
                    $config['target_price'] ?? null,
                    $config['operation_type'] ?? 'buy'
                );
                $results[] = $result;
            } catch (\Exception $e) {
                $errors[] = [
                    'instrument_id' => $config['instrument_id'] ?? null,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => $results,
            'errors' => $errors,
        ];
    }
}
