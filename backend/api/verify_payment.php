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

if(!isset($data->user_id) || !isset($data->phone) || !isset($data->email) || !isset($data->mpesa_code)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "All fields are required"]);
    exit();
}

$user_id = $data->user_id;
$phone = $data->phone;
$email = $data->email;
$mpesa_code = $data->mpesa_code;
$amount = isset($data->amount) ? $data->amount : 300;

try {
    // Check if M-PESA code already exists
    $check_query = "SELECT id FROM transactions WHERE mpesa_code = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$mpesa_code]);
    
    if($check_stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "This M-PESA code has already been used"]);
        exit();
    }
    
    // Insert transaction
    $insert_query = "INSERT INTO transactions (user_id, phone, email, mpesa_code, amount, status) 
                     VALUES (?, ?, ?, ?, ?, 'pending')";
    $insert_stmt = $db->prepare($insert_query);
    
    if($insert_stmt->execute([$user_id, $phone, $email, $mpesa_code, $amount])) {
        
        // Get user's name for response
        $user_query = "SELECT name, username FROM users WHERE id = ?";
        $user_stmt = $db->prepare($user_query);
        $user_stmt->execute([$user_id]);
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Payment submitted successfully! Admin will verify within 24 hours.",
            "transaction" => [
                "user_id" => $user_id,
                "name" => $user ? $user['name'] : '',
                "phone" => $phone,
                "email" => $email,
                "mpesa_code" => $mpesa_code,
                "amount" => $amount,
                "status" => "pending"
            ]
        ]);
    } else {
        http_response_code(503);
        echo json_encode(["success" => false, "message" => "Unable to process payment"]);
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
