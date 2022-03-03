<?php
namespace ReadyAPI;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/init.php';
include_once '../config/function.php';

require '../vendor/autoload.php';

include_once '../database/databaseSample.php';
include_once '../object/user.php';
include_once '../object/sampleObject.php';

use \Firebase\JWT\JWT;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
                        $user->password = $jwt->data->password;
                        if (!$user->connection()) {
                            displayError("Incorrect login token", array("messageError" => $user->errorMessage));
                            $checkSecure = false;
                        }
                    } else {
                        displayError("Incorrect login token", array("checkToken"=>"fail"));
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
            $setId = false;
            if (isset($_POST["id"])) {
                $sample->id = $_POST["id"];
                if ($sample->read()) {
                    displayError("Id already existing", array("id" => $_POST["id"]));
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
                        $sample->$column["Rename"] = ($column["Rename"] == "update" ? date("Y-m-d") : $_POST[$column["Rename"]]);
                    }
                }
                if ($sample->isEmpty() === false) {
                    if ($sample->isDataCorrect() === true) {
                        if ($sample->create($setId)) {
                            displayError("no", array("response" => $sample));
                            $sample->logInfo("CREATE - ".$sample->tableName, $user);
                        } else {
                            displayError("Cannot add item", array("message" => $sample->errorMessage));
                        }
                    } else {
                        displayError("Incorrect information", array("fail" => $sample->isDataCorrect()));
                    }
                } else {
                    displayError("Empty information", array("miss" => $sample->isEmpty()));
                }
            } else {
                $variables = array();
                foreach ($sample->getFieldsRename() as $column) {
                    if (($column != "id") && ($column != "update")) {
                        $variables[$column] = (isset($_POST[$column]) ? 'true' : 'false');
                    }
                }
                displayError("Uninitialized variables", array("post" => $variables));
            }
        }
    } else {
        displayError("Connection database fail", array("messageError" => $database->errorMessage));
    }
} else {
    displayError("Bad request method", array("expected" => "POST", "got" =>$_SERVER["REQUEST_METHOD"]));
}
