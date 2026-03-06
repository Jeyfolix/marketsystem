<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database config
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate required fields
if(!isset($data->user_id) || !isset($data->phone) || !isset($data->email) || !isset($data->mpesa_code)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "All fields are required"]);
    exit();
}

$user_id = $data->user_id;
$phone = $data->phone;
$email = $data->email;
$mpesa_code = $data->mpesa_code;
$amount = 300; // Fixed amount

try {
    // Check if user exists
    $user_check = "SELECT id FROM users WHERE id = :user_id";
    $user_stmt = $db->prepare($user_check);
    $user_stmt->bindParam(':user_id', $user_id);
    $user_stmt->execute();
    
    if($user_stmt->rowCount() == 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "User not found"]);
        exit();
    }
    
    // Check if M-PESA code already exists
    $check_query = "SELECT id FROM transactions WHERE mpesa_code = :mpesa_code";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':mpesa_code', $mpesa_code);
    $check_stmt->execute();
    
    if($check_stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "This M-PESA code has already been used"]);
        exit();
    }
    
    // Insert transaction - matching your exact table columns
    $insert_query = "INSERT INTO transactions (user_id, phone, email, amount, mpesa_code, status, created_at) 
                     VALUES (:user_id, :phone, :email, :amount, :mpesa_code, 'pending', NOW())";
    
    $insert_stmt = $db->prepare($insert_query);
    $insert_stmt->bindParam(':user_id', $user_id);
    $insert_stmt->bindParam(':phone', $phone);
    $insert_stmt->bindParam(':email', $email);
    $insert_stmt->bindParam(':amount', $amount);
    $insert_stmt->bindParam(':mpesa_code', $mpesa_code);
    
    if($insert_stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "✅ Payment of KES 300 submitted successfully! Admin will verify within 24 hours."
        ]);
    } else {
        http_response_code(503);
        echo json_encode(["success" => false, "message" => "Unable to process payment. Please try again."]);
    }
    
} catch(PDOException $e) {
    // Log error but don't expose details to client
    error_log("Payment error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error occurred. Please try again later."
    ]);
}
?>
