<?php
require_once __DIR__ . '/BaseRepository.php';

class CommunicationRepository extends BaseRepository {
    public function __construct() {
        parent::__construct();
    }

    public function create($data) {
        $sql = "INSERT INTO communication_notes 
                (appointment_id, sender_id, sender_role, content, tenant_id) 
                VALUES 
                (:appointment_id, :sender_id, :sender_role, :content, :tenant_id)";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->execute([
            ':appointment_id' => $data['appointmentId'] ?? $data['appointment_id'] ?? null,
            ':sender_id' => $data['senderId'] ?? $data['sender_id'] ?? null,
            ':sender_role' => $data['senderRole'] ?? $data['sender_role'] ?? null,
            ':content' => $data['content'],
            ':tenant_id' => $data['tenant_id']
        ]);

        return $this->db->lastInsertId();
    }

    public function getByAppointmentId($appointmentId, $tenantId) {
        $stmt = $this->db->prepare("
            SELECT id, appointment_id as appointmentId, sender_id as senderId, 
                   sender_role as senderRole, content, created_at as createdAt
            FROM communication_notes 
            WHERE appointment_id = :appointment_id AND tenant_id = :tenant_id
            ORDER BY created_at ASC
        ");
        $stmt->execute([
            ':appointment_id' => $appointmentId,
            ':tenant_id' => $tenantId
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMessageHistory($tenantId) {
        $stmt = $this->db->prepare("
            SELECT id, appointment_id as appointmentId, sender_id as senderId, 
                   sender_role as senderRole, content, created_at as createdAt
            FROM communication_notes 
            WHERE tenant_id = :tenant_id
            ORDER BY created_at DESC
            LIMIT 100
        ");
        $stmt->execute([':tenant_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

        /* ===== Get last opposite role sender for appointment ===== */
    /* ===================== GET LAST OPPOSITE USER ===================== */
public function getLastOppositeUser($appointmentId, $currentRole, $tenantId) {
    $stmt = $this->db->prepare("
        SELECT sender_id
        FROM communication_notes
        WHERE appointment_id = :appointment_id
          AND sender_role != :current_role
          AND tenant_id = :tenant_id
        ORDER BY created_at DESC
        LIMIT 1
    ");

    $stmt->execute([
        ':appointment_id' => $appointmentId,
        ':current_role' => $currentRole,
        ':tenant_id' => $tenantId
    ]);

    return $stmt->fetchColumn() ?: null;
}

/* ===================== GET APPOINTMENT DOCTOR ===================== */

public function getAppointmentDoctorUserId($appointmentId) {
    $stmt = $this->db->prepare("
        SELECT d.user_id
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        WHERE a.id = :appointment_id
        LIMIT 1
    ");

    $stmt->execute([
        ':appointment_id' => $appointmentId
    ]);

    return $stmt->fetchColumn() ?: null;
}



}
