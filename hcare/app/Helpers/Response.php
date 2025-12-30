<?php


class Response {
    /**
     * Send a JSON response (encrypted)
     */
    public static function json($data, $status = 200) {
        // Encrypt Response
        require_once __DIR__ . '/../Services/EncryptionService.php';
        $encryption = new EncryptionService();
        $encrypted = $encryption->encrypt($data);

        http_response_code($status);
        if (ob_get_length()) {
            ob_clean();
        }
        
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $response = ['payload' => $encrypted];
        
        // Optionally include raw data for debugging
        if (isset($headers['X-Debug-Mode']) || isset($headers['x-debug-mode'])) {
            $response['debug'] = $data;
        }

        echo json_encode($response);
        exit;
    }

    /**
     * Helper for sending standardized error responses
     * Matches the usage in index.php: Response::error($message, $status, $details)
     */
    public static function error($message, $status = 500, $details = null) {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($details !== null) {
            $payload['details'] = $details;
        }

        self::json($payload, $status);
    }
}
