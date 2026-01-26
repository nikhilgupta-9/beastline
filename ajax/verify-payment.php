<?php
// verify-payment.php
session_start();
require_once __DIR__ . '/../config/connect.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Razorpay\Api\Api;

// Set JSON header
header('Content-Type: application/json');

// Get POST data
$razorpay_payment_id = $_POST['razorpay_payment_id'] ?? '';
$razorpay_order_id = $_POST['razorpay_order_id'] ?? '';
$razorpay_signature = $_POST['razorpay_signature'] ?? '';
$order_id = $_POST['order_id'] ?? 0;
$is_cod = isset($_POST['is_cod']) && $_POST['is_cod'] == 'true';

try {
    // Validate inputs
    if (empty($razorpay_payment_id) || empty($razorpay_order_id) || empty($razorpay_signature) || empty($order_id)) {
        throw new Exception('Missing payment verification data');
    }
    
    // Get Razorpay API credentials
    require_once __DIR__ . '/../admin/models/PaymentSmtpSetting.php';
    $payment_setting = new PaymentSmtpSetting($conn);
    $api_key = $payment_setting->getSetting('razorpay', 'api_key');
    $api_secret = $payment_setting->getSetting('razorpay', 'api_secret');
    
    if (empty($api_key) || empty($api_secret)) {
        throw new Exception('Razorpay API credentials not configured');
    }
    
    // Initialize Razorpay client
    $api = new Api($api_key, $api_secret);
    
    // Verify payment signature
    $attributes = [
        'razorpay_order_id' => $razorpay_order_id,
        'razorpay_payment_id' => $razorpay_payment_id,
        'razorpay_signature' => $razorpay_signature
    ];
    
    $api->utility->verifyPaymentSignature($attributes);
    
    if ($is_cod) {
        // For COD advance payment
        $update_sql = "UPDATE orders SET 
            razorpay_payment_id = ?,
            payment_status = 'cod_advance_paid',
            updated_at = NOW()
            WHERE order_id = ?";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $razorpay_payment_id, $order_id);
        
        if ($stmt->execute()) {
            // Record advance payment
            $payment_sql = "INSERT INTO payments (
                order_id, payment_id, amount, currency, status, payment_type, created_at
            ) SELECT 
                o.order_id, ?, 200, 'INR', 'completed', 'cod_advance', NOW()
                FROM orders o WHERE o.order_id = ?";
            
            $payment_stmt = $conn->prepare($payment_sql);
            $payment_stmt->bind_param("si", $razorpay_payment_id, $order_id);
            $payment_stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'COD advance payment verified successfully!',
                'order_id' => $order_id
            ]);
        } else {
            throw new Exception('Failed to update COD order status');
        }
    } else {
        // For regular Razorpay payment
        $update_sql = "UPDATE orders SET 
            payment_status = 'paid', 
            order_status = 'confirmed',
            razorpay_payment_id = ?,
            updated_at = NOW()
            WHERE order_id = ?";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $razorpay_payment_id, $order_id);
        
        if ($stmt->execute()) {
            // Record payment in payments table
            $payment_sql = "INSERT INTO payments (
                order_id, payment_id, amount, currency, status, created_at
            ) SELECT 
                o.order_id, ?, o.final_amount, 'INR', 'completed', NOW()
                FROM orders o WHERE o.order_id = ?";
            
            $payment_stmt = $conn->prepare($payment_sql);
            $payment_stmt->bind_param("si", $razorpay_payment_id, $order_id);
            $payment_stmt->execute();
            
            // Clear cart
            unset($_SESSION['cart']);
            if (isset($_SESSION['promotion_code'])) {
                unset($_SESSION['promotion_code']);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Payment verified successfully!',
                'order_id' => $order_id
            ]);
        } else {
            throw new Exception('Failed to update order status');
        }
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Payment verification failed: ' . $e->getMessage()
    ]);
}
?>