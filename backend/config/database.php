<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

class Database {
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        // marketSystem database configuration
        $this->host = getenv('DB_HOST') ?: "gateway01.eu-central-1.prod.aws.tidbcloud.com";
        $this->port = getenv('DB_PORT') ?: "4000";
        $this->db_name = getenv('DB_NAME') ?: "affiliatepro"; // Your TiDB database name
        $this->username = getenv('DB_USER') ?: "3pMRcvLxZEGwhfN.root";
        $this->password = getenv('DB_PASSWORD') ?: "tPb6oe5MYUtDMyJ6";
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt',
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            $this->conn->exec("set names utf8mb4");
            
        } catch(PDOException $exception) {
            error_log("marketSystem Connection error: " . $exception->getMessage());
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "marketSystem Database connection failed: " . $exception->getMessage()
            ]);
            exit();
        }

        return $this->conn;
    }
}
?>
