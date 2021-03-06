<?php
namespace ReadyAPI;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/init.php';

require '../vendor/autoload.php';

use ArrayObject;
use \Firebase\JWT\JWT;

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $database = new DatabaseSample();
    $user = null;
    if (!is_null($database->conn)) {
        $checkSecure = true;
        if (StaticFunctions::$SECURE_API) {
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                if (preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
                    $jwt = $matches[1];
                    if ($jwt) {
                        $jwt = JWT::decode($jwt, $keyJWT, array('HS256'));
                        if (StaticFunctions::checkJWT($jwt)) {
                            $data = $jwt->data;
                            $user = new User($database->conn);
                            $user->mail = $data->mail;
                            $user->password = $data->password;
                            if (!$user->connection()) {
                                StaticFunctions::displayError("Incorrect login token", array("messageError" => $user->errorMessage));
                                $checkSecure = false;
                            }
                        } else {
                            StaticFunctions::displayError("Incorrect login token", array("checkToken"=>StaticFunctions::checkJWT($jwt)));
                            $checkSecure = false;
                        }
                    } else {
                        StaticFunctions::displayError("No token initialized", array("matches" => $matches));
                        $checkSecure = false;
                    }
                } else {
                    StaticFunctions::displayError("Bad format token", array("Auth" => $_SERVER['HTTP_AUTHORIZATION']));
                    $checkSecure = false;
                }
            } else {
                StaticFunctions::displayError("No token provided", []);
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
                    StaticFunctions::displayError("no", array("response" => $sample));
                } else {
                    StaticFunctions::displayError("No element with this id", array("messageError" => $sample->errorMessage));
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
                        StaticFunctions::displayError("index ".$row." missing in db", array("GET" => $_GET));
                        exit;
                    }
                }
                foreach ($listCondition as $key => $row) {
                    if (in_array($row, $listAuthCondition)) {
                        $condition[$key] = strval($row);
                    } else {
                        StaticFunctions::displayError("Condition inexistante", array("expected" => $listAuthCondition, "have" => $row));
                        exit;
                    }
                }
                $valuePOST = new ArrayObject();
                $valuePOST = explode(",", $_GET["value"]);
                $value = [];
                $goNext = true;
                foreach ($valuePOST as $key => $row) {
                    if ($goNext) {
                        if (preg_match("/(\[)/m", $row)) {
                            $arrayRet = [];
                            $i = 0;
                            $arrayRet[] = str_replace("[", "", $row);
                            do {
                                $i++;
                                $arrayRet[] = $valuePOST[$key+$i];
                                unset($valuePOST[$key+$i]);
                                array_values($value);
                            } while (!preg_match("/(\])/m", $arrayRet[count($arrayRet)-1]));
                            $arrayRet[count($arrayRet)-1] = str_replace("]", "", $arrayRet[count($arrayRet)-1]);
                            $value[] = $arrayRet;
                            $goNext = false;
                        } else {
                            $value[] = $row;
                        }
                    } else {
                        $goNext = true;
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
                    StaticFunctions::displayError("no", $response);
                } else {
                    StaticFunctions::displayError("Empty", array("errorMessage" => $sample->errorMessage));
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
                    StaticFunctions::displayError("no", array("response" => $samples));
                } else {
                    StaticFunctions::displayError("Empty", array("errorMessage" => $sample->errorMessage));
                }
            }
        }
    } else {
        StaticFunctions::displayError("Connection database fail", array("messageError" => $database->errorMessage));
    }
} else {
    StaticFunctions::displayError("Bad request method", array("expected" => "GET", "got" =>$_SERVER["REQUEST_METHOD"]));
}
