<?php
require_once 'BaseRepository.php';

class InventoryRepository extends BaseRepository {

    protected $table = 'medicines';

    public function getAll($tenantId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE (tenant_id = :tenant_id OR tenant_id IS NULL) ORDER BY id DESC");
        $stmt->execute([':tenant_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $sql = "INSERT INTO {$this->table} 
            (medicine_name, category, price, stock, expiry_date, description, tenant_id) 
            VALUES 
            (:medicine_name, :category, :price, :stock, :expiry_date, :description, :tenant_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':medicine_name' => $data['medicine_name'] ?? '',
            ':category' => $data['category'] ?? null,
            ':price' => $data['price'] ?? 0,
            ':stock' => $data['stock'] ?? 0,
            ':expiry_date' => $data['expiry_date'] ?? null,
            ':description' => $data['description'] ?? null,
            ':tenant_id' => $data['tenant_id']
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                    medicine_name = :medicine_name,
                    category = :category,
                    price = :price,
                    stock = :stock,
                    expiry_date = :expiry_date,
                    description = :description
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':medicine_name' => $data['medicine_name'] ?? '',
            ':category' => $data['category'] ?? null,
            ':price' => $data['price'] ?? 0,
            ':stock' => $data['stock'] ?? 0,
            ':expiry_date' => $data['expiry_date'] ?? null,
            ':description' => $data['description'] ?? null,
            ':id' => $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
