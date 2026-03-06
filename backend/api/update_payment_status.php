<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if(!isset($data->payment_id) || !isset($data->status)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Payment ID and status required"]);
    exit();
}

try {
    $query = "UPDATE transactions 
              SET status = :status, 
                  verified_by = :verified_by, 
                  verified_at = NOW() 
              WHERE id = :payment_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':payment_id', $data->payment_id);
    $stmt->bindParam(':status', $data->status);
    $stmt->bindParam(':verified_by', $data->verified_by);
    
    if($stmt->execute()) {
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Payment status updated successfully"
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Failed to update payment"]);
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
