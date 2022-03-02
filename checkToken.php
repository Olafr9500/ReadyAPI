<?php
namespace ReadyAPI;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once './config/init.php';
include_once './config/function.php';
include_once './config/iconnection.php';
include_once './config/database.php';
include_once './config/databaseMySQL.php';
include_once './config/objectMySql.php';
include_once './object/user.php';

require 'vendor/autoload.php';

use \Firebase\JWT\JWT;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new DatabaseSample();
    if (!is_null($database->conn)) {
        if (preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            $jwt = $matches[1];
            if ($jwt && $jwt != "undefined") {
                $jwt = JWT::decode($jwt, $keyJWT, array('HS256'));
                if (checkJWT($jwt)) {
                    $data = $jwt->data;
                    $user = new User($database->conn);
                    $user->mail = $data->mail;
                    $user->password = $jwt->data->password;
                    if ($user->connection()) {
                        $user->logInfo("CHECK", $user);
                        displayError("no", array("response" => $user));
                    } else {
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
    } else {
        displayError("Connection database fail", array("messageError" => $database->errorMessage));
    }
} else {
    displayError("Bad request method", array("expected" => "POST", "got" =>$_SERVER["REQUEST_METHOD"]));
}
