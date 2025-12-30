<?php
require_once __DIR__ . '/../Helpers/Response.php';
require_once __DIR__ . '/../Services/PrescriptionService.php';

class PrescriptionController {
    private $service;

    public function __construct() {
        $this->service = new PrescriptionService();
    }

    public function index() {
        try {
            $data = $this->service->getAllPrescriptions();
            Response::json($data);
        } catch (Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id) {
        try {
            $data = $this->service->getPrescriptionById($id);
            if (!$data) {
                Response::json(['error' => 'Prescription not found'], 404);
                return;
            }
            Response::json($data);
        } catch (Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function store() {
        $data = $_REQUEST['decoded_input'] ?? $_POST;
        try {
            $id = $this->service->createPrescription($data);
            Response::json(['message' => 'Prescription created', 'id' => $id], 201);
        } catch (Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    public function updateStatus($id) {
        $input = $_REQUEST['decoded_input'] ?? $_POST;
        $status = $input['status'] ?? null;

        if (!$status) {
            Response::json(['error' => 'Status is required'], 400);
            return;
        }

        try {
            $success = $this->service->updateDispensingStatus($id, $status);
            if ($success) {
                Response::json(['message' => 'Prescription status updated successfully']);
            } else {
                Response::json(['error' => 'Failed to update status or prescription not found'], 404);
            }
        } catch (Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    public function getByStatus($status) {
        try {
            $data = $this->service->getPrescriptionsByStatus($status);
            Response::json($data);
        } catch (Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete($id) {
        try {
            $success = $this->service->deletePrescription($id);
            if ($success) {
                Response::json(['message' => 'Prescription deleted successfully']);
            } else {
                Response::json(['error' => 'Failed to delete prescription'], 404);
            }
        } catch (Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }
    public function getByAppointment($appointmentId) {
        try {
            $data = $this->service->getByAppointmentId($appointmentId);

            if (!$data) {
                Response::json(null, 200); // No prescription yet
                return;
            }

            Response::json($data);
        } catch (Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }
}
