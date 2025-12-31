<?php
/**
 * Healthcare Management System - Main Entry Point
 * 
 * This file serves as the entry point for all API requests.
 * It handles CORS, loads the environment configuration, initializes services,
 * and routes requests to the appropriate controllers.
 */

// Start output buffering to prevent any accidental output
ob_start();

// Function to set CORS headers - call this early and in all error handlers
function setCorsHeaders() {
    if (headers_sent()) {
        return;
    }
    
    // Define allowed origins
    $allowedOrigins = [
        'http://localhost:3000',
        'http://localhost:3001',
        'https://hcarefrontend-7t0ah3e14-athithyas-projects-37de8dcf.vercel.app',
    ];
    
    // Get additional allowed origins from environment variable (comma-separated)
    $envFrontendUrl = getenv('FRONTEND_URL');
    if ($envFrontendUrl && $envFrontendUrl !== 'https://your-frontend-domain.com') {
        $envOrigins = array_map('trim', explode(',', $envFrontendUrl));
        $allowedOrigins = array_merge($allowedOrigins, $envOrigins);
    }
    
    // Remove duplicates and empty values
    $allowedOrigins = array_unique(array_filter($allowedOrigins));
    
    // Get the origin from the request
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    // Determine which origin to allow
    $frontendUrl = null;
    if (!empty($origin)) {
        // Check if request origin is in allowed list
        if (in_array($origin, $allowedOrigins)) {
            $frontendUrl = $origin;
        } else {
            // Check if it's a Vercel domain (for preview deployments)
            if (preg_match('/^https:\/\/.*\.vercel\.app$/', $origin)) {
                $frontendUrl = $origin; // Allow any Vercel deployment
            }
        }
    }
    
    // Fallback: use first allowed origin or default
    if (!$frontendUrl) {
        $frontendUrl = !empty($allowedOrigins) ? reset($allowedOrigins) : 'http://localhost:3000';
    }
    
    // Set CORS headers
    header('Access-Control-Allow-Origin: ' . $frontendUrl);
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Requested-With, Accept');
    header('Access-Control-Allow-Credentials: true');
}

// Set CORS headers immediately
setCorsHeaders();

// Handle preflight OPTIONS requests early
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Set JSON content type immediately to prevent HTML responses
// This must be done before any output to prevent bot protection from injecting HTML
if (!headers_sent()) {
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Requested-With: XMLHttpRequest');
    header('Accept: application/json');
    // Set a user agent to help bypass bot protection
    if (!isset($_SERVER['HTTP_USER_AGENT']) || empty($_SERVER['HTTP_USER_AGENT'])) {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
    }
}

// Start session for authentication
session_start();

// Set the base path
define('BASE_PATH', dirname(__DIR__));

// Load environment variables
require_once BASE_PATH . '/app/Config/env.php';

// Configure error reporting based on DEBUG_MODE
// Temporarily enable errors for debugging - set DEBUG_MODE=false in production
$debugMode = defined('DEBUG_MODE') ? DEBUG_MODE : (getenv('DEBUG_MODE') === 'true' || getenv('DEBUG_MODE') === '1');

// Always log errors, but never display them as HTML (always return JSON)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Never display errors as HTML
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/logs/php_errors.log');

// Custom error handler to convert PHP errors to JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) use ($debugMode) {
    // Log the error
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    
    // If output buffering is active, clear any output
    if (ob_get_level() > 0) {
        ob_clean();
    }
    
    // Set CORS headers and JSON content type
    if (!headers_sent()) {
        setCorsHeaders();
        header('Content-Type: application/json; charset=UTF-8');
    }
    
    // Return JSON error response
    http_response_code(500);
    $response = [
        'success' => false,
        'error' => 'Internal server error',
        'message' => $debugMode ? $errstr : 'An error occurred processing your request'
    ];
    
    if ($debugMode) {
        $response['debug'] = [
            'file' => $errfile,
            'line' => $errline,
            'type' => $errno
        ];
    }
    
    echo json_encode($response);
    exit;
}, E_ALL | E_STRICT);

// Custom exception handler
set_exception_handler(function($exception) use ($debugMode) {
    // Log the exception
    error_log("Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    error_log("Stack trace: " . $exception->getTraceAsString());
    
    // If output buffering is active, clear any output
    if (ob_get_level() > 0) {
        ob_clean();
    }
    
    // Set CORS headers and JSON content type
    if (!headers_sent()) {
        setCorsHeaders();
        header('Content-Type: application/json; charset=UTF-8');
    }
    
    // Return JSON error response
    http_response_code(500);
    $response = [
        'success' => false,
        'error' => 'Internal server error',
        'message' => $debugMode ? $exception->getMessage() : 'An error occurred processing your request'
    ];
    
    if ($debugMode) {
        $response['debug'] = [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];
    }
    
    echo json_encode($response);
    exit;
});

// Shutdown function to catch any final output and ensure JSON
register_shutdown_function(function() use ($debugMode) {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        // Fatal error occurred
        if (ob_get_level() > 0) {
            $output = ob_get_contents();
            ob_clean();
            
            // Check if output contains HTML/error messages
            if (!empty($output) && (strpos($output, '<') !== false || strpos($output, '<br') !== false)) {
                // Clear it and send JSON instead
                if (!headers_sent()) {
                    setCorsHeaders();
                    header('Content-Type: application/json; charset=UTF-8');
                }
                http_response_code(500);
                $response = [
                    'success' => false,
                    'error' => 'Internal server error',
                    'message' => $debugMode ? $error['message'] : 'An error occurred processing your request'
                ];
                if ($debugMode) {
                    $response['debug'] = [
                        'file' => $error['file'],
                        'line' => $error['line'],
                        'type' => $error['type']
                    ];
                }
                echo json_encode($response);
                exit;
            }
        }
    }
});

