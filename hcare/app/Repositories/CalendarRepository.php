<?php
/**
 * CalendarRepository
 * 
 * Handles database operations for calendar-related queries
 */

class CalendarRepository extends BaseRepository {
    
    /**
     * Get appointments by date range
     */
    public function getByDateRange($userId, $role, $startDate, $endDate, $tenantId) {
        try {
            // Build query based on role
            $query = "SELECT 
                        a.id,
                        a.patient_id as patientId,
                        a.doctor_id as doctorId,
                        a.appointment_date as appointmentDate,
                        a.status,
                        a.reason,
                        p.name as patientName,
                        u.name as doctorName
                      FROM appointments a
                      INNER JOIN patients p ON a.patient_id = p.id
                      INNER JOIN users u ON a.doctor_id = u.id
                      WHERE a.tenant_id = ?
                        AND a.appointment_date BETWEEN ? AND ?
                        AND a.deleted_at IS NULL";
            
            // Add role-based filtering
            if ($role === 'doctor') {
                $query .= " AND a.doctor_id = ?";
            } elseif ($role === 'patient') {
                $query .= " AND a.patient_id = ?";
            }
            
            $query .= " ORDER BY a.appointment_date ASC";
            
            $stmt = $this->db->prepare($query);
            
            if ($role === 'doctor' || $role === 'patient') {
                $stmt->bind_param('issi', $tenantId, $startDate, $endDate, $userId);
            } else {
                $stmt->bind_param('iss', $tenantId, $startDate, $endDate);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->fetch_all(MYSQLI_ASSOC);
            
        } catch (Exception $e) {
            Log::error('Error in getByDateRange: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get appointment by ID
     */
    public function getById($appointmentId, $tenantId) {
        try {
            $query = "SELECT 
                        a.id,
                        a.patient_id as patientId,
                        a.doctor_id as doctorId,
                        a.appointment_date as appointmentDate,
                        a.status,
                        a.reason,
                        a.notes,
                        p.name as patientName,
                        p.email as patientEmail,
                        p.phone as patientPhone,
                        u.name as doctorName,
                        u.email as doctorEmail
                      FROM appointments a
                      INNER JOIN patients p ON a.patient_id = p.id
                      INNER JOIN users u ON a.doctor_id = u.id
                      WHERE a.id = ? AND a.tenant_id = ? AND a.deleted_at IS NULL";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('ii', $appointmentId, $tenantId);
            $stmt->execute();
            
            $result = $stmt->get_result();
            return $result->fetch_assoc();
            
        } catch (Exception $e) {
            Log::error('Error in getById: ' . $e->getMessage());
            throw $e;
        }
    }
}
