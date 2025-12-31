<?php
require_once __DIR__ . '/../Repositories/AppointmentRepository.php';
require_once __DIR__ . '/../Repositories/PatientRepository.php';
require_once __DIR__ . '/../Repositories/DoctorRepository.php';
require_once __DIR__ . '/../Repositories/UserRepository.php';

class AppointmentService {
    private $repo;
    private $patientRepo;
    private $doctorRepo;
    private $userRepo;

    public function __construct() {
        $this->repo = new AppointmentRepository();
        $this->patientRepo = new PatientRepository();
        $this->doctorRepo = new DoctorRepository();
        $this->userRepo = new UserRepository();
    }

    public function getAllAppointments() {
        $user = $_REQUEST['user'];
        $tenantId = $user['tenant_id'] ?? 1;
        $role = strtolower($user['role'] ?? '');

        if ($role === 'patient') {
            $patient = $this->getSelfPatient($user, $tenantId);
            return $this->repo->getAll($tenantId, $patient['id'] ?? -1);
        } elseif ($role === 'doctor' || $role === 'provider') {
            $doctor = $this->getSelfDoctor($user, $tenantId);
            return $this->repo->getAll($tenantId, null, $doctor['id'] ?? -1);
        }

        // Admin/Nurse see everything
        return $this->repo->getAll($tenantId);
    }

    public function getAppointmentById($id) {
        $user = $_REQUEST['user'];
        $tenantId = $user['tenant_id'] ?? 1;
        $role = $user['role'];

        $appointment = $this->repo->getById($id, $tenantId);
        if (!$appointment) return null;

        // Role-based access check
        $role = strtolower($role);
        if ($role === 'patient') {
            $patient = $this->getSelfPatient($user, $tenantId);
            if ($appointment['patient_id'] != ($patient['id'] ?? -1)) {
                throw new Exception("Unauthorized access to this appointment.");
            }
        } elseif ($role === 'doctor' || $role === 'provider') {
            $doctor = $this->getSelfDoctor($user, $tenantId);
            if ($appointment['doctor_id'] != ($doctor['id'] ?? -1)) {
                throw new Exception("Unauthorized access to this appointment.");
            }
        }

        return $appointment;
    }

    public function createAppointment($data) {
        $user = $_REQUEST['user'];
        $data['tenant_id'] = $user['tenant_id'] ?? 1;

        // If patient is booking, force their own ID
        $role = strtolower($user['role'] ?? '');
        if ($role === 'patient') {
            $patient = $this->getSelfPatient($user, $data['tenant_id']);
            $data['patientId'] = $patient['id'];
        }

        if ($this->repo->hasConflict($data['doctorId'], $data['appointmentDate'], $data['appointmentTime'])) {
            throw new Exception("Conflict: The doctor already has an appointment at this time.");
        }

        return $this->repo->create($data);
    }

    public function updateAppointment($id, $data) {
        $user = $_REQUEST['user'];
        $tenantId = $user['tenant_id'] ?? 1;
        
        $appointment = $this->repo->getById($id, $tenantId);
        if (!$appointment) throw new Exception("Appointment not found.");

        // Authorization check
        if ($user['role'] === 'Patient') {
             $patient = $this->getSelfPatient($user, $tenantId);
             if ($appointment['patient_id'] != $patient['id']) {
                 throw new Exception("Unauthorized to update this appointment.");
             }
             // Patients might only be allowed to update certain fields or just cancel
        } elseif ($user['role'] === 'Provider') {
            $doctor = $this->getSelfDoctor($user, $tenantId);
            if ($appointment['doctor_id'] != $doctor['id']) {
                throw new Exception("Unauthorized to update this appointment.");
            }
        }

        // Conflict check if time/doctor/date changed
        $doctorId = $data['doctorId'] ?? $appointment['doctor_id'];
        $date = $data['appointmentDate'] ?? $appointment['appointment_date'];
        $time = $data['appointmentTime'] ?? $appointment['appointment_time'];

        if ($this->repo->hasConflict($doctorId, $date, $time, $id)) {
            throw new Exception("Conflict: The doctor already has an appointment at this time.");
        }

        return $this->repo->update($id, $data);
    }

