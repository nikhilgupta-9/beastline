<?php
class PaymentSmtpSetting {
    private $conn;
    private $encryption_key = 'Nike10liteBeastLine2024Sec!@#'; // 32 bytes for AES-256
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Generate proper 32-byte key from password
    private function getEncryptionKey() {
        // Use hash to create 32-byte key from any length password
        return hash('sha256', $this->encryption_key, true);
    }
    
    // Generate proper 16-byte IV
    private function getIV() {
        // Static IV (should be unique per encryption in production)
        // For better security, consider storing IV with each encrypted value
        return substr(hash('sha256', $this->encryption_key . 'IV'), 0, 16);
    }
    
    // Encrypt sensitive data
    private function encrypt($data) {
        if(empty($data)) {
            return $data;
        }
        
        $key = $this->getEncryptionKey();
        $iv = $this->getIV();
        
        // Encrypt
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        
        // Return base64 encoded
        return base64_encode($encrypted);
    }
    
    // Decrypt data
    private function decrypt($data) {
        if(empty($data)) {
            return $data;
        }
        
        $key = $this->getEncryptionKey();
        $iv = $this->getIV();
        
        // Decode base64
        $encrypted = base64_decode($data);
        
        // Decrypt
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    }
    
    // Get all settings by type
    public function getSettingsByType($type) {
        $sql = "SELECT * FROM payment_smtp_settings WHERE setting_type = ? ORDER BY id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $type);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $settings = [];
        while($row = $result->fetch_assoc()) {
            if($row['is_encrypted'] == 1 && !empty($row['setting_value'])) {
                try {
                    $row['setting_value'] = $this->decrypt($row['setting_value']);
                } catch(Exception $e) {
                    // If decryption fails, return empty string
                    $row['setting_value'] = '';
                }
            }
            $settings[] = $row;
        }
        
        return $settings;
    }
    
    // Update settings
    public function updateSettings($type, $data) {
        $success = true;
        
        foreach($data as $key => $value) {
            // Get setting details
            $stmt = $this->conn->prepare("SELECT is_encrypted FROM payment_smtp_settings WHERE setting_type = ? AND setting_key = ?");
            $stmt->bind_param("ss", $type, $key);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($row = $result->fetch_assoc()) {
                $is_encrypted = $row['is_encrypted'];
                
                // Encrypt if needed
                $final_value = ($is_encrypted == 1 && !empty($value)) ? $this->encrypt($value) : $value;
                
                // Update setting
                $update_stmt = $this->conn->prepare("UPDATE payment_smtp_settings SET setting_value = ?, updated_at = NOW() WHERE setting_type = ? AND setting_key = ?");
                $update_stmt->bind_param("sss", $final_value, $type, $key);
                if(!$update_stmt->execute()) {
                    $success = false;
                }
                $update_stmt->close();
            }
        }
        
        return $success;
    }
    
    // Get specific setting
    public function getSetting($type, $key) {
        $stmt = $this->conn->prepare("SELECT * FROM payment_smtp_settings WHERE setting_type = ? AND setting_key = ?");
        $stmt->bind_param("ss", $type, $key);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($row = $result->fetch_assoc()) {
            if($row['is_encrypted'] == 1 && !empty($row['setting_value'])) {
                try {
                    $row['setting_value'] = $this->decrypt($row['setting_value']);
                } catch(Exception $e) {
                    $row['setting_value'] = '';
                }
            }
            return $row['setting_value'];
        }
        
        return null;
    }
    
    // Toggle active status
    public function toggleActive($type) {
        $current = $this->getSetting($type, 'is_active');
        $new_value = $current == '1' ? '0' : '1';
        
        $stmt = $this->conn->prepare("UPDATE payment_smtp_settings SET setting_value = ? WHERE setting_type = ? AND setting_key = 'is_active'");
        $stmt->bind_param("ss", $new_value, $type);
        return $stmt->execute();
    }
    
    // Test SMTP connection
    public function testSMTP($host, $port, $username, $password, $encryption) {
        // Check if PHPMailer is available
        if(!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            // Try to include PHPMailer
            $phpmailer_path = __DIR__ . '/../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
            if(file_exists($phpmailer_path)) {
                require_once $phpmailer_path;
                require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/SMTP.php';
                require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/Exception.php';
            } else {
                return "PHPMailer not installed. Install via: composer require phpmailer/phpmailer";
            }
        }
        
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $password;
            $mail->Port = $port;
            $mail->SMTPDebug = 0; // Set to 2 for debugging
            
            if($encryption == 'ssl') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } else if($encryption == 'tls') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = '';
            }
            
            $mail->Timeout = 10;
            
            // Test connection
            if($mail->smtpConnect()) {
                $mail->smtpClose();
                return true;
            }
            return false;
        } catch(Exception $e) {
            return $e->getMessage();
        }
    }
    
    // Test Razorpay connection
    public function testRazorpay($api_key, $api_secret) {
        // Check if Razorpay SDK is available
        if(!class_exists('Razorpay\Api\Api')) {
            $razorpay_path = __DIR__ . '/../vendor/razorpay/razorpay/src/Api.php';
            if(file_exists($razorpay_path)) {
                require_once $razorpay_path;
            } else {
                return "Razorpay SDK not installed. Install via: composer require razorpay/razorpay";
            }
        }
        
        try {
            $client = new Razorpay\Api\Api($api_key, $api_secret);
            // Try to fetch a test payment to verify credentials
            $payments = $client->payment->all(['count' => 1]);
            return true;
        } catch(Exception $e) {
            return $e->getMessage();
        }
    }
    
    // Alternative: Simpler encryption method (for testing)
    public function simpleEncrypt($data) {
        if(empty($data)) return $data;
        
        // Use a simpler method for testing
        $method = 'AES-256-CBC';
        $key = hash('sha256', $this->encryption_key, true);
        $iv = substr(hash('sha256', 'beastlineiv'), 0, 16);
        
        return base64_encode(openssl_encrypt($data, $method, $key, 0, $iv));
    }
    
    public function simpleDecrypt($data) {
        if(empty($data)) return $data;
        
        $method = 'AES-256-CBC';
        $key = hash('sha256', $this->encryption_key, true);
        $iv = substr(hash('sha256', 'beastlineiv'), 0, 16);
        
        return openssl_decrypt(base64_decode($data), $method, $key, 0, $iv);
    }
}
?>