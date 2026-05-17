<?php
// =====================================================
// MEDICORE AI — DATABASE CONFIGURATION
// File: config/database.php
// XAMPP MySQL Connection
// =====================================================

define('DB_HOST',    'localhost');
define('DB_USER',    'root');       // XAMPP default username
define('DB_PASS',    '');           // XAMPP default password (empty)
define('DB_NAME',    'medicore_db');
define('DB_PORT',    3306);
define('DB_CHARSET', 'utf8mb4');

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        if ($this->conn->connect_error) {
            http_response_code(500);
            die(json_encode([
                'success' => false,
                'error'   => 'DB connection failed: ' . $this->conn->connect_error
            ]));
        }
        $this->conn->set_charset(DB_CHARSET);
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConn() {
        return $this->conn;
    }

    // Execute a prepared statement and return all rows
    public function fetchAll($sql, $types = '', $params = []) {
        if (empty($params)) {
            $result = $this->conn->query($sql);
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        }
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) throw new Exception("Prepare error: " . $this->conn->error);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    // Execute and return single row
    public function fetchOne($sql, $types = '', $params = []) {
        $rows = $this->fetchAll($sql, $types, $params);
        return $rows[0] ?? null;
    }

    // Insert row and return new ID
    public function insert($sql, $types, $params) {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) throw new Exception("Prepare error: " . $this->conn->error);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    // Execute UPDATE/DELETE
    public function execute($sql, $types, $params) {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) throw new Exception("Prepare error: " . $this->conn->error);
        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected;
    }
}