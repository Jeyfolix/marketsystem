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
$user_id = isset($data->user_id) ? $data->user_id : 0;

if(empty($user_id)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "User ID required"]);
    exit();
}

try {
    // Get user data
    $user_query = "SELECT id, name, username, email, phone, country, referral_code, referred_by, role, created_at 
                   FROM users WHERE id = :user_id";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->bindParam(':user_id', $user_id);
    $user_stmt->execute();
    
    if($user_stmt->rowCount() == 0) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "User not found"]);
        exit();
    }
    
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get referral stats (users who used this user's referral code)
    $ref_stats_query = "SELECT 
                        COUNT(*) as total_referrals,
                        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as referrals_this_month,
                        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as referrals_today
                        FROM users 
                        WHERE referred_by = :referral_code";
    $ref_stats_stmt = $db->prepare($ref_stats_query);
    $ref_stats_stmt->bindParam(':referral_code', $user['referral_code']);
    $ref_stats_stmt->execute();
    $ref_stats = $ref_stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    $total_referrals = (int)$ref_stats['total_referrals'];
    $total_earnings = $total_referrals * 150;
    $earnings_this_month = (int)$ref_stats['referrals_this_month'] * 150;
    $earnings_today = (int)$ref_stats['referrals_today'] * 150;
    
    // Get total withdrawals
    $withdrawals_query = "SELECT COALESCE(SUM(amount), 0) as total_withdrawn 
                          FROM withdrawals 
                          WHERE user_id = :user_id AND status = 'completed'";
    $withdrawals_stmt = $db->prepare($withdrawals_query);
    $withdrawals_stmt->bindParam(':user_id', $user_id);
    $withdrawals_stmt->execute();
    $withdrawals = $withdrawals_stmt->fetch(PDO::FETCH_ASSOC);
    
    $available_balance = $total_earnings - $withdrawals['total_withdrawn'];
    if ($available_balance < 0) $available_balance = 0;
    
    // Determine rank based on referrals
    $rank = 'Starter';
    $rank_color = '#6B7280';
    if($total_referrals >= 50) {
        $rank = 'Platinum';
        $rank_color = '#E5E4E2';
    } elseif($total_referrals >= 30) {
        $rank = 'Gold';
        $rank_color = '#FFD700';
    } elseif($total_referrals >= 15) {
        $rank = 'Silver';
        $rank_color = '#C0C0C0';
    } elseif($total_referrals >= 5) {
        $rank = 'Bronze';
        $rank_color = '#CD7F32';
    }
    
    // Get recent referrals (last 5)
    $recent_query = "SELECT id, name, username, created_at 
                     FROM users 
                     WHERE referred_by = :referral_code
                     ORDER BY created_at DESC 
                     LIMIT 5";
    $recent_stmt = $db->prepare($recent_query);
    $recent_stmt->bindParam(':referral_code', $user['referral_code']);
    $recent_stmt->execute();
    $recent_referrals = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "user" => [
            "id" => $user['id'],
            "name" => $user['name'],
            "username" => $user['username'],
            "email" => $user['email'],
            "phone" => $user['phone'],
            "country" => $user['country'],
            "referral_code" => $user['referral_code'],
            "referred_by" => $user['referred_by'],
            "role" => $user['role'],
            "created_at" => $user['created_at']
        ],
        "stats" => [
            "total_referrals" => $total_referrals,
            "referrals_this_month" => (int)$ref_stats['referrals_this_month'],
            "referrals_today" => (int)$ref_stats['referrals_today'],
            "total_earnings" => $total_earnings,
            "earnings_this_month" => $earnings_this_month,
            "earnings_today" => $earnings_today,
            "available_balance" => $available_balance,
            "rank" => $rank,
            "rank_color" => $rank_color
        ],
        "recent_referrals" => $recent_referrals
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
