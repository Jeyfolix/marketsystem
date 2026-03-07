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
        "message" => "Payment ID and status required"
    ]);
    exit();
}

// AGGRESSIVE INTEGER CONVERSION - Remove any non-numeric characters
$payment_id = preg_replace('/[^0-9]/', '', $data->payment_id);
$payment_id = intval($payment_id);

if ($payment_id <= 0) {
    http_response_code(400);
    echo json_encode([
        "success" => false, 
        "message" => "Invalid payment ID format - must be a positive number"
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
    // First check if payment exists using direct integer in query
    $check_query = "SELECT id FROM transactions WHERE id = " . $payment_id;
    $check_stmt = $db->query($check_query);
    
    if($check_stmt->rowCount() == 0) {
        http_response_code(404);
        echo json_encode([
            "success" => false, 
            "message" => "Payment not found with ID: " . $payment_id
        ]);
        exit();
    }
    
    // DIRECT STRING CONCATENATION - Avoid parameter binding issues
    $query = "UPDATE transactions 
              SET status = '" . $status . "', 
                  verified_by = '" . $verified_by . "', 
                  verified_at = NOW() 
              WHERE id = " . $payment_id;
    
    error_log("Executing query: " . $query);
    
    if($db->exec($query) !== false) {
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Payment status updated successfully",
            "payment_id" => $payment_id
        ]);
    } else {
        $error = $db->errorInfo();
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
