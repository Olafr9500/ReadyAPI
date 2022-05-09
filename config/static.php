<?php
namespace  ReadyAPI;

class StaticFunctions
{
    public static $SECURE_API = true;
    public static function displayError($message, $more)
    {
        echo json_encode(array_merge(array("error" => $message), $more));
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
