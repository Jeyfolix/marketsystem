<?php
header("Content-Type: application/json");
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if($db) {
        echo json_encode([
            "success" => true,
            "message" => "marketSystem Database connected successfully!",
            "database" => getenv('DB_NAME') ?: "affiliatepro"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "marketSystem Database connection failed"
        ]);
    }
} catch(Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "marketSystem Error: " . $e->getMessage()
    ]);
}
?>
