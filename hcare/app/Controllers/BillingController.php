<?php
// backend/app/Controllers/BillingController.php

require_once __DIR__ . '/../Helpers/Response.php';
require_once __DIR__ . '/../Services/BillingService.php';

class BillingController {
    private $service;

    public function __construct() {
        $this->service = new BillingService();
    }

    public function index() {
        try {
            $data = $this->service->getAllInvoices();
            Response::json($data);
        } catch (Exception $e) {
            error_log("CRITICAL ERROR in BillingController: " . $e->getMessage());
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id) {
        try {
            $data = $this->service->getInvoiceById($id);
            if (!$data) {
                Response::json(['error' => 'Invoice not found'], 404);
                return;
            }
            Response::json($data);
        } catch (Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function store() {
        $data = $_REQUEST['decoded_input'] ?? $_POST;
        try {
            $id = $this->service->createInvoice($data);
            Response::json(['message' => 'Invoice generated successfully', 'id' => $id], 201);
        } catch (Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    public function updateStatus($id) {
        $data = $_REQUEST['decoded_input'] ?? $_POST;
        $status = $data['status'] ?? null;
        $paidAmount = $data['paidAmount'] ?? $data['paid_amount'] ?? 0;

        if (!$status) {
            Response::json(['error' => 'Status is required'], 400);
            return;
        }

        try {
            $success = $this->service->updateStatus($id, $status, $paidAmount);
            if ($success) {
                Response::json(['message' => 'Invoice status updated successfully']);
            } else {
                Response::json(['error' => 'Failed to update invoice status'], 404);
            }
        } catch (Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    public function summary() {
        try {
            $data = $this->service->getSummary();
            Response::json($data);
        } catch (Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function patientInvoices($id) {
        try {
            $data = $this->service->getPatientInvoices($id);
            Response::json($data);
        } catch (Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }
}
