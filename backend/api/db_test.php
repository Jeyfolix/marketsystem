<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

try {
    include_once '../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo json_encode([
            "success" => true,
            "message" => "Database connected successfully",
            "server_info" => $db->getAttribute(PDO::ATTR_SERVER_VERSION)
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Database connection failed"
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
