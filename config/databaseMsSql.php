<?php

namespace ReadyAPI;

class DatabaseMsSql extends Database
{

    public function __construct($host, $dbName, $userName, $password)
    {
        parent::__construct($host, $dbName, $userName, $password);
        $this->connect();
    }

    protected function connect()
    {
        $this->conn = sqlsrv_connect($this->host, array("UID" => $this->userName, "PWD" => $this->password, "Database" => $this->dbName, "CharacterSet" => "UTF-8"));
        if ($this->conn === false) {
            $this->errorMessage = sqlsrv_errors();
            $this->conn = null;
        }
    }
}
