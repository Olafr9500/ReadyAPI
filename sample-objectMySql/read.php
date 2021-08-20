<?php
namespace ReadyAPI;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/core.php';
include_once '../config/database.php';
include_once '../config/objectMySql.php';
include_once '../object/user.php';
include_once '../object/sample-objectMySql.php';

include_once '../libs/php-jwt-master/src/BeforeValidException.php';
include_once '../libs/php-jwt-master/src/ExpiredException.php';
include_once '../libs/php-jwt-master/src/SignatureInvalidException.php';
include_once '../libs/php-jwt-master/src/JWT.php';

use \Firebase\JWT\JWT;
use ReadyAPI\Database;
use ReadyAPI\User;

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $database = new Database();
    if (!is_null($database->conn)) {
        if (preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            $jwt = $matches[1];
            if ($jwt) {
                $jwt = JWT::decode($jwt, $key, array('HS256'));
                if (checkJWT($jwt)) {
                    $data = $jwt->data;
                    $user = new User($database->conn);
                    $user->mail = $data->mail;
                    $user->password = $data->password;
                    if ($user->connection()) {
                        $sample = new SampleObject($database->conn);
                        $lazy = true;
                        if (isset($_GET["lazy"])) {
                            $lazy = ($lazy == "false") ? false : true;
                        }
                        if (isset($_GET["id"])) {
                            $sample->id = $_GET["id"];
                            if ($sample->read($lazy)) {
                                displayError("no", array("response" => $sample));
                            } else {
                                displayError("No element with this id", array("messageError" => $sample->errorMessage));
                            }
                        } else if ((isset($_GET["index"])) && (isset($_GET["value"])) && (isset($_GET["condition"]))) {
                            if (array_search($_GET["index"], $sample->_fieldsRename)) {
                                $index = array_search($_GET["index"], $sample->_fieldsRename);
                                if (in_array($_GET["condition"], ["=", "!=", "<>", "<", ">", "<=", ">=","LIKE", "IN", "BETWEEN", "IS NULL", "IS NOT NULL"])) {
                                    $condition = strval($_GET["condition"]);
                                    $value = $_GET["value"];
                                    $orderby = 0;
                                    $sync = "asc";
                                    if (isset($_GET["orderby"])) {
                                        if ($result = $sample->isOrderByCorrect($_GET["orderby"])) {
                                            $orderby = $result;
                                        }
                                    }
                                    if ((isset($_GET["sync"])) && ($sample->isSyncCorrect($_GET["sync"]))) {
                                        $sync = $_GET["sync"];
                                    }
                                    $samples = $sample->readBy($index, $value, $condition, $orderby, $sync, $lazy);
                                    if (count($samples) > 0) {
                                        if (count($samples) == 200) {
                                            $response = array( "warning" => "200 or more query found", "response" => $samples);
                                        } else {
                                            $response = array("response" => $samples);
                                        }
                                        displayError("no", $response);
                                    } else {
                                        displayError("Empty", array("errorMessage" => $sample->errorMessage));
                                    }
                                } else {
                                    displayError("Condition inexistante", array("expected" => ["=", "!=", "<>", "<", ">", "<=", ">=","LIKE", "IN", "BETWEEN", "IS NULL", "IS NOT NULL"], "have" => $_GET["condition"]));
                                }
                            } else {
                                displayError("index ".$_GET["index"]." absent de la bdd", array("GET" => $_GET));
                            }
                        } else {
                            $orderby = 0;
                            $sync = "asc";
                            if (isset($_GET["orderby"])) {
                                if ($result = $sample->isOrderByCorrect($_GET["orderby"])) {
                                    $orderby = $result;
                                }
                            }
                            if ((isset($_GET["sync"])) && ($sample->isSyncCorrect($_GET["sync"]))) {
                                $sync = $_GET["sync"];
                            }
                            $samples = $sample->readAll($orderby, $sync, $lazy);
                            if (count($samples) > 0) {
                                displayError("no", array("response" => $samples));
                            } else {
                                displayError("no", array("errorMessage" => $sample->errorMessage));
                            }
                        }
                    } else {
                        displayError("Incorrect login token", array("messageError" => $user->errorMessage));
                    }
                } else {
                    displayError("Incorrect login token", array("checkToken"=>checkJWT($jwt)));
                }
            } else {
                displayError("No token initialized", array("matches" => $matches));
            }
        } else {
            displayError("No token initialized", array("Auth" => $_SERVER['HTTP_AUTHORIZATION']));
        }
    } else {
        displayError("Connection database fail", array("messageError" => $database->errorMessage));
    }
} else {
    displayError("Bad request method", array("expected" => "POST", "got" =>$_SERVER["REQUEST_METHOD"]));
}
