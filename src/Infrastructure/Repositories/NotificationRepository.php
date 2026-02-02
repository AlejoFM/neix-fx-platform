<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Notification;
use App\Domain\Interfaces\NotificationRepositoryInterface;
use App\Infrastructure\Database\DatabaseConnection;
use PDO;

class NotificationRepository implements NotificationRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseConnection::getInstance();
    }

    public function create(Notification $notification): Notification
    {
        $stmt = $this->db->prepare(
            'INSERT INTO notifications (user_id, type, title, message, is_read) 
             VALUES (:user_id, :type, :title, :message, :is_read)'
        );
        $stmt->execute([
            'user_id' => $notification->getUserId(),
            'type' => $notification->getType(),
            'title' => $notification->getTitle(),
            'message' => $notification->getMessage(),
            'is_read' => $notification->isRead() ? 1 : 0,
        ]);

        $id = (int) $this->db->lastInsertId();
        return $this->findById($id);
    }

    public function findByUserId(int $userId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, user_id, type, title, message, is_read, created_at 
             FROM notifications 
             WHERE user_id = :user_id 
             ORDER BY created_at DESC 
             LIMIT :limit'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll();

        return array_map([$this, 'hydrate'], $data);
    }

    public function markAsRead(int $notificationId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE notifications 
             SET is_read = 1 
             WHERE id = :id'
        );
        return $stmt->execute(['id' => $notificationId]);
    }

    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as count 
             FROM notifications 
             WHERE user_id = :user_id AND is_read = 0'
        );
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch();

        return (int) ($result['count'] ?? 0);
    }

    private function findById(int $id): ?Notification
    {
        $stmt = $this->db->prepare(
            'SELECT id, user_id, type, title, message, is_read, created_at 
             FROM notifications 
             WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    private function hydrate(array $data): Notification
    {
        return new Notification(
            (int) $data['id'],
            (int) $data['user_id'],
            $data['type'],
            $data['title'],
            $data['message'],
            (bool) $data['is_read'],
            new \DateTime($data['created_at'])
        );
    }
}
