<?php
require_once 'BaseRepository.php';

class PatientRepository extends BaseRepository {

    public function getAll($tenantId) {
        $sql = "SELECT id, name, email, phone as contact, gender, age, blood_group as bloodGroup, 
                address, registered_date as registeredDate, medical_history as medicalHistory, 
                allergies, emergency_contact as emergencyContact, status 
                FROM patients 
                WHERE (tenant_id = :tenant_id OR tenant_id IS NULL) AND is_deleted = 0";
        
        $params = [':tenant_id' => $tenantId];

        if (isset($_GET['email'])) {
            $sql .= " AND email = :email";
            $params[':email'] = $_GET['email'];
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id, $tenantId) {
        $stmt = $this->db->prepare("SELECT * FROM patients WHERE id = :id AND tenant_id = :tenant_id AND is_deleted = 0");
        $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $sql = "INSERT INTO patients (name, email, phone, gender, age, blood_group, address, registered_date, medical_history, allergies, emergency_contact, status, tenant_id) 
                VALUES (:name, :email, :phone, :gender, :age, :blood_group, :address, :registered_date, :medical_history, :allergies, :emergency_contact, :status, :tenant_id)";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':name' => $data['name'],
            ':email' => $data['email'] ?? null,
            ':phone' => $data['contact'] ?? $data['phone'] ?? null,
            ':gender' => $data['gender'] ?? null,
            ':age' => $data['age'] ?? null,
            ':blood_group' => $data['bloodGroup'] ?? $data['blood_group'] ?? null,
            ':address' => $data['address'] ?? null,
            ':registered_date' => $data['registeredDate'] ?? $data['registered_date'] ?? date('Y-m-d'),
            ':medical_history' => $data['medicalHistory'] ?? $data['medical_history'] ?? null,
            ':allergies' => $data['allergies'] ?? null,
            ':emergency_contact' => $data['emergencyContact'] ?? $data['emergency_contact'] ?? null,
            ':status' => $data['status'] ?? 'Active',
            ':tenant_id' => $data['tenant_id']
        ]) ? $this->db->lastInsertId() : false;
    }

    public function update($id, $data, $tenantId) {
        $params = [':id' => $id, ':tenant_id' => $tenantId];
        $dbUpdates = [];

        $mapping = [
            'name' => 'name',
            'email' => 'email',
            'phone' => 'phone',
            'contact' => 'phone',
            'gender' => 'gender',
            'age' => 'age',
            'bloodGroup' => 'blood_group',
            'blood_group' => 'blood_group',
            'address' => 'address',
            'registeredDate' => 'registered_date',
            'registered_date' => 'registered_date',
            'medicalHistory' => 'medical_history',
            'medical_history' => 'medical_history',
            'allergies' => 'allergies',
            'emergencyContact' => 'emergency_contact',
            'emergency_contact' => 'emergency_contact',
            'status' => 'status',
            'dateOfBirth' => 'dob',
            'dob' => 'dob'
        ];

        $columnsToUpdate = [];
        foreach ($mapping as $frontendKey => $dbCol) {
            if (isset($data[$frontendKey])) {
                $columnsToUpdate[$dbCol] = $data[$frontendKey];
            }
        }

        // Calculate Age from dateOfBirth if provided
        if (isset($data['dateOfBirth']) && $data['dateOfBirth']) {
            try {
                $dob = new DateTime($data['dateOfBirth']);
                $now = new DateTime();
                $columnsToUpdate['age'] = $now->diff($dob)->y;
            } catch (Exception $e) {
                // Ignore invalid date
            }
        }

        if (empty($columnsToUpdate)) return false;

        $fields = [];
        foreach ($columnsToUpdate as $col => $val) {
            $fields[] = "$col = :$col";
            $params[":$col"] = $val;
        }

        $sql = "UPDATE patients SET " . implode(', ', $fields) . " WHERE id = :id AND tenant_id = :tenant_id AND is_deleted = 0";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function softDelete($id, $tenantId) {
        $stmt = $this->db->prepare("UPDATE patients SET is_deleted = 1 WHERE id = :id AND tenant_id = :tenant_id");
        return $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
    }

    public function getAppointments($patientId, $tenantId) {
        $stmt = $this->db->prepare("
            SELECT a.*, d.name as doctor_name, d.specialization 
            FROM appointments a
            LEFT JOIN doctors d ON a.doctor_id = d.id
            WHERE a.patient_id = :patient_id AND a.tenant_id = :tenant_id
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ");
        $stmt->execute([':patient_id' => $patientId, ':tenant_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByEmail($email, $tenantId) {
        $stmt = $this->db->prepare("SELECT * FROM patients WHERE email = :email AND tenant_id = :tenant_id AND is_deleted = 0 LIMIT 1");
        $stmt->execute([':email' => $email, ':tenant_id' => $tenantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
