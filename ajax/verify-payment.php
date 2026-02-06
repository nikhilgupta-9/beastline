<?php
// verify-payment.php - UPDATED VERSION WITH ITHINK LOGISTICS
session_start();
require_once __DIR__ . '/../config/connect.php';
require_once __DIR__ . '/../models/OrderService.php';
require_once __DIR__ . '/../admin/models/PaymentSmtpSetting.php';
require_once __DIR__ . '/../api/LogisticsApi.php'; // ADD THIS

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $razorpay_payment_id = $_POST['razorpay_payment_id'] ?? '';
    $razorpay_order_id = $_POST['razorpay_order_id'] ?? '';
    $razorpay_signature = $_POST['razorpay_signature'] ?? '';
    $is_cod = isset($_POST['is_cod']) && $_POST['is_cod'] == 'true' ? true : false;

    // Verify payment signature
    $paymentSetting = new PaymentSmtpSetting($conn);
    $razorpay_secret = $paymentSetting->getSetting('razorpay', 'api_secret');

    if (empty($razorpay_secret)) {
        throw new Exception('Razorpay secret key not configured');
    }

    $generated_signature = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, $razorpay_secret);

    if ($generated_signature !== $razorpay_signature) {
        throw new Exception('Payment verification failed - Invalid signature');
    }

    // Check if pending order exists in session
    if (!isset($_SESSION['pending_order'])) {
        throw new Exception('No pending order found. Session may have expired.');
    }

    $pendingOrder = $_SESSION['pending_order'];
    $orderData = $pendingOrder['order_data'];
    $cartItems = $pendingOrder['cart_items'];
    $userId = $pendingOrder['user_id'];
    $userData = $pendingOrder['user_data'] ?? [];

    // Calculate totals again for verification
    $subtotal = 0;
    foreach ($cartItems as $item) {
        $subtotal += $item['total_price'];
    }

    $shipping_fee = ($subtotal >= 1000) ? 0 : 0.00;
    $discount = $orderData['discount'] ?? 0;
    $total = $subtotal - $discount + $shipping_fee;

    // Validate order data
    if (abs($total - $orderData['total']) > 0.01) {
        throw new Exception('Order total mismatch. Please try again.');
    }

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

    $orderId = $orderResult['order_id'];
    $orderNumber = $orderResult['order_number'];

    // Add order items
    if (!$orderService->addOrderItems($orderId, $cartItems)) {
        throw new Exception('Failed to add order items');
    }

    // Update payment status
    $paymentStatus = '';
    if ($is_cod) {
        $orderService->processCODAdvance($orderId);
        $paymentStatus = 'cod_advance_paid';
    } else {
        $orderService->completePayment($orderId, $razorpay_payment_id, $razorpay_signature);
        $paymentStatus = 'paid';
    }

    if ($is_cod) {
        $orderService->processCODAdvance($orderId);
        $conn->query("UPDATE orders SET order_status = 'confirmed' WHERE order_id = {$orderId}");
    } else {
        $orderService->completePayment($orderId, $razorpay_payment_id, $razorpay_signature);
        $conn->query("UPDATE orders SET order_status = 'confirmed' WHERE order_id = {$orderId}");
    }

    // --- CRITICAL: SYNC TO ITHINK LOGISTICS ---
    $shouldSyncToLogistics = true;

    // Conditions when NOT to sync:
    // 1. Order total is 0 (free orders)
    // 2. Test/demo orders
    if ($total <= 0) {
        $shouldSyncToLogistics = false;
        error_log("Order #{$orderNumber}: Skipping logistics sync - order total is 0");
    }

    if ($shouldSyncToLogistics) {

        try {
            require_once __DIR__ . '/../api/LogisticsApi.php';
            $logisticsApi = new LogisticsApi();

            // Prepare order data
            $orderData = [
                'order_number' => $orderResult['order_number'],
                'total_amount' => number_format($total, 2, '.', ''),
                'consignee_name' => $pendingOrder['user_data']['first_name'] . ' ' . $pendingOrder['user_data']['last_name'],
                'consignee_address' => $pendingOrder['order_data']['billing_address_1'],
                'consignee_city' => $pendingOrder['order_data']['billing_city'],
                'consignee_state' => $pendingOrder['order_data']['billing_state'],
                'consignee_pincode' => $pendingOrder['order_data']['billing_postcode'],
                'consignee_country' => $pendingOrder['order_data']['billing_country'] ?? 'India',
                'consignee_phone' => $pendingOrder['user_data']['phone'],
                'consignee_email' => $pendingOrder['user_data']['email'],
                'payment_type' => $is_cod ? 'cod' : 'prepaid',
                'cod_amount' => $is_cod ? $total : 0,
                'product_name' => 'Order #' . $orderResult['order_number'],
                'quantity' => count($pendingOrder['cart_items']),
                'weight' => max(0.5, count($pendingOrder['cart_items']) * 0.3),
                'logistics' => 'delhivery'
            ];

            // Sync to iThink
            $logisticsResult = $logisticsApi->createShipment($orderData);

            if (isset($logisticsResult['status']) && $logisticsResult['status'] == 'success') {
                // Update order with tracking info
                $trackingData = null;
                if (isset($logisticsResult['data']) && is_array($logisticsResult['data'])) {
                    $firstKey = array_key_first($logisticsResult['data']);
                    $trackingData = $logisticsResult['data'][$firstKey] ?? null;
                }

                $updateSql = "UPDATE orders SET 
                     tracking_number = ?,
                     awb_number = ?,
                     courier_name = ?,
                     shipment_data = ?,
                     order_status = 'ready_to_dispatch'
                     WHERE order_id = ?";

                $stmt = $conn->prepare($updateSql);
                $trackingNumber = $trackingData['waybill'] ?? '';
                $awbNumber = $trackingData['waybill'] ?? '';
                $courierName = $trackingData['logistic_name'] ?? 'Delhivery';
                $shipmentJson = json_encode($logisticsResult);

                $stmt->bind_param(
                    "ssssi",
                    $trackingNumber,
                    $awbNumber,
                    $courierName,
                    $shipmentJson,
                    $orderId
                );
                $stmt->execute();

                $debug_log[] = "✅ Order synced to iThink. Tracking: $trackingNumber";
            } else {
                $debug_log[] = "⚠️ iThink sync failed: " . ($logisticsResult['message'] ?? 'Unknown error');
            }
        } catch (Exception $e) {
            $debug_log[] = "⚠️ Logistics exception: " . $e->getMessage();
        }
    } else {
        // For non-synced orders, set appropriate status
        $orderStatus = $total <= 0 ? 'completed' : 'confirmed';
        $conn->query("UPDATE orders SET order_status = '{$orderStatus}' WHERE order_id = {$orderId}");
    }

    // Clear cart sessions
    $orderService->clearSessions();

    // Clear pending order from session
    unset($_SESSION['pending_order']);

    // Create order confirmation URL
    $confirmationUrl = $site . "order-confirmation/" . $orderId;

    $response = [
        'success' => true,
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'confirmation_url' => $confirmationUrl,
        'message' => 'Payment successful! Your order has been placed.'
    ];
} catch (Exception $e) {
    // Clear sessions on error
    if (isset($_SESSION['cart'])) unset($_SESSION['cart']);
    if (isset($_SESSION['promotion_code'])) unset($_SESSION['promotion_code']);
    if (isset($_SESSION['buy_now'])) unset($_SESSION['buy_now']);
    if (isset($_SESSION['pending_order'])) unset($_SESSION['pending_order']);

    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    error_log("Payment Verification Error: " . $e->getMessage() . " - Trace: " . $e->getTraceAsString());
}

