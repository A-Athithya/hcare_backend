<?php

class JwtService {
    private $secret;
    private $expiry; // in seconds

    public function __construct() {
        // Load from config
        $config = require __DIR__ . '/../Config/config.php';
        $this->secret = $config['security']['jwt_secret'];
        $this->expiry = $config['security']['jwt_expiry'];
    }

    public function generateAccessToken($userId, $role, $tenantId) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'sub' => $userId,
            'role' => $role,
            'tenant_id' => $tenantId,
            'iat' => time(),
            'exp' => time() + $this->expiry
        ]);

        $base64Header = $this->base64UrlEncode($header);
        $base64Payload = $this->base64UrlEncode($payload);

        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $this->secret, true);
        $base64Signature = $this->base64UrlEncode($signature);

        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }

    public function validateToken($token) {
        require_once __DIR__ . '/../Helpers/Log.php';
        
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            Log::error("JwtService: Token format invalid (parts count)", ['count' => count($parts)]);
            return false;
        }

        list($header, $payload, $signature) = $parts;

        $validSignature = hash_hmac('sha256', $header . "." . $payload, $this->secret, true);
        $base64ValidSignature = $this->base64UrlEncode($validSignature);

        if (!hash_equals($base64ValidSignature, $signature)) {
            Log::error("JwtService: Signature Mismatch", [
                'provided' => $signature, 
                'calculated' => $base64ValidSignature,
                'secret_preview' => substr($this->secret, 0, 5) . '...' // Security risk but needed for debug
            ]);
            return false;
        }

        $decodedPayload = json_decode($this->base64UrlDecode($payload), true);
        if ($decodedPayload['exp'] < time()) {
            Log::error("JwtService: Token Expired", ['exp' => $decodedPayload['exp'], 'now' => time()]);
            return false;
        }

        return $decodedPayload;
    }

    private function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private function base64UrlDecode($data) {
        $urlUnsafeData = str_replace(['-', '_'], ['+', '/'], $data);
        $remainder = strlen($urlUnsafeData) % 4;
        if ($remainder) {
            $urlUnsafeData .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode($urlUnsafeData);
    }
}
