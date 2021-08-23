<?php
namespace ReadyAPI;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once './config/core.php';
include_once './config/database.php';
include_once './config/objectMySql.php';
include_once './object/user.php';

require __DIR__ . '/vendor/autoload.php';

use \Firebase\JWT\JWT;
use ReadyAPI\Database;
use ReadyAPI\User;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    if (!is_null($database->conn)) {
        if ((isset($_POST["user"])) && (isset($_POST["password"]))) {
            $user = new User($database->conn);
            $user->mail = $_POST["user"];
            $user->password = $database::encodePassword($_POST["password"]);
            if ($user->connection()) {
                $token = array(
                    "iat" => $issued_at,
                    "exp" => $expiration_time,
                    "iss" => $issuer,
                    "data" => $user
                );
                $jwt = JWT::encode($token, $key);
                displayError("no", array("response" => $jwt));
            } else {
                displayError("Connection fail", array("user" => $user->errorMessage));
            }
        } else {
            displayError("Uninitialized variables", array("post" => array("user" => (isset($_POST["user"]) ? 'true' : 'false'), "password" => (isset($_POST["password"]) ? 'true' : 'false'))));
        }
    } else {
        displayError("Connection database fail", array("messageError" => $database->errorMessage));
    }
} else {
    displayError("Bad request method", array("expected" => "POST", "got" =>$_SERVER["REQUEST_METHOD"]));
}