echo json_encode($response);

// Helper functions
function getProductNames($cartItems)
{
    $names = [];
    foreach ($cartItems as $item) {
        $names[] = $item['product_name'];
    }
    return implode(', ', array_slice($names, 0, 3)) . (count($names) > 3 ? ' and more...' : '');
}

function calculateOrderWeight($cartItems)
{
    // Default weight calculation
    // You should adjust this based on your products
    $totalWeight = 0;
    foreach ($cartItems as $item) {
        $totalWeight += ($item['quantity'] * 0.5); // Assuming 0.5kg per item
    }
    return max(0.5, $totalWeight); // Minimum 0.5kg
}

function storeShipmentTracking($conn, $orderId, $trackingData)
{
    $sql = "INSERT INTO shipment_tracking 
            (order_id, awb_number, courier_name, tracking_data, status, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            tracking_data = VALUES(tracking_data),
            status = VALUES(status),
            updated_at = NOW()";

    $stmt = $conn->prepare($sql);

    $awbNumber = $trackingData['awb_number'] ?? '';
    $courierName = $trackingData['courier_name'] ?? '';
    $status = $trackingData['status'] ?? 'created';
    $trackingJson = json_encode($trackingData);

    $stmt->bind_param(
        "issss",
        $orderId,
        $awbNumber,
        $courierName,
        $trackingJson,
        $status
    );

    return $stmt->execute();
}

function sendOrderConfirmation($orderId, $email, $trackingNumber, $awbNumber, $courierName)
{
    // Implement email sending logic here
    // You can use PHPMailer or your existing email setup
    // This is a placeholder function
    return true;
}
