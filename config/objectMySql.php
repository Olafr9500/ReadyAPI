<?php

namespace ReadyAPI;

use ArrayObject;
use PDO;

/**
 * Class MySql object
 */
class ObjectMySql extends ObjectSQL
{
    /**
     * Constructor of the MySql object
     *
     * @param PDO $db Database connector
     * @param string $nameBase Database Name
     * @param array $fieldsWant List of object we want
     * @param array $fieldsRename List of object variables in the same order as the database columns
     */
    public function __construct($db, $nameBase, $fieldsWant, $fieldsRename = ["id"])
    {
        $stmt = $db->prepare("DESCRIBE `" . $nameBase . "`");
        $table = [];
        if ($stmt->execute()) {
            $result = $stmt->fetchAll();
            foreach ($result as $row) {
                if (array_search($row["Field"], $fieldsWant) !== false) {
                    $row["Rename"] = $fieldsRename[array_search($row["Field"], $fieldsWant)];
                    $table[] = $row;
                }
            }
        }
        parent::__construct($db, $nameBase, $table);
    }

    public function create($setId = false)
    {
        $query = "INSERT INTO `" . $this->tableName . "` SET ";
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
        }
        $this->errorMessage = $stmt->errorInfo();
        return false;
    }

    public function read()
    {
        if (isset($this->id)) {
            $head = $this->constructHead();
            $query = "SELECT " . ($head == "" ? "*" : $head) . " FROM `" . $this->tableName . "` WHERE `" . $this->table[0]["Field"] . "` = ?";
            $stmt = $this->conn->prepare($query);
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

    public function readAll($orderby = [0], $sync = ["asc"])
    {
        if (count($orderby) == count($sync)) {
            $head = $this->constructHead();
            $query = "SELECT " . ($head == "" ? "*" : $head) . " FROM `" . $this->tableName . "` ORDER BY";
            foreach ($orderby as $key => $order) {
                $query .= " `" . $this->table[$order]["Field"] . "` " . $sync[$key];
                if ($key != (count($orderby) - 1)) {
                    $query .= ",";
                }
            }
            $stmt = $this->conn->prepare($query);
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

    public function readBy($index, $value, $condition, $separator, $orderby = [0], $sync = ["asc"])
    {
        if ((count($orderby) == count($sync)) && (count($index) == count($condition))) {
            $head = $this->constructHead();
            $query = "SELECT " . ($head == "" ? "*" : $head) . " FROM `" . $this->tableName . "` WHERE ";
            $queryOrder = " ORDER BY";
            foreach ($orderby as $key => $order) {
                $queryOrder .= " `" . $this->table[$order]["Field"] . "` " . $sync[$key];
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
                        $query .= "`" . $this->table[$index[$key]]["Field"] . "` " . $row . " ?";
                        break;
                    case "IN":
                        if ($valueRead instanceof ArrayObject) {
                            $query .= "`" . $this->table[$index[$key]]["Field"] . "` IN (";
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
}
