<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define("SECURE_API", true, true);

date_default_timezone_set('Europe/Paris');

// TODO Mot de passe à modifier
$key = "<password>";
$issued_at = time();
$expiration_time = $issued_at + (60 * 60 * 24 * 30);
$issuer = gethostname();

function displayError($message, $more)
{
    echo json_encode(array_merge(array("error" => $message), $more));
}

function checkJWT($jwt)
{
    return (
        $jwt->iss !== gethostname() ||
        $jwt->iat > time() ||
        $jwt->exp < time()
    ) ? false : true;
}
