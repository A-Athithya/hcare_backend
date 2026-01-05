<?php

class CsrfMiddleware
{
    public static function handle()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // 🔑 ONLY ADDITION (route exclude)
        $excludedRoutes = [
            '/csrf-token',
            '/login',
            '/register',
            '/refresh-token'
        ];

        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (in_array($path, $excludedRoutes)) {
            return true;
        }

        self::validate();
        return true;
    }

    public static function generate()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function regenerate()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }

    public static function validate()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return true;
        }

        $provided =
            $_POST['csrf_token']
            ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)
            ?? ($_REQUEST['decoded_input']['csrf_token'] ?? null);

        if (!$provided) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing CSRF token']);
            exit;
        }

        if (!hash_equals($_SESSION['csrf_token'] ?? '', $provided)) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        return true;
    }
}
