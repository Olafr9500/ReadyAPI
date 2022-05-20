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
            if (isset($_POST["id"])) {
                $sample = new SampleObject($database->conn);
                $sample->id = $_POST["id"];
                if ($sample->read()) {
                    foreach ($sample->table as $column) {
                        if (($column["Rename"] != "id")) {
                            $sample->__set($column["Rename"], ($column["Rename"] == "update" ? date("Y-m-d") : (isset($_POST[$column["Rename"]]) ? $_POST[$column["Rename"]] : $sample->$column["Rename"])));
                        }
                    }
                    if ($sample->isEmpty() === false) {
                        if ($sample->isDataCorrect() === true) {
                            if ($sample->update()) {
                                $sample->logInfo("UPDATE - ".$sample->tableName, $user);
                                StaticFunctions::displayError("no", array("response" => $sample));
                            } else {
                                StaticFunctions::displayError("Cannot update item", array("message" => $sample->errorMessage));
                            }
                        } else {
                            StaticFunctions::displayError("Incorrect information", array("fail" => $sample->isDataCorrect()));
                        }
                    } else {
                        StaticFunctions::displayError("Empty information", array("miss" => $sample->isEmpty()));
                    }
                } else {
                    StaticFunctions::displayError("No element with this id", array("messageError" => $sample->errorMessage));
                }
            } else {
                StaticFunctions::displayError("Uninitialized variable", array("post" => array("id" => (isset($_POST["id"]) ? 'true' : 'false'))));
            }
        }
    } else {
        StaticFunctions::displayError("Connection database fail", array("messageError" => $database->errorMessage));
    }
} else {
    StaticFunctions::displayError("Bad request method", array("expected" => "POST", "got" =>$_SERVER["REQUEST_METHOD"]));
}
