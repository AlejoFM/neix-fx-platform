<?php

namespace App\Domain\Entities;

class Notification
{
    private int $id;
    private int $userId;
    private string $type; // 'success', 'error', 'info', 'warning'
    private string $title;
    private string $message;
    private bool $isRead;
    private \DateTime $createdAt;

    public function __construct(
        int $id,
        int $userId,
        string $type,
        string $title,
        string $message,
        bool $isRead,
        \DateTime $createdAt
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->type = $type;
        $this->title = $title;
        $this->message = $message;
        $this->isRead = $isRead;
        $this->createdAt = $createdAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function markAsRead(): void
    {
        $this->isRead = true;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'is_read' => $this->isRead,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
