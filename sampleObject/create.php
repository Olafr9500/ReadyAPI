<?php
namespace ReadyAPI;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/init.php';

require '../vendor/autoload.php';

use \Firebase\JWT\JWT;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
            $setId = false;
            if (isset($_POST["id"])) {
                $sample->id = $_POST["id"];
                if ($sample->read()) {
                    StaticFunctions::displayError("Id already existing", array("id" => $_POST["id"]));
                    exit;
                } else {
                    $setId = true;
                    unset($sample->errorMessage);
                }
            }
            $checkVariablesSet = true;
            foreach ($sample->table as $column) {
                if (($column["Rename"] != "id") && ($column["Rename"] != "update")) {
                    $checkVariablesSet &= isset($_POST[$column["Rename"]]);
                }
            }
            if ($checkVariablesSet) {
                foreach ($sample->table as $column) {
                    if (($column["Rename"] != "id")) {
                        $sample->__set($column["Rename"], ($column["Rename"] == "update" ? date("Y-m-d") : $_POST[$column["Rename"]]));
                    }
                }
                if ($sample->isEmpty() === false) {
                    if ($sample->isDataCorrect() === true) {
                        if ($sample->create($setId)) {
                            StaticFunctions::displayError("no", array("response" => $sample));
                            $sample->logInfo("CREATE - ".$sample->tableName, $user);
                        } else {
                            StaticFunctions::displayError("Cannot add item", array("message" => $sample->errorMessage));
                        }
                    } else {
                        StaticFunctions::displayError("Incorrect information", array("fail" => $sample->isDataCorrect()));
                    }
                } else {
                    StaticFunctions::displayError("Empty information", array("miss" => $sample->isEmpty()));
                }
            } else {
                $variables = array();
                foreach ($sample->getFieldsRename() as $column) {
                    if (($column != "id") && ($column != "update")) {
                        $variables[$column] = (isset($_POST[$column]) ? 'true' : 'false');
                    }
                }
                StaticFunctions::displayError("Uninitialized variables", array("post" => $variables));
            }
        }
    } else {
        StaticFunctions::displayError("Connection database fail", array("messageError" => $database->errorMessage));
    }
} else {
    StaticFunctions::displayError("Bad request method", array("expected" => "POST", "got" =>$_SERVER["REQUEST_METHOD"]));
}
