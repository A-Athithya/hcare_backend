<?php
require_once __DIR__ . '/../Repositories/CommunicationRepository.php';
require_once __DIR__ . '/EncryptionService.php';
require_once __DIR__ . '/../Repositories/NotificationRepository.php';

class CommunicationService {
    private $repo;
    private $encryption;
    private $notificationRepo;

    public function __construct() {
        $this->repo = new CommunicationRepository();
        $this->encryption = new EncryptionService();
        $this->notificationRepo = new NotificationRepository();
    }

    /* ===================== GET SESSION USER ===================== */
    private function getSessionUser() {
        $user = [
            'id' => $_SESSION['user_id'] ?? null,
            'role' => $_SESSION['user_role'] ?? null,
            'tenant_id' => $_SESSION['tenant_id'] ?? null
        ];

        if (!$user['id'] || !$user['role'] || !$user['tenant_id']) {
            throw new Exception('Unauthorized');
        }

        return $user;
    }

    /* ===================== ADD NOTE ===================== */
    public function addNote($data) {
        $user = $this->getSessionUser();

        $encryptedContent = $this->encryption->encrypt($data['content']);
        $noteData = [
            'appointment_id' => (int)$data['appointment_id'],
            'sender_id'      => (int)$user['id'],
            'sender_role'    => $user['role'],
            'content'        => $encryptedContent,
            'tenant_id'      => (int)$user['tenant_id']
        ];

        // 1️⃣ Insert note
        $insertId = $this->repo->create($noteData);

        // 2️⃣ Detect receiver
        $receiverId = null;

        if ($user['role'] === 'doctor') {
            $receiverId = $this->repo->getLastOppositeUser(
                $data['appointment_id'],
                'doctor',
                $user['tenant_id']
            );
        } elseif ($user['role'] === 'nurse') {
            $receiverId = $this->repo->getAppointmentDoctorUserId(
                $data['appointment_id']
            );
        }

        // 3️⃣ Create notification
        if ($receiverId) {
            $notifData = [
                'user_id'   => $receiverId,
                'message'   => 'New communication note for Appointment ' . $data['appointment_id'],
                'tenant_id' => $user['tenant_id']
            ];
            $this->notificationRepo->create($notifData);
        }

        return $insertId;
    }

    /* ===================== GET NOTES BY APPOINTMENT ===================== */
    public function getNotesByAppointment($appointmentId) {
        $tenantId = $_SESSION['tenant_id'] ?? null;
        if (!$tenantId) {
            throw new Exception('Unauthorized');
        }

        $notes = $this->repo->getByAppointmentId($appointmentId, $tenantId);
        if (!is_array($notes)) {
            return [];
        }

        $decryptedNotes = [];
        foreach ($notes as $note) {
            try {
                $note['content'] = $this->encryption->decrypt($note['content']);
            } catch (Exception $e) {
                $note['content'] = '[DECRYPTION FAILED]';
            }
            $decryptedNotes[] = $note;
        }

        return $decryptedNotes;
    }

    /* ===================== GET MESSAGE HISTORY ===================== */
    public function getMessageHistory() {
        $tenantId = $_SESSION['tenant_id'] ?? null;
        if (!$tenantId) {
            throw new Exception('Unauthorized');
        }

        $messages = $this->repo->getMessageHistory($tenantId);
        if (!is_array($messages)) {
            return [];
        }

        foreach ($messages as &$msg) {
            try {
                $msg['content'] = $this->encryption->decrypt($msg['content']);
            } catch (Exception $e) {
                $msg['content'] = '[DECRYPTION FAILED]';
            }
        }

        return $messages;
    }
}
