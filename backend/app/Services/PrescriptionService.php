<?php
require_once __DIR__ . '/../Repositories/PrescriptionRepository.php';
require_once __DIR__ . '/EncryptionService.php';

class PrescriptionService {
    private $repo;
    private $encryption;

    public function __construct() {
        $this->repo = new PrescriptionRepository();
        $this->encryption = new EncryptionService();
    }

    public function getAllPrescriptions() {
        $tenantId = $_REQUEST['user']['tenant_id'] ?? 1;
        $prescriptions = $this->repo->getAll($tenantId);
        
        return array_map([$this, 'decryptPrescriptionData'], $prescriptions);
    }

    public function getPrescriptionById($id) {
        $tenantId = $_REQUEST['user']['tenant_id'] ?? 1;
        $prescription = $this->repo->getById($id, $tenantId);
        return $prescription ? $this->decryptPrescriptionData($prescription) : null;
    }

    public function createPrescription($data) {
        $data['tenant_id'] = $_REQUEST['user']['tenant_id'] ?? 1;
        
        // Encrypt sensitive fields
        $encryptedData = $this->encryptPrescriptionData($data);
        
        // Ensure medicines is JSON string
        if (isset($encryptedData['medicines']) && is_array($encryptedData['medicines'])) {
            $encryptedData['medicines'] = json_encode($encryptedData['medicines']);
        }

        return $this->repo->create($encryptedData);
    }

    public function updateDispensingStatus($id, $status) {
        $tenantId = $_REQUEST['user']['tenant_id'] ?? 1;
        $pharmacistId = $_REQUEST['user']['sub'] ?? null; // 'sub' is the user ID in JWT payload
        
        // Business Rule: Pharmacists can update status
        $allowedStatuses = ['Verified', 'Dispensed', 'Cancelled'];
        if (!in_array($status, $allowedStatuses)) {
            throw new Exception("Invalid status update.");
        }

        return $this->repo->updateStatus($id, $status, $pharmacistId, $tenantId);
    }

    public function getPrescriptionsByStatus($status) {
        $tenantId = $_REQUEST['user']['tenant_id'] ?? 1;
        return $this->repo->getByStatus($status, $tenantId);
    }

    public function deletePrescription($id) {
        $tenantId = $_REQUEST['user']['tenant_id'] ?? 1;
        return $this->repo->softDelete($id, $tenantId);
    }

    public function getByAppointmentId($appointmentId) {
    $tenantId = $_REQUEST['user']['tenant_id'] ?? null;

    if (!$tenantId) {
        throw new Exception('Unauthorized');
    }

    $data = $this->repo->getByAppointmentId(
        (int)$appointmentId,
        (int)$tenantId
    );

    return $data ? $this->decryptPrescriptionData($data) : null;
}



    private function encryptPrescriptionData($data) {
        $sensitiveFields = ['dosage', 'instructions', 'notes'];
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $data[$field] = $this->encryption->encrypt($data[$field]);
            }
        }
        
        // Encrypt medicines if it's a list
        if (isset($data['medicines']) && is_array($data['medicines'])) {
            $data['medicines'] = array_map(function($medicine) {
                if (isset($medicine['name'])) {
                    $medicine['name'] = $this->encryption->encrypt($medicine['name']);
                }
                return $medicine;
            }, $data['medicines']);
        }

        return $data;
    }

    private function decryptPrescriptionData($prescription) {
        $sensitiveFields = ['dosage', 'instructions', 'notes'];
        foreach ($sensitiveFields as $field) {
            if (isset($prescription[$field]) && !empty($prescription[$field])) {
                $prescription[$field] = $this->encryption->decrypt($prescription[$field]);
            }
        }

        // Decrypt medicines
        if (isset($prescription['medicines'])) {
            $medicines = is_string($prescription['medicines']) ? json_decode($prescription['medicines'], true) : $prescription['medicines'];
            if (is_array($medicines)) {
                $prescription['medicines'] = array_map(function($medicine) {
                    if (isset($medicine['name'])) {
                        $medicine['name'] = $this->encryption->decrypt($medicine['name']);
                    }
                    return $medicine;
                }, $medicines);
            } else {
                $prescription['medicines'] = [];
            }
        }

        return $prescription;
    }
}
