<?php
require_once __DIR__ . '/../Helpers/Response.php';
require_once __DIR__ . '/../Services/PatientService.php';

class PatientController {
    private $patientService;

    public function __construct() {
        $this->patientService = new PatientService();
    }

    public function index() {
        $patients = $this->patientService->getAllPatients();
        Response::json($patients);
    }

    public function show($id) {
        $patient = $this->patientService->getPatientById($id);
        if ($patient) {
            Response::json($patient);
        } else {
            Response::json(['error' => 'Patient not found'], 404);
        }
    }

    public function store() {
        $data = $_REQUEST['decoded_input'];
        try {
            $id = $this->patientService->createPatient($data);
            Response::json(['message' => 'Patient created', 'id' => $id], 201);
        } catch (Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    public function update($id) {
        $data = $_REQUEST['decoded_input'];
        $updated = $this->patientService->updatePatient($id, $data);
        if ($updated) {
            Response::json(['message' => 'Patient updated', 'id' => $id]);
        } else {
            Response::json(['error' => 'Update failed'], 400);
        }
    }

    public function delete($id) {
        $deleted = $this->patientService->deletePatient($id);
        if ($deleted) {
            Response::json(['message' => 'Patient deleted']);
        } else {
            Response::json(['error' => 'Delete failed'], 400);
        }
    }

    public function appointments($id) {
        $appointments = $this->patientService->getPatientAppointments($id);
        Response::json($appointments);
    }
}
