<?php

namespace App\Domain\Entities;

class Instrument
{
    private int $id;
    private string $symbol;
    private string $name;
    private string $baseCurrency;
    private string $quoteCurrency;
    private bool $isActive;
    private \DateTime $createdAt;

    public function __construct(
        int $id,
        string $symbol,
        string $name,
        string $baseCurrency,
        string $quoteCurrency,
        bool $isActive,
        \DateTime $createdAt
    ) {
        $this->id = $id;
        $this->symbol = $symbol;
        $this->name = $name;
        $this->baseCurrency = $baseCurrency;
        $this->quoteCurrency = $quoteCurrency;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getBaseCurrency(): string
    {
        return $this->baseCurrency;
    }

    public function getQuoteCurrency(): string
    {
        return $this->quoteCurrency;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'symbol' => $this->symbol,
            'name' => $this->name,
            'base_currency' => $this->baseCurrency,
            'quote_currency' => $this->quoteCurrency,
            'is_active' => $this->isActive,
        ];
    }
}
