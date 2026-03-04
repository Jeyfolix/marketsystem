<?php
// Allow specific origin - GitHub Pages
$allowed_origins = [
    'https://jeyfolix.github.io',
    'http://localhost',
    'http://localhost:8080',
    'http://localhost:8081'
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
} else {
    header("Access-Control-Allow-Origin: *");
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 86400");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json; charset=UTF-8");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../config/database.php';
include_once '../includes/User.php';

$database = new Database();
$db = $database->getConnection();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if(!isset($data->user_id) || empty($data->user_id)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "User ID required"]);
    exit();
}

$user_id = $data->user_id;

try {
    // Get user data
    $query = "SELECT id, name, username, email, phone, country, referral_code, referred_by, role, created_at 
              FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if($stmt->rowCount() == 0) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "User not found"]);
        exit();
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get referral stats
    $ref_query = "SELECT 
                    COUNT(*) as total_referrals,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as referrals_this_month,
                    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as referrals_today
                  FROM users 
                  WHERE referred_by = (SELECT referral_code FROM users WHERE id = :user_id)";
    $ref_stmt = $db->prepare($ref_query);
    $ref_stmt->bindParam(':user_id', $user_id);
    $ref_stmt->execute();
    $ref_stats = $ref_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent referrals
    $recent_query = "SELECT id, name, username, created_at 
                     FROM users 
                     WHERE referred_by = (SELECT referral_code FROM users WHERE id = :user_id)
                     ORDER BY created_at DESC 
                     LIMIT 10";
    $recent_stmt = $db->prepare($recent_query);
    $recent_stmt->bindParam(':user_id', $user_id);
    $recent_stmt->execute();
    $recent_referrals = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate earnings (KES 150 per referral)
    $total_earnings = $ref_stats['total_referrals'] * 150;
    $earnings_this_month = $ref_stats['referrals_this_month'] * 150;
    $earnings_today = $ref_stats['referrals_today'] * 150;
    $available_balance = $total_earnings; // Subtract withdrawals if you have that table
    
    // Determine rank based on referrals
    $rank = 'Starter';
    $rank_color = '#6B7280';
    $referral_count = $ref_stats['total_referrals'];
    
    if($referral_count >= 50) {
        $rank = 'Platinum';
        $rank_color = '#E5E4E2';
    } elseif($referral_count >= 30) {
        $rank = 'Gold';
        $rank_color = '#FFD700';
    } elseif($referral_count >= 15) {
        $rank = 'Silver';
        $rank_color = '#C0C0C0';
    } elseif($referral_count >= 5) {
        $rank = 'Bronze';
        $rank_color = '#CD7F32';
    }
    
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
            "total_referrals" => (int)$ref_stats['total_referrals'],
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
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
