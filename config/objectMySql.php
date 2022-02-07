<?php

namespace ReadyAPI;

use PDO;

/**
 * Class MySql object
 */
class ObjectMySql implements IConn
{
    /**
     * Connector PDO MySQL
     *
     * @var PDO
     */
    private $conn;
    /**
     * Name table SQL
     *
     * @var string
     */
    private $tableName;
    /**
     * List column table SQL with metadata
     *
     * @var array
     */
    private $table = [];
    /**
     * Primary Key in table
     *
     * @var int
     */
    public $id;
    /**
     * Error Message
     *
     * @var string
     */
    public $errorMessage;
    /**
     * Constructor of the MySql object
     *
     * @param PDO $db Database connector
     * @param string $nameBase Database Name
     * @param array $fieldsRename List of object variables in the same order as the database columns
     */
    public function __construct($db, $nameBase, $fieldsRename = ["id"])
    {
        $this->conn = $db;
        $this->tableName = $nameBase;
        $stmt = $db->prepare("DESCRIBE `" . $nameBase . "`");
        $stmt->execute();
        $this->table = $stmt->fetchAll();
        foreach ($this->table as $key => $row) {
            $row["Rename"] = array_shift($fieldsRename);
            $this->table[$key] = $row;
        }
    }

    public function __get($property)
    {
        return $this->$property;
    }

