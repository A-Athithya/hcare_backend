<?php
/**
 * Healthcare Management System - Main Entry Point
 * 
 * This file serves as the entry point for all API requests.
 * It handles CORS, loads the environment configuration, initializes services,
 * and routes requests to the appropriate controllers.
 */

// Start output buffering
ob_start();

// Set CORS headers FIRST - before anything else
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = [
    'http://localhost:3000',
    'http://localhost:3001',
    'https://hcarefrontend-theta.vercel.app'
    "https://hcarefrontend-45sitjc9h-athithyas-projects-37de8dcf.vercel.app",
    "https://hcarefrontend-7t0ah3e14-athithyas-projects-37de8dcf.vercel.app"
];

// Get additional allowed origins from environment variable
$envFrontendUrl = getenv('FRONTEND_URL');
if ($envFrontendUrl && $envFrontendUrl !== 'https://your-frontend-domain.com') {
    $envOrigins = array_map('trim', explode(',', $envFrontendUrl));
    $allowedOrigins = array_merge($allowedOrigins, $envOrigins);
}

$allowedOrigins = array_unique(array_filter($allowedOrigins));

// Determine which origin to allow
$frontendUrl = null;
if (!empty($origin)) {
    if (in_array($origin, $allowedOrigins)) {
        $frontendUrl = $origin;
    } elseif (preg_match('/^https:\/\/.*\.vercel\.app$/', $origin)) {
        $frontendUrl = $origin; // Allow any Vercel deployment
    }
}

if (!$frontendUrl) {
    $frontendUrl = !empty($allowedOrigins) ? reset($allowedOrigins) : 'http://localhost:3000';
}

// Set CORS headers immediately
header('Access-Control-Allow-Origin: ' . $frontendUrl);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Requested-With, Accept');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight requests early
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configure error reporting - NEVER display errors as HTML
error_reporting(E_ALL);
ini_set('display_errors', 0); // Critical: Never display errors as HTML
ini_set('log_errors', 1);

// Start session for authentication
try {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
} catch (Exception $e) {
    // Continue without session if it fails
}

// Set the base path
define('BASE_PATH', dirname(__DIR__));

// Load environment variables
require_once BASE_PATH . '/app/Config/env.php';

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
