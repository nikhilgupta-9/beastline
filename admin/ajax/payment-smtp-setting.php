<?php
require_once __DIR__ . '/../config/db-conn.php';
require_once __DIR__ . '/../auth/admin-auth.php';
require_once __DIR__ . '/../models/setting.php';
require_once __DIR__ . '/../models/PaymentSmtpSetting.php';

header('Content-Type: application/json');

$settingModel = new PaymentSmtpSetting($conn);
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $updated = [];

    if (isset($_POST['update_razorpay'])) {
        $data = [
            'api_key' => $_POST['api_key'] ?? '',
            'api_secret' => $_POST['api_secret'] ?? '',
            'webhook_secret' => $_POST['webhook_secret'] ?? '',
            'merchant_name' => $_POST['merchant_name'] ?? '',
            'theme_color' => $_POST['theme_color'] ?? '#0d6efd'
        ];

        if ($settingModel->updateSettings('razorpay', $data)) {
            $updated[] = 'Razorpay';
        }
    }

    if (isset($_POST['update_smtp'])) {
        $data = [
            'host' => $_POST['host'] ?? '',
            'port' => $_POST['port'] ?? '587',
            'username' => $_POST['username'] ?? '',
            'password' => $_POST['password'] ?? '',
            'encryption' => $_POST['encryption'] ?? 'tls',
            'from_name' => $_POST['from_name'] ?? '',
            'from_email' => $_POST['from_email'] ?? ''
        ];

        if ($settingModel->updateSettings('smtp', $data)) {
            $updated[] = 'SMTP';
        }
    }

    if ($updated) {
        $response = [
            'success' => true,
            'message' => implode(' & ', $updated) . ' settings updated successfully!'
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'No settings were updated.'
        ];
    }

    echo json_encode($response);
    exit;
}

echo json_encode($response);
