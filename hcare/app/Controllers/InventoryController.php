<?php
require_once __DIR__ . '/../Services/InventoryService.php';
require_once __DIR__ . '/../Helpers/Response.php';

class InventoryController {
    private $service;

    public function __construct() {
        $this->service = new InventoryService();
    }

    // GET /medicines
    public function index() {
        $data = $this->service->getAllMedicines();
        Response::json($data);
    }

    // GET /medicines/{id}
    public function show($id) {
        $medicine = $this->service->getMedicine($id);
        if ($medicine) {
            Response::json($medicine);
        } else {
            Response::json(['error' => 'Medicine not found'], 404);
        }
    }

    // POST /medicines
    public function store() {
        $input = $_REQUEST['decoded_input'];
        if (!$input || empty($input['medicine_name'])) {
            Response::json(['error' => 'Medicine name is required'], 400);
            return;
        }
        $id = $this->service->addMedicine($input);
        Response::json(['id' => $id], 201);
    }

    // PUT /medicines/{id}
    public function update($id) {
        $input = $_REQUEST['decoded_input'];
        if (!$input) {
            Response::json(['error' => 'Invalid data'], 400);
            return;
        }

        $updated = $this->service->updateMedicine($id, $input);
        if ($updated) {
            Response::json(['message' => 'Medicine updated successfully']);
        } else {
            Response::json(['error' => 'Update failed or medicine not found'], 400);
        }
    }

    // DELETE /medicines/{id}
    public function delete($id) {
        $deleted = $this->service->deleteMedicine($id);
        if ($deleted) {
            Response::json(['message' => 'Medicine deleted successfully']);
        } else {
            Response::json(['error' => 'Medicine not found'], 404);
        }
    }
}
