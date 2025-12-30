<?php
require_once __DIR__ . '/../Config/config.php';

class StaffRepository {

    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    // ================= COMMON HELPERS =================
    private function getTable($role) {
        $tables = [
            'doctor' => 'doctors',
            'nurse' => 'nurses',
            'pharmacist' => 'pharmacists',
            'receptionist' => 'receptionists'
        ];

        if (!isset($tables[$role])) {
            throw new Exception("Invalid role table");
        }

        return $tables[$role];
    }

    // ================= GET ALL =================
    public function getAll($role, $tenantId) {
        $table = $this->getTable($role);
        $stmt = $this->db->prepare("SELECT * FROM $table WHERE (tenant_id = ? OR tenant_id IS NULL) ORDER BY id DESC");
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ================= GET BY ID =================
    public function getById($role, $id, $tenantId) {
        $table = $this->getTable($role);
        $stmt = $this->db->prepare("SELECT * FROM $table WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$id, $tenantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ================= CREATE STAFF =================
    public function createDoctor($data) {
        $sql = "INSERT INTO doctors 
                (name, gender, age, specialization, qualification, experience, contact, email, address, available_days, available_time, status, department, license_number, rating, consultation_fee, bio, tenant_id, user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $availableDays = $data['availableDays'] ?? $data['available_days'] ?? null;
        if (is_array($availableDays)) $availableDays = implode(',', $availableDays);

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['gender'] ?? null,
            $data['age'] ?? null,
            $data['specialization'] ?? null,
            $data['qualification'] ?? null,
            $data['experience'] ?? null,
            $data['contact'] ?? null,
            $data['email'] ?? null,
            $data['address'] ?? null,
            $availableDays,
            $data['availableTime'] ?? $data['available_time'] ?? null,
            $data['status'] ?? 'Active',
            $data['department'] ?? null,
            $data['licenseNumber'] ?? $data['license_number'] ?? null,
            $data['rating'] ?? 0,
            $data['consultationFee'] ?? $data['consultation_fee'] ?? 0,
            $data['bio'] ?? null,
            $data['tenant_id'],
            $data['user_id'] ?? null
        ]);

        return $this->db->lastInsertId();
    }

    public function createNurse($data) {
        $sql = "INSERT INTO nurses 
                (name, gender, age, phone, email, department, shift, experience, status, tenant_id, date_joined, user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['gender'] ?? null,
            $data['age'] ?? null,
            $data['phone'] ?? $data['contact'] ?? null,
            $data['email'] ?? null,
            $data['department'] ?? null,
            $data['shift'] ?? null,
            $data['experience'] ?? null,
            $data['status'] ?? 'Active',
            $data['tenant_id'],
            $data['dateJoined'] ?? $data['date_joined'] ?? date('Y-m-d'),
            $data['user_id'] ?? null
        ]);

        return $this->db->lastInsertId();
    }

    public function createPharmacist($data) {
        $sql = "INSERT INTO pharmacists 
                (name, email, license_no, contact, experience, status, tenant_id, user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['email'] ?? null,
            $data['licenseNo'] ?? $data['license_no'] ?? null,
            $data['contact'] ?? null,
            $data['experience'] ?? null,
            $data['status'] ?? 'Active',
            $data['tenant_id'],
            $data['user_id'] ?? null
        ]);

        return $this->db->lastInsertId();
    }

    public function createReceptionist($data) {
        $sql = "INSERT INTO receptionists 
                (name, shift, contact, email, status, tenant_id, user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['shift'] ?? null,
            $data['contact'] ?? null,
            $data['email'] ?? null,
            $data['status'] ?? 'Active',
            $data['tenant_id'],
            $data['user_id'] ?? null
        ]);

        return $this->db->lastInsertId();
    }

    // ================= UPDATE STAFF =================
    public function update($role, $id, $tenantId, $data) {
        $table = $this->getTable($role);

        $mapping = [
            'name' => 'name',
            'email' => 'email',
            'gender' => 'gender',
            'age' => 'age',
            'phone' => 'phone',
            'contact' => 'contact',
            'address' => 'address',
            'department' => 'department',
            'shift' => 'shift',
            'experience' => 'experience',
            'status' => 'status',
            'licenseNo' => 'license_no',
            'licenseNumber' => 'license_number',
            'license_no' => 'license_no',
            'license_number' => 'license_number',
            'qualification' => 'qualification',
            'specialization' => 'specialization',
            'bloodGroup' => 'blood_group',
            'dateJoined' => 'date_joined',
            'dateOfBirth' => 'dob',
            'dob' => 'dob'
        ];

        if ($role === 'doctor' || $role === 'pharmacist' || $role === 'receptionist') {
            $mapping['phone'] = 'contact';
            $mapping['contact'] = 'contact';
        } elseif ($role === 'nurse') {
            $mapping['phone'] = 'phone';
            $mapping['contact'] = 'phone';
        }

        $columnsToUpdate = [];
        foreach ($mapping as $frontendKey => $dbCol) {
            if (isset($data[$frontendKey])) $columnsToUpdate[$dbCol] = $data[$frontendKey];
        }

        if (isset($data['dateOfBirth']) && $data['dateOfBirth']) {
            try {
                $dob = new DateTime($data['dateOfBirth']);
                $now = new DateTime();
                $columnsToUpdate['age'] = $now->diff($dob)->y;
            } catch (Exception $e) {}
        }

        if (empty($columnsToUpdate)) return false;

        $fields = [];
        $values = [];
        foreach ($columnsToUpdate as $col => $val) {
            $fields[] = "$col = ?";
            $values[] = $val;
        }

        $values[] = $id;
        $values[] = $tenantId;

        $sql = "UPDATE $table SET " . implode(', ', $fields) . " WHERE id = ? AND tenant_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    // ================= DELETE STAFF =================
    public function delete($role, $id, $tenantId) {
        $table = $this->getTable($role);
        $stmt = $this->db->prepare("DELETE FROM $table WHERE id = ? AND tenant_id = ?");
        return $stmt->execute([$id, $tenantId]);
    }
}
