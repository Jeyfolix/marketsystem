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
    // Get earnings from referrals
    $query = "SELECT r.*, u.name as referred_name, u.username as referred_username, u.email as referred_email 
              FROM referrals r 
              JOIN users u ON r.referred_id = u.id 
              WHERE r.referrer_id = :user_id 
              ORDER BY r.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $earnings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total earnings
    $total_query = "SELECT COALESCE(SUM(commission), 0) as total FROM referrals WHERE referrer_id = :user_id AND status = 'verified'";
    $total_stmt = $db->prepare($total_query);
    $total_stmt->bindParam(':user_id', $user_id);
    $total_stmt->execute();
    $total = $total_stmt->fetch(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "earnings" => $earnings,
        "total" => $total['total']
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error"]);
}
?>
