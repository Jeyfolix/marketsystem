<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';
include_once '../includes/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$code = isset($_GET['code']) ? $_GET['code'] : '';

if(!empty($code)) {
    $referrer = $user->getReferralByCode($code);
    
    if($referrer) {
        echo json_encode([
            "success" => true,
            "referrer" => $referrer['username']
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Invalid referral code"
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "No referral code provided"
    ]);
}
?>
