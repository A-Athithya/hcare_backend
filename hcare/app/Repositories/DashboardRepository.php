<?php
require_once __DIR__ . '/BaseRepository.php';

class DashboardRepository extends BaseRepository {
    public function __construct() {
        parent::__construct();
    }

    public function getPatients($tenantId) {
        $stmt = $this->db->prepare("SELECT * FROM patients WHERE tenant_id = :tenant_id AND is_deleted = 0 ORDER BY id DESC LIMIT 50");
        $stmt->execute([':tenant_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDoctors($tenantId) {
        $stmt = $this->db->prepare("SELECT * FROM doctors WHERE tenant_id = :tenant_id");
        $stmt->execute([':tenant_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAppointments($tenantId) {
        $stmt = $this->db->prepare("
            SELECT a.*, p.name as patient_name, d.name as doctor_name 
            FROM appointments a 
            LEFT JOIN patients p ON a.patient_id = p.id
            LEFT JOIN doctors d ON a.doctor_id = d.id
            WHERE a.tenant_id = :tenant_id 
            ORDER BY a.appointment_date DESC, a.appointment_time ASC LIMIT 50
        ");
        $stmt->execute([':tenant_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMedicines($tenantId) {
        $stmt = $this->db->prepare("SELECT * FROM medicines WHERE tenant_id = :tenant_id OR tenant_id IS NULL");
        $stmt->execute([':tenant_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getInvoices($tenantId) {
        $stmt = $this->db->prepare("SELECT * FROM invoices WHERE tenant_id = :tenant_id ORDER BY invoice_date DESC LIMIT 100");
        $stmt->execute([':tenant_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPatientCount($tenantId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM patients 
            WHERE tenant_id = :tenant_id AND is_deleted = 0
        ");
        $stmt->execute([':tenant_id' => $tenantId]);
        return (int) $stmt->fetchColumn();
    }

    public function getAppointmentStats($tenantId) {
        // Keeping this for potential future analytics usage, 
        // effectively duplicated by getAppointments count but useful for status breakdown
        $stmt = $this->db->prepare("
            SELECT status, COUNT(*) as count 
            FROM appointments 
            WHERE tenant_id = :tenant_id 
            GROUP BY status
        ");
        $stmt->execute([':tenant_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPrescriptionStats($tenantId) {
        $stmt = $this->db->prepare("
            SELECT status, COUNT(*) as count 
            FROM prescriptions 
            WHERE tenant_id = :tenant_id AND is_deleted = 0
            GROUP BY status
        ");
        $stmt->execute([':tenant_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTenantAnalytics() {
        $stmt = $this->db->prepare("
            SELECT t.name as tenant_name, 
                   (SELECT COUNT(*) FROM patients p WHERE p.tenant_id = t.id AND p.is_deleted = 0) as patient_count,
                   (SELECT COUNT(*) FROM appointments a WHERE a.tenant_id = t.id) as appointment_count,
                   (SELECT COUNT(*) FROM prescriptions pr WHERE pr.tenant_id = t.id AND pr.is_deleted = 0) as prescription_count
            FROM tenants t
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
