<?php

namespace App\Domain\Interfaces;

use App\Domain\Entities\Instrument;

interface InstrumentRepositoryInterface
{
    public function findAll(): array;
    public function findBySymbol(string $symbol): ?Instrument;
    public function findById(int $id): ?Instrument;
}
