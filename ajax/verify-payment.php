<?php
// verify-payment.php - UPDATED WITH PROPER ITHINK SYNC API
session_start();
require_once __DIR__ . '/../config/connect.php';
require_once __DIR__ . '/../models/OrderService.php';
require_once __DIR__ . '/../admin/models/PaymentSmtpSetting.php';
require_once __DIR__ . '/../api/LogisticsApi.php'; // Updated API class

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

    // Verify payment signature (only for non-COD)
    if (!$is_cod) {
        $paymentSetting = new PaymentSmtpSetting($conn);
        $razorpay_secret = $paymentSetting->getSetting('razorpay', 'api_secret');

        if (empty($razorpay_secret)) {
            throw new Exception('Razorpay secret key not configured');
        }

        $generated_signature = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, $razorpay_secret);

        if ($generated_signature !== $razorpay_signature) {
            throw new Exception('Payment verification failed - Invalid signature');
        }
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

    $shipping_fee = ($subtotal >= 1000) ? 0 : 1.00;
    $discount = $orderData['discount'] ?? 0;
    $total = $subtotal - $discount + $shipping_fee;

    // Validate order data
    if (abs($total - $orderData['total']) > 0.01) {
        throw new Exception('Order total mismatch. Please try again.');
    }

    // Create order in database
    $orderService = new OrderService($conn, $site);

    $paymentMethod = $is_cod ? 'cod' : 'razorpay';
    $razorpayOrderId = !$is_cod ? $razorpay_order_id : null;

    $orderResult = $orderService->createOrder(
        $orderData,
        $userId,
        $paymentMethod,
        $razorpayOrderId
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
    if ($is_cod) {
        $orderService->processCODAdvance($orderId);
        $conn->query("UPDATE orders SET order_status = 'confirmed' WHERE order_id = {$orderId}");
        $paymentStatus = 'cod_advance_paid';
    } else {
        $orderService->completePayment($orderId, $razorpay_payment_id, $razorpay_signature);
        $conn->query("UPDATE orders SET order_status = 'confirmed' WHERE order_id = {$orderId}");
        $paymentStatus = 'paid';
    }


    $normalizedItems = [];

    // foreach ($cartItems as $item) {
    //     $normalizedItems[] = [
    //         'product_name' => $item['product_name'] ?? 'Product',
    //         'sku' => $item['sku'] ?? 'SKU-' . ($item['product_id'] ?? rand(100, 999)),
    //         'quantity' => (string)$item['quantity'],
    //         'price' => (string)$item['unit_price'],
    //         'tax_rate' => $item['tax_rate'] ?? '0',
    //         'hsn_code' => $item['hsn_code'] ?? '',
    //         'discount' => '0'
    //     ];
    // }

    $sql = "SELECT sku, weight, selling_price FROM products WHERE pro_id = ?";
    $stmt = $conn->prepare($sql);

    foreach ($cartItems as $item) {
        $stmt->bind_param("i", $item['product_id']);
        $stmt->execute();
        $p = $stmt->get_result()->fetch_assoc();

        $normalizedItems[] = [
            'product_name'     => $item['product_name'],
            'sku'              => $p['sku'],
            'quantity'         => (string)$item['quantity'],
            'price'            => (string)$p['selling_price'],
            'tax_rate'         => '5',
            'hsn_code'         => '91308',
            'discount'         => '0',
            'weight'           => (float)$p['weight']
        ];
    }


    // --- SYNC TO ITHINK LOGISTICS USING SYNC API ---
    $shouldSyncToLogistics = true;

    // Conditions when NOT to sync:
    if ($total <= 0) {
        $shouldSyncToLogistics = false;
        error_log("Order #{$orderNumber}: Skipping logistics sync - order total is 0");
    }

    if ($shouldSyncToLogistics) {
        try {
            $logisticsApi = new LogisticsApi();

            // Get shipping address (use shipping if available, otherwise billing)
            $shippingAddress = isset($orderData['shipping_address_1']) && !empty($orderData['shipping_address_1'])
                ? $orderData['shipping_address_1']
                : $orderData['billing_address_1'];

            $shippingCity = isset($orderData['shipping_city']) && !empty($orderData['shipping_city'])
                ? $orderData['shipping_city']
                : $orderData['billing_city'];

            $shippingState = isset($orderData['shipping_state']) && !empty($orderData['shipping_state'])
                ? $orderData['shipping_state']
                : $orderData['billing_state'];

            $shippingPostcode = isset($orderData['shipping_postcode']) && !empty($orderData['shipping_postcode'])
                ? $orderData['shipping_postcode']
                : $orderData['billing_postcode'];

            // Calculate order weight based on cart items
            $orderWeight = calculateOrderWeight($cartItems);

            // Prepare order data for iThink
            $logisticsData = [
                'order_number' => $orderNumber,
                'total_amount' => number_format($total, 2, '.', ''),
                'consignee_name' => trim($userData['first_name'] . ' ' . $userData['last_name']),
                'consignee_address' => $shippingAddress,
                'consignee_city' => $shippingCity,
                'consignee_state' => $shippingState,
                'consignee_pincode' => $shippingPostcode,
                'consignee_country' => $orderData['billing_country'] ?? 'India',
                'consignee_phone' => $userData['phone'] ?? '',
                'consignee_email' => $userData['email'] ?? '',
                'payment_type' => $is_cod ? 'cod' : 'prepaid',
                'cod_amount' => $is_cod ? number_format($total, 2, '.', '') : '0',
                'pickup_location' => 'Beastline Delhi',
                'cart_items' => $normalizedItems, // Pass cart items for product details
                'weight' => $orderWeight,
                'length' => 15,
                'width' => 10,
                'height' => 5
            ];

            // Sync to iThink using sync API
            $logisticsResult = $logisticsApi->createShipment($logisticsData);

            // Check if sync was successful
            if (
                isset($logisticsResult['data']) &&
                is_array($logisticsResult['data'])
            ) {
                $firstKey = array_key_first($logisticsResult['data']);
                $shipmentResult = $logisticsResult['data'][$firstKey];

                if (
                    isset($shipmentResult['status']) &&
                    $shipmentResult['status'] === 'success'
                ) {

                    // Get tracking data from response
                    $trackingData = null;
                    $awbNumber = '';
                    $courierName = 'Delhivery';

                    if (isset($logisticsResult['data']) && is_array($logisticsResult['data'])) {

                        $firstKey = array_key_first($logisticsResult['data']);
                        $shipmentData = $logisticsResult['data'][$firstKey];

                        // STEP 1 — GET REFNUM
                        $refnum = $shipmentResult['refnum'] ?? '';

                        if (empty($refnum)) {
                            throw new Exception('iThink refnum missing');
                        }

                        if (!empty($refnum)) {

                            // STEP 2 — CALL AWB API
                            $awbResponse = $logisticsApi->assignAwb($refnum);

                            if (
                                isset($awbResponse['status']) &&
                                $awbResponse['status'] === 'success'
                            ) {
                                $awbKey = array_key_first($awbResponse['data']);
                                $awbData = $awbResponse['data'][$awbKey];

                                $awbNumber = $awbData['awb_number'] ?? '';
                                $courierName = $awbData['logistic_name'] ?? 'iThink';

                                if (!empty($awbNumber)) {
                                    $conn->query("
                                        UPDATE orders SET
                                        order_status = 'processing',
                                        logistics_sync_status = 'synced',
                                        logistics_refnum = '" . mysqli_real_escape_string($conn, $refnum) . "',
                                        awb_number = '" . mysqli_real_escape_string($conn, $awbNumber) . "',
                                        tracking_number = '" . mysqli_real_escape_string($conn, $awbNumber) . "',
                                        courier_name = '" . mysqli_real_escape_string($conn, $courierName) . "'
                                        WHERE order_id = $orderId
                                    ");
                                }
                            }
                        }
                    }
                }
            } else {

                $errorMsg = $logisticsResult['html_message']
                    ?? ($logisticsResult['message'] ?? 'iThink sync failed');

                error_log("⚠️ iThink sync failed for order #{$orderNumber}: " . $errorMsg);

                $conn->query("
                    UPDATE orders SET
                    order_status = 'confirmed',
                    logistics_sync_status = 'failed',
                    logistics_sync_error = '" . mysqli_real_escape_string($conn, $errorMsg) . "'
                    WHERE order_id = $orderId
                ");
            }
        } catch (Exception $e) {
            // Log exception but don't stop order processing
            error_log("⚠️ Logistics exception for order #{$orderNumber}: " . $e->getMessage());

            // Update order with exception info
            $conn->query("UPDATE orders SET 
            order_status = 'confirmed',
            logistics_sync_status = 'error',
            logistics_sync_error = '" . mysqli_real_escape_string($conn, $e->getMessage()) . "'
            WHERE order_id = $orderId");
        }
    } else {
        // For non-synced orders (free orders)
        $orderStatus = 'confirmed';
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
        'message' => 'Order placed successfully!'
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
    error_log("Payment Verification Error: " . $e->getMessage());
}

echo json_encode($response);

// Helper functions
function calculateOrderWeight($cartItems)
{
    $totalWeight = 0;

    foreach ($cartItems as $item) {
        $weight = isset($item['weight']) ? (float)$item['weight'] : 0.3;
        $totalWeight += $weight * $item['quantity'];
    }

    return round(max(0.5, $totalWeight), 2);
}
