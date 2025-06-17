<?php
class Database {
    private $host = 'localhost';
    private $db   = 'gobchi_livewd_2k19';
    private $user = 'saiadmin';
    private $pass = 'F06A!=5PN4e[';
    private $charset = 'utf8mb4';

    private $pdo;
    private static $instance;

    private function __construct() {
        $dsn = "mysql:host=$this->host;dbname=$this->db;charset=$this->charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            // Log error or handle it appropriately
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public static function getInstance(): PDO {
        if (!self::$instance) {
            self::$instance = (new self())->pdo;
        }
        return self::$instance;
    }
}


/* to test the connectivity of the database host uncomment the below code and run it


try {
    $pdo = Database::getInstance();
    echo "<p style='color: green;'>✅ Database connection successful.</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

*/