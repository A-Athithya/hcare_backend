<?php




class CsrfMiddleware
{
    // Router calls this method for middleware execution
    public static function handle()
    {
        // Start session if needed (validate() does this too, but safe to do here)
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Validate the token
        self::validate();

        // Ensure we don't return an array to avoid overwriting $_REQUEST['user'] in Router.php
        return true; 
    }

    // Generate a new token if one doesn't exist, and return it
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

    // Helper to validate token on POST/PUT/DELETE
    public static function validate()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return true; // no validation needed for safe methods
        }

        // Retrieve token from request (POST body, header, or query string)
        $provided = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

        if (!$provided) {
            // Also check decoded_input if available (common in this app)
            $decoded = $_REQUEST['decoded_input'] ?? null;
            if ($decoded && isset($decoded['csrf_token'])) {
                $provided = $decoded['csrf_token'];
            }
        }

        if (!$provided) {
            require_once __DIR__ . '/../Helpers/Response.php';
            Response::json(['error' => 'Missing CSRF token'], 400);
        }

        if (!hash_equals($_SESSION['csrf_token'] ?? '', $provided)) {
            require_once __DIR__ . '/../Helpers/Response.php';
            Response::json(['error' => 'Invalid CSRF token'], 403);
        }

        return true;
    }
}