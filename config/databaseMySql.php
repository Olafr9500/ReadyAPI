<?php

namespace ReadyAPI;

use PDO;
use PDOException;

class DatabaseMySql extends Database
{

    public function __construct($host, $dbName, $userName, $password)
    {
        parent::__construct($host, $dbName, $userName, $password);
        $this->connect();
    }

    protected function connect()
    {
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->dbName, $this->userName, $this->password);
        } catch (PDOException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }
}
