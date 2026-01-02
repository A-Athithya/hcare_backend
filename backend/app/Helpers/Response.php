<?php


class Response {
    public static function json($data, $status = 200, $encrypt = true) {
        if ($encrypt) {
            // Encrypt Response
            require_once __DIR__ . '/../Services/EncryptionService.php';
            $encryption = new EncryptionService();
            $encrypted = $encryption->encrypt($data);
            $response = ['payload' => $encrypted];
        } else {
            $response = $data;
        }

        http_response_code($status);
        if (ob_get_length()) ob_clean();
        
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        
        if ($encrypt && (isset($headers['X-Debug-Mode']) || isset($headers['x-debug-mode']))) {
            $response['debug'] = $data;
        }

        echo json_encode($response);
        exit;
    }
}
