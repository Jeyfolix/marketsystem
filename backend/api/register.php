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
$user = new User($db);

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate required fields
if(
    !empty($data->name) && 
    !empty($data->username) && 
    !empty($data->email) && 
    !empty($data->phone) && 
    !empty($data->country) && 
    !empty($data->password)
) {
    
    $user->name = $data->name;
    $user->username = $data->username;
    $user->email = $data->email;
    $user->phone = $data->phone;
    $user->country = $data->country;
    $user->password = $data->password;
    $user->referred_by = !empty($data->referral_code) ? $data->referral_code : null;

    // Check if referral code exists
    if(!empty($user->referred_by)) {
        $referrer = $user->getReferralByCode($user->referred_by);
        if(!$referrer) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Invalid referral code!"]);
            exit();
        }
    }

    // Check if username exists
    if($user->checkUsername()) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Username already exists!"]);
        exit();
    }

    // Check if email exists
    if($user->emailExists()) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Email already registered!"]);
        exit();
    }

    // Check if phone exists
    if(method_exists($user, 'checkPhone') && $user->checkPhone()) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Phone number already registered!"]);
        exit();
    }

    if($user->create()) {
        http_response_code(201);
        echo json_encode([
            "success" => true, 
            "message" => "Registration successful!",
            "referral_code" => $user->referral_code
        ]);
    } else {
        http_response_code(503);
        echo json_encode(["success" => false, "message" => "Registration failed!"]);
    }
} else {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "All fields are required!"]);
}
?>
