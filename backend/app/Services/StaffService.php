<?php
require_once __DIR__ . '/../Repositories/StaffRepository.php';
require_once __DIR__ . '/../Repositories/UserRepository.php';

class StaffService {

    private $repo;
    private $userRepo;

    public function __construct() {
        $this->repo = new StaffRepository();
        $this->userRepo = new UserRepository();
    }

    // ================= ROLE NORMALIZATION =================
    private function normalizeRole($role) {
        $role = strtolower($role);
        $map = [
            'doctors' => 'doctor',
            'nurses' => 'nurse',
            'pharmacists' => 'pharmacist',
            'receptionists' => 'receptionist',
            'admins' => 'admin',
        ];
        return $map[$role] ?? $role;
    }

    // ================= GET ALL STAFF =================
    public function getAllStaff($role = 'doctor') {
        $tenantId = $_REQUEST['user']['tenant_id'] ?? 1;
        $role = $this->normalizeRole($role);
        return $this->repo->getAll($role, $tenantId);
    }

    // ================= GET SINGLE STAFF =================
    public function getStaffById($role, $id) {
        $tenantId = $_REQUEST['user']['tenant_id'] ?? 1;
        $role = $this->normalizeRole($role);
        return $this->repo->getById($role, $id, $tenantId);
    }

    // ================= CREATE STAFF =================
    public function createStaff($data) {
        $data['tenant_id'] = $_REQUEST['user']['tenant_id'] ?? $data['tenant_id'] ?? 1;
        $role = $this->normalizeRole($data['role'] ?? 'doctor');

        $userId = null;

        // ✅ Create or find User account
        if (!empty($data['email'])) {
            $existing = $this->userRepo->findByEmail($data['email']);
            if ($existing) {
                $userId = $existing['id'];
            } elseif (!empty($data['password'])) {
                $userId = $this->userRepo->create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'], // hashes internally
                    'role' => $role,
                    'tenant_id' => $data['tenant_id']
                ]);
            }
        }

        if ($userId) {
            $data['user_id'] = $userId; // link staff -> user
        }

        // ✅ Create staff record
        switch ($role) {
            case 'doctor':
                return $this->repo->createDoctor($data);
            case 'nurse':
                return $this->repo->createNurse($data);
            case 'pharmacist':
                return $this->repo->createPharmacist($data);
            case 'receptionist':
                return $this->repo->createReceptionist($data);
            default:
                throw new Exception('Invalid staff role');
        }
    }

    // ================= UPDATE STAFF =================
    public function updateStaff($role, $id, $data) {
        $tenantId = $_REQUEST['user']['tenant_id'] ?? 1;
        $role = $this->normalizeRole($role);

        // ✅ Update linked user account if email/password provided
        if (!empty($data['email']) || !empty($data['password'])) {
            $staff = $this->repo->getById($role, $id, $tenantId);
            if (!empty($staff['user_id'])) {
                $userData = [];
                if (!empty($data['email'])) $userData['email'] = $data['email'];
                if (!empty($data['password'])) $userData['password'] = $data['password'];
                if (!empty($data['name'])) $userData['name'] = $data['name'];
                $this->userRepo->update($staff['user_id'], $userData);
            }
        }

        return $this->repo->update($role, $id, $tenantId, $data);
    }

    // ================= DELETE STAFF =================
    public function deleteStaff($role, $id) {
        $tenantId = $_REQUEST['user']['tenant_id'] ?? 1;
        $role = $this->normalizeRole($role);

        // ✅ Optionally delete linked user
        $staff = $this->repo->getById($role, $id, $tenantId);
        if (!empty($staff['user_id'])) {
            $this->userRepo->delete($staff['user_id']);
        }

        return $this->repo->delete($role, $id, $tenantId);
    }
}
