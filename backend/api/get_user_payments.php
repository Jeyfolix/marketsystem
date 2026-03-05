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
    // Get user's payment status
    $status_query = "SELECT status FROM transactions WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1";
    $status_stmt = $db->prepare($status_query);
    $status_stmt->bindParam(':user_id', $user_id);
    $status_stmt->execute();
    $current_status = $status_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get all user's payments
    $payments_query = "SELECT * FROM transactions WHERE user_id = :user_id ORDER BY created_at DESC";
    $payments_stmt = $db->prepare($payments_query);
    $payments_stmt->bindParam(':user_id', $user_id);
    $payments_stmt->execute();
    $payments = $payments_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get referral info for each payment
    $referrals_query = "SELECT u.name, u.phone, u.email, u.referral_code 
                       FROM users u 
                       WHERE u.referred_by = (SELECT referral_code FROM users WHERE id = :user_id)";
    $referrals_stmt = $db->prepare($referrals_query);
    $referrals_stmt->bindParam(':user_id', $user_id);
    $referrals_stmt->execute();
    $referrals = $referrals_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "current_status" => $current_status ? $current_status['status'] : 'unpaid',
        "payments" => $payments,
        "referrals" => $referrals,
        "total_referrals" => count($referrals)
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
