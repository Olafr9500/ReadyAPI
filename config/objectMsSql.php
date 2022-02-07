<?php

namespace ReadyAPI;

use PDO;

/**
 * Class MsSql object
 */
class ObjectMsSql implements IConn
{
    private $conn;
    private $tableName;
    private $table = [];
    public $id;
    public $errorMessage;
    /**
     * Constructor of the MsSql object
     *
     * @param PDO $db Database connector
     * @param string $nameBase Database Name
     * @param array $fieldsWant List of object we want
     * @param array $filedsRename List of object variables in the same order as the database columns
     */
    public function __construct($db, $nameBase, $fieldsWant, $filedsRename = ["id"])
    {
        $this->conn = $db;
        $this->tableName = $nameBase;
        $query = "EXEC SP_COLUMNS  " . $this->tableName;
        $stmt = sqlsrv_query($db, $query);
        if ($stmt) {
            while ($column = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                if (array_search($column["COLUMN_NAME"], $fieldsWant) !== false) {
                    $column["Rename"] = $filedsRename[array_search($column["COLUMN_NAME"], $fieldsWant)];
                    $this->table[] = $column;
                }
            }
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
        $query = "INSERT INTO " . $this->tableName . " SET ";
        $values = [];
        foreach ($this->table as $key => $row) {
            if (($key != 0) || ($setId)) {
                $query .= "" . $row["COLUMN_NAME"] . " = ?, ";
                $values[] = $this->__get($this->table[$key]["Rename"]);
            }
        }
        $stmt = sqlsrv_query($this->conn, substr($query, 0, -2), $values);
        if ($stmt) {
            if (!$setId) {
                sqlsrv_next_result($stmt);
                sqlsrv_fetch($stmt);
                $this->id = sqlsrv_get_field($stmt, 0);
            }
            return true;
        } else {
            $this->errorMessage = sqlsrv_errors();
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
            $head = $this->constructHead();
            $query = "SELECT " . ($head == "" ? "*" : $head) . " FROM " . $this->tableName . " WHERE " . $this->table[0]["COLUMN_NAME"] . " = ?";
            $stmt = sqlsrv_query($this->conn, $query, array($this->id));
            if ($stmt) {
                $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
                if (!empty($result)) {
                    foreach ($result as $key => $row) {
                        if ($this->table[$key]["Rename"]) {
                            if (gettype($row) == "string") {
                                $row = (json_encode($row) == null ? utf8_encode($row) : $row);
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
                $this->errorMessage = sqlsrv_errors();
            }
        } else {
            $this->errorMessage = "No id set";
        }
        return false;
    }
    public function readAll($orderby = [0], $sync = ["asc"])
    {
        if (count($orderby) == count($sync)) {
            $head = $this->constructHead();
            $query = "SELECT " . ($head == "" ? "*" : $head) . " from " . $this->tableName . " ORDER BY";
            foreach ($orderby as $key => $order) {
                $query .= " " . $this->table[$order]["COLUMN_NAME"] . " " . $sync[$key];
                if ($key != (count($orderby) - 1)) {
                    $query .= ",";
                }
            }
            $stmt = sqlsrv_query($this->conn, $query);
            if ($stmt) {
                $result = [];
                $key = 0;
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $restaurant = new $this($this->conn);
                    $restaurant->id = $row[$this->table[0]["COLUMN_NAME"]];
                    if ($restaurant->read()) {
                        $result[$key] = $restaurant;
                        $key++;
                    }
                }
                $this->errorMessage = $query;
                return $result;
            } else {
                $this->errorMessage = sqlsrv_errors();
            }
        } else {
            $this->errorMessage = "Nombre de 'orderby' différent de celui des 'sync'";
        }
        return false;
    }
    public function readBy($index, $value, $condition, $separator, $orderby = [0], $sync = ["asc"])
    {
        if ((count($orderby) == count($sync)) && (count($index) == count($condition))) {
            $head = $this->constructHead();
            $query = "SELECT TOP 200 " . ($head == "" ? "*" : $head) . " FROM " . $this->tableName . " WHERE ";
            $queryOrder = " ORDER BY";
            foreach ($orderby as $key => $order) {
                $queryOrder .= " " . $this->table[$order]["COLUMN_NAME"] . " " . $sync[$key];
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
                        $query .= "" . $this->table[$index[$key]]["COLUMN_NAME"] . " " . $row . " ?";
                        break;
                    case "IN":
                        if (gettype($value[$key]) == "array") {
                            $query .= "" . $this->table[$index]["COLUMN_NAME"] . " IN (";
                            $input = "";
                            foreach ($value[$key] as $row) {
                                $input .= "?,";
                            }
                            $query = $query . substr($input, 0, strlen($input) - 1) . ")";
                        } else {
                            $this->errorMessage = "Mauvais type de données entrée";
                        }
                        break;
                    case "BETWEEN":
                        if ((gettype($value[$key]) == "array") && (count($value[$key]) == 2)) {
                            $query = "" . $this->table[$index]["COLUMN_NAME"] . " BETWEEN ? AND ?";
                        }
                        break;
                    case "IS":
                        $query = "" . $this->table[$index]["COLUMN_NAME"] . " IS " . $value[$key];
                        break;
                }
                if ($key != (count($index) - 1)) {
                    $query .= " " . ($separator[$key] ? $separator[$key] : "AND") . " ";
                }
            }
            $stmt = sqlsrv_query($this->conn, $query . $queryOrder, $value);
            if ($stmt) {
                $result = [];
                $key = 0;
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $restaurant = new $this($this->conn);
                    $restaurant->id = $row[$this->table[0]["COLUMN_NAME"]];
                    if ($restaurant->read()) {
                        $result[$key] = $restaurant;
                        $key++;
                    }
                }
                $this->errorMessage = array($query, $value);
                return $result;
            } else {
                $this->errorMessage = sqlsrv_errors();
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
        $query = "UPDATE " . $this->tableName . " SET ";
        $values = [];
        foreach ($this->table as $key => $row) {
            if ($key != 0) {
                $val = $this->__get($row["Rename"]);
                if (json_decode($val) == null) {
                    $val = utf8_decode($val);
                }
                $query .= "" . $row["COLUMN_NAME"] . " = ?, ";
                $values[] = $val;
            }
        }
        $query = substr($query, 0, -2) . " WHERE " . $this->table[0]["COLUMN_NAME"] . " = ?";
        $values[] = $this->id;
        $stmt = sqlsrv_query($this->conn, $query, $values);
        if ($stmt) {
            return true;
        } else {
            $this->errorMessage = sqlsrv_errors();
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
        $stmt = sqlsrv_query($this->conn, "DELETE FROM " . $this->tableName . " WHERE " . $this->table[0]["COLUMN_NAME"] . " = ?", array($this->id));
        if ($stmt) {
            return true;
        } else {
            $this->errorMessage = sqlsrv_errors();
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
     * Undocumented function
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
                if (!is_dir("../log")) {
                    mkdir('../log');
                }
                error_log(date("H:i:s") . " (" . $user->id . ") - " . $action . "\n", 3, "../log/" . date("Y-m-d") . ".log");
            }
        }
        return false;
    }
    /**
     * Check if the variables in the object is correct or not.
     *
     * @return boolean|array If date incorrect, return an array with checking by variables.
     */
    public function isDataCorrect()
    {
        return true;
    }
    /**
     * return head of table
     *
     * @return string
     */
    public function constructHead()
    {
        $head = "";
        foreach ($this->table as $key => $row) {
            $head .= $row["COLUMN_NAME"] . ($key != count($this->table) - 1 ? "," : "");
        }
        return $head;
    }
}
