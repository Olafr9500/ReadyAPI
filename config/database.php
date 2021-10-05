<?php

namespace ReadyAPI;

use PDO;
use PDOException;

class Database
{
    protected $host = "<dbHost>";
    protected $dbName = "<dbName>";
    protected $userName = "<user>";
    protected $password = "<password>";

    public $conn;
    public $errorMessage;

    public function __construct()
    {
        $this->conn = null;
        $this->errorMessage = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->dbName, $this->userName, $this->password);
        } catch (PDOException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public static function encodePassword($password)
    {
        return hash("sha256", $password);
    }
}
