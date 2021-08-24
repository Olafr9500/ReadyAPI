<?php
namespace ReadyAPI;

use PDO;

include_once 'iconn.php';
/**
 * Class MySql object
 */
class ObjectMySql implements IConn
{
    private $_conn;
    private $_tableName;
    private $_table = [];
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
        $this->_table = $stmt->fetchAll();
        foreach ($this->_table as $row) {
            $this->_table[$row]["Rename"] = array_shift($filedsRename);
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
        foreach ($this->_table as $key => $row) {
            if (($key != 0) || ($setId)) {
                $query .= "`".$row."` = ?, ";
                $values[] = $this->__get($this->_table[$key]["Rename"]);
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
            $stmt = $this->_conn->prepare("SELECT * from `" . $this->_tableName . "` where `".$this->_table[0]["Field"]."` = ?");
            if ($stmt->execute(array($this->id))) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!empty($result)) {
                    foreach ($result as $key => $row) {
                        if ($this->_table[$key]["Rename"]) {
                            if (gettype($row) == "string") {
                                $row = utf8_encode($row);
                            }
                            $this->__set($this->_table[$key]["Rename"], $row);
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
        $stmt = $this->_conn->prepare("SELECT * from `" . $this->_tableName . "` ORDER BY `". $this->_table[$orderby]["Field"] ."` ".$sync." LIMIT 200");
        if ($stmt->execute()) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($result) > 0) {
                foreach ($result as $key => $row) {
                    $object = new $this($this->_conn, $this->_tableName, $this->getFieldsRename());
                    $object->id = $row[$this->_table[0]["Field"]];
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
    /**
     * Retrieves object's entries in the database with a condition
     *
     * @param string|integer $index
     * @param intege|array $value
     * @param string $condition
     * @param integer $orderby
     * @param string $sync
     * @return array|false List of database entries or false
     */
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
                $stmt = $this->_conn->prepare("SELECT * FROM `".$this->_tableName."` WHERE `". $this->_table[$index]["Field"]."` ".$condition." ? ORDER BY ".$this->_table[$orderby]["Field"]." ".$sync."  LIMIT 200");
                if ($stmt->execute(array($value))) {
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (count($result) > 0) {
                        foreach ($result as $key => $row) {
                            $object = new $this($this->_conn, $this->_tableName, $this->getFieldsRename());
                            $object->id = $row[$this->_table[0]["Field"]];
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
                    $query = "SELECT * FROM `".$this->_tableName."` WHERE `". $this->_table[$index]["Field"]."` IN (";
                    $input = "";
                    foreach ($value as $row) {
                        $input .= "?,";
                    }
                    $query = $query.substr($input, 0, strlen($input) - 1).") ORDER BY ".$this->_table[$orderby]["Field"]." ".$sync."  LIMIT 200";
                    $stmt = $this->_conn->prepare($query);
                    if ($stmt->execute($value)) {
                        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if (count($result) > 0) {
                            foreach ($result as $key => $row) {
                                $object = new $this($this->_conn, $this->_tableName, $this->getFieldsRename());
                                $object->id = $row[$this->_table[0]["Field"]];
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
                    $query = "SELECT * FROM `".$this->_tableName."` WHERE `". $this->_table[$index]["Field"]."` BETWEEN ? AND ? ORDER BY ".$this->_table[$orderby]["Field"]." ".$sync."  LIMIT 200";
                    $stmt = $this->_conn->prepare($query);
                    if ($stmt->execute($value)) {
                        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if (count($result) > 0) {
                            foreach ($result as $key => $row) {
                                $object = new $this($this->_conn, $this->_tableName, $this->getFieldsRename());
                                $object->id = $row[$this->_table[0]["Field"]];
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
                $query = "SELECT * FROM `".$this->_tableName."` WHERE `". $this->_table[$index]["Field"]."` ".$condition." ORDER BY ".$this->_table[$orderby]["Field"]." ".$sync."  LIMIT 200";
                $stmt = $this->_conn->prepare($query);
                if ($stmt->execute(array($value))) {
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (count($result) > 0) {
                        foreach ($result as $key => $row) {
                            $object = new $this($this->_conn, $this->_tableName, $this->getFieldsRename());
                            $object->id = $row[$this->_table[0]["Field"]];
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
    /**
     * Edit an entry for a database object
     *
     * @return boolean True if the modification has been made
     */
    public function update()
    {
        $query = "UPDATE `". $this->_tableName."` SET ";
        $values = [];
        foreach ($this->_table as $key => $row) {
            if ($key != 0) {
                $val = $this->__get($row["Rename"]);
                if (json_decode($val) == null) {
                    $val = utf8_decode($val);
                }
                $query .= "`".$row."` = ?, ";
                $values[] = $val;
            }
        }
        $query = substr($query, 0, -2)." WHERE `".$this->_table[0]["Field"]."` = ?";
        $stmt = $this->_conn->prepare($query);
        $values[] = $this->id;
        if ($stmt->execute($values)) {
            return true;
        } else {
            $this->errorMessage = $stmt->errorInfo();
        }
        return false;
    }
    /**
     * Delete an entry of a database object
     *
     * @return boolean True if the modification has been made
     */
    public function delete()
    {
        $stmt = $this->_conn->prepare("DELETE FROM `". $this->_tableName ."` WHERE `".$this->_table[0]["Field"]."` = ?");
        if ($stmt->execute(array($this->id))) {
            return true;
        } else {
            $this->errorMessage = $stmt->errorInfo();
        }
        return false;
    }
    /**
     * Check if the ordering index is indeed contained in the database table
     *
     * @param string|boolean $orderby
     * @return boolean|interger Return false if absent from the table
     */
    public function isOrderByCorrect($orderby)
    {
        if (in_array($orderby, $this->getFieldsRename())) {
            return intval(array_search($orderby, $this->getFieldsRename()));
        } else {
            if (intval($orderby < count($this->_table))) {
                return intval($orderby);
            }
        }
        return false;
    }
    /**
     * Check if the ordering sense exist
     *
     * @param string $sync
     * @return boolean
     */
    public function isSyncCorrect($sync)
    {
        return in_array(strtolower($sync), ["asc", "desc"]);
    }
    /**
     * Undocumented function
     *
     * @return array
     */
    public function getFieldsRename()
    {
        $ret = [];
        foreach ($this->_table as $row) {
            $ret[] = $row["Rename"];
        }
        return $ret;
    }
    /**
     * Check if the variables in the object is empty or not.
     *
     * @return boolean|array If empty, return an array with checking by variables.
     */
    public function isEmpty()
    {
        $ret = false;
        $values = array();
        foreach ($this->_table as $row) {
            if ($row["Rename"] != "id") {
                switch (explode('(', $row["Type"])[0]) {
                    case "tinyint":
                        $ret = $ret || (!in_array($this->__get($row["Rename"]), [0,1]));
                        $values[$row["Rename"]] = (in_array($this->__get($row["Rename"]), [0,1]) ? 'false' : 'true');
                        break;
                    case "int":
                    case "float":
                    case "double":
                        $ret = $ret || $this->__get($row["Rename"]) < 0;
                        $values[$row["Rename"]] = ($this->__get($row["Rename"]) < 0 ? 'true' : 'false');
                        break;
                    case "varchar":
                    case "datetime":
                    case "date":
                        $value = $this->__get($row["Rename"]);
                        $ret = $ret || (empty($value));
                        $values[$row["Rename"]] = (empty($value) ? 'true' : 'false');
                        break;
                    default:
                        break;
                }
            }
        }
        return $ret ? $values : false;
    }
    /**
     * Check if the variables in the object is correct or not.
     *
     * @return boolean|array If date incorrect, return an array with checking by variables.
     */
    public function isDataCorrect()
    {
        // $ret = true;
        // $values = array();
        // foreach ($this->_table as $key => $row) {
        //     if ($row["Rename"] != "id") {
        //         switch (strtolower($row["Rename"])) {
        //             case "ip":
        //             case "ipv4":
        //                 $ret = $ret && (filter_var($this->__get($row["Rename"]), FILTER_VALIDATE_IP));
        //                 $values[$row["Rename"]] = (filter_var($this->__get($row["Rename"]), FILTER_VALIDATE_IP) ? 'true' : 'false');
        //                 break;
        //             case "package":
        //                 // $ret = $ret && ();
        //                 break;
        //             case "datetime":
        //                 $ret = $ret && (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1]) (0[0-9]|1[1-9]|2|[0-3]):([0-5][0-9]):([0-5][0-9])$/", $this->__get($row["Rename"])));
        //                 $values[$row["Rename"]] = (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1]) (0[0-9]|1[1-9]|2|[0-3]):([0-5][0-9]):([0-5][0-9])$/", $this->__get($row["Rename"])) ? 'true' : 'false');
        //                 break;
        //             case "date":
        //                 $ret = $ret && (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $this->__get($row["Rename"])));
        //                 $values[$row["Rename"]] = (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $this->__get($row["Rename"])) ? 'true' : 'false');
        //                 break;
        //             default:
        //                 break;
        //         }
        //     }
        // }
        // return $ret ? true : $values;
        return true;
    }
}
