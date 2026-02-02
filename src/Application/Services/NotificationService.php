<?php

namespace App\Application\Services;

use App\Domain\Entities\Notification;
use App\Domain\Interfaces\NotificationRepositoryInterface;
use App\Infrastructure\Logger\LoggerFactory;

class NotificationService
{
    private NotificationRepositoryInterface $notificationRepository;
    private $logger;

    public function __construct(NotificationRepositoryInterface $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;
        $this->logger = LoggerFactory::getLogger('api');
    }

    public function createNotification(
        int $userId,
        string $type,
        string $title,
        string $message
    ): Notification {
        if (!in_array($type, ['success', 'error', 'info', 'warning'])) {
            throw new \InvalidArgumentException("Tipo de notificación inválido");
        }

        $notification = new Notification(
            0,
            $userId,
            $type,
            $title,
            $message,
            false,
            new \DateTime()
        );

        $created = $this->notificationRepository->create($notification);
        $this->logger->info('Notificación creada', [
            'user_id' => $userId,
            'type' => $type,
        ]);

        return $created;
    }

    public function getUserNotifications(int $userId, int $limit = 10): array
    {
        return $this->notificationRepository->findByUserId($userId, $limit);
    }

    public function markAsRead(int $notificationId): bool
    {
        return $this->notificationRepository->markAsRead($notificationId);
    }

    public function getUnreadCount(int $userId): int
    {
        return $this->notificationRepository->getUnreadCount($userId);
    }
}