// Load configuration
require_once BASE_PATH . '/app/Config/database.php';

// Load helpers
require_once BASE_PATH . '/app/Helpers/Router.php';
require_once BASE_PATH . '/app/Helpers/Log.php';
require_once BASE_PATH . '/app/Helpers/Response.php';
require_once BASE_PATH . '/app/Helpers/Validator.php';
require_once BASE_PATH . '/app/Helpers/Encryption.php';

// Load services
require_once BASE_PATH . '/app/Services/JwtService.php';
require_once BASE_PATH . '/app/Services/AuthService.php';
require_once BASE_PATH . '/app/Services/PatientService.php';
require_once BASE_PATH . '/app/Services/AppointmentService.php';
require_once BASE_PATH . '/app/Services/PrescriptionService.php';
require_once BASE_PATH . '/app/Services/BillingService.php';
require_once BASE_PATH . '/app/Services/DashboardService.php';
require_once BASE_PATH . '/app/Services/StaffService.php';
require_once BASE_PATH . '/app/Services/CommunicationService.php';
require_once BASE_PATH . '/app/Services/InventoryService.php';
require_once BASE_PATH . '/app/Services/NotificationService.php';
require_once BASE_PATH . '/app/Services/CalendarService.php';

// Load repositories
require_once BASE_PATH . '/app/Repositories/UserRepository.php';
require_once BASE_PATH . '/app/Repositories/PatientRepository.php';
require_once BASE_PATH . '/app/Repositories/AppointmentRepository.php';
require_once BASE_PATH . '/app/Repositories/PrescriptionRepository.php';
require_once BASE_PATH . '/app/Repositories/BillingRepository.php';
require_once BASE_PATH . '/app/Repositories/DashboardRepository.php';
require_once BASE_PATH . '/app/Repositories/StaffRepository.php';
require_once BASE_PATH . '/app/Repositories/CommunicationRepository.php';
require_once BASE_PATH . '/app/Repositories/InventoryRepository.php';
require_once BASE_PATH . '/app/Repositories/NotificationRepository.php';
require_once BASE_PATH . '/app/Repositories/CalendarRepository.php';

// Load middleware
require_once BASE_PATH . '/app/Middleware/AuthMiddleware.php';
require_once BASE_PATH . '/app/Middleware/RoleMiddleware.php';
require_once BASE_PATH . '/app/Middleware/EncryptionMiddleware.php';
require_once BASE_PATH . '/app/Middleware/CsrfMiddleware.php';

// Load controllers
require_once BASE_PATH . '/app/Controllers/AuthController.php';
require_once BASE_PATH . '/app/Controllers/PatientController.php';
require_once BASE_PATH . '/app/Controllers/AppointmentController.php';
require_once BASE_PATH . '/app/Controllers/PrescriptionController.php';
require_once BASE_PATH . '/app/Controllers/BillingController.php';
require_once BASE_PATH . '/app/Controllers/DashboardController.php';
require_once BASE_PATH . '/app/Controllers/StaffController.php';
require_once BASE_PATH . '/app/Controllers/CommunicationController.php';
require_once BASE_PATH . '/app/Controllers/InventoryController.php';
require_once BASE_PATH . '/app/Controllers/NotificationController.php';
require_once BASE_PATH . '/app/Controllers/CalendarController.php';

// Load routes
require_once BASE_PATH . '/app/Routes/api.php';

// CORS headers are already set at the beginning of the file
// OPTIONS requests are also handled early

// Get request URI and method
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove base path if needed (for subdirectory installations)
// Remove /Healthcare/backup-final/backend/public portion
$scriptName = dirname(dirname($_SERVER['SCRIPT_NAME'])); // Gets /Healthcare/backup-final/backend
$publicPath = dirname($_SERVER['SCRIPT_NAME']); // Gets /Healthcare/backup-final/backend/public

// Try to remove the public path first
if (strpos($requestUri, $publicPath) === 0) {
    $requestUri = substr($requestUri, strlen($publicPath));
}

// Ensure URI starts with /
if (empty($requestUri) || $requestUri[0] !== '/') {
    $requestUri = '/' . $requestUri;
}

// Log the incoming request
Log::info("Incoming request: $requestMethod $requestUri");

// Clear any accidental output that might have been generated
if (ob_get_level() > 0) {
    $output = ob_get_contents();
    if (!empty($output) && !empty(trim($output))) {
        // Log any unexpected output
        error_log("Unexpected output detected before routing: " . substr($output, 0, 200));
        ob_clean();
    }
}

try {
    // Initialize database connection
    $db = getDbConnection();
    
    // Handle encrypted input for non-GET requests
    if (in_array($requestMethod, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
        EncryptionMiddleware::handleInput();
    }
    
    // Route the request
    Route::dispatch($requestUri, $requestMethod);
    
} catch (Exception $e) {
    Log::error('Fatal error in index.php: ' . $e->getMessage());
    Log::error('Stack trace: ' . $e->getTraceAsString());
    
    Response::error('Internal server error', 500, [
        'error' => $e->getMessage(),
        'trace' => DEBUG_MODE ? $e->getTraceAsString() : null
    ]);
}
