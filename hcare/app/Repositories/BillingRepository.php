<?php
require_once __DIR__ . '/BaseRepository.php';

class BillingRepository extends BaseRepository {
    public function __construct() {
        parent::__construct();
    }

    public function createInvoice($data) {
        $sql = "INSERT INTO invoices (invoice_date, patient_id, doctor_id, appointment_id, total_amount, paid_amount, status, tenant_id) 
                VALUES (:invoice_date, :patient_id, :doctor_id, :appointment_id, :total_amount, :paid_amount, :status, :tenant_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':invoice_date' => $data['invoiceDate'] ?? $data['invoice_date'] ?? date('Y-m-d'),
            ':patient_id' => $data['patientId'] ?? $data['patient_id'] ?? null,
            ':doctor_id' => $data['doctorId'] ?? $data['doctor_id'] ?? null,
            ':appointment_id' => $data['appointmentId'] ?? $data['appointment_id'] ?? null,
            ':total_amount' => $data['totalAmount'] ?? $data['total_amount'] ?? 0.00,
            ':paid_amount' => $data['paidAmount'] ?? $data['paid_amount'] ?? 0.00,
            ':status' => $data['status'] ?? 'Unpaid',
            ':tenant_id' => $data['tenant_id']
        ]);
        return $this->db->lastInsertId();
    }

    public function updateInvoiceStatus($id, $status, $paidAmount, $tenantId) {
        $sql = "UPDATE invoices SET status = :status, paid_amount = :paid_amount 
                WHERE id = :id AND tenant_id = :tenant_id";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            ':status' => $status,
            ':paid_amount' => $paidAmount,
            ':id' => $id,
            ':tenant_id' => $tenantId
        ]);

        if ($success && $status === 'Paid') {
            require_once __DIR__ . '/../Helpers/Log.php';
            Log::info("Invoice $id marked as Paid. Checking for linked appointment...");
            
            // Check if there is an associated appointment_id
            $stmt = $this->db->prepare("SELECT appointment_id FROM invoices WHERE id = :id AND tenant_id = :tenant_id");
            $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($invoice && !empty($invoice['appointment_id'])) {
                Log::info("Found linked appointment: " . $invoice['appointment_id']);
                // Update appointment status to Completed
                $stmt = $this->db->prepare("UPDATE appointments SET status = 'Completed' WHERE id = :appt_id AND tenant_id = :tenant_id");
                $updated = $stmt->execute([
                    ':appt_id' => $invoice['appointment_id'],
                    ':tenant_id' => $tenantId
                ]);
                Log::info("Appointment status update result: " . ($updated ? "Success" : "Failed"));
            } else {
                Log::info("No linked appointment found for invoice $id");
            }
        }

        return $success;
    }

    public function getInvoicesByTenant($tenantId) {
        $sql = "SELECT i.*, p.name as patient_name, d.name as doctor_name 
                FROM invoices i
                JOIN patients p ON i.patient_id = p.id
                LEFT JOIN doctors d ON i.doctor_id = d.id
                WHERE i.tenant_id = :tenant_id 
                ORDER BY i.invoice_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':tenant_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getInvoiceById($id, $tenantId) {
        $sql = "SELECT i.*, p.name as patient_name, d.name as doctor_name 
                FROM invoices i
                JOIN patients p ON i.patient_id = p.id
                LEFT JOIN doctors d ON i.doctor_id = d.id
                WHERE i.id = :id AND i.tenant_id = :tenant_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getInvoicesByPatient($patientId, $tenantId) {
        $sql = "SELECT i.*, d.name as doctor_name 
                FROM invoices i
                LEFT JOIN doctors d ON i.doctor_id = d.id
                WHERE i.patient_id = :patient_id AND i.tenant_id = :tenant_id 
                ORDER BY i.invoice_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':patient_id' => $patientId, ':tenant_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getInvoiceSummary($tenantId) {
        $sql = "SELECT 
                    SUM(CASE WHEN status = 'Paid' THEN total_amount ELSE 0 END) as total_paid,
                    SUM(CASE WHEN status != 'Paid' THEN total_amount ELSE 0 END) as total_pending,
                    COUNT(CASE WHEN status = 'Paid' THEN 1 END) as paid_count,
                    COUNT(CASE WHEN status != 'Paid' THEN 1 END) as pending_count
                FROM invoices 
                WHERE tenant_id = :tenant_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':tenant_id' => $tenantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
