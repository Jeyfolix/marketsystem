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

// Handle preflight OPTIONS request
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

if(!isset($data->username) || !isset($data->password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Username and password required"]);
    exit();
}

$username = $data->username;
$password = $data->password;

try {
    // Query to get admin from users table where role = 'admin'
    $query = "SELECT id, name, username, email, phone, country, referral_code, role, created_at 
              FROM users 
              WHERE (username = :username OR email = :username) 
              AND role = 'admin' 
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // For demo purposes - plain text password comparison
        // In production, you should use password_verify() with hashed passwords
        // Get the stored password for this user
        $pass_query = "SELECT password FROM users WHERE id = :id";
        $pass_stmt = $db->prepare($pass_query);
        $pass_stmt->bindParam(':id', $user['id']);
        $pass_stmt->execute();
        $pass_result = $pass_stmt->fetch(PDO::FETCH_ASSOC);
        $stored_password = $pass_result['password'];
        
        // Compare passwords (plain text for demo)
        if($password === $stored_password) {
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "user" => [
                    "id" => $user['id'],
                    "name" => $user['name'],
                    "username" => $user['username'],
                    "email" => $user['email'],
                    "phone" => $user['phone'],
                    "country" => $user['country'],
                    "referral_code" => $user['referral_code'],
                    "role" => $user['role']
                ],
                "message" => "Admin login successful"
            ]);
        } else {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Invalid password"]);
        }
    } else {
        http_response_code(401);
        echo json_encode([
            "success" => false, 
            "message" => "No admin user found with these credentials. Please ensure you have an admin account with role='admin'"
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
