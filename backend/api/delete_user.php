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

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if(!isset($data->id) || !isset($data->admin_id)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "User ID and Admin ID required"]);
    exit();
}

try {
    // Check if admin exists
    $check_admin = $db->prepare("SELECT id FROM users WHERE id = ? AND role = 'admin'");
    $check_admin->execute([$data->admin_id]);
    
    if($check_admin->rowCount() == 0) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Unauthorized"]);
        exit();
    }
    
    // First delete any transactions related to this user
    $delete_trans = $db->prepare("DELETE FROM transactions WHERE user_id = ?");
    $delete_trans->execute([$data->id]);
    
    // Then delete the user
    $delete_user = $db->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    $delete_user->execute([$data->id]);
    
    if($delete_user->rowCount() > 0) {
        http_response_code(200);
        echo json_encode(["success" => true, "message" => "User deleted successfully"]);
    } else {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "User not found or is an admin"]);
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
