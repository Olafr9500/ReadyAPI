<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Europe/Paris');

// TODO Update password
$key = "<password>";
$issued_at = time();
$expiration_time = $issued_at + (60 * 60 * 24 * 30);
$issuer = gethostname();
