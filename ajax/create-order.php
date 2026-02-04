<?php
session_start();
require_once __DIR__ . '/../config/connect.php';
require_once __DIR__ . '/../models/OrderService.php';
require_once __DIR__ . '/../admin/models/PaymentSmtpSetting.php';

header('Content-Type: application/json');

$orderService = new OrderService($conn, $site);
$paymentSetting = new PaymentSmtpSetting($conn);
$razorpay_key_id = $paymentSetting->getSetting('razorpay', 'api_key');
$razorpay_secret = $paymentSetting->getSetting('razorpay', 'api_secret');

// Initialize response
$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $action = $_POST['action'] ?? '';
    parse_str($_POST['form_data'] ?? '', $formData);
    
    // Validate form data
    if (empty($formData)) {
        throw new Exception('Form data is empty');
    }
    
    // Get cart items
    $cart_items = [];
    $subtotal = 0;
    
    if (isset($_SESSION['buy_now'])) {
        // Process buy now
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
        // Process regular cart
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
    
    // Calculate totals
    $shipping_fee = ($subtotal >= 1000) ? 0 : 0.00;
    $discount = 0;
    
    if (isset($_SESSION['promotion_code'])) {
        $discount_percentage = 15;
        $discount = ($subtotal * $discount_percentage) / 100;
    }
    
    $total = $subtotal - $discount + $shipping_fee;
    
    // Prepare order data - make sure all required fields exist
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
    
    // Get or create user (but don't insert order yet)
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
    
    // Store order data in session for later use (after payment verification)
    $_SESSION['pending_order'] = [
        'order_data' => $orderData,
        'cart_items' => $cart_items,
        'user_id' => $userId,
        'is_cod' => ($action === 'create_cod_order'),
        'user_data' => $userData
    ];
    
    // Load Razorpay SDK
    $razorpayLoaded = false;
    
    // Method 1: Check Composer autoloader
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
        $razorpayLoaded = true;
    }
    
    // Method 2: Direct include if using manual installation
    if (!$razorpayLoaded && file_exists(__DIR__ . '/../razorpay/razorpay-php/src/Api.php')) {
        require_once __DIR__ . '/../razorpay/razorpay-php/src/Api.php';
        $razorpayLoaded = true;
    }
    
    // Method 3: Manual require for specific file structure
    if (!$razorpayLoaded && file_exists(__DIR__ . '/../vendor/razorpay/razorpay/src/Api.php')) {
        require_once __DIR__ . '/../vendor/razorpay/razorpay/src/Api.php';
        $razorpayLoaded = true;
    }
    
    if (!$razorpayLoaded) {
        // If all else fails, try to load it directly
        try {
            $possiblePaths = [
                __DIR__ . '/../vendor/razorpay/razorpay/src/Razorpay.php',
                __DIR__ . '/../razorpay-php/src/Razorpay.php',
                __DIR__ . '/../Razorpay.php'
            ];
            
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    require_once $path;
                    $razorpayLoaded = true;
                    break;
                }
            }
        } catch (Exception $e) {
            // Continue to error below
        }
    }
    
    if (!$razorpayLoaded) {
        throw new Exception('Razorpay SDK not found. Please install via: composer require razorpay/razorpay');
    }
    
    // Check if Razorpay class exists
    if (!class_exists('Razorpay\Api\Api')) {
        throw new Exception('Razorpay\Api\Api class not loaded');
    }
    
    try {
        // Create Razorpay order
        $api = new Razorpay\Api\Api($razorpay_key_id, $razorpay_secret);
        
        if ($action === 'create_cod_order') {
            // COD with advance payment
            $amount = 200; // ₹200 advance
            $description = 'COD Advance Payment';
        } else {
            // Full payment
            $amount = $total;
            $description = 'Order Payment';
        }
        
        $razorpayOrder = $api->order->create([
            'receipt' => 'receipt_' . time(),
            'amount' => $amount * 100, // in paise
            'currency' => 'INR',
            'payment_capture' => 1,
            'notes' => [
                'type' => ($action === 'create_cod_order') ? 'cod_advance' : 'full_payment'
            ]
        ]);
        
        // Store Razorpay order ID in session
        $_SESSION['pending_order']['razorpay_order_id'] = $razorpayOrder->id;
        
        // Generate temporary order number (for display only)
        $tempOrderNumber = 'TEMP' . strtoupper(uniqid());
        
        $response = [
            'success' => true,
            'razorpay_order_id' => $razorpayOrder->id,
            'key_id' => $razorpay_key_id,
            'temp_order_number' => $tempOrderNumber
        ];
        
        if ($action === 'create_cod_order') {
            $response['cod_advance'] = 200;
            $response['cod_remaining'] = $total - 200;
            $response['final_amount'] = 200;
        } else {
            $response['final_amount'] = $total;
        }
        
    } catch (Razorpay\Api\Errors\Error $e) {
        throw new Exception('Razorpay Error: ' . $e->getMessage());
    }
    
} catch (Exception $e) {
    // Clear pending order on error
    if (isset($_SESSION['pending_order'])) {
        unset($_SESSION['pending_order']);
    }
    
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    error_log("Order Creation Error: " . $e->getMessage());
}

echo json_encode($response);
?>