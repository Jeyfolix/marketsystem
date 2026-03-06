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

$database = new Database();
$db = $database->getConnection();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if(!isset($data->user_id)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "User ID required"]);
    exit();
}

$user_id = $data->user_id;

try {
    // Get user's referral code
    $user_query = "SELECT referral_code, name, username, email FROM users WHERE id = :user_id";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->bindParam(':user_id', $user_id);
    $user_stmt->execute();
    
    if($user_stmt->rowCount() == 0) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "User not found"]);
        exit();
    }
    
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get all referrals (people who used this user's referral code)
    $referrals_query = "SELECT id, name, username, email, phone, created_at 
                       FROM users 
                       WHERE referred_by = :referral_code
                       ORDER BY created_at DESC";
    $referrals_stmt = $db->prepare($referrals_query);
    $referrals_stmt->bindParam(':referral_code', $user['referral_code']);
    $referrals_stmt->execute();
    $referrals = $referrals_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all payments for this user and their referrals
    $payments_query = "SELECT * FROM transactions WHERE user_id = :user_id OR user_id IN (
                        SELECT id FROM users WHERE referred_by = :referral_code2
                      ) ORDER BY created_at DESC";
    $payments_stmt = $db->prepare($payments_query);
    $payments_stmt->bindParam(':user_id', $user_id);
    $payments_stmt->bindParam(':referral_code2', $user['referral_code']);
    $payments_stmt->execute();
    $payments = $payments_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "user" => $user,
        "referral_code" => $user['referral_code'],
        "referrals" => $referrals,
        "payments" => $payments
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
