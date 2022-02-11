<?php

namespace ReadyAPI;

use PDO;
use PDOException;

class DatabaseMySQL extends Database
{
    public function __construct()
    {
        // TODO Update Database informations
        parent::__construct("<Name_Server>", "<Name_DataBase>", "<Username>", "<Password>");
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->dbName, $this->userName, $this->password);
        } catch (PDOException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }
}
