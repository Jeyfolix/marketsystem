<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../includes/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$data = json_decode(file_get_contents("php://input"));

if(!empty($data->name) && !empty($data->username) && !empty($data->email) && !empty($data->phone) && !empty($data->country) && !empty($data->password)) {
    
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
    if($user->checkPhone()) {
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
