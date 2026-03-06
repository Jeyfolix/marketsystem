<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Complex query to combine users and transactions with referral information
    $query = "SELECT 
                u.id,
                u.name,
                u.referral_code,
                u.referred_by as referred_by_code,
                u.phone,
                t.id as payment_id,
                t.amount,
                t.mpesa_code,
                t.status,
                t.verified_by,
                t.created_at as payment_date,
                (SELECT phone FROM users WHERE referral_code = u.referred_by LIMIT 1) as referred_by_phone
              FROM users u
              LEFT JOIN transactions t ON u.id = t.user_id
              WHERE u.role != 'admin'
              ORDER BY u.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "data" => $results
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
