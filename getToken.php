<?php
namespace ReadyAPI;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once 'config/init.php';

require 'vendor/autoload.php';

use \Firebase\JWT\JWT;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new DatabaseSample();
    if (!is_null($database->conn)) {
        $user = new User($database->conn);
        if ((isset($_SERVER["PHP_AUTH_USER"])) && (isset($_SERVER["PHP_AUTH_PW"]))) {
            $user->mail = $_SERVER["PHP_AUTH_USER"];
            $user->password = $database::encodePassword($_SERVER["PHP_AUTH_PW"]);
        }
        if ((isset($_POST["user"])) && (isset($_POST["password"]))) {
            $user->mail = $_POST["user"];
            $user->password = $database::encodePassword($_POST["password"]);
        }
        if (isset($_POST['expirationServer'])) {
            $expiration_time = StaticFunctions::setExpirationTime(365);
        } else {
            $expiration_time = StaticFunctions::setExpirationTime(30);
        }
        if (!is_null($user->mail)) {
            if ($user->connection()) {
                $token = array(
                    "iat" => time(),
                    "exp" => $expiration_time,
                    "iss" => gethostname(),
                    "data" => $user
                );
                $jwt = JWT::encode($token, $keyJWT);
                $user->logInfo("CONNECT", $user);
                StaticFunctions::displayError("no", array("response" => $jwt));
            } else {
                StaticFunctions::displayError("Connection fail", array("user" => $user->errorMessage));
            }
        } else {
            StaticFunctions::displayError("Uninitialized variables", array("post" => array("user" => (isset($_POST["user"]) || isset($_SERVER["PHP_AUTH_USER"]) ? 'true' : 'false'), "password" => (isset($_POST["password"]) || isset($_SERVER["PHP_AUTH_PW"]) ? 'true' : 'false'))));
        }
    } else {
        StaticFunctions::displayError("Connection database fail", array("messageError" => $database->errorMessage));
    }
} else {
    StaticFunctions::displayError("Bad request method", array("expected" => "POST", "got" =>$_SERVER["REQUEST_METHOD"]));
}
