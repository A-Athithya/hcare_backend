<?php
require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__ . '/../Repositories/TokenRepository.php';
require_once __DIR__ . '/../Config/config.php';

class AuthService {
    private $userRepo;
    private $tokenRepo;

    public function __construct() {
        $this->userRepo = new UserRepository();
        $this->tokenRepo = new TokenRepository();
    }

    public function register($data) {
        $existing = $this->userRepo->findByEmail($data['email']);
        if ($existing) {
            throw new Exception("Email already registered");
        }

        // 1. Create User
        $userId = $this->userRepo->create($data); // Creates in 'users' table

        if (!$userId) {
            throw new Exception("Failed to create user record.");
        }

        // 2. Create Role Profile
        $role = strtolower($data['role'] ?? 'patient');

        if ($role === 'patient') {
            require_once __DIR__ . '/PatientService.php';
            $patientService = new PatientService();
            $patientService->createPatient($data);
        } elseif ($role === 'admin') {
            // Admin only exists in users table, no separate profile table needed
            return $userId;
        } else {
            // Assume it's staff
            require_once __DIR__ . '/StaffService.php';
            $staffService = new StaffService();
            $staffService->createStaff($data);
        }

        return $userId;
    }

    public function login($email, $password, $requestedRole = null) {
        $user = $this->userRepo->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            throw new Exception("Invalid credentials");
        }

        // ✅ ROLE VALIDATION (CRITICAL RBAC FIX)
        if ($requestedRole !== null) {
            if (strtolower($user['role']) !== strtolower($requestedRole)) {
                throw new Exception("Role mismatch");
            }
        }

        return [
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'role' => $user['role'],
                'email' => $user['email'],
                'tenant_id' => $user['tenant_id']
            ]
        ];
    }


    public function changePassword($userId, $oldPassword, $newPassword) {
        $user = $this->userRepo->findById($userId);
        if (!$user) {
            throw new Exception("User not found");
        }

        if (!password_verify($oldPassword, $user['password'])) {
            throw new Exception("Current password incorrect");
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        return $this->userRepo->updatePassword($userId, $hashedPassword);
    }
}
