<?php
require_once __DIR__ . '/../Repositories/NotificationRepository.php';

class NotificationService {

    private $repo;
    private $logFile;

    public function __construct() {
        $this->repo = new NotificationRepository();

        $this->logFile = __DIR__ . '/../../logs/php_error.log';
        ini_set('log_errors', 1);
        ini_set('error_log', $this->logFile);
    }

    /* ===================== LOGGER ===================== */
    private function log($message, $context = []) {
        file_put_contents(
            $this->logFile,
            json_encode([
                'time' => date('Y-m-d H:i:s'),
                'message' => $message,
                'context' => $context
            ], JSON_PRETTY_PRINT) . PHP_EOL,
            FILE_APPEND
        );
    }

    /* ===================== SESSION USER ===================== */
    private function getSessionUser() {
        if (
            empty($_SESSION['user_id']) ||
            empty($_SESSION['tenant_id'])
        ) {
            throw new Exception('Unauthorized');
        }

        return [
            'user_id' => (int)$_SESSION['user_id'],
            'tenant_id' => (int)$_SESSION['tenant_id']
        ];
    }

    /* ===================== GET NOTIFICATIONS ===================== */
   public function getNotifications($limit = 50) {
    $user = $this->getSessionUser();

    return $this->repo->getUnreadByUserId(
        $user['user_id'],
        $user['tenant_id'],
        $limit
    );
}



    /* ===================== UNREAD COUNT ===================== */
    public function getUnreadCount() {
        $user = $this->getSessionUser();

        return $this->repo->getUnreadCount(
            $user['user_id'],
            $user['tenant_id']
        );
    }

    /* ===================== MARK ONE AS READ ===================== */
    public function markAsRead($notificationId) {
        $user = $this->getSessionUser();

        return $this->repo->markAsRead(
            (int)$notificationId,
            $user['user_id'],
            $user['tenant_id']
        );
    }

    /* ===================== MARK ALL AS READ ===================== */
    public function markAllAsRead() {
        $user = $this->getSessionUser();

        return $this->repo->markAllAsRead(
            $user['user_id'],
            $user['tenant_id']
        );
    }

    /* ===================== CREATE NOTIFICATION ===================== */
    public function create($data) {
        $user = $this->getSessionUser();

        if (empty($data['user_id']) || empty($data['message'])) {
            throw new Exception('user_id and message are required');
        }

        return $this->repo->create([
            'user_id'   => (int)$data['user_id'],
            'message'   => $data['message'],
            'tenant_id' => $user['tenant_id']
        ]);
    }
}
