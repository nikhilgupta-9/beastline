<?php
// create-db-order.php
session_start();
require_once __DIR__ . '/../config/connect.php';
require_once __DIR__ . '/../models/OrderService.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];
$orderService = new OrderService($conn, $site);

try {
    // Check if pending order exists in session
    if (!isset($_SESSION['pending_order'])) {
        throw new Exception('No pending order found');
    }
    
    $pendingOrder = $_SESSION['pending_order'];
    $orderData = $pendingOrder['order_data'];
    $cartItems = $pendingOrder['cart_items'];
    $isCOD = $pendingOrder['is_cod'];
    
    // Get or create user
    $userId = $orderService->getOrCreateUser([
        'first_name' => $orderData['billing_first_name'],
        'last_name' => $orderData['billing_last_name'],
        'phone' => $orderData['billing_phone'],
        'email' => $orderData['billing_email'],
        'address_1' => $orderData['billing_address_1'],
        'city' => $orderData['billing_city'],
        'state' => $orderData['billing_state'],
        'postcode' => $orderData['billing_postcode']
    ]);
    
    if (!$userId) {
        throw new Exception('Failed to create user account');
    }
    
    // Create order in database
    $paymentMethod = $isCOD ? 'cod' : 'razorpay';
    $razorpayOrderId = $_POST['razorpay_order_id'] ?? $pendingOrder['razorpay_order_id'] ?? null;
    
    $orderResult = $orderService->createOrder(
        $orderData,
        $userId,
        $paymentMethod,
        $razorpayOrderId
    );
    
    if (!$orderResult) {
        throw new Exception('Failed to create order');
    }
    
    // Add order items
    $orderService->addOrderItems($orderResult['order_id'], $cartItems);
    
    // Update payment status
    $paymentId = $_POST['razorpay_payment_id'] ?? null;
    $signature = $_POST['razorpay_signature'] ?? null;
    
    if ($isCOD) {
        $orderService->processCODAdvance($orderResult['order_id']);
    } else {
        $orderService->completePayment($orderResult['order_id'], $paymentId, $signature);
    }
    
    // Clear sessions
    $orderService->clearSessions();
    
    // Clear pending order from session
    unset($_SESSION['pending_order']);
    
    $response = [
        'success' => true,
        'order_id' => $orderResult['order_id'],
        'order_number' => $orderResult['order_number']
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    error_log("Create DB Order Error: " . $e->getMessage());
}

echo json_encode($response);
?>