    public function deleteAppointment($id) {
        $user = $_REQUEST['user'];
        $tenantId = $user['tenant_id'] ?? 1;

        $appointment = $this->repo->getById($id, $tenantId);
        if (!$appointment) throw new Exception("Appointment not found.");

        // Authorization (Admin/Nurse can delete, Patients/Providers maybe can only Cancel)
        // For simplicity, let's allow actual delete if Admin/Nurse, otherwise check ownership
        if ($user['role'] !== 'Admin' && $user['role'] !== 'Nurse' && $user['role'] !== 'Receptionist') {
            if ($user['role'] === 'Patient') {
                $patient = $this->getSelfPatient($user, $tenantId);
                if ($appointment['patient_id'] != $patient['id']) throw new Exception("Unauthorized.");
            } elseif ($user['role'] === 'Provider') {
                $doctor = $this->getSelfDoctor($user, $tenantId);
                if ($appointment['doctor_id'] != $doctor['id']) throw new Exception("Unauthorized.");
            }
        }

        return $this->repo->delete($id, $tenantId);
    }

    public function getUpcomingAppointments() {
        $user = $_REQUEST['user'];
        $tenantId = $user['tenant_id'] ?? 1;
        $role = strtolower($user['role'] ?? '');

        if ($role === 'patient') {
            $patient = $this->getSelfPatient($user, $tenantId);
            return $this->repo->getUpcoming($tenantId, $patient['id'] ?? -1);
        } elseif ($role === 'doctor' || $role === 'provider') {
            $doctor = $this->getSelfDoctor($user, $tenantId);
            return $this->repo->getUpcoming($tenantId, null, $doctor['id'] ?? -1);
        }

        return $this->repo->getUpcoming($tenantId);
    }

    public function getCalendarAppointments($startDate, $endDate) {
        $user = $_REQUEST['user'];
        $tenantId = $user['tenant_id'] ?? 1;
        $role = strtolower($user['role'] ?? '');

        if ($role === 'patient') {
            $patient = $this->getSelfPatient($user, $tenantId);
            return $this->repo->getByDateRange($tenantId, $startDate, $endDate, $patient['id'] ?? -1);
        } elseif ($role === 'doctor' || $role === 'provider') {
            $doctor = $this->getSelfDoctor($user, $tenantId);
            return $this->repo->getByDateRange($tenantId, $startDate, $endDate, null, $doctor['id'] ?? -1);
        }

        // Admin, Nurse, Receptionist see all
        return $this->repo->getByDateRange($tenantId, $startDate, $endDate);
    }

    public function getAppointmentTooltip($id) {
        $user = $_REQUEST['user'];
        $tenantId = $user['tenant_id'] ?? 1;

        $appointment = $this->getAppointmentById($id);
        if (!$appointment) return null;

        // Return a subset of fields for the tooltip
        return [
            'id' => $appointment['id'],
            'patient_name' => $appointment['patient_name'],
            'doctor_name' => $appointment['doctor_name'],
            'appointment_date' => $appointment['appointment_date'],
            'appointment_time' => $appointment['appointment_time'],
            'reason' => $appointment['reason'],
            'status' => $appointment['status']
        ];
    }

    private function getSelfPatient($user, $tenantId) {
        $userData = $this->userRepo->findById($user['sub']);
        return $this->patientRepo->findByEmail($userData['email'], $tenantId);
    }

    private function getSelfDoctor($user, $tenantId) {
        $userData = $this->userRepo->findById($user['sub']);
        return $this->doctorRepo->findByEmail($userData['email'], $tenantId);
    }
}
