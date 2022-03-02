<?php

namespace ReadyAPI;

use PDO;
use PDOException;

class Database
{
    public static $TypeConnector = ['mysql', 'mariadb', 'mssql'];

    protected $host;
    protected $dbName;
    protected $userName;
    protected $password;

    public $conn;
    public $errorMessage;

    public function __construct($type, $host, $dbName, $userName, $password)
    {
        $this->conn = null;
        $this->errorMessage = null;
        $this->host = $host;
        $this->dbName = $dbName;
        $this->userName = $userName;
        $this->password = $password;
        switch ($type) {
            case 'mysql':
            case 'mariadb':
                $this->connectMySql();
                break;
            case 'mssql':
                $this->connectMsSql();
                break;
            default:
                throw "Connection type not set";
                break;
        }
    }

    private function connectMySql()
    {
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->dbName, $this->userName, $this->password);
        } catch (PDOException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    private function connectMsSql()
    {
        $this->conn = sqlsrv_connect($this->host, array("UID" => $this->userName, "PWD" => $this->password, "Database" => $this->dbName, "CharacterSet" => "UTF-8"));
        if ($this->conn === false) {
            $this->errorMessage = sqlsrv_errors();
            $this->conn = null;
        }
    }

    public static function encodePassword($password)
    {
        return hash("sha256", $password);
    }
}
