<?php
// backend/app/Middleware/RoleMiddleware.php

require_once __DIR__ . '/../Helpers/Response.php';

class RoleMiddleware {
    /**
     * Handle the middleware request.
     * 
     * @param array $allowedRoles List of roles permitted to access the route.
     * @return void
     */
    public static function handle($allowedRoles = []) {
        // AuthMiddleware must have already run and set $_REQUEST['user']
        $user = $_REQUEST['user'] ?? null;

        if (!$user) {
            Response::json(['error' => 'Unauthorized'], 401);
            exit;
        }

        $userRole = strtolower($user['role'] ?? '');
        $allowedRoles = array_map('strtolower', $allowedRoles);

        if (!$userRole || !in_array($userRole, $allowedRoles)) {
            Response::json([
                'error' => 'Forbidden',
                'message' => 'You do not have permission to access this resource.',
                'debug_role' => $user['role'],
                'allowed' => $allowedRoles
            ], 403);
            exit;
        }
    }
}
