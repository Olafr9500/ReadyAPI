<?php
define("SECURE_API", true, true);
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
