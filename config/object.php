<?php

namespace ReadyAPI;

use PDO;

/**
 * Class MsSql object
 */
abstract class ObjectSQL implements IConnection
{

    private $conn;
    private $tableName;
    private $table;
    public $id;
    public $errorMessage;

    public function __get($property)
    {
        return $this->$property;
    }

    public function __set($property, $value)
    {
        $this->$property = $value;
    }
    /**
     * Constructor of the SQL object
     *
     * @param PDO $db Database connector
     * @param string $nameBase Database Name
     */
    public function __construct($db, $nameBase, $table)
    {
        $this->conn = $db;
        $this->tableName = $nameBase;
        $this->table = $table;
    }

    abstract public function create();
    abstract public function read();
    abstract public function readAll();
    abstract public function readBy($value, $index, $condition, $separator);
    abstract public function update();
    abstract public function delete();

    public function isDataCorrect()
    {
        return true;
    }

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

    public function logInfo($action, $user)
    {
        if (gettype($action) == "string") {
            if (!is_dir("log")) {
                mkdir('log');
            }
            error_log(date("H:i:s") . " (" . ($user instanceof User ? $user->id : "Not Connected") . ") - " . $action . "\n", 3, "log/" . date("Y-m-d") . ".log");
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
     * Return format table Field element
     *
     * @return string
     */
    public function constructHead()
    {
        $head = "";
        foreach ($this->table as $key => $row) {
            $head .= $row["Field"] . ($key != count($this->table) - 1 ? "," : "");
        }
        return $head;
    }
}
