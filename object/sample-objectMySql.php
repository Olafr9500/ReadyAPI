<?php

namespace ReadyAPI;

class SampleObject extends ObjectMySql
{
    public $nom;
    public $directory;
    public $update;

    public function __construct($db)
    {
        parent::__construct($db, "document", ["id", "nom", "directory", "update"]);
    }

    public function isDataCorrect()
    {
        return (
            (strlen($this->nom) < 255) &&
            (file_exists($this->directory)) &&
            (preg_match('/[0-9]{4}-(0[0-9]|1[0-2])-([0-2][0-9]|3[0-1])/m', $this->update))) ? true
            : array(
                "nom" => (strlen($this->nom) < 255 ? 'true' : 'false'),
                "directory" => (file_exists($this->directory) ? 'true' : 'false'),
                "update" => (preg_match('/[0-9]{4}-(0[0-9]|1[0-2])-([0-2][0-9]|3[0-1])/m', $this->update) ? 'true' : 'false')
            );
    }
}
