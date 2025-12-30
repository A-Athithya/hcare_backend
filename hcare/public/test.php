<?php
/**
 * Diagnostic Test Script
 * Use this to identify issues with your deployment
 * Delete this file after fixing issues for security
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>HCare Backend - Diagnostic Test</h1>";

// Test 1: Check PHP Version
echo "<h2>1. PHP Version</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Status: " . (version_compare(phpversion(), '7.4.0', '>=') ? "✅ OK" : "❌ PHP 7.4+ required") . "<br><br>";

// Test 2: Check Required Extensions
echo "<h2>2. Required PHP Extensions</h2>";
$required = ['mysqli', 'pdo', 'pdo_mysql', 'mbstring', 'openssl', 'json'];
foreach ($required as $ext) {
    $loaded = extension_loaded($ext);
    echo "$ext: " . ($loaded ? "✅ Loaded" : "❌ Not loaded") . "<br>";
}
echo "<br>";

// Test 3: Check File Structure
echo "<h2>3. File Structure</h2>";
$basePath = dirname(__DIR__);
echo "Base Path: $basePath<br>";
echo "Base Path Exists: " . (is_dir($basePath) ? "✅ Yes" : "❌ No") . "<br>";

$requiredDirs = ['app', 'app/Config', 'app/Controllers', 'logs', 'public'];
foreach ($requiredDirs as $dir) {
    $path = $basePath . '/' . $dir;
    echo "$dir: " . (is_dir($path) ? "✅ Exists" : "❌ Missing") . "<br>";
}
echo "<br>";

// Test 4: Check .env File
echo "<h2>4. Environment Configuration</h2>";
$envFile = $basePath . '/.env';
if (file_exists($envFile)) {
    echo ".env file: ✅ Exists<br>";
    echo ".env readable: " . (is_readable($envFile) ? "✅ Yes" : "❌ No") . "<br>";
    
    // Try to load it
    define('BASE_PATH', $basePath);
    try {
        require_once $basePath . '/app/Config/env.php';
        echo ".env loaded: ✅ Success<br>";
        echo "DEBUG_MODE: " . (defined('DEBUG_MODE') ? (DEBUG_MODE ? 'true' : 'false') : 'not defined') . "<br>";
    } catch (Exception $e) {
        echo ".env loaded: ❌ Error: " . $e->getMessage() . "<br>";
    }
} else {
    echo ".env file: ❌ Not found at $envFile<br>";
}
echo "<br>";

// Test 5: Check Database Configuration
echo "<h2>5. Database Configuration</h2>";
if (defined('BASE_PATH')) {
    try {
        require_once BASE_PATH . '/app/Config/database.php';
        echo "Database config loaded: ✅ Success<br>";
        
        // Try to connect
        $host = getenv('DB_HOST') ?: 'localhost';
        $dbname = getenv('DB_NAME') ?: 'hcare_db';
        $username = getenv('DB_USER') ?: 'root';
        $password = getenv('DB_PASS') ?: '';
        
        echo "DB_HOST: " . ($host ?: '❌ Not set') . "<br>";
        echo "DB_NAME: " . ($dbname ?: '❌ Not set') . "<br>";
        echo "DB_USER: " . ($username ?: '❌ Not set') . "<br>";
        echo "DB_PASS: " . ($password ? '✅ Set' : '❌ Not set') . "<br>";
        
        // Try connection
        try {
            $conn = new mysqli($host, $username, $password, $dbname);
            if ($conn->connect_error) {
                echo "Database connection: ❌ Failed - " . $conn->connect_error . "<br>";
            } else {
                echo "Database connection: ✅ Success<br>";
                $conn->close();
            }
        } catch (Exception $e) {
            echo "Database connection: ❌ Error - " . $e->getMessage() . "<br>";
        }
    } catch (Exception $e) {
        echo "Database config: ❌ Error - " . $e->getMessage() . "<br>";
    }
} else {
    echo "BASE_PATH not defined. Cannot test database config.<br>";
}
echo "<br>";

// Test 6: Check File Permissions
echo "<h2>6. File Permissions</h2>";
$checkPaths = [
    $basePath,
    $basePath . '/logs',
    $basePath . '/app',
    $envFile
];
foreach ($checkPaths as $path) {
    if (file_exists($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $readable = is_readable($path);
        $writable = is_writable($path);
        echo basename($path) . ": Permissions: $perms, Readable: " . ($readable ? "✅" : "❌") . ", Writable: " . ($writable ? "✅" : "❌") . "<br>";
    }
}
echo "<br>";

// Test 7: Try Loading index.php Components
echo "<h2>7. Loading Application Components</h2>";
if (defined('BASE_PATH')) {
    $components = [
        'app/Helpers/Router.php',
        'app/Helpers/Log.php',
        'app/Helpers/Response.php',
    ];
    
    foreach ($components as $component) {
        $path = BASE_PATH . '/' . $component;
        if (file_exists($path)) {
            try {
                require_once $path;
                echo "$component: ✅ Loaded<br>";
            } catch (Exception $e) {
                echo "$component: ❌ Error - " . $e->getMessage() . "<br>";
            }
        } else {
            echo "$component: ❌ File not found<br>";
        }
    }
}
echo "<br>";

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>Fix any ❌ errors shown above</li>";
echo "<li>Ensure .env file exists and has correct values</li>";
echo "<li>Check database credentials</li>";
echo "<li>Verify file permissions (folders: 755, files: 644, logs: 777)</li>";
echo "<li>Delete this test.php file after fixing issues</li>";
echo "</ul>";

