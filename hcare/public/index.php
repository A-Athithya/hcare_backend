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
    
    // Ensure JSON headers are set
    if (!headers_sent()) {
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
    
    // Ensure JSON headers are set
    if (!headers_sent()) {
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
        if (ob_get_level()
