<?php
session_start();
require_once __DIR__ . '/../config/connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'complete_razorpay':
            $order_id = intval($_POST['order_id']);
            $payment_response = json_decode($_POST['payment_response'], true);
            
            // Verify payment with Razorpay
            require_once __DIR__ . '/../admin/models/PaymentSmtpSetting.php';
            require_once __DIR__ . '/../vendor/autoload.php';
            
            $payment_setting = new PaymentSmtpSetting($conn);
            $api_secret = $payment_setting->getSetting('razorpay', 'api_secret');
            
            // Verify signature
            $generated_signature = hash_hmac('sha256', $payment_response['razorpay_order_id'] . '|' . $payment_response['razorpay_payment_id'], $api_secret);
            
            if ($generated_signature === $payment_response['razorpay_signature']) {
                // Update order status
                $sql = "UPDATE orders SET 
                        payment_status = 'paid', 
                        order_status = 'processing',
                        razorpay_payment_id = ?,
                        razorpay_signature = ?,
                        updated_at = NOW()
                        WHERE order_id = ?";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "ssi",
                    $payment_response['razorpay_payment_id'],
                    $payment_response['razorpay_signature'],
                    $order_id
                );
                
                if ($stmt->execute()) {
                    // Clear cart
                    unset($_SESSION['cart']);
                    unset($_SESSION['promotion_code']);
                    
                    echo json_encode([
                        'success' => true,
                        'order_id' => $order_id,
                        'message' => 'Payment successful!'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update order']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Payment verification failed']);
            }
            break;
            
        case 'complete_cod':
            $order_id = intval($_POST['order_id']);
            $payment_response = json_decode($_POST['payment_response'], true);
            
            // Update COD order with advance payment details
            $sql = "UPDATE orders SET 
                    payment_status = 'cod_advance_paid',
                    order_status = 'pending',
                    razorpay_payment_id = ?,
                    razorpay_signature = ?,
                    updated_at = NOW()
                    WHERE order_id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssi",
                $payment_response['razorpay_payment_id'],
                $payment_response['razorpay_signature'],
                $order_id
            );
            
            if ($stmt->execute()) {
                // Clear cart
                unset($_SESSION['cart']);
                unset($_SESSION['promotion_code']);
                
                echo json_encode([
                    'success' => true,
                    'order_id' => $order_id,
                    'message' => 'COD order created successfully!'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update COD order']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>