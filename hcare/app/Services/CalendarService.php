<?php
/**
 * CalendarService
 * 
 * Handles calendar and appointment scheduling business logic
 */

class CalendarService {
    
    private $appointmentRepo;
    
    public function __construct($db) {
        $this->appointmentRepo = new AppointmentRepository($db);
    }
    
    /**
     * Get appointments for calendar view
     */
    public function getCalendarAppointments($userId, $role, $startDate, $endDate, $tenantId) {
        try {
            return $this->appointmentRepo->getByDateRange($userId, $role, $startDate, $endDate, $tenantId);
        } catch (Exception $e) {
            Log::error('Error getting calendar appointments: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get appointment tooltip data
     */
    public function getAppointmentTooltip($appointmentId, $userId, $role, $tenantId) {
        try {
            $appointment = $this->appointmentRepo->getById($appointmentId, $tenantId);
            
            if (!$appointment) {
                throw new Exception('Appointment not found');
            }
            
            // Check if user has permission to view this appointment
            if ($role !== 'admin' && $role !== 'receptionist') {
                if ($role === 'doctor' && $appointment['doctorId'] != $userId) {
                    throw new Exception('Unauthorized');
                }
                if ($role === 'patient' && $appointment['patientId'] != $userId) {
                    throw new Exception('Unauthorized');
                }
            }
            
            return $appointment;
        } catch (Exception $e) {
            Log::error('Error getting appointment tooltip: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get appointments for a specific date
     */
    public function getAppointmentsByDate($userId, $role, $date, $tenantId) {
        try {
            $startOfDay = $date . ' 00:00:00';
            $endOfDay = $date . ' 23:59:59';
            
            return $this->appointmentRepo->getByDateRange($userId, $role, $startOfDay, $endOfDay, $tenantId);
        } catch (Exception $e) {
            Log::error('Error getting appointments by date: ' . $e->getMessage());
            throw $e;
        }
    }
}
