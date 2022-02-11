<?php

namespace ReadyAPI;

class User extends ObjectMySql
{
    public $mail;
    public $password;

    public function __construct($db)
    {
        parent::__construct($db, "utilisateur", ["id", "mail", "password"]);
    }

    public function connection()
    {
        $positionUser = intval(array_search("mail", $this->getFieldsRename()));
        $positionPass = intval(array_search("password", $this->getFieldsRename()));
        $query = "SELECT `" . $this->table[0]["Field"] . "` FROM `" . $this->tableName . "` WHERE `" . $this->table[$positionUser]["Field"] . "` = ? AND `" . $this->table[$positionPass]["Field"] . "` = ?";
        $command = $this->conn->prepare($query);
        if ($command->execute(array($this->mail, $this->password))) {
            if ($result = $command->fetch()) {
                $this->id = $result[0];
                return $this->read();
            } else {
                $this->errorMessage = $this->getFieldsRename()[$positionUser] . " or " . $this->getFieldsRename()[$positionPass] . " not correct : " . $query;
            }
        } else {
            $this->errorMessage = $command->errorInfo();
        }
        return false;
    }
}
