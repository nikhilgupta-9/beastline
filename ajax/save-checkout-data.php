<?php
// save-checkout-data.php
session_start();
require_once __DIR__ . '/../config/connect.php';
require_once __DIR__ . '/../models/OrderService.php';
require_once __DIR__ . '/../admin/models/PaymentSmtpSetting.php';

header('Content-Type: application/json');

$orderService = new OrderService($conn, $site);
$paymentSetting = new PaymentSmtpSetting($conn);
$razorpay_key_id = $paymentSetting->getSetting('razorpay', 'api_key');
$razorpay_secret = $paymentSetting->getSetting('razorpay', 'api_secret');

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    parse_str($_POST['form_data'] ?? '', $formData);
    
    if (empty($formData)) {
        throw new Exception('Form data is empty');
    }
    
    // Get cart items
    $cart_items = [];
    $subtotal = 0;
    
    if (isset($_SESSION['buy_now'])) {
        $buyNowItem = $_SESSION['buy_now'];
        $sql = "SELECT * FROM products WHERE pro_id = ? AND status = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $buyNowItem['product_id']);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        
        if ($product) {
            $item_total = $buyNowItem['price'] * $buyNowItem['quantity'];
            $cart_items[] = [
                'product_id' => $buyNowItem['product_id'],
                'product_name' => $product['pro_name'],
                'quantity' => $buyNowItem['quantity'],
                'unit_price' => $buyNowItem['price'],
                'total_price' => $item_total,
                'color' => $buyNowItem['color'] ?? '',
                'size' => $buyNowItem['size'] ?? '',
                'variant_id' => $buyNowItem['variant_id'] ?? 0
            ];
            $subtotal = $item_total;
        }
    } else {
        foreach ($_SESSION['cart'] as $item) {
            $sql = "SELECT * FROM products WHERE pro_id = ? AND status = 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $item['product_id']);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
            
            if ($product) {
                $item_total = $item['price'] * $item['quantity'];
                $cart_items[] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $product['pro_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'total_price' => $item_total,
                    'color' => $item['color'] ?? '',
                    'size' => $item['size'] ?? '',
                    'variant_id' => $item['variant_id'] ?? 0
                ];
                $subtotal += $item_total;
            }
        }
    }
    
    if (empty($cart_items)) {
        throw new Exception('No items in cart');
    }
    
    // Calculate totals (SAME AS CHECKOUT PAGE)
    $shipping_fee = ($subtotal >= 1000) ? 0 : 1.00; // Fix: Should be 1.00 not 0.00
    $discount = 0;
    
    if (isset($_SESSION['promotion_code'])) {
        $discount_percentage = 15;
        $discount = ($subtotal * $discount_percentage) / 100;
    }
    
    $total = $subtotal - $discount + $shipping_fee;
    
    // Prepare order data
    $orderData = [
        'subtotal' => $subtotal,
        'discount' => $discount,
        'shipping_fee' => $shipping_fee,
        'total' => $total,
        'tax' => 0,
        'billing_first_name' => $formData['billing_first_name'] ?? '',
        'billing_last_name' => $formData['billing_last_name'] ?? '',
        'billing_phone' => $formData['billing_phone'] ?? '',
        'billing_email' => $formData['billing_email'] ?? '',
        'billing_address_1' => $formData['billing_address_1'] ?? '',
        'billing_address_2' => $formData['billing_address_2'] ?? '',
        'billing_city' => $formData['billing_city'] ?? '',
        'billing_state' => $formData['billing_state'] ?? '',
        'billing_country' => $formData['billing_country'] ?? '',
        'billing_postcode' => $formData['billing_postcode'] ?? '',
        'order_note' => $formData['order_note'] ?? ''
    ];
    
    // Validate required fields
    $requiredFields = [
        'billing_first_name', 'billing_last_name', 'billing_phone',
        'billing_email', 'billing_address_1', 'billing_city',
        'billing_state', 'billing_country', 'billing_postcode'
    ];
    
    foreach ($requiredFields as $field) {
        if (empty($orderData[$field])) {
            throw new Exception("Required field '$field' is missing");
        }
    }
    
    // Get or create user
    $userData = [
        'first_name' => $orderData['billing_first_name'],
        'last_name' => $orderData['billing_last_name'],
        'phone' => $orderData['billing_phone'],
        'email' => $orderData['billing_email'],
        'address_1' => $orderData['billing_address_1'],
        'city' => $orderData['billing_city'],
        'state' => $orderData['billing_state'],
        'postcode' => $orderData['billing_postcode']
    ];
    
    $userId = $orderService->getOrCreateUser($userData);
    
    if (!$userId) {
        throw new Exception('Failed to create user account');
    }
    
    // Store order data in session
    $_SESSION['pending_order'] = [
        'order_data' => $orderData,
        'cart_items' => $cart_items,
        'user_id' => $userId,
        'user_data' => $userData
    ];
    
    // Load Razorpay SDK
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    } else if (file_exists(__DIR__ . '/../vendor/razorpay/razorpay/src/Api.php')) {
        require_once __DIR__ . '/../vendor/razorpay/razorpay/src/Api.php';
    } else {
        throw new Exception('Razorpay SDK not found');
    }
    
    if (!class_exists('Razorpay\Api\Api')) {
        throw new Exception('Razorpay\Api\Api class not loaded');
    }
    
    // Get payment method from form
    $paymentMethod = $formData['payment_method'] ?? 'razorpay';
    
    // Create Razorpay order
    $api = new Razorpay\Api\Api($razorpay_key_id, $razorpay_secret);
    
    if ($paymentMethod === 'cod') {
        // COD with advance payment
        $amount = 200;
        $description = 'COD Advance Payment';
    } else {
        // Full payment
        $amount = $total;
        $description = 'Order Payment';
    }
    
    $razorpayOrder = $api->order->create([
        'receipt' => 'receipt_' . time(),
        'amount' => $amount * 100,
        'currency' => 'INR',
        'payment_capture' => 1,
        'notes' => [
            'type' => ($paymentMethod === 'cod') ? 'cod_advance' : 'full_payment'
        ]
    ]);
    
    // Store Razorpay order ID in session
    $_SESSION['pending_order']['razorpay_order_id'] = $razorpayOrder->id;
    $_SESSION['pending_order']['is_cod'] = ($paymentMethod === 'cod');
    
    $response = [
        'success' => true,
        'razorpay_order_id' => $razorpayOrder->id,
        'key_id' => $razorpay_key_id,
        'final_amount' => $amount
    ];
    
    if ($paymentMethod === 'cod') {
        $response['cod_advance'] = 200;
        $response['cod_remaining'] = $total - 200;
    }
    
} catch (Exception $e) {
    if (isset($_SESSION['pending_order'])) {
        unset($_SESSION['pending_order']);
    }
    
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    error_log("Save Checkout Data Error: " . $e->getMessage());
}

echo json_encode($response);
?>