<?php
require_once 'BaseRepository.php';

class TokenRepository extends BaseRepository {
    public function store($userId, $token, $expiresAt) {
        $sql = "INSERT INTO refresh_tokens (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)";
        $stmt = $this->db->prepare($sql);
        
        $tokenHash = hash('sha256', $token);
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':token_hash', $tokenHash);
        $stmt->bindParam(':expires_at', $expiresAt);
        
        return $stmt->execute();
    }
    
    public function isValid($token) {
        $tokenHash = hash('sha256', $token);
        $sql = "SELECT * FROM refresh_tokens WHERE token_hash = :token_hash AND expires_at > NOW() AND revoked = 0 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':token_hash', $tokenHash);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function revoke($token) {
        $tokenHash = hash('sha256', $token);
        $sql = "UPDATE refresh_tokens SET revoked = 1 WHERE token_hash = :token_hash";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':token_hash', $tokenHash);
        return $stmt->execute();
    }
}
