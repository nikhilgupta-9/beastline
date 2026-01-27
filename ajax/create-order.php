<?php
session_start();
require_once __DIR__ . '/../config/connect.php';
require_once(__DIR__ .  '/../vendor/autoload.php');

use Razorpay\Api\Api;

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

try {
    // Parse form data
    if (!isset($_POST['form_data'])) {
        throw new Exception('Form data not received');
    }

    parse_str($_POST['form_data'], $formData);

    // Validate cart
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        throw new Exception('Cart is empty');
    }

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Please login to place an order');
    }

    // Calculate totals from session cart
    $subtotal = 0;
    $cart_items_data = [];

    foreach ($_SESSION['cart'] as $cart_item_id => $item) {
        $product_sql = "SELECT * FROM products WHERE pro_id = ?";
        $product_stmt = $conn->prepare($product_sql);
        $product_stmt->bind_param("i", $item['product_id']);
        $product_stmt->execute();
        $product_result = $product_stmt->get_result();

        if ($product_result->num_rows == 0) {
            continue;
        }

        $product = $product_result->fetch_assoc();

        $item_total = $item['price'] * $item['quantity'];
        $subtotal += $item_total;

        $cart_items_data[] = [
            'product' => $product,
            'cart_item' => $item,
            'item_total' => $item_total
        ];
    }

    // Calculate other amounts
    $discount_amount = isset($_SESSION['promotion_code']) ? ($subtotal * 0.15) : 0;
    $shipping_amount = ($subtotal >= 1000) ? 0 : 1.00;
    $tax_amount = 0;
    $final_amount = $subtotal - $discount_amount + $shipping_amount + $tax_amount;

    // Prepare addresses as JSON
    $billing_address = json_encode([
        'first_name' => $formData['billing_first_name'] ?? '',
        'last_name' => $formData['billing_last_name'] ?? '',
        'email' => $formData['billing_email'] ?? '',
        'phone' => $formData['billing_phone'] ?? '',
        'country' => $formData['billing_country'] ?? '',
        'address_1' => $formData['billing_address_1'] ?? '',
        'address_2' => $formData['billing_address_2'] ?? '',
        'city' => $formData['billing_city'] ?? '',
        'state' => $formData['billing_state'] ?? '',
        'postcode' => $formData['billing_postcode'] ?? ''
    ]);

    $shipping_address = json_encode([
        'first_name' => $formData['shipping_first_name'] ?? $formData['billing_first_name'] ?? '',
        'last_name' => $formData['shipping_last_name'] ?? $formData['billing_last_name'] ?? '',
        'email' => $formData['shipping_email'] ?? $formData['billing_email'] ?? '',
        'phone' => $formData['shipping_phone'] ?? $formData['billing_phone'] ?? '',
        'country' => $formData['shipping_country'] ?? $formData['billing_country'] ?? '',
        'address_1' => $formData['shipping_address_1'] ?? $formData['billing_address_1'] ?? '',
        'address_2' => $formData['shipping_address_2'] ?? ($formData['billing_address_2'] ?? ''),
        'city' => $formData['shipping_city'] ?? $formData['billing_city'] ?? '',
        'state' => $formData['shipping_state'] ?? $formData['billing_state'] ?? '',
        'postcode' => $formData['shipping_postcode'] ?? $formData['billing_postcode'] ?? ''
    ]);

    $user_id = $_SESSION['user_id'];
    $payment_method = $_POST['payment_method'] ?? 'razorpay';
    $notes = $formData['order_note'] ?? '';

    // Store order data in session
    $temp_order_data = [
        'user_id' => $user_id,
        'form_data' => $formData,
        'subtotal' => $subtotal,
        'discount_amount' => $discount_amount,
        'shipping_amount' => $shipping_amount,
        'tax_amount' => $tax_amount,
        'final_amount' => $final_amount,
        'cart_items_data' => $cart_items_data,
        'billing_address' => $billing_address,
        'shipping_address' => $shipping_address,
        'notes' => $notes,
        'payment_method' => $payment_method,
        'created_at' => date('Y-m-d H:i:s')
    ];

    $_SESSION['temp_order_data'] = $temp_order_data;
    $_SESSION['temp_order_payment_method'] = $payment_method;

    // Create Razorpay order
    require_once __DIR__ . '/../admin/models/PaymentSmtpSetting.php';
    $payment_setting = new PaymentSmtpSetting($conn);
    $api_key = $payment_setting->getSetting('razorpay', 'api_key');
    $api_secret = $payment_setting->getSetting('razorpay', 'api_secret');

    if (empty($api_key) || empty($api_secret)) {
        throw new Exception('Razorpay API credentials not configured');
    }

    $client = new Api($api_key, $api_secret);

    // Generate temporary order number
    $temp_order_number = 'TEMP' . date('YmdHis') . mt_rand(1000, 9999);

    // Create Razorpay order
    $razorpay_order = $client->order->create([
        'amount' => $final_amount * 100,
        'currency' => 'INR',
        'receipt' => $temp_order_number,
        'notes' => [
            'temp_order_number' => $temp_order_number,
            'type' => 'full_payment'
        ]
    ]);

    $razorpay_order_array = $razorpay_order->toArray();
    $razorpay_order_id = $razorpay_order_array['id'];

    // Store Razorpay order ID in session
    $_SESSION['temp_razorpay_order_id'] = $razorpay_order_id;
    $_SESSION['temp_order_number'] = $temp_order_number;

    $response = [
        'success' => true,
        'razorpay_order_id' => $razorpay_order_id,
        'temp_order_number' => $temp_order_number,
        'final_amount' => $final_amount,
        'message' => 'Ready for payment'
    ];

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}