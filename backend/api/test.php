<?php
header("Content-Type: application/json");
echo json_encode([
    "success" => true,
    "message" => "marketSystem API is working!",
    "project" => "marketSystem",
    "timestamp" => date("Y-m-d H:i:s"),
    "php_version" => phpversion()
]);
?>
