<?php

namespace App\Domain\Entities;

class Price
{
    private string $instrumentSymbol;
    private float $price;
    private \DateTime $timestamp;

    public function __construct(
        string $instrumentSymbol,
        float $price,
        \DateTime $timestamp
    ) {
        $this->instrumentSymbol = $instrumentSymbol;
        $this->price = $price;
        $this->timestamp = $timestamp;
    }

    public function getInstrumentSymbol(): string
    {
        return $this->instrumentSymbol;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    public function toArray(): array
    {
        return [
            'instrument' => $this->instrumentSymbol,
            'price' => $this->price,
            'timestamp' => $this->timestamp->format('Y-m-d H:i:s.u'),
        ];
    }
}
