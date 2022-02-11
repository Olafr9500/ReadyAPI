<?php

namespace ReadyAPI;

class SampleObject extends ObjectMsSql
{
    public $nom;
    public $directory;
    public $update;

    public function __construct($db)
    {
        parent::__construct($db, "document", ["id", "nom", "directory", "update"], ["id", "nom", "directory", "update"]);
    }
}
