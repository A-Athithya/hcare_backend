<?php
require_once __DIR__ . '/../Services/StaffService.php';
require_once __DIR__ . '/../Helpers/Response.php';

class StaffController {

    private $service;

    public function __construct() {
        $this->service = new StaffService();
    }

    // ================= GET ALL STAFF =================
    // GET /staff?role=nurses
    public function index() {
        $role = $_GET['role'] ?? '';
        $data = $this->service->getAllStaff($role);
        Response::json($data);
    }

    // ================= GET SINGLE STAFF =================
    // GET /staff/{id}?role=nurse
    public function show($id) {
        $role = $_GET['role'] ?? '';
        if (!$role) {
            Response::json(['error' => 'Role missing'], 400);
            return;
        }
        $data = $this->service->getStaffById($role, $id);
        Response::json($data);
    }

    // ================= CREATE STAFF =================
    // POST /staff
    public function store() {
        $data = $_REQUEST['decoded_input'];

        if (!isset($data['role'])) {
            Response::json(['error' => 'Role missing'], 400);
            return;
        }

        $id = $this->service->createStaff($data);
        Response::json([
            'message' => 'Staff created successfully',
            'id' => $id
        ]);
    }

    // ================= UPDATE STAFF =================
    // PUT /staff/{id}
    public function update($id) {
        $data = $_REQUEST['decoded_input'];
        $role = $data['role'] ?? ($_GET['role'] ?? '');

        if (!$role) {
            Response::json(['error' => 'Role missing'], 400);
            return;
        }

        $this->service->updateStaff($role, $id, $data);
        Response::json(['message' => 'Staff updated successfully']);
    }

    // ================= DELETE STAFF =================
    // DELETE /staff/{id}?role=nurse
    public function delete($id) {
        $role = $_GET['role'] ?? '';

        if (!$role) {
            Response::json(['error' => 'Role missing'], 400);
            return;
        }

        $this->service->deleteStaff($role, $id);
        Response::json(['message' => 'Staff deleted successfully']);
    }
}
