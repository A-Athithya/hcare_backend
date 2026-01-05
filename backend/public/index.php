<?php
/**
 * Healthcare Management System - Main Entry Point
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/app/Config/env.php';
require_once BASE_PATH . '/app/Config/database.php';

// Helpers
require_once BASE_PATH . '/app/Helpers/Router.php';
require_once BASE_PATH . '/app/Helpers/Log.php';
require_once BASE_PATH . '/app/Helpers/Response.php';
require_once BASE_PATH . '/app/Helpers/Validator.php';
require_once BASE_PATH . '/app/Helpers/Encryption.php';

// Services
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

// Repositories
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

// Middleware
require_once BASE_PATH . '/app/Middleware/AuthMiddleware.php';
require_once BASE_PATH . '/app/Middleware/RoleMiddleware.php';
require_once BASE_PATH . '/app/Middleware/EncryptionMiddleware.php';
require_once BASE_PATH . '/app/Middleware/CsrfMiddleware.php';

// Controllers
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

// Routes
require_once BASE_PATH . '/app/Routes/api.php';


// ===== ✅ CORS FIX (LIVE URLs) =====
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

$allowedOrigins = [
    'http://localhost:3000',
    'https://hcarefrontend-theta.vercel.app'
];

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
// ===== END CORS =====


// Routing
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

$publicPath = dirname($_SERVER['SCRIPT_NAME']);
if (strpos($requestUri, $publicPath) === 0) {
    $requestUri = substr($requestUri, strlen($publicPath));
}

if (empty($requestUri) || $requestUri[0] !== '/') {
    $requestUri = '/' . $requestUri;
}

Log::info("Incoming request: $requestMethod $requestUri");

try {
    $db = getDbConnection();

    if (in_array($requestMethod, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
        EncryptionMiddleware::handleInput();
    }

    Route::dispatch($requestUri, $requestMethod);

} catch (Exception $e) {
    Log::error('Fatal error: ' . $e->getMessage());
    Response::error('Internal server error', 500);
}
