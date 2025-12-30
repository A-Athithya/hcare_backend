<?php
/**
 * Encryption Helper
 * 
 * Provides encryption and decryption methods using AES-256-CBC
 */

class Encryption {
    
    private static $cipher = 'AES-256-CBC';
    
    /**
     * Get encryption key from environment
     */
    private static function getKey() {
        $key = getenv('AES_KEY');
        if (!$key) {
            throw new Exception('AES_KEY not found in environment');
        }
        // Ensure key is 32 bytes for AES-256
        return substr(hash('sha256', $key, true), 0, 32);
    }
    
    /**
     * Encrypt data
     * 
     * @param mixed $data Data to encrypt
     * @return string Encrypted data (base64 encoded)
     */
    public static function encrypt($data) {
        try {
            if (is_array($data) || is_object($data)) {
                $data = json_encode($data);
            }
            
            $key = self::getKey();
            $ivLength = openssl_cipher_iv_length(self::$cipher);
            $iv = openssl_random_pseudo_bytes($ivLength);
            
            $encrypted = openssl_encrypt(
                $data,
                self::$cipher,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );
            
            if ($encrypted === false) {
                throw new Exception('Encryption failed');
            }
            
            // Combine IV and encrypted data, then base64 encode
            return base64_encode($iv . $encrypted);
            
        } catch (Exception $e) {
            Log::error('Encryption error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Decrypt data
     * 
     * @param string $encryptedData Encrypted data (base64 encoded)
     * @return mixed Decrypted data
     */
    public static function decrypt($encryptedData) {
        try {
            if (empty($encryptedData)) {
                return null;
            }
            
            $key = self::getKey();
            $data = base64_decode($encryptedData);
            
            if ($data === false) {
                throw new Exception('Invalid base64 data');
            }
            
            $ivLength = openssl_cipher_iv_length(self::$cipher);
            $iv = substr($data, 0, $ivLength);
            $encrypted = substr($data, $ivLength);
            
            $decrypted = openssl_decrypt(
                $encrypted,
                self::$cipher,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );
            
            if ($decrypted === false) {
                throw new Exception('Decryption failed');
            }
            
            // Try to decode JSON if applicable
            $json = json_decode($decrypted, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
            
            return $decrypted;
            
        } catch (Exception $e) {
            Log::error('Decryption error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Hash password using bcrypt
     * 
     * @param string $password Plain text password
     * @return string Hashed password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }
    
    /**
     * Verify password against hash
     * 
     * @param string $password Plain text password
     * @param string $hash Hashed password
     * @return bool True if password matches
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate random token
     * 
     * @param int $length Token length
     * @return string Random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Generate CSRF token
     * 
     * @return string CSRF token
     */
    public static function generateCsrfToken() {
        $secret = getenv('CSRF_SECRET') ?: 'default_csrf_secret';
        $timestamp = time();
        $random = self::generateToken(16);
        
        $token = hash_hmac('sha256', $timestamp . $random, $secret);
        return base64_encode($timestamp . '|' . $token);
    }
    
    /**
     * Verify CSRF token
     * 
     * @param string $token CSRF token to verify
     * @param int $maxAge Maximum token age in seconds (default 3600)
     * @return bool True if token is valid
     */
    public static function verifyCsrfToken($token, $maxAge = 3600) {
        try {
            $decoded = base64_decode($token);
            if ($decoded === false) {
                return false;
            }
            
            list($timestamp, $hash) = explode('|', $decoded, 2);
            
            // Check if token has expired
            if (time() - $timestamp > $maxAge) {
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            Log::error('CSRF verification error: ' . $e->getMessage());
            return false;
        }
    }
}
