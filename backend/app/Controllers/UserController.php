<?php
require_once __DIR__ . '/../Helpers/Response.php';

require_once __DIR__ . '/../Repositories/UserRepository.php';

class UserController {
    private $userRepo;

    public function __construct() {
        $this->userRepo = new UserRepository();
    }

    public function index() {
        // Admin only (enforced by middleware)
        $users = $this->userRepo->findAll();
        Response::json($users);
    }

    public function show($id) {
        $user = $this->userRepo->findById($id);
        if (!$user) {
            Response::json(['error' => 'User not found'], 404);
        }
        Response::json($user);
    }

    public function store() {
        $data = $_REQUEST['decoded_input'];
        
        // Basic validation
        if (empty($data['email']) || empty($data['password']) || empty($data['role'])) {
            Response::json(['error' => 'Email, password, and role are required'], 400);
        }

        if (!isset($data['tenant_id'])) {
            $data['tenant_id'] = 1;
        }

        try {
            $userId = $this->userRepo->create($data);
            if ($userId) {
                Response::json(['message' => 'User created', 'id' => $userId], 201);
            } else {
                Response::json(['error' => 'Failed to create user'], 500);
            }
        } catch (Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    public function update($id) {
        $data = $_REQUEST['decoded_input'];
        if ($this->userRepo->update($id, $data)) {
            Response::json(['message' => 'User updated']);
        } else {
            Response::json(['error' => 'Failed to update user or no changes made'], 400);
        }
    }

    public function delete($id) {
        if ($this->userRepo->delete($id)) {
            Response::json(['message' => 'User deleted']);
        } else {
            Response::json(['error' => 'Failed to delete user'], 500);
        }
    }

    // Profile APIs
    public function getProfile() {
        $currentUser = $_REQUEST['user']; // From AuthMiddleware
        $user = $this->userRepo->findById($currentUser['sub']);
        
        if (!$user) {
            Response::json(['error' => 'Profile not found'], 404);
        }

        unset($user['password']); // Do not expose password hash
        
        Response::json($user);
    }

    public function updateProfile() {
        $currentUser = $_REQUEST['user']; // [sub => id, role => role, tenant_id]
        $data = $_REQUEST['decoded_input'];
        $userId = $currentUser['sub'];
        $role = strtolower($currentUser['role']);
        $tenantId = $currentUser['tenant_id'] ?? 1;

        // 1. Get current user to have their email
        $userBase = $this->userRepo->findById($userId);
        if (!$userBase) {
            Response::json(['error' => 'User not found'], 404);
        }
        $email = $userBase['email'];

        // 2. Prevent changing sensitive info
        unset($data['role']);
        unset($data['tenant_id']);
        unset($data['id']);
        unset($data['password']); // Prevent password update via this route

        $success = false;

        // 3. Update specialized table based on role
        if ($role === 'doctor' || $role === 'provider') {
            require_once __DIR__ . '/../Repositories/DoctorRepository.php';
            $doctorRepo = new DoctorRepository();
            $doctor = $doctorRepo->findByEmail($email, $tenantId);
            if ($doctor) {
                if ($doctorRepo->update($doctor['id'], $data, $tenantId)) $success = true;
            }
        } elseif ($role === 'patient') {
            require_once __DIR__ . '/../Repositories/PatientRepository.php';
            $patientRepo = new PatientRepository();
            $patient = $patientRepo->findByEmail($email, $tenantId);
            if ($patient) {
                if ($patientRepo->update($patient['id'], $data, $tenantId)) $success = true;
            }
        } elseif (in_array($role, ['nurse', 'pharmacist', 'receptionist', 'staff'])) {
            require_once __DIR__ . '/../Repositories/StaffRepository.php';
            require_once __DIR__ . '/../Config/database.php';
            $staffRepo = new StaffRepository();
            $table = $role . 's'; 
            if ($role === 'staff') $table = 'staff'; // Fallback if needed
            
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT id FROM $table WHERE email = ? AND tenant_id = ?");
            $stmt->execute([$email, $tenantId]);
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($staff) {
                if ($staffRepo->update($role, $staff['id'], $tenantId, $data)) $success = true;
            }
        }

        // 4. Update base user record (Name/Email)
        if ($this->userRepo->update($userId, $data)) {
            $success = true;
        }

        if ($success) {
            Response::json(['message' => 'Profile updated successfully']);
        } else {
            Response::json(['error' => 'No changes made or update failed'], 400);
        }
    }
}
