<?php
/**
 * CalendarController
 * 
 * Handles calendar and appointment viewing requests
 */

class CalendarController {
    
    private $service;
    
    public function __construct($db) {
        $this->service = new CalendarService($db);
    }
    
    /**
     * Get calendar appointments for a date range
     * Endpoint: GET /calendar
     */
    public function index($db) {
        try {
            // Get user info from request
            $userId = $_SERVER['user_id'] ?? null;
            $role = $_SERVER['user_role'] ?? null;
            $tenantId = $_SERVER['tenant_id'] ?? null;
            
            if (!$userId || !$role || !$tenantId) {
                return Response::error('Unauthorized', 401);
            }
            
            // Get query parameters
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            
            if (!$startDate || !$endDate) {
                return Response::error('Start date and end date are required', 400);
            }
            
            // Get appointments
            $appointments = $this->service->getCalendarAppointments($userId, $role, $startDate, $endDate, $tenantId);
            
            return Response::success('Calendar appointments retrieved', $appointments);
            
        } catch (Exception $e) {
            Log::error('Error in CalendarController@index: ' . $e->getMessage());
            return Response::error('Failed to retrieve calendar appointments', 500);
        }
    }
    
    /**
     * Get appointment tooltip details
     * Endpoint: GET /calendar/tooltip/{id}
     */
    public function tooltip($db, $id) {
        try {
            // Get user info from request
            $userId = $_SERVER['user_id'] ?? null;
            $role = $_SERVER['user_role'] ?? null;
            $tenantId = $_SERVER['tenant_id'] ?? null;
            
            if (!$userId || !$role || !$tenantId) {
                return Response::error('Unauthorized', 401);
            }
            
            // Get appointment details
            $appointment = $this->service->getAppointmentTooltip($id, $userId, $role, $tenantId);
            
            if (!$appointment) {
                return Response::error('Appointment not found', 404);
            }
            
            return Response::success('Appointment details retrieved', $appointment);
            
        } catch (Exception $e) {
            Log::error('Error in CalendarController@tooltip: ' . $e->getMessage());
            
            if ($e->getMessage() === 'Unauthorized' || $e->getMessage() === 'Appointment not found') {
                return Response::error($e->getMessage(), 404);
            }
            
            return Response::error('Failed to retrieve appointment details', 500);
        }
    }
    
    /**
     * Get appointments for a specific date
     * Endpoint: GET /calendar/date
     */
    public function byDate($db) {
        try {
            // Get user info from request
            $userId = $_SERVER['user_id'] ?? null;
            $role = $_SERVER['user_role'] ?? null;
            $tenantId = $_SERVER['tenant_id'] ?? null;
            
            if (!$userId || !$role || !$tenantId) {
                return Response::error('Unauthorized', 401);
            }
            
            // Get query parameters
            $date = $_GET['date'] ?? null;
            
            if (!$date) {
                return Response::error('Date is required', 400);
            }
            
            // Get appointments for the date
            $appointments = $this->service->getAppointmentsByDate($userId, $role, $date, $tenantId);
            
            return Response::success('Appointments retrieved', $appointments);
            
        } catch (Exception $e) {
            Log::error('Error in CalendarController@byDate: ' . $e->getMessage());
            return Response::error('Failed to retrieve appointments', 500);
        }
    }
}
