<?php
require_once __DIR__ . '/BaseRepository.php';

class NotificationRepository extends BaseRepository {

    /* ===================== CREATE NOTIFICATION ===================== */
    public function create($data) {
        $sql = "INSERT INTO notifications (user_id, message, tenant_id)
                VALUES (:user_id, :message, :tenant_id)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id'   => $data['user_id'],
            ':message'   => $data['message'],
            ':tenant_id' => $data['tenant_id']
        ]);

        return $this->db->lastInsertId();
    }

    /* ===================== GET USER NOTIFICATIONS ===================== */
  public function getUnreadByUserId($userId, $tenantId, $limit = 50) {
    $sql = "
        SELECT *
        FROM notifications
        WHERE user_id = :user_id
          AND tenant_id = :tenant_id
          AND read_status = 0
        ORDER BY created_at DESC
        LIMIT :limit
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':tenant_id', $tenantId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


    /* ===================== UNREAD COUNT (DOT LOGIC) ===================== */
    public function getUnreadCount($userId, $tenantId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM notifications
            WHERE user_id = :user_id
              AND tenant_id = :tenant_id
              AND read_status = 0
        ");

        $stmt->execute([
            ':user_id' => $userId,
            ':tenant_id' => $tenantId
        ]);

        return (int)$stmt->fetchColumn();
    }

    /* ===================== MARK ONE AS READ ===================== */
    public function markAsRead($notificationId, $userId, $tenantId) {
        $stmt = $this->db->prepare("
            UPDATE notifications
            SET read_status = 1
            WHERE id = :id
              AND user_id = :user_id
              AND tenant_id = :tenant_id
        ");

        return $stmt->execute([
            ':id' => $notificationId,
            ':user_id' => $userId,
            ':tenant_id' => $tenantId
        ]);
    }

    /* ===================== MARK ALL AS READ ===================== */
    public function markAllAsRead($userId, $tenantId) {
        $stmt = $this->db->prepare("
            UPDATE notifications
            SET read_status = 1
            WHERE user_id = :user_id
              AND tenant_id = :tenant_id
              AND read_status = 0
        ");

        return $stmt->execute([
            ':user_id' => $userId,
            ':tenant_id' => $tenantId
        ]);
    }
}
