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

if(!empty($data->name) && !empty($data->username) && !empty($data->email) && !empty($data->password)) {
    
    $user->name = $data->name;
    $user->username = $data->username;
    $user->email = $data->email;
    $user->password = $data->password;

    if($user->checkUsername()) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Username already exists!"]);
        exit();
    }

    if($user->emailExists()) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Email already registered!"]);
        exit();
    }

    if($user->create()) {
        http_response_code(201);
        echo json_encode(["success" => true, "message" => "Registration successful!"]);
    } else {
        http_response_code(503);
        echo json_encode(["success" => false, "message" => "Registration failed!"]);
    }
} else {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Incomplete data!"]);
}
?>
