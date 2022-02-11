<?php

namespace ReadyAPI;

class DatabaseMsSQL extends Database
{

    public function __construct()
    {
        // TODO Update Database informations
        parent::__construct("<Name_Server>", "<Name_DataBase>", "<Username>", "<Password>");
        $this->conn = sqlsrv_connect($this->host, array("UID" => $this->userName, "PWD" => $this->password, "Database" => $this->dbName, "CharacterSet" => "UTF-8"));
        if ($this->conn === false) {
            $this->errorMessage = sqlsrv_errors();
            $this->conn = null;
        }
    }
}
