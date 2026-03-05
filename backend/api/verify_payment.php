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

try {
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
    
    // Insert transaction
    $query = "INSERT INTO transactions (user_id, phone, email, mpesa_code, amount, status) 
              VALUES (:user_id, :phone, :email, :mpesa_code, 300, 'pending')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':mpesa_code', $mpesa_code);
    
    if($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Payment verification submitted! Admin will verify within 24 hours."
        ]);
    } else {
        http_response_code(503);
        echo json_encode(["success" => false, "message" => "Unable to process payment"]);
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
