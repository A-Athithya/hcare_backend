<?php
require_once __DIR__ . '/../Helpers/Response.php';
require_once __DIR__ . '/../Services/DashboardService.php';

class DashboardController {
    private $service;

    public function __construct() {
        $this->service = new DashboardService();
    }

    /**
     * Get dashboard summary for the current tenant.
     * Roles: Provider, Admin
     */
    public function index() {
        try {
            $tenantId = $_REQUEST['user']['tenant_id'] ?? 1;
            $stats = $this->service->getDashboardStats($tenantId);
            Response::json($stats);
        } catch (Exception $e) {
            require_once __DIR__ . '/../Helpers/Log.php';
            Log::error("Dashboard Error", ['msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get aggregated analytics for all tenants.
     * Roles: Admin
     */
    public function getAnalytics() {
        try {
            // Role check is already handled by RoleMiddleware, 
            // but we can add extra safety if needed.
            $analytics = $this->service->getTenantAnalytics();
            Response::json($analytics);
        } catch (Exception $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
    }
}
