<?php

class Database
{
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $dbname = "online_voting_db";

    protected $conn = null;

    public function connect()
    {
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            $this->conn = new PDO(
                "mysql:host=$this->host;dbname=$this->dbname;charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_PERSISTENT => false,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            return $this->conn;
        } catch (PDOException $e) {
            error_log("Connection failed: " . $e->getMessage());
            die("Database connection failed. Please contact administrator.");
        }
    }
}
