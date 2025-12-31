<?php


class Response {
    /**
     * Send a JSON response (encrypted)
     */
    public static function json($data, $status = 200) {
        // Ensure CORS headers are set (safety check - should already be set in index.php)
        if (!headers_sent() && function_exists('setCorsHeaders')) {
            // Check if CORS headers are already set
            $headersSet = false;
            foreach (headers_list() as $header) {
                if (stripos($header, 'Access-Control-Allow-Origin') !== false) {
                    $headersSet = true;
                    break;
                }
            }
            if (!$headersSet) {
                setCorsHeaders();
            }
        }
        
        // Ensure we're sending JSON
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }
        
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Encrypt Response
        require_once __DIR__ . '/../Services/EncryptionService.php';
        $encryption = new EncryptionService();
        $encrypted = $encryption->encrypt($data);
        
        http_response_code($status);
        
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