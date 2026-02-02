<?php

namespace App\Application\Services;

use App\Domain\Entities\Instrument;
use App\Domain\Interfaces\InstrumentRepositoryInterface;

class InstrumentService
{
    private InstrumentRepositoryInterface $instrumentRepository;

    public function __construct(InstrumentRepositoryInterface $instrumentRepository)
    {
        $this->instrumentRepository = $instrumentRepository;
    }

    public function getAllInstruments(): array
    {
        return $this->instrumentRepository->findAll();
    }

    public function getInstrumentBySymbol(string $symbol): ?Instrument
    {
        return $this->instrumentRepository->findBySymbol($symbol);
    }

    public function getInstrumentById(int $id): ?Instrument
    {
        return $this->instrumentRepository->findById($id);
    }
}
