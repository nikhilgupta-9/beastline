<?php
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';

// Default settings
$defaultSettings = [
    // General
    'site_name' => 'My Website',
    'site_logo' => 'assets/img/logo_icon.jpg',
    'favicon' => 'assets/img/favicon.ico',
    'copyright_text' => '© 2024 Your Company. All rights reserved.',
    'meta_description' => '',
    'currency' => 'INR',
    
    // Appearance
    'primary_color' => '#3498db',
    'secondary_color' => '#2ecc71',
    'font_family' => 'Inter',
    'font_size' => '16',
    'enable_dark_mode' => '0',
    
    // Admin Panel
    'admin_theme' => 'dark',
    'sidebar_color' => '#2c3e50',
    'header_color' => '#34495e',
    'admin_font' => 'default',
    'table_style' => 'striped',
    'enable_sidebar_collapse' => '0',
    
    // Contact
    'business_email' => '',
    'support_phone' => '',
    'whatsapp_number' => '',
    'business_address' => '',
    'google_maps' => '',
    
    // Social Media
    'facebook_url' => '',
    'instagram_url' => '',
    'twitter_url' => '',
    'linkedin_url' => '',
    'youtube_url' => '',
    'pinterest_url' => '',
    
    // Security
    'session_timeout' => '30',
    'max_login_attempts' => '5',
    'enable_2fa' => '0',
    'force_strong_passwords' => '0',
    'enable_captcha' => '0',
    'log_login_attempts' => '0'
];

// Reset all settings
$success = true;
foreach($defaultSettings as $key => $value) {
    $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param("sss", $key, $value, $value);
    if(!$stmt->execute()) {
        $success = false;
    }
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode(['success' => $success]);
?>