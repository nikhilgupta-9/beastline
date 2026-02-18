<?php
session_start();
use Razorpay\Api\Api;
include_once __DIR__ . "/../config/connect.php";
require_once __DIR__ . '/../admin/models/PaymentSmtpSetting.php';
require_once __DIR__ . '/../models/OrderService.php';
require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');

try {
    // Get form data
    parse_str($_POST['form_data'], $formData);
    $cart_items = json_decode($_POST['cart_items'], true);
    
    // Initialize OrderService
    $orderService = new OrderService($conn, $site);
    
    // Initialize Razorpay
    $payment_setting = new PaymentSmtpSetting($conn);
    $razorpay_key_id = $payment_setting->getSetting('razorpay', 'api_key');
    $razorpay_secret = $payment_setting->getSetting('razorpay', 'api_secret');
    
    
    $api = new Api($razorpay_key_id, $razorpay_secret);
    
    // Calculate totals
    $subtotal = 0;
    $line_items = [];
    
    foreach ($cart_items as $item_data) {
        $product = $item_data['product'];
        $cart_item = $item_data['cart_item'];
        $item_total = $cart_item['price'] * $cart_item['quantity'];
        $subtotal += $item_total;
        
        // Get product image
        $image_sql = "SELECT image_url FROM product_images WHERE product_id = ? AND is_main = 1 LIMIT 1";
        $image_stmt = $conn->prepare($image_sql);
        $image_stmt->bind_param("i", $product['pro_id']);
        $image_stmt->execute();
        $image_result = $image_stmt->get_result();
        $image = $image_result->fetch_assoc();
        
        $image_url = $image ? $site . 'admin/assets/img/uploads/' . $image['image_url'] : $site . 'assets/img/product/default.jpg';
        $product_url = $site . 'product-details/' . $product['slug_url'];
        
        // Build line items for Magic Checkout
        $line_items[] = [
            'sku' => $cart_item['variant_id'] ?: ($product['sku'] ?? 'SKU' . $product['pro_id']),
            'variant_id' => $cart_item['variant_id'] ?: 'default',
            'other_product_codes' => [
                'upc' => $product['upc_code'] ?? '',
                'ean' => $product['ean_code'] ?? '',
                'unspsc' => $product['unspsc_code'] ?? ''
            ],
            'price' => (int)($cart_item['price'] * 100), // Convert to paise
            'offer_price' => (int)($cart_item['price'] * 100),
            'tax_amount' => 0,
            'quantity' => (int)$cart_item['quantity'],
            'name' => $product['pro_name'],
            'description' => substr(strip_tags($product['short_desc'] ?? $product['description']), 0, 100),
            'weight' => (int)($product['weight'] ?? 500), // in grams
            'dimensions' => [
                'length' => (int)($product['length'] ?? 10),
                'width' => (int)($product['width'] ?? 10),
                'height' => (int)($product['height'] ?? 10)
            ],
            'image_url' => $image_url,
            'product_url' => $product_url,
            'notes' => []
        ];
    }
    
    // Calculate shipping and discount
    $shipping_fee = ($subtotal >= 1000) ? 0 : 0; // in rupees
    $discount = 0;
    
    if (isset($_SESSION['promotion_code'])) {
        $discount_percentage = 15;
        $discount = ($subtotal * $discount_percentage) / 100;
    }
    
    $total = $subtotal - $discount + $shipping_fee;
    
    // Prepare user data
    $userData = [
        'first_name' => $formData['billing_first_name'],
        'last_name' => $formData['billing_last_name'],
        'phone' => $formData['billing_phone'],
        'email' => $formData['billing_email'],
        'address_1' => $formData['billing_address_1'],
        'city' => $formData['billing_city'],
        'state' => $formData['billing_state'],
        'postcode' => $formData['billing_postcode']
    ];
    
    // Get or create user
    $userId = $orderService->getOrCreateUser($userData);
    
    if (!$userId) {
        throw new Exception('Failed to create user account');
    }
    
    // Create Razorpay order with Magic Checkout parameters
    $orderData = [
        'amount' => (int)($total * 100), // Convert to paise
        'currency' => 'INR',
        'receipt' => 'order_' . time(),
        'notes' => [
            'user_id' => $userId,
            'shipping_fee' => $shipping_fee,
            'discount' => $discount
        ],
        'line_items_total' => (int)($subtotal * 100), // IMPORTANT: For Magic Checkout
        'line_items' => $line_items // IMPORTANT: For Magic Checkout
    ];
    
    $razorpay_order = $api->order->create($orderData);
    
    // Prepare order data for database
    $orderDbData = [
        'subtotal' => $subtotal,
        'discount' => $discount,
        'shipping_fee' => $shipping_fee,
        'total' => $total,
        'tax' => 0,
        'billing_first_name' => $formData['billing_first_name'],
        'billing_last_name' => $formData['billing_last_name'],
        'billing_phone' => $formData['billing_phone'],
        'billing_email' => $formData['billing_email'],
        'billing_address_1' => $formData['billing_address_1'],
        'billing_address_2' => $formData['billing_address_2'] ?? '',
        'billing_city' => $formData['billing_city'],
        'billing_state' => $formData['billing_state'],
        'billing_country' => $formData['billing_country'],
        'billing_postcode' => $formData['billing_postcode'],
        'order_note' => $formData['order_note'] ?? ''
    ];
    
    // Create pending order in database
    $orderResult = $orderService->createOrder(
        $orderDbData,
        $userId,
        'razorpay',
        $razorpay_order['id']
    );
    
    if (!$orderResult) {
        throw new Exception('Failed to create order in database');
    }
    
    // Add order items
    $orderItems = [];
    foreach ($cart_items as $item_data) {
        $product = $item_data['product'];
        $cart_item = $item_data['cart_item'];
        
        $orderItems[] = [
            'product_id' => $product['pro_id'],
            'product_name' => $product['pro_name'],
            'quantity' => $cart_item['quantity'],
            'unit_price' => $cart_item['price'],
            'total_price' => $cart_item['price'] * $cart_item['quantity'],
            'color' => $cart_item['color'] ?? '',
            'size' => $cart_item['size'] ?? '',
            'sku' => $cart_item['sku'] ?? '',
            'variant_id' => $cart_item['variant_id'] ?? 0
        ];
    }
    
    $orderService->addOrderItems($orderResult['order_id'], $orderItems);
    
    // Store in session for later verification
    $_SESSION['pending_order'] = [
        'order_id' => $orderResult['order_id'],
        'razorpay_order_id' => $razorpay_order['id'],
        'user_id' => $userId,
        'total' => $total
    ];
    
    echo json_encode([
        'success' => true,
        'razorpay_order_id' => $razorpay_order['id'],
        'key_id' => $razorpay_key_id,
        'final_amount' => $total,
        'order_id' => $orderResult['order_id'],
        'order_number' => $orderResult['order_number'],
        'is_magic_checkout' => true
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    // Log error for debugging
    error_log("Magic Order Creation Error: " . $e->getMessage());
}
?>