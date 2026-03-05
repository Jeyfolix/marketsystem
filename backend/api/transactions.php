<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));
$user_id = isset($data->user_id) ? $data->user_id : 0;

if(empty($user_id)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "User ID required"]);
    exit();
}

try {
    // Get user's payment status
    $status_query = "SELECT status FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
    $status_stmt = $db->prepare($status_query);
    $status_stmt->execute([$user_id]);
    $status_row = $status_stmt->fetch(PDO::FETCH_ASSOC);
    $current_status = $status_row ? $status_row['status'] : 'unpaid';
    
    // Get all user's payments
    $payments_query = "SELECT id, user_id, phone, email, amount, mpesa_code, status, created_at 
                       FROM transactions 
                       WHERE user_id = ? 
                       ORDER BY created_at DESC";
    $payments_stmt = $db->prepare($payments_query);
    $payments_stmt->execute([$user_id]);
    $payments = $payments_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "current_status" => $current_status,
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
