<?php


class Response {
    public static function json($data, $status = 200) {
        
        // Encrypt Response
        require_once __DIR__ . '/../Services/EncryptionService.php';
        $encryption = new EncryptionService();
        $encrypted = $encryption->encrypt($data);
        http_response_code($status);
        if (ob_get_length()) ob_clean();
        
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $response = ['payload' => $encrypted];
        
        if (isset($headers['X-Debug-Mode']) || isset($headers['x-debug-mode'])) {
            $response['debug'] = $data;
        }

        echo json_encode($response);
        exit;
    }
}
