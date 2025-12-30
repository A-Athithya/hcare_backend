<?php
// backend/app/Services/BillingService.php

require_once __DIR__ . '/../Repositories/BillingRepository.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';

class BillingService {
    private $repository;

    public function __construct() {
        $this->repository = new BillingRepository();
    }

    private function getTenantId() {
        return $_REQUEST['user']['tenant_id'] ?? 1;
    }

    public function getAllInvoices() {
        $tenantId = $this->getTenantId();
        return $this->repository->getInvoicesByTenant($tenantId);
    }

    public function getInvoiceById($id) {
        $tenantId = $this->getTenantId();
        return $this->repository->getInvoiceById($id, $tenantId);
    }

    public function createInvoice($data) {
        $data['tenant_id'] = $this->getTenantId();
        
        // Validation
        $patientId = $data['patientId'] ?? $data['patient_id'] ?? null;
        $totalAmount = $data['totalAmount'] ?? $data['total_amount'] ?? null;

        if (empty($patientId)) {
            throw new Exception("Patient ID is required");
        }
        if (empty($totalAmount) || $totalAmount < 0) {
            throw new Exception("Valid total amount is required");
        }

        return $this->repository->createInvoice($data);
    }

    public function updateStatus($id, $status, $paidAmount) {
        $tenantId = $this->getTenantId();
        
        if (!in_array($status, ['Paid', 'Unpaid', 'Partial Paid'])) {
            throw new Exception("Invalid status");
        }

        return $this->repository->updateInvoiceStatus($id, $status, $paidAmount, $tenantId);
    }

    public function getPatientInvoices($patientId) {
        $tenantId = $this->getTenantId();
        // If current user is a patient, they can only see their own invoices
        $currentUser = $_REQUEST['user'] ?? null;
        if ($currentUser && $currentUser['role'] === 'Patient' && ($currentUser['sub'] ?? null) != $patientId) {
            // Further validation could be added here
        }
        return $this->repository->getInvoicesByPatient($patientId, $tenantId);
    }

    public function getSummary() {
        $tenantId = $this->getTenantId();
        return $this->repository->getInvoiceSummary($tenantId);
    }
}
