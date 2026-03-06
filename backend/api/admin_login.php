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

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->username) && !empty($data->password)) {
    
    $username = $data->username;
    
    // Query to get admin from users table where role = 'admin'
    $query = "SELECT id, name, username, email, phone, country, referral_code, role, password, created_at 
              FROM users 
              WHERE (username = :username OR email = :username) 
              AND role = 'admin' 
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Use password_verify() like the user login does
        if(password_verify($data->password, $user['password'])) {
            
            // Remove password before sending
            unset($user['password']);
            
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Admin login successful",
                "user" => [
                    "id" => $user['id'],
                    "name" => $user['name'],
                    "username" => $user['username'],
                    "email" => $user['email'],
                    "phone" => $user['phone'],
                    "country" => $user['country'],
                    "referral_code" => $user['referral_code'],
                    "role" => $user['role'],
                    "created_at" => $user['created_at']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Invalid password!"]);
        }
    } else {
        http_response_code(401);
        echo json_encode([
            "success" => false, 
            "message" => "Admin user not found! User must have role='admin'"
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Username and password required!"]);
}
?>
