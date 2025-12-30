<?php
require_once __DIR__ . '/../Helpers/Response.php';

class AppointmentController {
    private $service;

    public function __construct() {
        require_once __DIR__ . '/../Services/AppointmentService.php';
        $this->service = new AppointmentService();
    }

    // ✅ Get all appointments
    public function index() {
        try {
            $data = $this->service->getAllAppointments();
            Response::json($data);
        } catch (Exception $e) {
            $code = strpos($e->getMessage(), 'Unauthorized') !== false ? 403 : 400;
            Response::json(['error' => $e->getMessage()], $code);
        }
    }

    // ✅ Get single appointment
    public function show($id) {
        try {
            $appointment = $this->service->getAppointmentById($id);
            if (!$appointment) {
                Response::json(['error' => 'Appointment not found'], 404);
                return;
            }
            Response::json($appointment);
        } catch (Exception $e) {
            $code = strpos($e->getMessage(), 'Unauthorized') !== false ? 403 : 400;
            Response::json(['error' => $e->getMessage()], $code);
        }
    }

    // ✅ Create new appointment
    public function store() {
        $data = $_REQUEST['decoded_input'] ?? [];
        try {
            $id = $this->service->createAppointment($data);
            Response::json(['message' => 'Appointment created', 'id' => $id], 201);
        } catch (Exception $e) {
            $code = strpos($e->getMessage(), 'Conflict') !== false ? 409 : 400;
            Response::json(['error' => $e->getMessage()], $code);
        }
    }

    // ✅ Update appointment
    public function update($id) {
        $data = $_REQUEST['decoded_input'] ?? [];
        try {
            $this->service->updateAppointment($id, $data);
            Response::json(['message' => 'Appointment updated', 'id' => $id]);
        } catch (Exception $e) {
            $code = strpos($e->getMessage(), 'Unauthorized') !== false ? 403 : (strpos($e->getMessage(), 'Conflict') !== false ? 409 : 400);
            Response::json(['error' => $e->getMessage()], $code);
        }
    }

    // ✅ Delete appointment
    public function delete($id) {
        try {
            $this->service->deleteAppointment($id);
            Response::json(['message' => 'Appointment deleted', 'id' => $id]);
        } catch (Exception $e) {
            $code = strpos($e->getMessage(), 'Unauthorized') !== false ? 403 : 400;
            Response::json(['error' => $e->getMessage()], $code);
        }
    }

    // ✅ Get upcoming appointments
    public function upcoming() {
        try {
            $data = $this->service->getUpcomingAppointments();
            Response::json($data);
        } catch (Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    // ✅ Calendar date range fetch
    public function calendar() {
        $startDate = $_GET['start'] ?? date('Y-m-d');
        $endDate = $_GET['end'] ?? date('Y-m-d');
        try {
            $data = $this->service->getCalendarAppointments($startDate, $endDate);
            Response::json($data);
        } catch (Exception $e) {
            $code = strpos($e->getMessage(), 'Unauthorized') !== false ? 403 : 400;
            Response::json(['error' => $e->getMessage()], $code);
        }
    }

    // ✅ Tooltip details API
    public function tooltip($id) {
        try {
            $data = $this->service->getAppointmentTooltip($id);
            if (!$data) {
                Response::json(['error' => 'Appointment not found'], 404);
                return;
            }
            Response::json($data);
        } catch (Exception $e) {
            $code = strpos($e->getMessage(), 'Unauthorized') !== false ? 403 : 400;
            Response::json(['error' => $e->getMessage()], $code);
        }
    }
}
