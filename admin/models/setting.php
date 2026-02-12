<?php
class Setting {
    private $conn;
    private $settings = [];
    
    public function __construct($db) {
        $this->conn = $db;
        $this->loadAllSettings();
    }
    
    private function loadAllSettings() {
        $sql = "SELECT setting_key, setting_value FROM site_settings";
        $result = $this->conn->query($sql);
        
        if ($result) {
            while($row = $result->fetch_assoc()) {
                $this->settings[$row['setting_key']] = $row['setting_value'];
            }
        }
    }
    
    public function get($key, $default = '') {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }
    
    public function set($key, $value) {
        $sql = "INSERT INTO site_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sss", $key, $value, $value);
            $success = $stmt->execute();
            $stmt->close();
            
            // Update local cache
            $this->settings[$key] = $value;
            
            return $success;
        }
        return false;
    }
    
    public function getAll() {
        return $this->settings;
    }
    
    public function getByGroup($group) {
        $sql = "SELECT setting_key, setting_value FROM site_settings WHERE setting_group = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $group);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $settings = [];
        while($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    }
}
?>