<?php

namespace App\Application\Services;

use App\Domain\Entities\Price;
use App\Domain\Entities\UserConfiguration;
use App\Domain\Interfaces\UserConfigurationRepositoryInterface;
use App\Domain\Interfaces\InstrumentRepositoryInterface;
use App\Domain\Interfaces\NotificationRepositoryInterface;
use App\Infrastructure\Logger\LoggerFactory;

class PriceTargetMonitorService
{
    private UserConfigurationRepositoryInterface $configRepository;
    private InstrumentRepositoryInterface $instrumentRepository;
    private NotificationRepositoryInterface $notificationRepository;
    private $logger;

    public function __construct(
        UserConfigurationRepositoryInterface $configRepository,
        InstrumentRepositoryInterface $instrumentRepository,
        NotificationRepositoryInterface $notificationRepository
    ) {
        $this->configRepository = $configRepository;
        $this->instrumentRepository = $instrumentRepository;
        $this->notificationRepository = $notificationRepository;
        $this->logger = LoggerFactory::getLogger('prices');
    }

    /**
     * Verifica si algún precio objetivo fue alcanzado
     * @param Price[] $currentPrices
     * @return array Array de notificaciones generadas
     */
    public function checkPriceTargets(array $currentPrices): array
    {
        $notifications = [];
        
        // Crear mapa de precios por ID de instrumento
        $pricesByInstrumentId = [];
        foreach ($currentPrices as $price) {
            $instrument = $this->instrumentRepository->findBySymbol($price->getInstrumentSymbol());
            if ($instrument) {
                $pricesByInstrumentId[$instrument->getId()] = $price;
            }
        }

        // Obtener todas las configuraciones activas con precio objetivo
        $allConfigs = $this->configRepository->findAllActiveWithTargetPrice();

        foreach ($allConfigs as $config) {
            $instrumentId = $config->getInstrumentId();
            
            // Verificar si tenemos precio actual para este instrumento
            if (!isset($pricesByInstrumentId[$instrumentId])) {
                continue;
            }

            $currentPrice = $pricesByInstrumentId[$instrumentId];
            $targetPrice = $config->getTargetPrice();
            $currentPriceValue = $currentPrice->getPrice();
            $operationType = $config->getOperationType();
            
            // Verificar si el objetivo fue alcanzado según el tipo de operación
            $targetReached = false;
            if ($operationType === 'buy') {
                // Para compra: precio actual >= precio objetivo (comprar cuando sube)
                $targetReached = $currentPriceValue >= $targetPrice;
            } elseif ($operationType === 'sell') {
                // Para venta: precio actual <= precio objetivo (vender cuando baja)
                $targetReached = $currentPriceValue <= $targetPrice;
            }

            // Notificar cada vez que se alcance el objetivo (sin verificación de duplicados)
            if ($targetReached) {
                // Objetivo alcanzado - crear notificación
                $instrument = $this->instrumentRepository->findById($instrumentId);
                $instrumentSymbol = $instrument ? $instrument->getSymbol() : 'Unknown';
                
                $notification = new \App\Domain\Entities\Notification(
                    0,
                    $config->getUserId(),
                    'success',
                    'Precio Objetivo Alcanzado',
                    sprintf(
                        'El precio objetivo de %s para %s (%s) ha sido alcanzado. Precio actual: %.6f, Objetivo: %.6f',
                        $operationType === 'buy' ? 'compra' : 'venta',
                        $instrumentSymbol,
                        $operationType === 'buy' ? 'Compra' : 'Venta',
                        $currentPriceValue,
                        $targetPrice
                    ),
                    false,
                    new \DateTime()
                );

                $created = $this->notificationRepository->create($notification);
                $notifications[] = $created;
                
                $this->logger->info('Precio objetivo alcanzado', [
                    'user_id' => $config->getUserId(),
                    'instrument_id' => $instrumentId,
                    'target_price' => $targetPrice,
                    'current_price' => $currentPriceValue,
                    'operation_type' => $operationType,
                ]);
            }
        }

        return $notifications;
    }

}
