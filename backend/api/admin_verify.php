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

if(!isset($data->transaction_id) || !isset($data->admin_id)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Transaction ID and Admin ID required"]);
    exit();
}

$transaction_id = $data->transaction_id;
$admin_id = $data->admin_id;

try {
    $db->beginTransaction();
    
    // Get transaction details
    $trans_query = "SELECT * FROM transactions WHERE id = :id AND status = 'pending'";
    $trans_stmt = $db->prepare($trans_query);
    $trans_stmt->bindParam(':id', $transaction_id);
    $trans_stmt->execute();
    
    if($trans_stmt->rowCount() == 0) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Transaction not found or already processed"]);
        exit();
    }
    
    $transaction = $trans_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Update transaction status
    $update_query = "UPDATE transactions SET status = 'verified', verified_by = :admin_id, verified_at = NOW() WHERE id = :id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':admin_id', $admin_id);
    $update_stmt->bindParam(':id', $transaction_id);
    $update_stmt->execute();
    
    // Check if user was referred
    $user_query = "SELECT referred_by FROM users WHERE id = :user_id";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->bindParam(':user_id', $transaction['user_id']);
    $user_stmt->execute();
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if($user && $user['referred_by']) {
        // Get referrer ID
        $referrer_query = "SELECT id FROM users WHERE referral_code = :code";
        $referrer_stmt = $db->prepare($referrer_query);
        $referrer_stmt->bindParam(':code', $user['referred_by']);
        $referrer_stmt->execute();
        
        if($referrer_stmt->rowCount() > 0) {
            $referrer = $referrer_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Insert into referrals table
            $ref_query = "INSERT INTO referrals (referrer_id, referred_id, commission, status) 
                         VALUES (:referrer_id, :referred_id, 150, 'verified')";
            $ref_stmt = $db->prepare($ref_query);
            $ref_stmt->bindParam(':referrer_id', $referrer['id']);
            $ref_stmt->bindParam(':referred_id', $transaction['user_id']);
            $ref_stmt->execute();
        }
    }
    
    $db->commit();
    
    http_response_code(200);
    echo json_encode(["success" => true, "message" => "Transaction verified successfully"]);
    
} catch(Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>
