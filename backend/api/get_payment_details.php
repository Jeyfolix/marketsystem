<?php
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
