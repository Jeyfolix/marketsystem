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
error_log("=== UPDATE PAYMENT STATUS REQUEST ===");
error_log("Raw input: " . file_get_contents("php://input"));
error_log("Decoded data: " . print_r($data, true));

if(!isset($data->payment_id) || !isset($data->status)) {
    http_response_code(400);
    echo json_encode([
        "success" => false, 
        "message" => "Payment ID and status required"
    ]);
    exit();
}

// ABSOLUTE FORCE: Get the raw value and ensure it's a pure integer
$raw_payment_id = $data->payment_id;

// Log what we received
error_log("Raw payment_id type: " . gettype($raw_payment_id));
error_log("Raw payment_id value: " . print_r($raw_payment_id, true));

// If it's an object or array, convert to string
if (is_object($raw_payment_id) || is_array($raw_payment_id)) {
    $raw_payment_id = json_encode($raw_payment_id);
}

// Convert to string and remove ALL non-numeric characters
$clean_id = preg_replace('/[^0-9]/', '', (string)$raw_payment_id);

// If we got nothing, try intval as last resort
if ($clean_id === '') {
    $clean_id = (string)intval($raw_payment_id);
}

// Convert to integer
$payment_id = intval($clean_id);

error_log("Cleaned payment_id: " . $payment_id);

if ($payment_id <= 0) {
    http_response_code(400);
    echo json_encode([
        "success" => false, 
        "message" => "Invalid payment ID format. Must be a positive number.",
        "received" => $raw_payment_id,
        "cleaned" => $clean_id
    ]);
    exit();
}

$status = $data->status;
$verified_by = isset($data->verified_by) ? $data->verified_by : '';

// Validate status against your database enum
$allowed_status = ['pending', 'verified', 'paid'];
if (!in_array($status, $allowed_status)) {
    http_response_code(400);
    echo json_encode([
        "success" => false, 
        "message" => "Invalid status value. Allowed: pending, verified, paid"
    ]);
    exit();
}

try {
    // METHOD 1: First try with prepared statement
    $query = "UPDATE transactions SET status = ?, verified_by = ?, verified_at = NOW() WHERE id = ?";
    $stmt = $db->prepare($query);
    
    // Bind with explicit types - use the integer directly
    $stmt->bindValue(1, $status, PDO::PARAM_STR);
    $stmt->bindValue(2, $verified_by, PDO::PARAM_STR);
    $stmt->bindValue(3, $payment_id, PDO::PARAM_INT);
    
    error_log("Executing query with payment_id: " . $payment_id . " (type: " . gettype($payment_id) . ")");
    
    if ($stmt->execute()) {
        $rowCount = $stmt->rowCount();
        error_log("Rows affected: " . $rowCount);
        
        if ($rowCount > 0) {
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Payment status updated successfully",
                "payment_id" => $payment_id
            ]);
        } else {
            // Check if payment exists
            $check = $db->prepare("SELECT id FROM transactions WHERE id = ?");
            $check->bindValue(1, $payment_id, PDO::PARAM_INT);
            $check->execute();
            
            if ($check->rowCount() > 0) {
                http_response_code(200);
                echo json_encode([
                    "success" => true,
                    "message" => "Payment status already set",
                    "payment_id" => $payment_id
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    "success" => false,
                    "message" => "Payment not found with ID: " . $payment_id
                ]);
            }
        }
    } else {
        $error = $stmt->errorInfo();
        error_log("SQL Error: " . print_r($error, true));
        
        // METHOD 2: If prepared statement fails, try direct SQL
        error_log("Trying direct SQL as fallback...");
        
        $direct_query = "UPDATE transactions SET status = '" . addslashes($status) . "', verified_by = '" . addslashes($verified_by) . "', verified_at = NOW() WHERE id = " . $payment_id;
        
        error_log("Direct query: " . $direct_query);
        
        $rows_affected = $db->exec($direct_query);
        
        if ($rows_affected !== false) {
            if ($rows_affected > 0) {
                http_response_code(200);
                echo json_encode([
                    "success" => true,
                    "message" => "Payment status updated successfully (via direct SQL)",
                    "payment_id" => $payment_id
                ]);
            } else {
                http_response_code(200);
                echo json_encode([
                    "success" => true,
                    "message" => "No changes made",
                    "payment_id" => $payment_id
                ]);
            }
        } else {
            $error2 = $db->errorInfo();
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Both methods failed. Error: " . $error2[2]
            ]);
        }
    }
    
} catch(PDOException $e) {
    error_log("PDO Exception: " . $e->getMessage());
    
    // METHOD 3: Last resort - try with CAST
    try {
        $cast_query = "UPDATE transactions SET status = :status, verified_by = :verified_by, verified_at = NOW() WHERE CAST(id AS UNSIGNED) = :id";
        $cast_stmt = $db->prepare($cast_query);
        $cast_stmt->bindValue(':id', $payment_id, PDO::PARAM_INT);
        $cast_stmt->bindValue(':status', $status);
        $cast_stmt->bindValue(':verified_by', $verified_by);
        
        if ($cast_stmt->execute()) {
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Payment status updated successfully (via CAST)",
                "payment_id" => $payment_id
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
    } catch(Exception $e2) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Database error: " . $e->getMessage()
        ]);
    }
}
?>
