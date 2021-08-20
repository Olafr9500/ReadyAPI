<?php
namespace ReadyAPI;

use PDO;
use PDOException;

class Database
{
    private $_host = "<dbHost>";
    private $_dbName = "<dbName>";
    private $_userName = "<user>";
    private $_password = "<password>";

    public $conn;
    public $errorMessage;

    public function __construct()
    {
        $this->conn = null;
        $this->errorMessage = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->_host . ";dbname=" . $this->_dbName, $this->_userName, $this->_password);
        } catch (PDOException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public static function encodePassword($password)
    {
        return hash("sha256", $password);
    }
}
