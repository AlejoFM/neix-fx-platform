<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Instrument;
use App\Domain\Interfaces\InstrumentRepositoryInterface;
use App\Infrastructure\Database\DatabaseConnection;
use PDO;

class InstrumentRepository implements InstrumentRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseConnection::getInstance();
    }

    public function findAll(): array
    {
        $stmt = $this->db->query(
            'SELECT id, symbol, name, base_currency, quote_currency, is_active, created_at 
             FROM instruments 
             WHERE is_active = 1 
             ORDER BY symbol'
        );
        $data = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $data);
    }

    public function findBySymbol(string $symbol): ?Instrument
    {
        $stmt = $this->db->prepare(
            'SELECT id, symbol, name, base_currency, quote_currency, is_active, created_at 
             FROM instruments 
             WHERE symbol = :symbol'
        );
        $stmt->execute(['symbol' => $symbol]);
        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    public function findById(int $id): ?Instrument
    {
        $stmt = $this->db->prepare(
            'SELECT id, symbol, name, base_currency, quote_currency, is_active, created_at 
             FROM instruments 
             WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    private function hydrate(array $data): Instrument
    {
        return new Instrument(
            (int) $data['id'],
            $data['symbol'],
            $data['name'],
            $data['base_currency'],
            $data['quote_currency'],
            (bool) $data['is_active'],
            new \DateTime($data['created_at'])
        );
    }
}
