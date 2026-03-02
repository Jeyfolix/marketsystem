<?php
// Allow from any origin
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
} else {
    header("Access-Control-Allow-Origin: *");
}

// Handle OPTIONS preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    
    exit(0);
}

header("Content-Type: application/json; charset=UTF-8");

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
