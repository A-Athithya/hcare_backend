<?php
require_once __DIR__ . '/../Services/AuthService.php';
require_once __DIR__ . '/../Services/JwtService.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../Helpers/Response.php';

class AuthController {
    private $authService;
    private $jwtService;

    public function __construct() {
        $this->authService = new AuthService();
        $this->jwtService = new JwtService();
    }

    public function register() {
        $data = $_REQUEST['decoded_input'];
        
        // Ensure tenant_id is present (default logic or validation)
        if (!isset($data['tenant_id'])) {
            $data['tenant_id'] = 1; 
        }

        try {
            $userId = $this->authService->register($data);
            
            // Immediately log the user in after registration
            $user = $this->getUserById($userId);
            
            if (!$user) {
                throw new Exception("User registration succeeded but user record not found.");
            }

            // 201 Created Response - No Tokens
            Response::json([
                'message' => 'User registered successfully. Please login.', 
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'email' => $user['email'],
                    'tenant_id' => $user['tenant_id']
                ],
                'csrfToken' => CsrfMiddleware::generate()
            ], 201);
        } catch (Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    public function login() {
        require_once __DIR__ . '/../Helpers/Log.php';
        $data = $_REQUEST['decoded_input'];
        
        Log::info("Login Attempt", ['email' => $data['email'] ?? 'missing']);

        if (!isset($data['email']) || !isset($data['password'])) {
            Log::error("Login Missing Creds", $data);
            Response::json(['error' => 'Please enter both email and password'], 400);
        }

        try {
            // 1. Verify Credentials
                $result = $this->authService->login(
                    $data['email'],
                    $data['password']
                );

                $user = $result['user'];

                // 🔒 STRICT ROLE VALIDATION (NO TRUST ON CLIENT)
                if (
                    empty($data['role']) ||
                    strtolower($data['role']) !== strtolower($user['role'])
                ) {
                    Response::json(['error' => 'This account does not have access to the selected role'], 403);
                }

            
            // 2. Generate Tokens
            $accessToken = $this->jwtService->generateAccessToken($user['id'], $user['role'], $user['tenant_id'] ?? 1);
            $refreshToken = bin2hex(random_bytes(32));
            
            // 3. Store Refresh Token in DB
            require_once __DIR__ . '/../Repositories/TokenRepository.php';
            $tokenRepo = new TokenRepository();
            $expiresAt = date('Y-m-d H:i:s', time() + 604800); // 7 days
            $tokenRepo->store($user['id'], $refreshToken, $expiresAt);
            
            // 4. Store Access Token and User Info in PHP Session
            $_SESSION['accessToken'] = $accessToken;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['tenant_id'] = $user['tenant_id'] ?? 1;

            // Set HttpOnly Cookie for Refresh Token
            $cookieSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            setcookie('refreshToken', $refreshToken, [
                'expires' => time() + 604800,
                'path' => '/',
                'domain' => '', // Current domain
                'secure' => $cookieSecure,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);

            // 5. Return Response (Access Token only, Refresh Token in Cookie)
            Log::info("Login Success", ['user_id' => $user['id'], 'role' => $user['role']]);
            Response::json([
                'user' => $user,
                'accessToken' => $accessToken,
                'expiresIn' => 900, // 15 minutes
                'csrfToken' => CsrfMiddleware::generate()
            ]);
            
        } catch (Exception $e) {
            Log::error("Login Exception", ['msg' => $e->getMessage()]);
            Response::json(['error' => $e->getMessage()], 401);
        }
    }
    
    public function refresh() {
        try {
            // Read from Cookie ONLY
            $refreshToken = $_COOKIE['refreshToken'] ?? null;
            
            if (!$refreshToken) {
                Response::json(['error' => 'Refresh token required (cookie missing)'], 401);
            }
            
            require_once __DIR__ . '/../Repositories/TokenRepository.php';
            $tokenRepo = new TokenRepository();
            
            $storedToken = $tokenRepo->isValid($refreshToken);
            
            if (!$storedToken) {
                // Invalid token - clear cookie
                setcookie('refreshToken', '', time() - 3600, '/');
                Response::json(['error' => 'Invalid or expired refresh token'], 401);
            }
            
            // Revoke old token
            $tokenRepo->revoke($refreshToken);
            
            // Generate new tokens
            $userId = $storedToken['user_id'];
            $user = $this->getUserById($userId); 
            
            if (!$user) {
                Response::json(['error' => 'User not found'], 401);
            }
            
            $newAccessToken = $this->jwtService->generateAccessToken($user['id'], $user['role'], $user['tenant_id']);
            $newRefreshToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 604800);
            
            $tokenRepo->store($userId, $newRefreshToken, $expiresAt);
            
            // Update Session
            $_SESSION['accessToken'] = $newAccessToken;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['tenant_id'] = $user['tenant_id'] ?? 1;

            // Set new HttpOnly Cookie
            $cookieSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            setcookie('refreshToken', $newRefreshToken, [
                'expires' => time() + 604800,
                'path' => '/',
                'domain' => '',
                'secure' => $cookieSecure,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);

            Response::json([
                'accessToken' => $newAccessToken,
                'expiresIn' => 900
            ]);
        } catch (Exception $e) {
            error_log("REFRESH ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            Response::json(['error' => 'Refresh failed', 'message' => $e->getMessage()], 500);
        }
    }
    
    public function csrf() {
        Response::json([
            'csrfToken' => CsrfMiddleware::generate(),
            'csrf_token' => $_SESSION['csrf_token'] ?? CsrfMiddleware::generate()
        ]);
    }

    public function logRemote() {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?? $_POST;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $path = BASE_PATH . '/public/remote_debug.log';
        $logMsg = "[" . date('Y-m-d H:i:s') . "] [$ip] FE_LOG: " . json_encode($data) . "\n";
        
        if (file_put_contents($path, $logMsg, FILE_APPEND) === false) {
            $err = error_get_last();
            error_log("CRITICAL: Failed to write remote_debug.log: " . json_encode($err));
        }
        Response::json(['status' => 'logged']);
    }

    public function changePassword() {
        $data = $_REQUEST['decoded_input'];
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            Response::json(['error' => 'Unauthorized'], 401);
        }

        if (empty($data['oldPassword']) || empty($data['newPassword'])) {
            Response::json(['error' => 'Old and new passwords required'], 400);
        }

        try {
            $this->authService->changePassword($userId, $data['oldPassword'], $data['newPassword']);
            Response::json(['message' => 'Password changed successfully']);
        } catch (Exception $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }
    public function regenerateCsrf() {
        Response::json(['csrf_token' => CsrfMiddleware::regenerate()]);
    }
    
    public function logout() {
        $refreshToken = $_COOKIE['refreshToken'] ?? null;
        
        if ($refreshToken) {
            require_once __DIR__ . '/../Repositories/TokenRepository.php';
            $tokenRepo = new TokenRepository();
            $tokenRepo->revoke($refreshToken);
        }
        
        // Clear cookie
        setcookie('refreshToken', '', time() - 3600, '/');

        // Clear session data
        unset($_SESSION['accessToken']);
        session_destroy(); 
        
        Response::json(['message' => 'Logged out']);
    }
    
    private function getUserById($id) {
        require_once __DIR__ . '/../Repositories/UserRepository.php';
        return (new UserRepository())->findById($id);
    }
}
