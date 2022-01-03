<?php

namespace ReadyAPI;

use PDO;
use PDOException;

class DatabaseMySQL extends Database
{
    public function __construct()
    {
        parent::__construct("tumbler", "recup_stuart", "plv", "plv");
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->dbName, $this->userName, $this->password);
        } catch (PDOException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }
}
