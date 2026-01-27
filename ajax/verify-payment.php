<?php
header("Access-Control-Allow-Origin: *"); // Or specific domain
header("Access-Control-Allow-Headers: x-rtb-fingerprint-id, Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

session_start();
require_once __DIR__ . '/../config/connect.php';
require_once(__DIR__ .  '/../vendor/autoload.php');

use Razorpay\Api\Api;

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

function jsonResponse($success, $message = '', $data = []) {
    $response = ['success' => $success];
    if ($message) $response['message'] = $message;
    if ($data) $response = array_merge($response, $data);
    echo json_encode($response);
    exit();
}

try {
    // Get POST data
    $razorpay_payment_id = $_POST['razorpay_payment_id'] ?? '';
    $razorpay_order_id = $_POST['razorpay_order_id'] ?? '';
    $razorpay_signature = $_POST['razorpay_signature'] ?? '';
    $is_cod = isset($_POST['is_cod']) ? true : false;

    if (empty($razorpay_payment_id) || empty($razorpay_order_id) || empty($razorpay_signature)) {
        jsonResponse(false, 'Payment verification data missing');
    }

    // Get Razorpay API secret
    require_once __DIR__ . '/../admin/models/PaymentSmtpSetting.php';
    $payment_setting = new PaymentSmtpSetting($conn);
    $api_secret = $payment_setting->getSetting('razorpay', 'api_secret');

    if (empty($api_secret)) {
        jsonResponse(false, 'Razorpay API credentials not configured');
    }

    // Verify signature
    $generated_signature = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, $api_secret);

    if ($generated_signature !== $razorpay_signature) {
        jsonResponse(false, 'Invalid payment signature');
    }

    // Payment verified - now save order
    if (!isset($_SESSION['temp_order_data'])) {
        jsonResponse(false, 'No order data found. Please start checkout again.');
    }

    $temp_data = $_SESSION['temp_order_data'];
    $payment_method = $_SESSION['temp_order_payment_method'] ?? 'razorpay';
    
    // Generate final order number
    $prefix = ($payment_method == 'cod') ? 'COD' : 'ORD';
    $order_number = $prefix . date('YmdHis') . mt_rand(1000, 9999);

    // Extract data
    $user_id = $temp_data['user_id'];
    $subtotal = $temp_data['subtotal'];
    $discount_amount = $temp_data['discount_amount'];
    $shipping_amount = $temp_data['shipping_amount'];
    $tax_amount = $temp_data['tax_amount'];
    $final_amount = $temp_data['final_amount'];
    $cart_items_data = $temp_data['cart_items_data'];
    $billing_address = $temp_data['billing_address'];
    $shipping_address = $temp_data['shipping_address'];
    $notes = $temp_data['notes'];

    // For COD, add advance info
    if ($payment_method == 'cod') {
        $cod_advance = $_SESSION['cod_advance'] ?? 200;
        $cod_remaining = $_SESSION['cod_remaining'] ?? ($final_amount - $cod_advance);
        $notes .= '|cod_advance:' . $cod_advance . '|cod_remaining:' . $cod_remaining;
    }

    // Determine payment status
    $payment_status = ($payment_method == 'cod') ? 'cod_advance_paid' : 'paid';
    $order_status = 'pending';

   // Create order record - FIXED VERSION
$sql = "INSERT INTO orders (
    user_id, order_number, total_amount, discount_amount, 
    shipping_amount, tax_amount, final_amount, payment_method, 
    payment_status, order_status, shipping_address, billing_address, 
    notes, razorpay_order_id, created_at
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";  // 14 ? placeholders

$stmt = $conn->prepare($sql);
if (!$stmt) {
    throw new Exception('Prepare failed: ' . $conn->error);
}

$razorpay_order_id_from_session = $_SESSION['temp_razorpay_order_id'] ?? null;

// Changed "isdddddssssss" to "isdddddsssssss" (14 type specifiers)
$stmt->bind_param(
    "isdddddsssssss",  // Added one more 's' for razorpay_order_id
    $user_id,
    $order_number,
    $subtotal,
    $discount_amount,
    $shipping_amount,
    $tax_amount,
    $final_amount,
    $payment_method,
    $payment_status,
    $order_status,
    $shipping_address,
    $billing_address,
    $notes,
    $razorpay_order_id_from_session  // Now matches the 14th placeholder
);

    if ($stmt->execute()) {
        $order_id = $stmt->insert_id;

        // Add order items
        foreach ($cart_items_data as $item_data) {
            $product = $item_data['product'];
            $cart_item = $item_data['cart_item'];

            $attributes = [];
            if (!empty($cart_item['color'])) $attributes['color'] = $cart_item['color'];
            if (!empty($cart_item['size'])) $attributes['size'] = $cart_item['size'];

            $item_sql = "INSERT INTO order_items (
                order_id, product_id, product_name, quantity, 
                unit_price, total_price, attributes
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";

            $item_stmt = $conn->prepare($item_sql);
            $attributes_json = json_encode($attributes);

            $item_stmt->bind_param(
                "iisidds",
                $order_id,
                $product['pro_id'],
                $product['pro_name'],
                $cart_item['quantity'],
                $cart_item['price'],
                $item_data['item_total'],
                $attributes_json
            );
            $item_stmt->execute();
        }

        // Clear session data
        unset($_SESSION['temp_order_data']);
        unset($_SESSION['temp_order_payment_method']);
        unset($_SESSION['temp_razorpay_order_id']);
        unset($_SESSION['temp_order_number']);
        unset($_SESSION['cod_advance']);
        unset($_SESSION['cod_remaining']);
        unset($_SESSION['cart']);
        unset($_SESSION['promotion_code']);

        jsonResponse(true, 'Order saved successfully', [
            'order_id' => $order_id,
            'order_number' => $order_number
        ]);

    } else {
        throw new Exception('Failed to save order: ' . $stmt->error);
    }

} catch (Exception $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}