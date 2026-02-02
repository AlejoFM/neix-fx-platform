<?php

namespace App\Domain\Entities;

class UserConfiguration
{
    private int $id;
    private int $userId;
    private int $instrumentId;
    private ?float $targetPrice;
    private string $operationType; // 'buy' | 'sell'
    private bool $isActive;
    private \DateTime $createdAt;
    private ?\DateTime $updatedAt;

    public function __construct(
        int $id,
        int $userId,
        int $instrumentId,
        ?float $targetPrice,
        string $operationType,
        bool $isActive,
        \DateTime $createdAt,
        ?\DateTime $updatedAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->instrumentId = $instrumentId;
        $this->targetPrice = $targetPrice;
        $this->operationType = $operationType;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getInstrumentId(): int
    {
        return $this->instrumentId;
    }

    public function getTargetPrice(): ?float
    {
        return $this->targetPrice;
    }

    public function getOperationType(): string
    {
        return $this->operationType;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function validate(): array
    {
        $errors = [];

        if ($this->targetPrice !== null && $this->targetPrice <= 0) {
            $errors[] = 'El precio objetivo debe ser mayor a cero';
        }

        if (!in_array($this->operationType, ['buy', 'sell'])) {
            $errors[] = 'El tipo de operación debe ser "buy" o "sell"';
        }

        return $errors;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'instrument_id' => $this->instrumentId,
            'target_price' => $this->targetPrice,
            'operation_type' => $this->operationType,
            'is_active' => $this->isActive,
        ];
    }
}
