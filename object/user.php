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
        $positionUser = intval(array_search("mail", $this->_fieldsRename));
        $positionPass = intval(array_search("password", $this->_fieldsRename));
        $query = "SELECT `".$this->_fields[0]."` FROM `".$this->_tableName."` WHERE `".$this->_fields[$positionUser]."` = ? AND `".$this->_fields[$positionPass]."` = ?";
        $command = $this->_conn->prepare($query);
        if ($command->execute(array($this->mail, $this->password))) {
            if ($result = $command->fetch()) {
                $this->id = $result[0];
                return $this->read();
            } else {
                $this->errorMessage = $this->_fieldsRename[$positionUser]." or ".$this->_fieldsRename[$positionPass]." not correct";
            }
        } else {
            $this->errorMessage = $command->errorInfo();
        }
        return false;
    }
}
