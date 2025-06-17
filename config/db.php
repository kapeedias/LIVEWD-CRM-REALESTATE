<?php
class Database {
    private $host = 'localhost';
    private $db   = 'gobchi_livewd_2k19';
    private $user = 'saiadmin';
    private $pass = 'K1{[CDUJG;5h';
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
            if($this){
                echo "Connected Successfully to database";
            }
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
