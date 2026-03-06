<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    $query = "SELECT t.*, u.name, u.email, u.phone as user_phone 
              FROM transactions t
              LEFT JOIN users u ON t.user_id = u.id
              ORDER BY t.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "payments" => $payments
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
