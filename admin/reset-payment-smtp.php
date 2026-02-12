<?php
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';

// Default settings
$defaults = [
    'razorpay' => [
        ['api_key', '', 1],
        ['api_secret', '', 1],
        ['webhook_secret', '', 1],
        ['merchant_name', '', 0],
        ['theme_color', '#0d6efd', 0],
        ['is_active', '0', 0]
    ],
    'smtp' => [
        ['host', '', 0],
        ['port', '587', 0],
        ['username', '', 0],
        ['password', '', 1],
        ['encryption', 'tls', 0],
        ['from_name', '', 0],
        ['from_email', '', 0],
        ['is_active', '0', 0]
    ]
];

// Clear existing data
$conn->query("TRUNCATE TABLE payment_smtp_settings");

// Insert defaults
foreach($defaults as $type => $settings) {
    foreach($settings as $setting) {
        $stmt = $conn->prepare("INSERT INTO payment_smtp_settings (setting_type, setting_key, setting_value, is_encrypted) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $type, $setting[0], $setting[1], $setting[2]);
        $stmt->execute();
    }
}

header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>