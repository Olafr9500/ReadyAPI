<?php

namespace ReadyAPI;

use ArrayObject;
use PDO;

/**
 * Class MsSql object
 */
class ObjectMsSql extends ObjectSQL
{
    /**
     * Constructor of the MsSql object
     *
     * @param PDO $db Database connector
     * @param string $nameBase Database Name
     * @param array $fieldsWant List of object we want
     * @param array $fieldsRename List of object variables in the same order as the database columns
     */
    public function __construct($db, $nameBase, $fieldsWant, $fieldsRename = ["id"])
    {
        $table = [];
        $stmt = sqlsrv_query($db, "EXEC SP_COLUMNS  " . $nameBase);
        if ($stmt) {
            while ($column = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $returnColumn = [];
                if (array_search($column["Field"], $fieldsWant) !== false) {
                    $returnColumn['Field'] = $column["Field"];
                    $returnColumn['Type'] = $column['TYPE_NAME'] == 'int identity' ? 'int' : $column['TYPE_NAME'];
                    $returnColumn['Null'] = $column['IS_NULLABLE'];
                    $returnColumn['Key'] = $column['TYPE_NAME'] == 'int identity' ? 'PRI' : '';
                    $returnColumn['Default'] = "NULL";
                    $returnColumn["Rename"] = $fieldsRename[array_search($column["Field"], $fieldsWant)];
                    $table[] = $returnColumn;
                }
            }
        }
        parent::__construct($db, $nameBase, $table);
    }

    public function create($setId = false)
    {
        $query = "INSERT INTO " . $this->tableName . " SET ";
        $values = [];
        foreach ($this->table as $key => $row) {
            if (($key != 0) || ($setId)) {
                $query .= "" . $row["Field"] . " = ?, ";
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
        }
        $this->errorMessage = sqlsrv_errors();
        return false;
    }

    public function read()
    {
        if (isset($this->id)) {
            $head = $this->constructHead();
            $query = "SELECT " . ($head == "" ? "*" : $head) . " FROM " . $this->tableName . " WHERE " . $this->table[0]["Field"] . " = ?";
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
            $query = "SELECT " . ($head == "" ? "*" : $head) . " FROM " . $this->tableName . " ORDER BY";
            foreach ($orderby as $key => $order) {
                $query .= " " . $this->table[$order]["Field"] . " " . $sync[$key];
                if ($key != (count($orderby) - 1)) {
                    $query .= ",";
                }
            }
            $stmt = sqlsrv_query($this->conn, $query);
            if ($stmt) {
                $result = [];
                $key = 0;
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $restaurant = new $this($this->conn, $this->tableName, $this->getFieldsRename());
                    $restaurant->id = $row[$this->table[0]["Field"]];
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
            $query = "SELECT " . ($head == "" ? "*" : $head) . " FROM " . $this->tableName . " WHERE ";
            $queryOrder = " ORDER BY";
            foreach ($orderby as $key => $order) {
                $queryOrder .= " " . $this->table[$order]["Field"] . " " . $sync[$key];
                if ($key != (count($orderby) - 1)) {
                    $queryOrder .= ",";
                }
            }
            foreach ($condition as $key => $row) {
                $valueRead = $value[$key];
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
                        $query .= "" . $this->table[$index[$key]]["Field"] . " " . $row . " ?";
                        break;
                    case "IN":
                        if ($valueRead instanceof ArrayObject) {
                            $query .= "" . $this->table[$index]["Field"] . " IN (";
                            $input = "";
                            $i = 0;
                            foreach ($valueRead as $row) {
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
                        if (($valueRead instanceof ArrayObject) && (count($valueRead) == 2)) {
                            $query = "" . $this->table[$index]["Field"] . " BETWEEN ? AND ?";
                        }
                        break;
                    case "IS":
                        $query = "" . $this->table[$index]["Field"] . " IS " . $valueRead;
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
                    $restaurant->id = $row[$this->table[0]["Field"]];
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
                $query .= "" . $row["Field"] . " = ?, ";
                $values[] = $val;
            }
        }
        $query = substr($query, 0, -2) . " WHERE " . $this->table[0]["Field"] . " = ?";
        $values[] = $this->id;
        $stmt = sqlsrv_query($this->conn, $query, $values);
        if ($stmt) {
            return true;
        }
        $this->errorMessage = sqlsrv_errors();
        return false;
    }

    public function delete()
    {
        $stmt = sqlsrv_query($this->conn, "DELETE FROM " . $this->tableName . " WHERE " . $this->table[0]["Field"] . " = ?", array($this->id));
        if ($stmt) {
            return true;
        }
        $this->errorMessage = sqlsrv_errors();
        return false;
    }
}
