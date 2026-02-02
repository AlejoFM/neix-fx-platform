<?php

namespace App\Domain\Interfaces;

use App\Domain\Entities\Notification;

interface NotificationRepositoryInterface
{
    public function create(Notification $notification): Notification;
    public function findByUserId(int $userId, int $limit = 10): array;
    public function markAsRead(int $notificationId): bool;
    public function getUnreadCount(int $userId): int;
}
