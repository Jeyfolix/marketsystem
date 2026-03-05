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

if(!isset($data->user_id) || !isset($data->amount) || !isset($data->method) || !isset($data->phone)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "All fields are required"]);
    exit();
}

$user_id = $data->user_id;
$amount = $data->amount;
$method = $data->method;
$phone = $data->phone;

try {
    $balance_query = "SELECT 
                        (SELECT COUNT(*) * 150 FROM users WHERE referred_by = (SELECT referral_code FROM users WHERE id = :user_id)) -
                        (SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE user_id = :user_id2 AND status = 'completed') as balance";
    $balance_stmt = $db->prepare($balance_query);
    $balance_stmt->bindParam(':user_id', $user_id);
    $balance_stmt->bindParam(':user_id2', $user_id);
    $balance_stmt->execute();
    $balance = $balance_stmt->fetch(PDO::FETCH_ASSOC);
    
    if($balance['balance'] < $amount) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Insufficient balance"]);
        exit();
    }
    
    if($amount < 100) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Minimum withdrawal amount is KES 100"]);
        exit();
    }
    
    $query = "INSERT INTO withdrawals (user_id, amount, method, phone, status) 
              VALUES (:user_id, :amount, :method, :phone, 'pending')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':amount', $amount);
    $stmt->bindParam(':method', $method);
    $stmt->bindParam(':phone', $phone);
    
    if($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Withdrawal request submitted! Admin will process within 24 hours."
        ]);
    } else {
        http_response_code(503);
        echo json_encode(["success" => false, "message" => "Unable to process withdrawal"]);
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
