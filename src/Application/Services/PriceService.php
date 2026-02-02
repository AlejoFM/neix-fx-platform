<?php

namespace App\Application\Services;

use App\Domain\Entities\Price;
use App\Infrastructure\Logger\LoggerFactory;

class PriceService
{
    private $logger;
    private string $priceGeneratorUrl;

    public function __construct()
    {
        $this->logger = LoggerFactory::getLogger('prices');
        $this->priceGeneratorUrl = $_ENV['PRICE_GENERATOR_URL'] ?? 'http://price-generator:5000';
    }

    public function fetchPrices(): array
    {
        try {
            $ch = curl_init($this->priceGeneratorUrl . '/prices');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new \RuntimeException("Error al obtener precios: HTTP $httpCode");
            }

            $data = json_decode($response, true);
            if (!$data || !isset($data['prices'])) {
                throw new \RuntimeException("Formato de respuesta inválido");
            }

            $prices = [];
            foreach ($data['prices'] as $priceData) {
                $prices[] = new Price(
                    $priceData['instrument'],
                    (float) $priceData['price'],
                    new \DateTime($priceData['timestamp'])
                );
            }

            $this->logger->debug('Precios obtenidos', ['count' => count($prices)]);
            return $prices;

        } catch (\Exception $e) {
            $this->logger->error('Error al obtener precios', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