    public function __set($property, $value)
    {
        $this->$property = $value;
    }
    /**
     * Add a new entry for the object in the database
     *
     * @param boolean $setId Activate auto increment or not
     * @return boolean Insertion validation status
     */
    public function create($setId = false)
    {
        $query = "insert into `" . $this->tableName . "` set ";
        $values = [];
        foreach ($this->table as $key => $row) {
            if (($key != 0) || ($setId)) {
                $query .= "`" . $row["Field"] . "` = ?, ";
                $values[] = $this->__get($this->table[$key]["Rename"]);
            }
        }
        $stmt = $this->conn->prepare(substr($query, 0, -2));
        if ($stmt->execute($values)) {
            if (!$setId) {
                $this->id = $this->conn->lastInsertId();
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
            $stmt = $this->conn->prepare("SELECT * from `" . $this->tableName . "` where `" . $this->table[0]["Field"] . "` = ?");
            if ($stmt->execute(array($this->id))) {
                $result = $stmt->fetch(PDO::FETCH_NUM);
                if (!empty($result)) {
                    foreach ($result as $key => $row) {
                        if ($this->table[$key]["Rename"]) {
                            if (gettype($row) == "string") {
                                $row = utf8_encode($row);
                            }
                            $this->__set($this->table[$key]["Rename"], $row);
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
    public function readAll($orderby = [0], $sync = ["asc"])
    {
        if (count($orderby) == count($sync)) {
            $query = "SELECT * from `" . $this->tableName . "` ORDER BY";
            foreach ($orderby as $key => $order) {
                $query .= " `" . $this->table[$order]["Field"] . "` " . $sync[$key];
                if ($key != (count($orderby) - 1)) {
                    $query .= ",";
                }
            }
            $stmt = $this->conn->prepare($query . " LIMIT 200");
            if ($stmt->execute()) {
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($result) > 0) {
                    foreach ($result as $key => $row) {
                        $object = new $this($this->conn, $this->tableName, $this->getFieldsRename());
                        $object->id = $row[$this->table[0]["Field"]];
                        if ($object->read()) {
                            $result[$key] = $object;
                        }
                    }
                    $this->errorMessage = $query;
                    return $result;
                }
            } else {
                $this->errorMessage = $stmt->errorInfo();
            }
        } else {
            $this->errorMessage = "Nombre de 'orderby' différent de celui des 'sync'";
        }
        return false;
    }
    /**
     * Retrieves object's entries in the database with a condition
     *
     * @param array $index
     * @param array $value
     * @param array $condition
     * @param array $separator
     * @param array $orderby
     * @param array $sync
     * @return array|false List of database entries or false
     */
    public function readBy($index, $value, $condition, $separator, $orderby = [0], $sync = ["asc"])
    {
        if ((count($orderby) == count($sync)) && (count($index) == count($condition))) {
            $query = "SELECT * FROM `" . $this->tableName . "` WHERE ";
            $queryOrder = " ORDER BY";
            foreach ($orderby as $key => $order) {
                $queryOrder .= " `" . $this->table[$order]["Field"] . "` " . $sync[$key];
                if ($key != (count($orderby) - 1)) {
                    $queryOrder .= ",";
                }
            }
            foreach ($condition as $key => $row) {
                switch ($row) {
                    case "=":
                    case "!=":
                    case "<>":
                    case ">":
                    case "<":
                    case ">=":
                    case "<=":
                    case "LIKE":
                    case "NOT LIKE":
                        $query .= "`" . $this->table[$index[$key]]["Field"] . "` " . $row . " ?";
                        break;
                    case "IN":
                        if (gettype($value[$key]) == "array") {
                            $trueValue = $value[$key];
                            $query .= "`" . $this->table[$index[$key]]["Field"] . "` IN (";
                            $input = "";
                            $i = 0;
                            foreach ($trueValue as $row) {
                                $input .= "?,";
                                $value[$key + $i] = $row;
                                $i++;
                            }
                            $query = $query . substr($input, 0, strlen($input) - 1) . ")";
                        } else {
                            $this->errorMessage = "Mauvais type de données entrée";
                        }
                        break;
                    case "BETWEEN":
                        if ((gettype($value[$key]) == "array") && (count($value[$key]) == 2)) {
                            $query = "`" . $this->table[$index]["Field"] . "` BETWEEN ? AND ?";
                        }
                        break;
                    case "IS":
                        $query = "`" . $this->table[$index]["Field"] . "` IS " . $value[$key];
                        break;
                }
                if ($key != (count($index) - 1)) {
                    $query .= " " . ($separator[$key] ? $separator[$key] : "AND") . " ";
                }
            }
            $stmt = $this->conn->prepare($query . $queryOrder . "  LIMIT 200");
            if ($stmt->execute($value)) {
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($result) > 0) {
                    foreach ($result as $key => $row) {
                        $object = new $this($this->conn, $this->tableName, $this->getFieldsRename());
                        $object->id = $row[$this->table[0]["Field"]];
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
            $this->errorMessage = "Nombre de 'orderby' différent de celui des 'sync'";
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
        $query = "UPDATE `" . $this->tableName . "` SET ";
        $values = [];
        foreach ($this->table as $key => $row) {
            if ($key != 0) {
                $val = $this->__get($row["Rename"]);
                if (json_decode($val) == null) {
                    $val = utf8_decode($val);
                }
                $query .= "`" . $row["Field"] . "` = ?, ";
                $values[] = $val;
            }
        }
        $query = substr($query, 0, -2) . " WHERE `" . $this->table[0]["Field"] . "` = ?";
        $stmt = $this->conn->prepare($query);
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
        $stmt = $this->conn->prepare("DELETE FROM `" . $this->tableName . "` WHERE `" . $this->table[0]["Field"] . "` = ?");
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
     * @return boolean|integer Return false if absent from the table
     */
    public function isOrderByCorrect($orderby)
    {
        if (in_array($orderby, $this->getFieldsRename())) {
            return intval(array_search($orderby, $this->getFieldsRename()));
        } else {
            if (intval($orderby < count($this->table))) {
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
     * Get only the names of columns
     *
     * @return array
     */
    public function getFieldsRename()
    {
        $ret = [];
        foreach ($this->table as $row) {
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
        foreach ($this->table as $row) {
            if ($row["Rename"] != "id") {
                switch (explode('(', $row["Type"])[0]) {
                    case "tinyint":
                        $ret = $ret || (!in_array($this->__get($row["Rename"]), [0, 1]));
                        $values[$row["Rename"]] = (in_array($this->__get($row["Rename"]), [0, 1]) ? 'false' : 'true');
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
                    case "time":
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
        // foreach ($this->table as $key => $row) {
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
    /**
     * Make log
     *
     * @param string $action
     * @param int $user
     * @return boolean
     */
    public function logInfo($action, $user)
    {
        if ($user instanceof User) {
            if (gettype($action) == "string") {
                if (!is_dir("log")) {
                    mkdir('log');
                }
                error_log(date("H:i:s") . " (" . $user->id . ") - " . $action . "\n", 3, "log/" . date("Y-m-d") . ".log");
            }
        }
        return false;
    }
}
