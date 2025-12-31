<?php
require_once __DIR__ . '/../Services/DoctorService.php';
require_once __DIR__ . '/../Helpers/Response.php';

class DoctorController {
    private $service;

    public function __construct() {
        $this->service = new DoctorService();
    }

    public function index() {
        try {
            $data = $this->service->getAllDoctors();
            Response::json($data);
        } catch (Exception $e) {
            error_log("CRITICAL ERROR in DoctorController: " . $e->getMessage());
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id) {
        try {
            $doctor = $this->service->find($id);
            if (!$doctor) {
                Response::json(['message' => 'Doctor not found'], 404);
            }
            Response::json($doctor);
        } catch (Exception $e) {
            error_log("DoctorController Error: " . $e->getMessage());
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function store() {
        $data = $_REQUEST['decoded_input'];
        $this->service->createDoctor($data);
        Response::json(['message' => 'Doctor created successfully']);
    }

    public function update($id) {
        $data = $_REQUEST['decoded_input'];
        $this->service->update($id, $data);
        Response::json(['message' => 'Doctor updated successfully']);
    }

    public function delete($id) {
        $this->service->delete($id);
        Response::json(['message' => 'Doctor deleted successfully']);
    }
}
