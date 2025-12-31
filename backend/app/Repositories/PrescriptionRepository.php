<?php
require_once __DIR__ . '/BaseRepository.php';

class PrescriptionRepository extends BaseRepository {
    public function __construct() {
        parent::__construct();
    }

    public function getAll($tenantId) {
        $stmt = $this->db->prepare("
            SELECT id, patient_id as patientId, doctor_id as doctorId, 
                   appointment_id as appointmentId, pharmacist_id as pharmacistId,
                   medicines, dosage, instructions, notes, 
                   status, prescription_date as prescriptionDate, created_at, updated_at
            FROM prescriptions 
            WHERE tenant_id = :tenant_id AND is_deleted = 0
            ORDER BY created_at DESC
        ");
        $stmt->execute([':tenant_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id, $tenantId) {
        $stmt = $this->db->prepare("
            SELECT * FROM prescriptions 
            WHERE id = :id AND tenant_id = :tenant_id AND is_deleted = 0
        ");
        $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $sql = "INSERT INTO prescriptions 
                (patient_id, doctor_id, appointment_id, pharmacist_id, medicines, dosage, instructions, notes, status, prescription_date, tenant_id) 
                VALUES 
                (:patient_id, :doctor_id, :appointment_id, :pharmacist_id, :medicines, :dosage, :instructions, :notes, :status, :prescription_date, :tenant_id)";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->execute([
            ':patient_id' => $data['patientId'] ?? $data['patient_id'] ?? null,
            ':doctor_id' => $data['doctorId'] ?? $data['doctor_id'] ?? null,
            ':appointment_id' => $data['appointmentId'] ?? $data['appointment_id'] ?? null,
            ':pharmacist_id' => $data['pharmacistId'] ?? $data['pharmacist_id'] ?? null,
            ':medicines' => $data['medicines'] ?? '[]',
            ':dosage' => $data['dosage'] ?? '',
            ':instructions' => $data['instructions'] ?? '',
            ':notes' => $data['notes'] ?? '',
            ':status' => $data['status'] ?? 'Pending',
            ':prescription_date' => $data['prescriptionDate'] ?? $data['prescription_date'] ?? date('Y-m-d'),
            ':tenant_id' => $data['tenant_id']
        ]);

        return $this->db->lastInsertId();
    }

    public function updateStatus($id, $status, $pharmacistId, $tenantId) {
        $stmt = $this->db->prepare("
            UPDATE prescriptions 
            SET status = :status, pharmacist_id = :pharmacist_id 
            WHERE id = :id AND tenant_id = :tenant_id AND is_deleted = 0
        ");
        return $stmt->execute([
            ':status' => $status,
            ':pharmacist_id' => $pharmacistId,
            ':id' => $id,
            ':tenant_id' => $tenantId
        ]);
    }

    public function getByStatus($status, $tenantId) {
        $stmt = $this->db->prepare("
            SELECT id, patient_id as patientId, doctor_id as doctorId, status, prescription_date as prescriptionDate
            FROM prescriptions 
            WHERE status = :status AND tenant_id = :tenant_id AND is_deleted = 0
            ORDER BY created_at DESC
        ");
        $stmt->execute([':status' => $status, ':tenant_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function softDelete($id, $tenantId) {
        $stmt = $this->db->prepare("
            UPDATE prescriptions SET is_deleted = 1 WHERE id = :id AND tenant_id = :tenant_id
        ");
        return $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
    }

    public function getByAppointmentId($appointmentId, $tenantId) {
    $stmt = $this->db->prepare("
        SELECT id, patient_id as patientId, doctor_id as doctorId,
               appointment_id as appointmentId, pharmacist_id as pharmacistId,
               medicines, dosage, instructions, notes,
               status, prescription_date as prescriptionDate,
               created_at
        FROM prescriptions
        WHERE appointment_id = :appointment_id
          AND tenant_id = :tenant_id
          AND is_deleted = 0
        LIMIT 1
    ");

    $stmt->execute([
        ':appointment_id' => $appointmentId,
        ':tenant_id' => $tenantId
    ]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}


}
