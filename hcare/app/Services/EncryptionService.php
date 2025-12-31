<?php

class EncryptionService {
    private $key;

    public function __construct() {
        if (!extension_loaded('openssl')) {
            throw new Exception("The openssl extension is required for encryption/decryption. Please enable it in your php.ini.");
        }
        // $config = require __DIR__ . '/../Config/config.php';
        // $this->key = $config['security']['aes_key'];
        
        // Ensure EnvLoader has been called in index.php before this service is instantiated
        // Direct getenv check to ensure it's loaded
        $key = getenv('AES_KEY');
        if (!$key && isset($_ENV['AES_KEY'])) $key = $_ENV['AES_KEY'];
        if (!$key && isset($_SERVER['AES_KEY'])) $key = $_SERVER['AES_KEY'];

        if (empty($key)) {
            // Fallback to loading via config if not in getenv (CLI usage maybe?)
            $config = require __DIR__ . '/../Config/config.php';
            $key = $config['security']['aes_key'];
        }

        if (empty($key)) {
             error_log("CRITICAL: AES_KEY missing in environment variables!");
             error_log("Available env vars: " . implode(', ', array_keys($_ENV)));
             throw new Exception("Server Configuration Error: Encryption Key Missing. Please set AES_KEY environment variable.");
        }
        
        $this->key = $key;
    }

    public function encrypt($data) {
        if (is_array($data) || is_object($data)) {
            $data = json_encode($data);
        }

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        // Use OPENSSL_RAW_DATA (1) to get binary ciphertext
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $this->key, OPENSSL_RAW_DATA, $iv);
        
        if ($encrypted === false) {
            $msg = openssl_error_string();
            error_log("CRITICAL: openssl_encrypt failed: " . $msg);
            throw new Exception("Encryption failed: " . $msg);
        }

        // Return IV + Encrypted Data (Base64 encoded)
        return base64_encode($iv . $encrypted);
    }

    public function decrypt($data) {
        $data = base64_decode($data);
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);

        if (strlen($iv) !== $ivLength) {
            return null; // Invalid IV
        }

        // Use OPENSSL_RAW_DATA (1) because $encrypted is binary
        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $this->key, OPENSSL_RAW_DATA, $iv);
        
        // Try to decode JSON
        $json = json_decode($decrypted, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $json : $decrypted;
    }
}