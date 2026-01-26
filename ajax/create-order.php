<?php
session_start();
require_once __DIR__ . '/../config/connect.php';
require_once __DIR__ . '/../util/mail-services.php';
require_once(__DIR__ .  '/../vendor/autoload.php');

use Razorpay\Api\Api;
// use Razorpay\Api\Client;

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Debug log
file_put_contents('create_order_debug.log', "[" . date('Y-m-d H:i:s') . "] Request received\n", FILE_APPEND);
file_put_contents('create_order_debug.log', "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    file_put_contents('create_order_debug.log', "Action: $action\n", FILE_APPEND);

    switch ($action) {
        case 'create_order':
            try {
                // Parse form data
                if (!isset($_POST['form_data'])) {
                    throw new Exception('Form data not received');
                }

                parse_str($_POST['form_data'], $formData);
                file_put_contents('create_order_debug.log', "Form data parsed: " . print_r($formData, true) . "\n", FILE_APPEND);

                // Generate order number
                $order_number = 'ORD' . date('YmdHis') . mt_rand(1000, 9999);

                // Calculate totals from session cart
                $subtotal = 0;
                $cart_items_data = [];

                if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
                    throw new Exception('Cart is empty');
                }

                foreach ($_SESSION['cart'] as $cart_item_id => $item) {
                    // Get product details
                    $product_sql = "SELECT * FROM products WHERE pro_id = ?";
                    $product_stmt = $conn->prepare($product_sql);
                    $product_stmt->bind_param("i", $item['product_id']);
                    $product_stmt->execute();
                    $product_result = $product_stmt->get_result();

                    if ($product_result->num_rows == 0) {
                        continue; // Skip if product not found
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
                $shipping_amount = ($subtotal >= 1000) ? 0 : 49.99;
                $tax_amount = 0; // You can add tax calculation if needed
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

                $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;
                $payment_method = $_POST['payment_method'] ?? 'razorpay';
                $notes = $formData['order_note'] ?? '';

                // Create order record
                $sql = "INSERT INTO orders (
                    user_id, order_number, total_amount, discount_amount, 
                    shipping_amount, tax_amount, final_amount, payment_method, 
                    payment_status, order_status, shipping_address, billing_address, 
                    notes, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, ?, ?, NOW())";

                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception('Prepare failed: ' . $conn->error);
                }

                $stmt->bind_param(
                    "isdddddssss",
                    $user_id,
                    $order_number,
                    $subtotal,
                    $discount_amount,
                    $shipping_amount,
                    $tax_amount,
                    $final_amount,
                    $payment_method,
                    $shipping_address,
                    $billing_address,
                    $notes
                );

                if ($stmt->execute()) {
                    $order_id = $stmt->insert_id;

                    // Add order items
                    foreach ($cart_items_data as $item_data) {
                        $product = $item_data['product'];
                        $cart_item = $item_data['cart_item'];

                        // Prepare attributes
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

                    // Create Razorpay order for online payment
                    if ($payment_method == 'razorpay') {
                        // Get Razorpay settings
                        require_once __DIR__ . '/../admin/models/PaymentSmtpSetting.php';
                        $payment_setting = new PaymentSmtpSetting($conn);
                        $api_key = $payment_setting->getSetting('razorpay', 'api_key');
                        $api_secret = $payment_setting->getSetting('razorpay', 'api_secret');

                        if (empty($api_key) || empty($api_secret)) {
                            throw new Exception('Razorpay API credentials not configured');
                        }

                        $client = new Api($api_key, $api_secret);

                        // Create Razorpay order for FULL amount (not advance)
                        $razorpay_order = $client->order->create([
                            'amount' => $final_amount * 100, // Amount in paise for FULL payment
                            'currency' => 'INR',
                            'receipt' => $order_number,
                            'notes' => [
                                'order_id' => $order_id,
                                'type' => 'full_payment'
                            ]
                        ]);

                        // Convert to array to avoid the notice
                        $razorpay_order_array = $razorpay_order->toArray();
                        $razorpay_order_id = $razorpay_order_array['id'];

                        // Update order with Razorpay order ID
                        $update_sql = "UPDATE orders SET razorpay_order_id = ? WHERE order_id = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param("si", $razorpay_order_id, $order_id);
                        $update_stmt->execute();

                        $response = [
                            'success' => true,
                            'order_id' => $order_id,
                            'razorpay_order_id' => $razorpay_order_id,
                            'order_number' => $order_number,
                            'final_amount' => $final_amount,
                            'message' => 'Order created successfully'
                        ];
                    } else if ($payment_method == 'cod') {
                        // For COD, return order details without creating Razorpay order yet
                        $response = [
                            'success' => true,
                            'order_id' => $order_id,
                            'order_number' => $order_number,
                            'final_amount' => $final_amount,
                            'message' => 'COD order created successfully'
                        ];
                    } else {
                        // For COD, return order details
                        $response = [
                            'success' => true,
                            'order_id' => $order_id,
                            'order_number' => $order_number,
                            'final_amount' => $final_amount,
                            'message' => 'COD order created successfully'
                        ];
                    }
                    file_put_contents('create_order_debug.log', "Success response: " . json_encode($response) . "\n", FILE_APPEND);
                    echo json_encode($response);
                } else {
                    throw new Exception('Failed to create order: ' . $stmt->error);
                }
            } catch (Exception $e) {
                file_put_contents('create_order_debug.log', "Error in create_order: " . $e->getMessage() . "\n", FILE_APPEND);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }
            break;

        case 'create_cod_order':
            try {
                // Parse form data
                if (!isset($_POST['form_data'])) {
                    throw new Exception('Form data not received');
                }

                parse_str($_POST['form_data'], $formData);

                // Generate order number
                $order_number = 'COD' . date('YmdHis') . mt_rand(1000, 9999);

                // Calculate totals from session cart
                $subtotal = 0;
                $cart_items_data = [];

                if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
                    throw new Exception('Cart is empty');
                }

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

                // Calculate amounts
                $discount_amount = isset($_SESSION['promotion_code']) ? ($subtotal * 0.15) : 0;
                $shipping_amount = ($subtotal >= 1000) ? 0 : 49.99;
                $tax_amount = 0;
                $final_amount = $subtotal - $discount_amount + $shipping_amount + $tax_amount;
                $cod_advance = $_POST['cod_advance'] ?? 200;
                $cod_remaining = $_POST['cod_remaining'] ?? ($final_amount - $cod_advance);

                // Prepare addresses
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

                $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;
                $notes = ($formData['order_note'] ?? '') . '|cod_advance:' . $cod_advance . '|cod_remaining:' . $cod_remaining;

                // Create COD order
                $sql = "INSERT INTO orders (
            user_id, order_number, total_amount, discount_amount, 
            shipping_amount, tax_amount, final_amount, payment_method, 
            payment_status, order_status, shipping_address, billing_address, 
            notes, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'cod', 'cod_advance_pending', 'pending', ?, ?, ?, NOW())";

                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception('Prepare failed: ' . $conn->error);
                }

                $stmt->bind_param(
                    "isdddddsss",
                    $user_id,
                    $order_number,
                    $subtotal,
                    $discount_amount,
                    $shipping_amount,
                    $tax_amount,
                    $final_amount,
                    $shipping_address,
                    $billing_address,
                    $notes
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

                    // For COD, create Razorpay order for advance payment
                    require_once __DIR__ . '/../vendor/autoload.php';

                    // Get Razorpay settings
                    require_once __DIR__ . '/../admin/models/PaymentSmtpSetting.php';
                    $payment_setting = new PaymentSmtpSetting($conn);
                    $api_key = $payment_setting->getSetting('razorpay', 'api_key');
                    $api_secret = $payment_setting->getSetting('razorpay', 'api_secret');

                    if (empty($api_key) || empty($api_secret)) {
                        throw new Exception('Razorpay API credentials not configured');
                    }

                    $client = new Api($api_key, $api_secret);

                    $razorpay_order = $client->order->create([
                        'amount' => $cod_advance * 100, // Amount in paise for advance only
                        'currency' => 'INR',
                        'receipt' => $order_number . '-ADV',
                        'notes' => [
                            'order_id' => $order_id,
                            'type' => 'cod_advance'
                        ]
                    ]);

                    // CORRECTED: Convert to array to get the ID
                    $razorpay_order_array = $razorpay_order->toArray();
                    $razorpay_order_id = $razorpay_order_array['id'];

                    // Update order with Razorpay order ID
                    $update_sql = "UPDATE orders SET razorpay_order_id = ? WHERE order_id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("si", $razorpay_order_id, $order_id);
                    $update_stmt->execute();

                    $response = [
                        'success' => true,
                        'order_id' => $order_id,
                        'razorpay_order_id' => $razorpay_order_id, // Use the array value
                        'order_number' => $order_number,
                        'final_amount' => $final_amount,
                        'cod_advance' => $cod_advance,
                        'message' => 'COD order created successfully'
                    ];

                    file_put_contents('create_order_debug.log', "COD success response: " . json_encode($response) . "\n", FILE_APPEND);
                    echo json_encode($response);
                } else {
                    throw new Exception('Failed to create COD order: ' . $stmt->error);
                }
            } catch (Exception $e) {
                file_put_contents('create_order_debug.log', "Error in create_cod_order: " . $e->getMessage() . "\n", FILE_APPEND);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }
            break;
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action: ' . $action
            ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request',
        'method' => $_SERVER['REQUEST_METHOD'],
        'has_action' => isset($_POST['action'])
    ]);
}
