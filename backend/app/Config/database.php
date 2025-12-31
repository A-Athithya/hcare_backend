<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        $this->host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'localhost');
        $this->db_name = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'hcare_db');
        $this->username = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root');
        $this->password = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? '');
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            // Don't echo, it breaks JSON
        }
        return $this->conn;
    }
}

/**
 * Helper function to get database connection
 */
function getDbConnection() {
    static $connection = null;
    
    if ($connection === null) {
        try {
            $host = getenv('DB_HOST') ?: 'localhost';
            $dbname = getenv('DB_NAME') ?: 'hcare_db';
            $username = getenv('DB_USER') ?: 'root';
            $password = getenv('DB_PASS') ?: '';
            
            $connection = new mysqli($host, $username, $password, $dbname);
            
            if ($connection->connect_error) {
                throw new Exception('Connection failed: ' . $connection->connect_error);
            }
            
            $connection->set_charset('utf8mb4');
            
        } catch (Exception $e) {
            Log::error('Database connection error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    return $connection;
}
