<?php
namespace ReadyAPI;

use PDO;

include_once 'iconn.php';
/**
 * Class table MySql
 */
class ObjectMySql implements IConn
{
    private $_conn;
    private $_tableName;
    private $_fields = [];
    private $_fieldsRename = [];
    public $id;
    public $errorMessage;
    /**
     * Constructor of the MySql object
     *
     * @param PDO $db Database connector
     * @param string $nameBase Database Name
     * @param array $filedsRename List of object variables in the same order as the database columns
     */
    public function __construct($db, $nameBase, $filedsRename = ["id"])
    {
        $this->_conn = $db;
        $this->_tableName = $nameBase;
        $stmt = $db->prepare("DESCRIBE `". $nameBase ."`");
        $stmt->execute();
        foreach ($stmt->fetchAll() as $row) {
            $this->_fields[] = $row["Field"];
            $valueRow = array_shift($filedsRename);
            $this->_fieldsRename[] = $valueRow;
            $this->_fieldsRename[$row["Field"]] = $valueRow;
        }
    }

    public function __get($property)
    {
        return $this->$property;
    }

    public function __set($property, $value)
    {
        $this->$property=$value;
    }
    /**
     * Add a new entry for the object in the database
     *
     * @param boolean $setId Activate auto increment or not
     * @return boolean Insertion validation status
     */
    public function create($setId = false)
    {
        $query = "insert into `". $this->_tableName."` set ";
        $values = [];
        foreach ($this->_fields as $key => $row) {
            if (($key != 0) || ($setId)) {
                $query .= "`".$row."` = ?, ";
                $values[] = $this->__get($this->_fieldsRename[$row]);
            }
        }
        $stmt = $this->_conn->prepare(substr($query, 0, -2));
        if ($stmt->execute($values)) {
            if (!$setId) {
                $this->id = $this->_conn->lastInsertId();
            }
            return true;
        } else {
            $this->errorMessage = $stmt->errorInfo();
        }
        return false;
    }
    /**
     * Retrieves data of the object in the database
     *
     * @return void Data recovery status
     */
    public function read()
    {
        if (isset($this->id)) {
            $stmt = $this->_conn->prepare("SELECT * from `" . $this->_tableName . "` where `".$this->_fields[0]."` = ?");
            if ($stmt->execute(array($this->id))) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!empty($result)) {
                    foreach ($result as $key => $row) {
                        if ($this->_fieldsRename[$key]) {
                            if (gettype($row) == "string") {
                                $row = utf8_encode($row);
                            }
                            $this->__set($this->_fieldsRename[$key], $row);
                        } else {
                            $this->__set($key, $row);
                        }
                    }
                    return true;
                } else {
                    $this->errorMessage = "Empty result sql";
                }
            } else {
                $this->errorMessage = $stmt->errorInfo();
            }
        } else {
            $this->errorMessage = "No id set";
        }
        return false;
    }
    /**
     * Retrieves all of the object's entries in the database
     *
     * @param integer $orderby
     * @param string $sync
     * @return array|false List of database entries or false
     */
    public function readAll($orderby = 0, $sync = "asc")
    {
        $stmt = $this->_conn->prepare("SELECT * from `" . $this->_tableName . "` ORDER BY `". $this->_fields[$orderby] ."` ".$sync." LIMIT 200");
        if ($stmt->execute()) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($result) > 0) {
                foreach ($result as $key => $row) {
                    $object = new $this($this->_conn, $this->_tableName, $this->_fieldsRename);
                    $object->id = $row[$this->_fields[0]];
                    if ($object->read()) {
                        $result[$key] = $object;
                    }
                }
                return $result;
            }
        } else {
            $this->errorMessage = $stmt->errorInfo();
        }
        return false;
    }

    public function readBy($index, $value, $condition, $orderby = 0, $sync = "asc")
    {
        switch ($condition) {
            case "=":
            case "!=":
            case "<>":
            case ">":
            case "<":
            case ">=":
            case "<=":
            case "LIKE":
                $stmt = $this->_conn->prepare("SELECT * FROM `".$this->_tableName."` WHERE `". $this->_fields[$index]."` ".$condition." ? ORDER BY ".$this->_fields[$orderby]." ".$sync."  LIMIT 200");
                if ($stmt->execute(array($value))) {
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (count($result) > 0) {
                        foreach ($result as $key => $row) {
                            $object = new $this($this->_conn, $this->_tableName, $this->_fieldsRename);
                            $object->id = $row[$this->_fields[0]];
                            if ($object->read()) {
                                $result[$key] = $object;
                            }
                        }
                    }
                    return $result;
                } else {
                    $this->errorMessage = $stmt->errorInfo();
                }
                break;
            case "IN":
                if (gettype($value) == "array") {
                    $query = "SELECT * FROM `".$this->_tableName."` WHERE `". $this->_fields[$index]."` IN (";
                    $input = "";
                    foreach ($value as $row) {
                        $input .= "?,";
                    }
                    $query = $query.substr($input, 0, strlen($input) - 1).") ORDER BY ".$this->_fields[$orderby]." ".$sync."  LIMIT 200";
                    $stmt = $this->_conn->prepare($query);
                    if ($stmt->execute($value)) {
                        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if (count($result) > 0) {
                            foreach ($result as $key => $row) {
                                $object = new $this($this->_conn, $this->_tableName, $this->_fieldsRename);
                                $object->id = $row[$this->_fields[0]];
                                if ($object->read()) {
                                    $result[$key] = $object;
                                }
                            }
                        }
                        return $result;
                    } else {
                        $this->errorMessage = $stmt->errorInfo();
                    }
                } else {
                    $this->errorMessage = "Mauvais type de données entrée";
                }
                break;
            case "BETWEEN":
                if ((gettype($value) == "array") && (count($value) == 2)) {
                    $query = "SELECT * FROM `".$this->_tableName."` WHERE `". $this->_fields[$index]."` BETWEEN ? AND ? ORDER BY ".$this->_fields[$orderby]." ".$sync."  LIMIT 200";
                    $stmt = $this->_conn->prepare($query);
                    if ($stmt->execute($value)) {
                        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if (count($result) > 0) {
                            foreach ($result as $key => $row) {
                                $object = new $this($this->_conn, $this->_tableName, $this->_fieldsRename);
                                $object->id = $row[$this->_fields[0]];
                                if ($object->read()) {
                                    $result[$key] = $object;
                                }
                            }
                        }
                        return $result;
                    } else {
                        $this->errorMessage = $stmt->errorInfo();
                    }
                }
                break;
            case "IS NULL":
            case "IS NOT NULL":
                $query = "SELECT * FROM `".$this->_tableName."` WHERE `". $this->_fields[$index]."` ".$condition." ORDER BY ".$orderby." ".$sync."  LIMIT 200";
                $stmt = $this->_conn->prepare($query);
                if ($stmt->execute(array($value))) {
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (count($result) > 0) {
                        foreach ($result as $key => $row) {
                            $object = new $this($this->_conn, $this->_tableName, $this->_fieldsRename);
                            $object->id = $row[$this->_fields[0]];
                            if ($object->read()) {
                                $result[$key] = $object;
                            }
                        }
                    }
                    return $result;
                } else {
                    $this->errorMessage = $stmt->errorInfo();
                }
                break;
        }
        return [];
    }

    public function update()
    {
        $query = "UPDATE `". $this->_tableName."` SET ";
        $values = [];
        foreach ($this->_fields as $key => $row) {
            if ($key != 0) {
                $val = $this->__get($this->_fieldsRename[$row]);
                if (json_decode($val) == null) {
                    $val = utf8_decode($val);
                }
                $query .= "`".$row."` = ?, ";
                $values[] = $val;
            }
        }
        $query = substr($query, 0, -2)." WHERE `".$this->_fields[0]."` = ?";
        $stmt = $this->_conn->prepare($query);
        $values[] = $this->id;
        if ($stmt->execute($values)) {
            return true;
        } else {
            $this->errorMessage = $stmt->errorInfo();
        }
        return false;
    }

    public function delete()
    {
        $stmt = $this->_conn->prepare("DELETE FROM `". $this->_tableName ."` WHERE `".$this->_fields[0]."` = ?");
        if ($stmt->execute(array($this->id))) {
            return true;
        } else {
            $this->errorMessage = $stmt->errorInfo();
        }
        return false;
    }

    public function isOrderByCorrect($orderby)
    {
        if (in_array($orderby, $this->_fieldsRename)) {
            return intval(array_search($orderby, $this->_fieldsRename));
        } else {
            if (intval($orderby < count($this->_fields))) {
                return intval($orderby);
            }
        }
        return false;
    }

    public function isSyncCorrect($sync)
    {
        return in_array(strtolower($sync), ["asc", "desc"]);
    }

    public function isEmpty()
    {
        return false;
    }

    public function isDataCorrect()
    {
        return true;
    }
}
