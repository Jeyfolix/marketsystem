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

// IMPORTANT: Use MySQLi instead of PDO
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Log received data
error_log("=== UPDATE PAYMENT STATUS REQUEST ===");
error_log("Raw input: " . file_get_contents("php://input"));

if(!isset($data->payment_id) || !isset($data->status)) {
    http_response_code(400);
    echo json_encode([
        "success" => false, 
        "message" => "Payment ID and status required"
    ]);
    exit();
}

// FORCE INTEGER CONVERSION - Multiple methods
$payment_id = $data->payment_id;

// If it's a string, clean it
if (is_string($payment_id)) {
    $payment_id = trim($payment_id, '"\'');
}

// Force to integer
$payment_id = (int)$payment_id;

if ($payment_id <= 0) {
    http_response_code(400);
    echo json_encode([
        "success" => false, 
        "message" => "Invalid payment ID format"
    ]);
    exit();
}

$status = $data->status;
$verified_by = isset($data->verified_by) ? $data->verified_by : '';

// Validate status
$allowed_status = ['pending', 'verified', 'paid'];
if (!in_array($status, $allowed_status)) {
    http_response_code(400);
    echo json_encode([
        "success" => false, 
        "message" => "Invalid status value"
    ]);
    exit();
}

try {
    // METHOD 1: Check if using MySQLi (assuming getConnection returns MySQLi)
    if ($db instanceof mysqli) {
        error_log("Using MySQLi connection");
        
        // Prepare statement with MySQLi
        $query = "UPDATE transactions SET status = ?, verified_by = ?, verified_at = NOW() WHERE id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt) {
            // Bind parameters - note the 'i' for integer
            $stmt->bind_param("ssi", $status, $verified_by, $payment_id);
            
            error_log("Executing with payment_id: " . $payment_id);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    http_response_code(200);
                    echo json_encode([
                        "success" => true,
                        "message" => "Payment status updated successfully",
                        "payment_id" => $payment_id
                    ]);
                } else {
                    // Check if payment exists
                    $check = $db->prepare("SELECT id FROM transactions WHERE id = ?");
                    $check->bind_param("i", $payment_id);
                    $check->execute();
                    $result = $check->get_result();
                    
                    if ($result->num_rows > 0) {
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
                error_log("MySQLi execute error: " . $stmt->error);
                http_response_code(500);
                echo json_encode([
                    "success" => false,
                    "message" => "Database error: " . $stmt->error
                ]);
            }
            $stmt->close();
        } else {
            error_log("MySQLi prepare error: " . $db->error);
            
            // METHOD 2: Direct query as fallback
            $direct_query = "UPDATE transactions SET status = '" . $db->real_escape_string($status) . "', verified_by = '" . $db->real_escape_string($verified_by) . "', verified_at = NOW() WHERE id = " . $payment_id;
            
            error_log("Direct query: " . $direct_query);
            
            if ($db->query($direct_query)) {
                if ($db->affected_rows > 0) {
                    http_response_code(200);
                    echo json_encode([
                        "success" => true,
                        "message" => "Payment status updated successfully",
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
                http_response_code(500);
                echo json_encode([
                    "success" => false,
                    "message" => "Database error: " . $db->error
                ]);
            }
        }
    } 
    // METHOD 3: If using PDO, try with different approach
    else {
        error_log("Using PDO connection");
        
        // Try with quote method
        $query = "UPDATE transactions SET status = " . $db->quote($status) . ", verified_by = " . $db->quote($verified_by) . ", verified_at = NOW() WHERE id = " . $payment_id;
        
        error_log("PDO quote query: " . $query);
        
        $stmt = $db->exec($query);
        
        if ($stmt !== false) {
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Payment status updated successfully",
                "payment_id" => $payment_id
            ]);
        } else {
            $error = $db->errorInfo();
            error_log("PDO error: " . print_r($error, true));
            
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Database error: " . $error[2]
            ]);
        }
    }
    
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}

// Close connection if needed
if (isset($db) && $db instanceof mysqli) {
    $db->close();
}
?>
