<?php
namespace  ReadyAPI;

class StaticFunctions
{
    public static $SECURE_API = true;
    public static function displayError($message, $more)
    {
        echo json_encode(array_merge(array("error" => $message), $more));
        StaticFunctions::log("SERVER", ($message == "no" ? "SUCCESS" : "ERROR"), ($message == "no" ? "" : $message) . json_encode($more));
    }

    public static function log($identifiant, $status, $message)
    {
        error_log(date("H:i:s") . " (" . $identifiant . ") " . $status . " " . $message . "\n", 3, "./logs/" . date("Y-m-d") . ".log");
    }
    
    public static function checkJWT($jwt)
    {
        return (
            $jwt->iss !== gethostname() ||
            $jwt->iat > time() ||
            $jwt->exp < time()
        ) ? false : true;
    }
    
    public static function setExpirationTime($nbDays)
    {
        return time() + (60 * 60 * 24 * $nbDays);
    }
}
