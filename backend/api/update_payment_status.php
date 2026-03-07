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

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Log received data for debugging
error_log("Update payment status request: " . print_r($data, true));

if(!isset($data->payment_id) || !isset($data->status)) {
    http_response_code(400);
    echo json_encode([
        "success" => false, 
        "message" => "Payment ID and status required",
        "received" => $data
    ]);
    exit();
}

// CRITICAL FIX: Force convert to integer and remove any quotes
$payment_id = intval($data->payment_id);

// Double-check that we have a valid integer
if ($payment_id === 0 && $data->payment_id !== "0") {
    http_response_code(400);
    echo json_encode([
        "success" => false, 
        "message" => "Invalid payment ID format - must be a number"
    ]);
    exit();
}

$status = $data->status;
$verified_by = isset($data->verified_by) ? $data->verified_by : null;

// Validate status
$allowed_status = ['pending', 'verified', 'unpaid'];
if (!in_array($status, $allowed_status)) {
    http_response_code(400);
    echo json_encode([
        "success" => false, 
        "message" => "Invalid status value"
    ]);
    exit();
}

try {
    // First check if payment exists
    $check_query = "SELECT id FROM transactions WHERE id = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$payment_id]);
    
    if($check_stmt->rowCount() == 0) {
        http_response_code(404);
        echo json_encode([
            "success" => false, 
            "message" => "Payment not found with ID: " . $payment_id
        ]);
        exit();
    }
    
    // Update payment status - using direct value binding
    $query = "UPDATE transactions 
              SET status = ?, 
                  verified_by = ?, 
                  verified_at = NOW() 
              WHERE id = ?";
    
    $stmt = $db->prepare($query);
    
    // Bind parameters - order matters
    $stmt->bindParam(1, $status);
    $stmt->bindParam(2, $verified_by);
    $stmt->bindParam(3, $payment_id, PDO::PARAM_INT);
    
    if($stmt->execute()) {
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Payment status updated successfully",
            "payment_id" => $payment_id
        ]);
    } else {
        $error = $stmt->errorInfo();
        http_response_code(500);
        echo json_encode([
            "success" => false, 
            "message" => "Failed to update payment: " . $error[2]
        ]);
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
