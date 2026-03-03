<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../includes/User.php';

class Dashboard {
    private $conn;
    private $user_id;

    public function __construct($db, $user_id) {
        $this->conn = $db;
        $this->user_id = $user_id;
    }

    public function getUserData() {
        $query = "SELECT id, name, username, email, phone, country, referral_code, referred_by, role, created_at 
                  FROM users WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }

    public function getReferralStats() {
        $query = "SELECT 
                    COUNT(*) as total_referrals,
                    COALESCE(SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END), 0) as referrals_this_month,
                    COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END), 0) as referrals_today
                  FROM users WHERE referred_by = (SELECT referral_code FROM users WHERE id = :user_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getEarnings() {
        $query = "SELECT 
                    COALESCE(COUNT(*) * 150, 0) as total_earnings,
                    COALESCE(SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 150 ELSE 0 END), 0) as earnings_this_month,
                    COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 150 ELSE 0 END), 0) as earnings_today
                  FROM users WHERE referred_by = (SELECT referral_code FROM users WHERE id = :user_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getBalance() {
        $query = "SELECT COALESCE(COUNT(*) * 150, 0) as balance 
                  FROM users 
                  WHERE referred_by = (SELECT referral_code FROM users WHERE id = :user_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if withdrawals table exists, if not, return full balance
        try {
            $withdrawn_query = "SELECT COALESCE(SUM(amount), 0) as withdrawn 
                               FROM withdrawals WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($withdrawn_query);
            $stmt->bindParam(':user_id', $this->user_id);
            $stmt->execute();
            $withdrawn = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['balance'] - $withdrawn['withdrawn'];
        } catch (Exception $e) {
            // Withdrawals table doesn't exist yet
            return $result['balance'];
        }
    }

    public function getRecentReferrals($limit = 10) {
        $query = "SELECT id, name, username, created_at 
                  FROM users 
                  WHERE referred_by = (SELECT referral_code FROM users WHERE id = :user_id)
                  ORDER BY created_at DESC 
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $referrals = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $referrals[] = $row;
        }
        return $referrals;
    }

    public function getRank() {
        $query = "SELECT COUNT(*) as referral_count 
                  FROM users 
                  WHERE referred_by = (SELECT referral_code FROM users WHERE id = :user_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $result['referral_count'];
        
        if($count >= 50) return ['name' => 'Platinum', 'color' => '#E5E4E2'];
        if($count >= 30) return ['name' => 'Gold', 'color' => '#FFD700'];
        if($count >= 15) return ['name' => 'Silver', 'color' => '#C0C0C0'];
        if($count >= 5) return ['name' => 'Bronze', 'color' => '#CD7F32'];
        return ['name' => 'Starter', 'color' => '#6B7280'];
    }
}

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));
$user_id = isset($data->user_id) ? $data->user_id : 0;

if(!empty($user_id)) {
    $dashboard = new Dashboard($db, $user_id);
    
    $user_data = $dashboard->getUserData();
    
    if($user_data) {
        $referral_stats = $dashboard->getReferralStats();
        $earnings = $dashboard->getEarnings();
        $balance = $dashboard->getBalance();
        $recent_referrals = $dashboard->getRecentReferrals();
        $rank = $dashboard->getRank();
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "user" => [
                "id" => $user_data['id'],
                "name" => $user_data['name'],
                "username" => $user_data['username'],
                "email" => $user_data['email'],
                "phone" => $user_data['phone'],
                "country" => $user_data['country'],
                "referral_code" => $user_data['referral_code'],
                "referred_by" => $user_data['referred_by'],
                "role" => $user_data['role'],
                "created_at" => $user_data['created_at']
            ],
            "stats" => [
                "total_referrals" => (int)$referral_stats['total_referrals'],
                "referrals_this_month" => (int)$referral_stats['referrals_this_month'],
                "referrals_today" => (int)$referral_stats['referrals_today'],
                "total_earnings" => (int)$earnings['total_earnings'],
                "earnings_this_month" => (int)$earnings['earnings_this_month'],
                "earnings_today" => (int)$earnings['earnings_today'],
                "available_balance" => (int)$balance,
                "rank" => $rank['name'],
                "rank_color" => $rank['color']
            ],
            "recent_referrals" => $recent_referrals
        ]);
    } else {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "User not found"]);
    }
} else {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "User ID required"]);
}
?>
