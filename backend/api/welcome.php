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
    $query = "SELECT id, name, username, created_at 
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
    
    // Get today's referrals count
    $today_query = "SELECT COUNT(*) as count 
                    FROM users 
                    WHERE referred_by = (SELECT referral_code FROM users WHERE id = :user_id)
                    AND DATE(created_at) = CURDATE()";
    $today_stmt = $db->prepare($today_query);
    $today_stmt->bindParam(':user_id', $user_id);
    $today_stmt->execute();
    $today = $today_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Generate greeting based on time
    $hour = (int)date('H');
    if ($hour < 12) {
        $greeting = 'Good morning';
    } elseif ($hour < 17) {
        $greeting = 'Good afternoon';
    } else {
        $greeting = 'Good evening';
    }
    
    $firstName = explode(' ', $user['name'])[0];
    $message = $greeting . ', ' . $firstName . '! 🚀';
    
    if ($today['count'] > 0) {
        $subtext = 'You have ' . $today['count'] . ' new referral' . ($today['count'] > 1 ? 's' : '') . ' today! Keep up the great work!';
    } else {
        $subtext = 'Share your referral code and start earning today!';
    }
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "welcome" => [
            "message" => $message,
            "subtext" => $subtext
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
