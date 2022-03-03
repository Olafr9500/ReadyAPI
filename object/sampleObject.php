<?php

namespace ReadyAPI;

class SampleObject extends ObjectMySql
{
    public $variable;

    public function __construct($db)
    {
        // TODO Update Table informations
        parent::__construct($db, "<TableName>", ["id", "variable"], ["id", "variable"]);
    }
}
