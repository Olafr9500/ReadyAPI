<?php

namespace ReadyAPI;

class DatabaseSample extends DatabaseMySql
{

    public function __construct()
    {
        // TODO Update Database informations
        parent::__construct("<NameServer>", "<NameDataBase>", "<Username>", "<Password>");
    }
}
