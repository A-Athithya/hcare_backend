<?php
require_once __DIR__ . '/BaseRepository.php';

class UserRepository extends BaseRepository {
    public function __construct() {
        parent::__construct();
    }

    public function create($data) {
        $sql = "INSERT INTO users (name, email, password, role, tenant_id) VALUES (:name, :email, :password, :role, :tenant_id)";
        $stmt = $this->db->prepare($sql);
        
        // Hash password
        $password = password_hash($data['password'], PASSWORD_BCRYPT);
        
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':role', $data['role']); 
        $stmt->bindParam(':tenant_id', $data['tenant_id']);
        
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug
        // Debug removed
        
        return $user;
    }

    public function findById($id) {
        $sql = "SELECT id, name, email, role, tenant_id, password, created_at FROM users WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updatePassword($id, $hashedPassword) {
        $sql = "UPDATE users SET password = :password WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function findAll($tenantId = null) {
        $sql = "SELECT id, name, email, role, tenant_id, created_at FROM users";
        if ($tenantId) {
            $sql .= " WHERE tenant_id = :tenant_id";
        }
        $stmt = $this->db->prepare($sql);
        if ($tenantId) {
            $stmt->bindParam(':tenant_id', $tenantId);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        if (isset($data['name'])) {
            $fields[] = "name = :name";
            $params[':name'] = $data['name'];
        }
        if (isset($data['email'])) {
            $fields[] = "email = :email";
            $params[':email'] = $data['email'];
        }
        if (isset($data['role'])) {
            $fields[] = "role = :role";
            $params[':role'] = $data['role'];
        }
        if (isset($data['password'])) {
            $fields[] = "password = :password";
            $params[':password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (empty($fields)) return false;

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id) {
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
