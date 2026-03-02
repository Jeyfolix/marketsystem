<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $name;
    public $username;
    public $email;
    public $phone;
    public $country;
    public $referral_code;
    public $referred_by;
    public $role;
    public $password;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        // Generate unique referral code
        $this->referral_code = $this->generateReferralCode();
        
        $query = "INSERT INTO " . $this->table_name . "
                SET 
                    name=:name, 
                    username=:username, 
                    email=:email,
                    phone=:phone,
                    country=:country,
                    referral_code=:referral_code,
                    referred_by=:referred_by,
                    role='user',
                    password=:password";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->country = htmlspecialchars(strip_tags($this->country));
        $this->referred_by = !empty($this->referred_by) ? htmlspecialchars(strip_tags($this->referred_by)) : null;
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);

        // Bind parameters
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":country", $this->country);
        $stmt->bindParam(":referral_code", $this->referral_code);
        $stmt->bindParam(":referred_by", $this->referred_by);
        $stmt->bindParam(":password", $this->password);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function usernameExists() {
        $query = "SELECT id, name, username, email, phone, country, referral_code, referred_by, role, password, created_at 
                FROM " . $this->table_name . " 
                WHERE username = :username_val OR email = :email_val 
                LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username_val", $this->username);
        $stmt->bindParam(":email_val", $this->username);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->phone = $row['phone'];
            $this->country = $row['country'];
            $this->referral_code = $row['referral_code'];
            $this->referred_by = $row['referred_by'];
            $this->role = $row['role'];
            $this->password = $row['password'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }

    public function emailExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function checkUsername() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $this->username);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function checkPhone() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE phone = :phone LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function getReferralByCode($code) {
        $query = "SELECT id, username FROM " . $this->table_name . " WHERE referral_code = :code LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":code", $code);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    private function generateReferralCode() {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = 'REF';
        for ($i = 0; $i < 6; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // Check if code already exists
        $query = "SELECT id FROM " . $this->table_name . " WHERE referral_code = :code";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":code", $code);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return $this->generateReferralCode();
        }
        
        return $code;
    }
}
?>
