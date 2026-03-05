<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Simple error reporting - turn off for production
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    include_once '../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    $data = json_decode(file_get_contents("php://input"));
    $user_id = isset($data->user_id) ? (int)$data->user_id : 0;
    
    if ($user_id === 0) {
        echo json_encode(["success" => false, "message" => "User ID required"]);
        exit;
    }
    
    // Get user data
    $user_query = "SELECT id, name, username, email, phone, country, referral_code, referred_by, role, created_at 
                   FROM users WHERE id = ?";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(["success" => false, "message" => "User not found"]);
        exit;
    }
    
    // Count total referrals (users who used this user's referral code)
    $ref_query = "SELECT COUNT(*) as total FROM users WHERE referred_by = ?";
    $ref_stmt = $db->prepare($ref_query);
    $ref_stmt->execute([$user['referral_code']]);
    $ref_count = $ref_stmt->fetch(PDO::FETCH_ASSOC);
    $total_referrals = (int)$ref_count['total'];
    
    // Calculate earnings (KES 150 per referral)
    $total_earnings = $total_referrals * 150;
    
    // Count referrals this month
    $month_query = "SELECT COUNT(*) as total FROM users 
                    WHERE referred_by = ? 
                    AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
                    AND YEAR(created_at) = YEAR(CURRENT_DATE())";
    $month_stmt = $db->prepare($month_query);
    $month_stmt->execute([$user['referral_code']]);
    $month_count = $month_stmt->fetch(PDO::FETCH_ASSOC);
    $referrals_this_month = (int)$month_count['total'];
    
    // Count referrals today
    $today_query = "SELECT COUNT(*) as total FROM users 
                    WHERE referred_by = ? 
                    AND DATE(created_at) = CURDATE()";
    $today_stmt = $db->prepare($today_query);
    $today_stmt->execute([$user['referral_code']]);
    $today_count = $today_stmt->fetch(PDO::FETCH_ASSOC);
    $referrals_today = (int)$today_count['total'];
    
    // Get withdrawals if table exists
    $withdrawn = 0;
    try {
        $withdraw_query = "SELECT COALESCE(SUM(amount), 0) as total FROM withdrawals WHERE user_id = ? AND status = 'completed'";
        $withdraw_stmt = $db->prepare($withdraw_query);
        $withdraw_stmt->execute([$user_id]);
        $withdraw_result = $withdraw_stmt->fetch(PDO::FETCH_ASSOC);
        $withdrawn = (int)$withdraw_result['total'];
    } catch (Exception $e) {
        // Withdrawals table might not exist
        $withdrawn = 0;
    }
    
    $available_balance = $total_earnings - $withdrawn;
    if ($available_balance < 0) $available_balance = 0;
    
    // Get recent referrals (last 5)
    $recent_query = "SELECT id, name, username, created_at 
                     FROM users 
                     WHERE referred_by = ?
                     ORDER BY created_at DESC 
                     LIMIT 5";
    $recent_stmt = $db->prepare($recent_query);
    $recent_stmt->execute([$user['referral_code']]);
    $recent_referrals = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Determine rank
    $rank = 'Starter';
    $rank_color = '#6B7280';
    if ($total_referrals >= 50) {
        $rank = 'Platinum';
        $rank_color = '#E5E4E2';
    } elseif ($total_referrals >= 30) {
        $rank = 'Gold';
        $rank_color = '#FFD700';
    } elseif ($total_referrals >= 15) {
        $rank = 'Silver';
        $rank_color = '#C0C0C0';
    } elseif ($total_referrals >= 5) {
        $rank = 'Bronze';
        $rank_color = '#CD7F32';
    }
    
    // Return success response
    echo json_encode([
        "success" => true,
        "user" => $user,
        "stats" => [
            "total_referrals" => $total_referrals,
            "referrals_this_month" => $referrals_this_month,
            "referrals_today" => $referrals_today,
            "total_earnings" => $total_earnings,
            "earnings_this_month" => $referrals_this_month * 150,
            "earnings_today" => $referrals_today * 150,
            "available_balance" => $available_balance,
            "rank" => $rank,
            "rank_color" => $rank_color
        ],
        "recent_referrals" => $recent_referrals
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>
