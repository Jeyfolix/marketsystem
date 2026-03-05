<?php
// Simplest possible API - no database, no nothing
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

echo json_encode([
    "success" => true,
    "message" => "Simple test API is working",
    "time" => date("Y-m-d H:i:s")
]);
?>
