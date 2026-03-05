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

if(!isset($data->user_id)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "User ID required"]);
    exit();
}

$user_id = $data->user_id;

try {
    $payments_query = "SELECT id, user_id, phone, email, amount, mpesa_code, status, created_at 
                       FROM transactions 
                       WHERE user_id = :user_id 
                       ORDER BY created_at DESC";
    $payments_stmt = $db->prepare($payments_query);
    $payments_stmt->bindParam(':user_id', $user_id);
    $payments_stmt->execute();
    $payments = $payments_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $ref_code_query = "SELECT referral_code FROM users WHERE id = :user_id";
    $ref_code_stmt = $db->prepare($ref_code_query);
    $ref_code_stmt->bindParam(':user_id', $user_id);
    $ref_code_stmt->execute();
    $user = $ref_code_stmt->fetch(PDO::FETCH_ASSOC);
    
    $referrals_query = "SELECT id, name, username, email, phone, created_at 
                       FROM users 
                       WHERE referred_by = :referral_code
                       ORDER BY created_at DESC";
    $referrals_stmt = $db->prepare($referrals_query);
    $referrals_stmt->bindParam(':referral_code', $user['referral_code']);
    $referrals_stmt->execute();
    $referrals = $referrals_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "payments" => $payments,
        "referrals" => $referrals
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
