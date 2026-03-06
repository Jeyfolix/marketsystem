<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if(!isset($data->payment_id)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Payment ID required"]);
    exit();
}

try {
    $query = "SELECT t.*, u.name, u.email, u.phone 
              FROM transactions t
              LEFT JOIN users u ON t.user_id = u.id
              WHERE t.id = :payment_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':payment_id', $data->payment_id);
    $stmt->execute();
    
    if($stmt->rowCount() > 0) {
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "payment" => $payment
        ]);
    } else {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Payment not found"]);
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
