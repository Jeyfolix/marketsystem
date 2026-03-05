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
    // Get user data for welcome banner
    $query = "SELECT id, name, username, email, created_at 
              FROM users 
              WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if($stmt->rowCount() == 0) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "User not found"]);
        exit();
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get today's stats for welcome message
    $stats_query = "SELECT 
                    COUNT(*) as total_referrals,
                    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as referrals_today
                    FROM users 
                    WHERE referred_by = (SELECT referral_code FROM users WHERE id = :user_id)";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->bindParam(':user_id', $user_id);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Generate personalized welcome message
    $hour = (int)date('H');
    $greeting = '';
    if ($hour < 12) {
        $greeting = 'Good morning';
    } elseif ($hour < 17) {
        $greeting = 'Good afternoon';
    } else {
        $greeting = 'Good evening';
    }
    
    $firstName = explode(' ', $user['name'])[0];
    
    $message = $greeting . ', ' . $firstName . '! 🚀';
    $subtext = 'Share your referral code and start earning today!';
    
    if ($stats['referrals_today'] > 0) {
        $subtext = 'You have ' . $stats['referrals_today'] . ' new referral' . ($stats['referrals_today'] > 1 ? 's' : '') . ' today! Keep up the great work!';
    }
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "welcome" => [
            "message" => $message,
            "subtext" => $subtext,
            "name" => $user['name'],
            "username" => $user['username'],
            "email" => $user['email'],
            "member_since" => date('F j, Y', strtotime($user['created_at'])),
            "referrals_today" => (int)$stats['referrals_today'],
            "total_referrals" => (int)$stats['total_referrals']
        ]
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
