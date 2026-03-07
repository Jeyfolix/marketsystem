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
error_log("Delete payment request: " . print_r($data, true));

if(!isset($data->id) || !isset($data->admin_id)) {
    http_response_code(400);
    echo json_encode([
        "success" => false, 
        "message" => "Payment ID and Admin ID required",
        "received" => $data
    ]);
    exit();
}

// Ensure IDs are integers
$payment_id = intval($data->id);
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
    
    // Check if payment exists
    $check_payment = $db->prepare("SELECT id FROM transactions WHERE id = ?");
    $check_payment->execute([$payment_id]);
    
    if($check_payment->rowCount() == 0) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Payment not found"]);
        exit();
    }
    
    // Delete the payment
    $delete_payment = $db->prepare("DELETE FROM transactions WHERE id = ?");
    $delete_payment->execute([$payment_id]);
    
    http_response_code(200);
    echo json_encode([
        "success" => true, 
        "message" => "Payment deleted successfully"
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
