<?php
require_once __DIR__ . '/../Repositories/DashboardRepository.php';

class DashboardService {
    private $repo;

    public function __construct() {
        $this->repo = new DashboardRepository();
    }

    public function getDashboardStats($tenantId) {
        $patients = $this->repo->getPatients($tenantId);
        $doctors = $this->repo->getDoctors($tenantId);
        $appointments = $this->repo->getAppointments($tenantId);
        $medicines = $this->repo->getMedicines($tenantId);
        $invoices = $this->repo->getInvoices($tenantId);

        return [
            'patients' => $patients,
            'doctors' => $doctors,
            'appointments' => $appointments,
            'medicines' => $medicines,
            'invoices' => $invoices,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    public function getTenantAnalytics() {
        return $this->repo->getTenantAnalytics();
    }
}
