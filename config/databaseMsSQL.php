<?php

namespace ReadyAPI;

class DatabaseMsSQL extends Database
{
    public function __construct()
    {
        parent::__construct("tumbler", "recup_stuart", "plv", "plv");
        $this->conn = sqlsrv_connect($this->host, array("UID" => $this->userName, "PWD" => $this->password, "Database" => $this->dbName, "CharacterSet" => "UTF-8"));
        if ($this->conn === false) {
            $this->errorMessage = sqlsrv_errors();
            $this->conn = null;
        }
    }
}
