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

// Log received data for debugging
error_log("Delete user request: " . print_r($data, true));

if(!isset($data->id) || !isset($data->admin_id)) {
    http_response_code(400);
    echo json_encode([
        "success" => false, 
        "message" => "User ID and Admin ID required",
        "received" => $data
    ]);
    exit();
}

// Ensure IDs are integers
$user_id = intval($data->id);
$admin_id = intval($data->admin_id);

try {
    // Check if admin exists
    $check_admin = $db->prepare("SELECT id FROM users WHERE id = ? AND role = 'admin'");
    $check_admin->execute([$admin_id]);
    
    if($check_admin->rowCount() == 0) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Unauthorized: Admin not found"]);
        exit();
    }
    
    // Check if user exists and is not admin
    $check_user = $db->prepare("SELECT role FROM users WHERE id = ?");
    $check_user->execute([$user_id]);
    $user = $check_user->fetch(PDO::FETCH_ASSOC);
    
    if(!$user) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "User not found"]);
        exit();
    }
    
    if($user['role'] === 'admin') {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Cannot delete admin users"]);
        exit();
    }
    
    // Start transaction
    $db->beginTransaction();
    
    // First delete any transactions related to this user
    $delete_trans = $db->prepare("DELETE FROM transactions WHERE user_id = ?");
    $delete_trans->execute([$user_id]);
    
    // Then delete the user
    $delete_user = $db->prepare("DELETE FROM users WHERE id = ?");
    $delete_user->execute([$user_id]);
    
    $db->commit();
    
    http_response_code(200);
    echo json_encode([
        "success" => true, 
        "message" => "User and related transactions deleted successfully"
    ]);
    
} catch(PDOException $e) {
    // Rollback transaction on error
    if($db->inTransaction()) {
        $db->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
