<?php

namespace ReadyAPI;

class DatabaseSample extends Database
{

    public function __construct()
    {
        // TODO Update Database informations
        parent::__construct(parent::$TypeConnector['<TypeConnector>'], "<Name_Server>", "<Name_DataBase>", "<Username>", "<Password>");
    }
}
