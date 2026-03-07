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

// Ensure payment_id is integer - CRITICAL FIX
$payment_id = filter_var($data->payment_id, FILTER_VALIDATE_INT);
if ($payment_id === false || $payment_id <= 0) {
    http_response_code(400);
    echo json_encode([
        "success" => false, 
        "message" => "Invalid payment ID format"
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
    // Check if payment exists
    $check_query = "SELECT id FROM transactions WHERE id = :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':id', $payment_id, PDO::PARAM_INT);
    $check_stmt->execute();
    
    if($check_stmt->rowCount() == 0) {
        http_response_code(404);
        echo json_encode([
            "success" => false, 
            "message" => "Payment not found"
        ]);
        exit();
    }
    
    // Update payment status
    $query = "UPDATE transactions 
              SET status = :status, 
                  verified_by = :verified_by, 
                  verified_at = NOW() 
              WHERE id = :payment_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':payment_id', $payment_id, PDO::PARAM_INT);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':verified_by', $verified_by);
    
    if($stmt->execute()) {
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Payment status updated successfully"
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
