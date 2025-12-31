<?php
/**
 * Environment Configuration Loader
 * 
 * Loads environment variables from .env file and makes them available via getenv()
 */

// Load .env file (if it exists)
// On platforms like Render.com, environment variables are set directly
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
}

// Define debug mode constant
define('DEBUG_MODE', getenv('DEBUG_MODE') === 'true' || getenv('DEBUG_MODE') === '1');

// Define frontend URL constant
define('FRONTEND_URL', getenv('FRONTEND_URL') ?: 'http://localhost:3000');