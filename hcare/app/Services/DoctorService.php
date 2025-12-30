<?php
require_once __DIR__ . '/../Repositories/DoctorRepository.php';

class DoctorService {
    private $repo;

    public function __construct() {
        $this->repo = new DoctorRepository();
    }

    public function getAllDoctors() {
        $tenantId = $_REQUEST['user']['tenant_id'] ?? 1;
        return $this->repo->getAll($tenantId);
    }

    public function find($id) {
        $tenantId = $_REQUEST['user']['tenant_id'] ?? 1;
        return $this->repo->getById($id, $tenantId);
    }

    public function createDoctor($data) {
        $data['tenant_id'] = $_REQUEST['user']['tenant_id'] ?? 1;
        return $this->repo->create($data);
    }

    public function update($id, $data) {
        $tenantId = $_REQUEST['user']['tenant_id'] ?? 1;
        return $this->repo->update($id, $data, $tenantId);
    }

    public function delete($id) {
        $tenantId = $_REQUEST['user']['tenant_id'] ?? 1;
        return $this->repo->delete($id, $tenantId);
    }
}
