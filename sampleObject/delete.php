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
            if (isset($_POST["id"])) {
                $sample = new SampleObject($database->conn);
                $sample->id = $_POST["id"];
                if ($sample->read()) {
                    if ($sample->delete()) {
                        $sample->logInfo("DELETE - ".$sample->tableName, $user);
                        displayError("no", array("response" => $sample));
                    } else {
                        displayError("Cannot delete item", array("message" => $sample->errorMessage));
                    }
                } else {
                    displayError("No element with this id", array("messageError" => $sample->errorMessage));
                }
            } else {
                displayError("Uninitialized variable", array("post" => array("id" => (isset($_POST["id"]) ? 'true' : 'false'))));
            }
        }
    } else {
        displayError("Connection database fail", array("messageError" => $database->errorMessage));
    }
} else {
    displayError("Bad request method", array("expected" => "POST", "got" =>$_SERVER["REQUEST_METHOD"]));
}
