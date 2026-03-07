<?php
header("Content-Type: application/json; charset=UTF-8"); 
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Check transactions table structure
    $query = "DESCRIBE transactions";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "structure" => $columns], JSON_PRETTY_PRINT);
} catch(PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
