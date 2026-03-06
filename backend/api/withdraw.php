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
$account_number = isset($data->account_number) ? $data->account_number : null;

try {
    // Check if user has sufficient balance (total verified payments)
    $balance_query = "SELECT COALESCE(SUM(amount), 0) as total 
                      FROM transactions 
                      WHERE user_id = ? AND status = 'verified'";
    $balance_stmt = $db->prepare($balance_query);
    $balance_stmt->execute([$user_id]);
    $balance = $balance_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check existing withdrawals
    $withdrawn_query = "SELECT COALESCE(SUM(amount), 0) as total 
                        FROM withdrawals 
                        WHERE user_id = ? AND status IN ('pending', 'completed')";
    $withdrawn_stmt = $db->prepare($withdrawn_query);
    $withdrawn_stmt->execute([$user_id]);
    $withdrawn = $withdrawn_stmt->fetch(PDO::FETCH_ASSOC);
    
    $available = $balance['total'] - $withdrawn['total'];
    
    if($available < $amount) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Insufficient balance"]);
        exit();
    }
    
    if($amount < 100) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Minimum withdrawal amount is KES 100"]);
        exit();
    }
    
    // Insert withdrawal
    $insert_query = "INSERT INTO withdrawals (user_id, amount, method, phone, account_number, status) 
                     VALUES (?, ?, ?, ?, ?, 'pending')";
    $insert_stmt = $db->prepare($insert_query);
    
    if($insert_stmt->execute([$user_id, $amount, $method, $phone, $account_number])) {
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Withdrawal request submitted successfully! Admin will process within 24 hours.",
            "withdrawal" => [
                "user_id" => $user_id,
                "amount" => $amount,
                "method" => $method,
                "phone" => $phone,
                "status" => "pending"
            ]
        ]);
    } else {
        http_response_code(503);
        echo json_encode(["success" => false, "message" => "Unable to process withdrawal"]);
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
