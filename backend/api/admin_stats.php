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

error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    $stats = [];
    
    // Total users
    $query = "SELECT COUNT(*) as total FROM users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['totalUsers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // New users today
    $query = "SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['newUsersToday'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total payments
    $query = "SELECT COUNT(*) as total FROM transactions";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['totalPayments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Pending payments
    $query = "SELECT COUNT(*) as total FROM transactions WHERE status = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['pendingPayments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Verified payments
    $query = "SELECT COUNT(*) as total FROM transactions WHERE status = 'verified'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['verifiedPayments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total revenue
    $query = "SELECT SUM(amount) as total FROM transactions WHERE status = 'verified'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['totalRevenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "stats" => $stats
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
