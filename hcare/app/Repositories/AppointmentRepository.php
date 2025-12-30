<?php
require_once 'BaseRepository.php';

class AppointmentRepository extends BaseRepository {
    public function getAll($tenantId, $patientId = null, $doctorId = null) {
        $sql = "SELECT a.*, p.name as patient_name, d.name as doctor_name 
                FROM appointments a
                LEFT JOIN patients p ON a.patient_id = p.id
                LEFT JOIN doctors d ON a.doctor_id = d.id
                WHERE a.tenant_id = :tenant_id";
        
        $params = [':tenant_id' => $tenantId];

        if ($patientId) {
            $sql .= " AND a.patient_id = :patient_id";
            $params[':patient_id'] = $patientId;
        }

        if ($doctorId) {
            $sql .= " AND a.doctor_id = :doctor_id";
            $params[':doctor_id'] = $doctorId;
        }

        $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
        
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getById($id, $tenantId) {
        $sql = "SELECT a.*, p.name as patient_name, d.name as doctor_name 
                FROM appointments a
                LEFT JOIN patients p ON a.patient_id = p.id
                LEFT JOIN doctors d ON a.doctor_id = d.id
                WHERE a.id = :id AND a.tenant_id = :tenant_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $sql = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status, payment_amount, notes, tenant_id) 
                VALUES (:patient_id, :doctor_id, :appointment_date, :appointment_time, :reason, :status, :payment_amount, :notes, :tenant_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':patient_id' => $data['patientId'] ?? $data['patient_id'] ?? null,
            ':doctor_id' => $data['doctorId'] ?? $data['doctor_id'] ?? null,
            ':appointment_date' => $data['appointmentDate'] ?? $data['appointment_date'] ?? null,
            ':appointment_time' => $data['appointmentTime'] ?? $data['appointment_time'] ?? null,
            ':reason' => $data['reason'] ?? null,
            ':status' => $data['status'] ?? 'Pending',
            ':payment_amount' => $data['paymentAmount'] ?? $data['payment_amount'] ?? 0,
            ':notes' => $data['notes'] ?? null,
            ':tenant_id' => $data['tenant_id']
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        $updatable = [
            'patientId' => 'patient_id',
            'doctorId' => 'doctor_id',
            'appointmentDate' => 'appointment_date',
            'appointmentTime' => 'appointment_time',
            'reason' => 'reason',
            'status' => 'status',
            'paymentAmount' => 'payment_amount',
            'notes' => 'notes'
        ];

        foreach ($updatable as $key => $column) {
            if (isset($data[$key])) {
                $fields[] = "$column = :$key";
                $params[":$key"] = $data[$key];
            }
        }

        if (empty($fields)) return false;

        $params[':tenant_id'] = $data['tenant_id'] ?? $_REQUEST['user']['tenant_id'] ?? 1;
        $sql = "UPDATE appointments SET " . implode(', ', $fields) . " WHERE id = :id AND tenant_id = :tenant_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id, $tenantId) {
        $stmt = $this->db->prepare("DELETE FROM appointments WHERE id = :id AND tenant_id = :tenant_id");
        return $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
    }

    public function hasConflict($doctorId, $date, $time, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM appointments 
                WHERE doctor_id = :doctor_id 
                AND appointment_date = :date 
                AND appointment_time = :time 
                AND status != 'Cancelled'";
        
        $params = [
            ':doctor_id' => $doctorId,
            ':date' => $date,
            ':time' => $time
        ];

        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    public function getUpcoming($tenantId, $patientId = null, $doctorId = null) {
        $today = date('Y-m-d');
        $now = date('H:i:s');

        $sql = "SELECT a.*, p.name as patient_name, d.name as doctor_name 
                FROM appointments a
                LEFT JOIN patients p ON a.patient_id = p.id
                LEFT JOIN doctors d ON a.doctor_id = d.id
                WHERE a.tenant_id = :tenant_id 
                AND (a.appointment_date > :today OR (a.appointment_date = :today AND a.appointment_time >= :now))
                AND a.status != 'Cancelled'";
        
        $params = [
            ':tenant_id' => $tenantId,
            ':today' => $today,
            ':now' => $now
        ];

        if ($patientId) {
            $sql .= " AND a.patient_id = :patient_id";
            $params[':patient_id'] = $patientId;
        }

        if ($doctorId) {
            $sql .= " AND a.doctor_id = :doctor_id";
            $params[':doctor_id'] = $doctorId;
        }

        $sql .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByDateRange($tenantId, $startDate, $endDate, $patientId = null, $doctorId = null) {
        $sql = "SELECT a.*, p.name as patient_name, d.name as doctor_name 
                FROM appointments a
                LEFT JOIN patients p ON a.patient_id = p.id
                LEFT JOIN doctors d ON a.doctor_id = d.id
                WHERE a.tenant_id = :tenant_id 
                AND a.appointment_date BETWEEN :start_date AND :end_date
                AND a.status != 'Cancelled'";
        
        $params = [
            ':tenant_id' => $tenantId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ];

        if ($patientId) {
            $sql .= " AND a.patient_id = :patient_id";
            $params[':patient_id'] = $patientId;
        }

        if ($doctorId) {
            $sql .= " AND a.doctor_id = :doctor_id";
            $params[':doctor_id'] = $doctorId;
        }

        $sql .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
