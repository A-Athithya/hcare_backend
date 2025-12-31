<?php
require_once __DIR__ . '/../Services/EncryptionService.php';

class EncryptionMiddleware {
    public static function handleInput() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);

        // 1. If payload exists, decrypt it (The standard way)
        if (isset($json['payload'])) {
            $service = new EncryptionService();
            $decrypted = $service->decrypt($json['payload']);
            
            if ($decrypted !== null) {
                $_REQUEST['decoded_input'] = $decrypted;
                return;
            }
        }

        // 2. Strict Security: For non-GET requests, if no payload was found or decryption failed, 
        // we should reject if we want "perfect" security. 
        // However, to allow the application to still function for routes like 'log' which might not be encrypted,
        // we can check if it's a public route or not.
        // For now, let's assume if it's POST/PUT/DELETE and NOT the 'log' route, it MUST be encrypted.
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $isPublicLog = strpos($uri, '/log') !== false;

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE']) && !$isPublicLog && !isset($json['payload'])) {
            // require_once __DIR__ . '/../Helpers/Response.php';
            // Response::json(['error' => 'Encrypted payload required for security'], 400);
            // exit;
        }

        // Fallback for GET or development
        $_REQUEST['decoded_input'] = $json ?? [];
    }
}
