<?php
require_once __DIR__ . '/BaseRepository.php';

class DoctorRepository extends BaseRepository {
    protected $table = 'doctors';

    public function getAll($tenantId) {
        $user = $_REQUEST['user'] ?? ['role' => 'unknown', 'id' => 0];
        $sql = "SELECT * FROM doctors WHERE tenant_id = :tenant_id";
        $params = [':tenant_id' => $tenantId];

        if (isset($_GET['email'])) {
            $sql .= " AND email = :email";
            $params[':email'] = $_GET['email'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id, $tenantId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$id, $tenantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $sql = "INSERT INTO {$this->table} 
        (name, email, gender, age, specialization, qualification, experience, contact,
         address, available_days, available_time, department, license_number,
         rating, consultation_fee, bio, status, tenant_id)
        VALUES
        (:name, :email, :gender, :age, :specialization, :qualification, :experience, :contact,
         :address, :available_days, :available_time, :department, :license_number,
         :rating, :consultation_fee, :bio, :status, :tenant_id)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function update($id, $data, $tenantId) {
        $params = [':id' => $id, ':tenant_id' => $tenantId];
        
        $mapping = [
            'name' => 'name',
            'email' => 'email',
            'gender' => 'gender',
            'age' => 'age',
            'specialization' => 'specialization',
            'qualification' => 'qualification',
            'experience' => 'experience',
            'contact' => 'contact',
            'phone' => 'contact',
            'address' => 'address',
            'department' => 'department',
            'licenseNo' => 'license_number',
            'licenseNumber' => 'license_number',
            'license_number' => 'license_number',
            'consultationFee' => 'consultation_fee',
            'consultation_fee' => 'consultation_fee',
            'bio' => 'bio',
            'status' => 'status',
            'availableTime' => 'available_time',
            'available_time' => 'available_time',
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

        // Special handling for available days if it's an array
        if (isset($data['availableDays']) || isset($data['available_days'])) {
            $days = $data['availableDays'] ?? $data['available_days'];
            if (is_array($days)) $days = implode(',', $days);
            $columnsToUpdate['available_days'] = $days;
        }

        if (empty($columnsToUpdate)) return false;

        $fields = [];
        foreach ($columnsToUpdate as $col => $val) {
            $fields[] = "$col = :$col";
            $params[":$col"] = $val;
        }

        $sql = "UPDATE doctors SET " . implode(', ', $fields) . " WHERE id = :id AND tenant_id = :tenant_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id, $tenantId) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ? AND tenant_id = ?");
        return $stmt->execute([$id, $tenantId]);
    }

    public function findByEmail($email, $tenantId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = :email AND tenant_id = :tenant_id LIMIT 1");
        $stmt->execute([':email' => $email, ':tenant_id' => $tenantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
