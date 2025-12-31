<?php

class Database
{
    private string $host;
    private string $db_name;
    private string $username;
    private string $password;
    private int $port;
    private ?PDO $conn = null;

    public function __construct()
    {
        $this->host     = getenv('DB_HOST') ?: '';
        $this->db_name  = getenv('DB_NAME') ?: '';
        $this->username = getenv('DB_USER') ?: '';
        $this->password = getenv('DB_PASS') ?: '';
        $this->port     = (int)(getenv('DB_PORT') ?: 4000);

        // 🔒 Fail fast if env vars are missing
        if (
            empty($this->host) ||
            empty($this->db_name) ||
            empty($this->username)
        ) {
            throw new Exception("Database environment variables not set");
        }
    }

    public function getConnection(): PDO
    {
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4",
                $this->host,
                $this->port,
                $this->db_name
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT => false,

                // ✅ REQUIRED for TiDB Cloud (TLS)
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ];

            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password,
                $options
            );

        } catch (PDOException $e) {
            // Log real error (never echo)
            error_log("TiDB Connection Error: " . $e->getMessage());

            // Throw clean error for API layer
            throw new Exception("Database connection failed");
        }

        return $this->conn;
    }
}

/**
 * Global helper (PDO only)
 */
function getDbConnection(): PDO
{
    static $db = null;

    if ($db === null) {
        $database = new Database();
        $db = $database->getConnection();
    }

    return $db;
}
