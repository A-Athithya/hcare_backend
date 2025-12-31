<?php
require_once __DIR__ . '/../Helpers/Response.php';
require_once __DIR__ . '/../Services/NotificationService.php';

class NotificationController {

    private $service;

    public function __construct() {
        $this->service = new NotificationService();
    }

    /* ===================== GET ALL NOTIFICATIONS ===================== */
    public function index() {
        try {
            $notifications = $this->service->getNotifications();
            Response::json($notifications);
        } catch (Exception $e) {
            Response::json(['error' => $e->getMessage()], 401);
        }
    }

    /* ===================== GET UNREAD COUNT ===================== */
    public function unreadCount() {
        try {
            $count = $this->service->getUnreadCount();
            Response::json(['count' => $count]);
        } catch (Exception $e) {
            Response::json(['error' => $e->getMessage()], 401);
        }
    }

    /* ===================== MARK ONE AS READ ===================== */
    public function markRead($id) {
        try {
            $this->service->markAsRead($id);
            Response::json(['message' => 'Notification marked as read']);
        } catch (Exception $e) {
            Response::json(['error' => $e->getMessage()], 401);
        }
    }

    /* ===================== MARK ALL AS READ ===================== */
    public function markAllRead() {
        try {
            $this->service->markAllAsRead();
            Response::json(['message' => 'All notifications marked as read']);
        } catch (Exception $e) {
            Response::json(['error' => $e->getMessage()], 401);
        }
    }
}
