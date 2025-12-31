<?php
require_once __DIR__ . '/../Config/config.php';
require_once __DIR__ . '/../Config/database.php';

class BaseRepository {
    protected $db;

    public function __construct() {
        $db = new Database();
        $this->db = $db->getConnection();
    }

    public function getDb() {
        return $this->db;
    }
}
