<?php
namespace ReadyAPI;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/init.php';
include_once '../config/function.php';

require '../vendor/autoload.php';

use ArrayObject;
use \Firebase\JWT\JWT;

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $database = new DatabaseSample();
    $user = null;
    if (!is_null($database->conn)) {
        $checkSecure = true;
        if (SECURE_API) {
            if (preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
                $jwt = $matches[1];
                if ($jwt) {
                    $jwt = JWT::decode($jwt, $keyJWT, array('HS256'));
                    if (checkJWT($jwt)) {
                        $data = $jwt->data;
                        $user = new User($database->conn);
                        $user->mail = $data->mail;
                        $user->password = $data->password;
                        if (!$user->connection()) {
                            displayError("Incorrect login token", array("messageError" => $user->errorMessage));
                            $checkSecure = false;
                        }
                    } else {
                        displayError("Incorrect login token", array("checkToken"=>checkJWT($jwt)));
                        $checkSecure = false;
                    }
                } else {
                    displayError("No token initialized", array("matches" => $matches));
                    $checkSecure = false;
                }
            } else {
                displayError("No token initialized", array("Auth" => $_SERVER['HTTP_AUTHORIZATION']));
                $checkSecure = false;
            }
        }
        if ($checkSecure) {
            $sample = new SampleObject($database->conn);
            $lazy = true;
            if (isset($_GET["lazy"])) {
                $lazy = ($lazy == "false") ? false : true;
            }
            if (isset($_GET["id"])) {
                $sample->id = $_GET["id"];
                if ($sample->read($lazy)) {
                    $sample->logInfo("READ BY ID - ".$sample->tableName, $user);
                    displayError("no", array("response" => $sample));
                } else {
                    displayError("No element with this id", array("messageError" => $sample->errorMessage));
                }
            } elseif ((isset($_GET["index"])) && (isset($_GET["value"])) && (isset($_GET["condition"]))) {
                $index = [];
                $listIndex = explode(",", $_GET["index"]);
                $listCondition = explode(",", $_GET["condition"]);
                $listAuthCondition = ["=", "!=", "<>", "<", ">", "<=", ">=","LIKE","NOT LIKE", "IN", "NOT IN", "BETWEEN", "IS"];
                foreach ($listIndex as $key => $row) {
                    if (array_search($row, $sample->getFieldsRename()) !== false) {
                        $index[$key] = array_search($row, $sample->getFieldsRename());
                    } else {
                        displayError("index ".$row." missing in db", array("GET" => $_GET));
                        exit;
                    }
                }
                foreach ($listCondition as $key => $row) {
                    if (in_array($row, $listAuthCondition)) {
                        $condition[$key] = strval($row);
                    } else {
                        displayError("Condition inexistante", array("expected" => $listAuthCondition, "have" => $row));
                        exit;
                    }
                }
                $value = new ArrayObject();
                $value = explode(",", $_GET["value"]);
                foreach ($value as $key => $row) {
                    if (preg_match("/(\[)/m", $row)) {
                        $i = 0;
                        $value[$key+$i]= str_replace("[", "", $row);
                        $arrayRet = new ArrayObject();
                        do {
                            $arrayRet[] = $value[$key+$i];
                            unset($value[$key+$i]);
                            array_values($value);
                            $i++;
                        } while (!preg_match("/(\])/m", $arrayRet[$i-1]));
                        $arrayRet[$i-1] = str_replace("]", "", $arrayRet[$i-1]);
                        $value[$key] = $arrayRet;
                    }
                }
                $separator = isset($_GET["separator"]) ? explode(",", $_GET["separator"]) : ["AND"];
                $orderby = [0];
                $sync = ["asc"];
                $listOrder = [];
                $listSync = [];
                if (isset($_GET["orderby"])) {
                    if (gettype($_GET["orderby"]) == "array") {
                        $listOrder = $_GET["orderby"];
                    } elseif (gettype($_GET["orderby"]) == "string") {
                        $listOrder = explode(",", $_GET["orderby"]);
                    }
                    foreach ($listOrder as $key => $order) {
                        if ($result = $sample->isOrderByCorrect($order)) {
                            $orderby[$key] = $result;
                        }
                    }
                }
                if (isset($_GET["sync"])) {
                    if (gettype($_GET["sync"]) == "array") {
                        $listSync = $_GET["sync"];
                    } elseif (gettype($_GET["sync"]) == "string") {
                        $listSync = explode(",", $_GET["sync"]);
                    }
                    foreach ($listSync as $key => $order) {
                        if ($sample->isSyncCorrect($order)) {
                            $sync[$key] = $order;
                        }
                    }
                }
                $samples = $sample->readBy($index, $value, $condition, $separator, $orderby, $sync, $lazy);
                if (count($samples) > 0) {
                    if (count($samples) == 200) {
                        $response = array( "warning" => "200 or more query found", "response" => $samples);
                    } else {
                        $response = array("response" => $samples);
                    }
                    $sample->logInfo("READ BY INDEX - ".$sample->tableName, $user);
                    displayError("no", $response);
                } else {
                    displayError("Empty", array("errorMessage" => $sample->errorMessage));
                }
            } else {
                $orderby = [0];
                $sync = ["asc"];
                $listOrder = [];
                $listSync = [];
                if (isset($_GET["orderby"])) {
                    if (gettype($_GET["orderby"]) == "array") {
                        $listOrder = $_GET["orderby"];
                    } elseif (gettype($_GET["orderby"]) == "string") {
                        $listOrder = explode(",", $_GET["orderby"]);
                    }
                    foreach ($listOrder as $key => $order) {
                        if ($result = $sample->isOrderByCorrect($order)) {
                            $orderby[$key] = $result;
                        }
                    }
                }
                if (isset($_GET["sync"])) {
                    if (gettype($_GET["sync"]) == "array") {
                        $listSync = $_GET["sync"];
                    } elseif (gettype($_GET["sync"]) == "string") {
                        $listSync = explode(",", $_GET["sync"]);
                    }
                    foreach ($listSync as $key => $order) {
                        if ($sample->isSyncCorrect($order)) {
                            $sync[$key] = $order;
                        }
                    }
                }
                $samples = $sample->readAll($orderby, $sync, $lazy);
                if (count($samples) > 0) {
                    $sample->logInfo("READ ALL - ".$sample->tableName, $user);
                    displayError("no", array("response" => $samples));
                } else {
                    displayError("Empty", array("errorMessage" => $sample->errorMessage));
                }
            }
        }
    } else {
        displayError("Connection database fail", array("messageError" => $database->errorMessage));
    }
} else {
    displayError("Bad request method", array("expected" => "GET", "got" =>$_SERVER["REQUEST_METHOD"]));
}
