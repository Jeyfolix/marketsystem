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
    // Get user's referral code
    $ref_code_query = "SELECT referral_code FROM users WHERE id = :user_id";
    $ref_code_stmt = $db->prepare($ref_code_query);
    $ref_code_stmt->bindParam(':user_id', $user_id);
    $ref_code_stmt->execute();
    $user = $ref_code_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get earnings from referrals (each referral = KES 150)
    $earnings_query = "SELECT u.id, u.name, u.username, u.email, u.created_at, 
                       '150' as commission, 'verified' as status
                       FROM users u 
                       WHERE u.referred_by = :referral_code
                       ORDER BY u.created_at DESC";
    $earnings_stmt = $db->prepare($earnings_query);
    $earnings_stmt->bindParam(':referral_code', $user['referral_code']);
    $earnings_stmt->execute();
    $earnings = $earnings_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total earnings
    $total_query = "SELECT COUNT(*) * 150 as total 
                    FROM users 
                    WHERE referred_by = :referral_code";
    $total_stmt = $db->prepare($total_query);
    $total_stmt->bindParam(':referral_code', $user['referral_code']);
    $total_stmt->execute();
    $total = $total_stmt->fetch(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "earnings" => $earnings,
        "total" => $total['total'] ? $total['total'] : 0
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
