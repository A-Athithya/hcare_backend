<?php


require_once __DIR__ . '/../Services/JwtService.php';

class AuthMiddleware {
    public static function handle() {
        // 1. Prefer Authorization header (Bearer Token)
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        $token = null;
        
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        }

        // 2. Fallback to PHP Session
        if (!$token) {
            $token = $_SESSION['accessToken'] ?? null;
        }

        if ($token) {
            $jwt = new JwtService();
            $decoded = $jwt->validateToken($token);
            
            if ($decoded) {
                return $decoded; // Returns payload (sub, role, tenant_id)
            } else {
                 require_once __DIR__ . '/../Helpers/Log.php';
                 Log::error("AuthMiddleware: Token Invalid", ['token_preview' => substr($token, 0, 10) . '...']);
            }
        } else {
             require_once __DIR__ . '/../Helpers/Log.php';
             Log::error("AuthMiddleware: No Token Provided", ['headers' => $headers]);
        }
        
        require_once __DIR__ . '/../Helpers/Response.php';
        Response::json(['error' => 'Unauthorized'], 401);
        exit;
    }
}
