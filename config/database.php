<?php

namespace ReadyAPI;

class Database
{
    protected $host;
    protected $dbName;
    protected $userName;
    protected $password;

    public $conn;
    public $errorMessage;

    public function __construct($host, $dbName, $userName, $password)
    {
        $this->conn = null;
        $this->errorMessage = null;
        $this->host = $host;
        $this->dbName = $dbName;
        $this->userName = $userName;
        $this->password = $password;
    }

    public static function encodePassword($password)
    {
        return hash("sha256", $password);
    }
}
