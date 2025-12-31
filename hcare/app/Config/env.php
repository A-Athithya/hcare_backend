<?php
/**
 * Environment Configuration Loader
 * 
 * Loads environment variables from .env file and makes them available via getenv()
 */

// Load .env file (if it exists)
// On platforms like Render.com, Heroku, etc., environment variables are set directly
// so the .env file may not exist, which is fine
$envFile = BASE_PATH . '/.env';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                $value = $matches[2];
            }
            
            // Only set if not already set in environment (env vars take precedence)
            if (!getenv($key) && !isset($_ENV[$key])) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
} else {
    // .env file doesn't exist - this is OK for production platforms
    // Environment variables should be set directly (e.g., via Render.com dashboard)
    // Only log if logs directory exists to avoid errors
    $logFile = BASE_PATH . '/logs/php_errors.log';
    if (is_dir(dirname($logFile)) || @file_put_contents($logFile, '', FILE_APPEND) !== false) {
        @error_log("Note: .env file not found. Using environment variables directly.");
    }
}

// Define debug mode constant
define('DEBUG_MODE', getenv('DEBUG_MODE') === 'true' || getenv('DEBUG_MODE') === '1');

// Define frontend URL constant
define('FRONTEND_URL', getenv('FRONTEND_URL') ?: 'http://localhost:3000');