<?php
// verify-payment.php - UPDATED VERSION
session_start();
require_once __DIR__ . '/../config/connect.php';
require_once __DIR__ . '/../models/OrderService.php';
require_once __DIR__ . '/../admin/models/PaymentSmtpSetting.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $razorpay_payment_id = $_POST['razorpay_payment_id'] ?? '';
    $razorpay_order_id = $_POST['razorpay_order_id'] ?? '';
    $razorpay_signature = $_POST['razorpay_signature'] ?? '';
    $is_cod = $_POST['is_cod'] ?? false;
    
    // Verify payment signature
    $paymentSetting = new PaymentSmtpSetting($conn);
    $razorpay_secret = $paymentSetting->getSetting('razorpay', 'api_secret');
    
    $generated_signature = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, $razorpay_secret);
    
    if ($generated_signature !== $razorpay_signature) {
        throw new Exception('Payment verification failed');
    }
    
    // Check if pending order exists in session
    if (!isset($_SESSION['pending_order'])) {
        throw new Exception('No pending order found');
    }
    
    $pendingOrder = $_SESSION['pending_order'];
    $orderData = $pendingOrder['order_data'];
    $cartItems = $pendingOrder['cart_items'];
    $userId = $pendingOrder['user_id'];
    
    // Create order in database (ONLY AFTER SUCCESSFUL PAYMENT)
    $orderService = new OrderService($conn, $site);
    
    $paymentMethod = $is_cod ? 'cod' : 'razorpay';
    
    $orderResult = $orderService->createOrder(
        $orderData,
        $userId,
        $paymentMethod,
        $razorpay_order_id
    );
    
    if (!$orderResult) {
        throw new Exception('Failed to create order in database');
    }
    
    // Add order items
    $orderService->addOrderItems($orderResult['order_id'], $cartItems);
    
    // Update payment status
    if ($is_cod) {
        $orderService->processCODAdvance($orderResult['order_id']);
    } else {
        $orderService->completePayment($orderResult['order_id'], $razorpay_payment_id, $razorpay_signature);
    }
    
    // Clear cart sessions
    if (isset($_SESSION['cart'])) unset($_SESSION['cart']);
    if (isset($_SESSION['promotion_code'])) unset($_SESSION['promotion_code']);
    if (isset($_SESSION['buy_now'])) unset($_SESSION['buy_now']);
    
    // Clear pending order from session
    unset($_SESSION['pending_order']);
    
    $response = [
        'success' => true,
        'order_id' => $orderResult['order_id'],
        'order_number' => $orderResult['order_number'],
        'message' => 'Payment successful!'
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    error_log("Payment Verification Error: " . $e->getMessage());
}

echo json_encode($response);
?>