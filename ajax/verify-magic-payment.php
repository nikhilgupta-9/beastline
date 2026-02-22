<?php
session_start();
require_once __DIR__ . '/../config/connect.php';
require_once __DIR__ . '/../models/OrderService.php';
require_once __DIR__ . '/../admin/models/PaymentSmtpSetting.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

header('Content-Type: application/json');

// Create debug log
$debug_file = __DIR__ . '/../logs/verify-debug.log';
file_put_contents($debug_file, date('Y-m-d H:i:s') . " - Verify started\n", FILE_APPEND);

try {
    // --- 1. Get and Parse Input ---
    $input = json_decode(file_get_contents('php://input'), true);
    file_put_contents($debug_file, "Input: " . print_r($input, true) . "\n", FILE_APPEND);

    $payment_id = $input['razorpay_payment_id'] ?? '';
    $razorpay_order_id = $input['razorpay_order_id'] ?? '';
    $signature = $input['razorpay_signature'] ?? '';
    $is_cod = $input['is_cod'] ?? false;

    if (!$payment_id || !$razorpay_order_id || !$signature) {
        throw new Exception('Missing payment details');
    }

    // --- 2. Verify Signature ---
    $paymentSetting = new PaymentSmtpSetting($conn);
    $key_id = $paymentSetting->getSetting('razorpay', 'api_key');
    $key_secret = $paymentSetting->getSetting('razorpay', 'api_secret');

    $api = new Api($key_id, $key_secret);
    $attributes = [
        'razorpay_order_id' => $razorpay_order_id,
        'razorpay_payment_id' => $payment_id,
        'razorpay_signature' => $signature
    ];
    $api->utility->verifyPaymentSignature($attributes);

    // --- 3. Fetch Pending Order from Session ---
    file_put_contents($debug_file, "Session data: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

    if (!isset($_SESSION['pending_magic_order'][$razorpay_order_id])) {
        // Try to find by searching all pending orders
        $found = false;
        foreach ($_SESSION['pending_magic_order'] as $key => $order) {
            if ($order['razorpay_order_id'] == $razorpay_order_id) {
                $pendingOrder = $order;
                $razorpay_order_id = $key;
                $found = true;
                file_put_contents($debug_file, "Found order by searching: " . print_r($order, true) . "\n", FILE_APPEND);
                break;
            }
        }
        if (!$found) {
            throw new Exception('Pending order not found in session. Order ID: ' . $razorpay_order_id);
        }
    } else {
        $pendingOrder = $_SESSION['pending_magic_order'][$razorpay_order_id];
    }

    // --- 4. FETCH COMPLETE ORDER DETAILS FROM RAZORPAY ---
    $order = $api->order->fetch($razorpay_order_id);
    $orderArray = $order->toArray();
    file_put_contents($debug_file, "Razorpay order: " . print_r($orderArray, true) . "\n", FILE_APPEND);

    $razorpay_order_status = $orderArray['status'] ?? 'unknown';

    // Get timestamp from Razorpay
    $razorpay_created_at = $orderArray['created_at'] ?? null;
    
    // Get customer details
    $customerDetails = $orderArray['customer_details'] ?? null;

    if ($customerDetails) {
        file_put_contents($debug_file, "CUSTOMER DETAILS FROM RAZORPAY: " . print_r($customerDetails, true) . "\n", FILE_APPEND);
    }

    // --- 5. Extract User Details from Razorpay Payload ---
    $userDetails = [];
    
    if ($customerDetails) {
        // Get billing address (preferred) or shipping address
        $address = $customerDetails['billing_address'] ?? $customerDetails['shipping_address'] ?? [];
        
        // Get name
        $fullName = $address['name'] ?? $customerDetails['name'] ?? 'Guest User';
        
        // Clean phone number
        $phone = preg_replace('/[^0-9]/', '', $customerDetails['contact'] ?? '');
        if (strlen($phone) > 10) {
            $phone = substr($phone, -10);
        }
        
        // Get email
        $email = $customerDetails['email'] ?? ('guest_' . time() . '@example.com');
        
        // Build address line
        $address_line = trim(
            ($address['line1'] ?? '') . ' ' . 
            ($address['line2'] ?? '')
        );
        
        $userDetails = [
            'name' => $fullName,
            'phone' => $phone ?: '9999999999',
            'email' => $email,
            'address_1' => $address_line ?: 'Address from Razorpay',
            'city' => $address['city'] ?? '',
            'state' => $address['state'] ?? '',
            'postcode' => $address['zipcode'] ?? '',
            'country' => strtoupper($address['country'] ?? 'IN')
        ];
        
        file_put_contents($debug_file, "Extracted user details from Razorpay: " . print_r($userDetails, true) . "\n", FILE_APPEND);
    } 
    elseif (isset($_SESSION['user_id'])) {
        // Fallback to session user
        $user_sql = "SELECT first_name, last_name, email, phone, address, city, state, zip_code FROM users WHERE id = ?";
        $user_stmt = $conn->prepare($user_sql);
        $user_stmt->bind_param("i", $_SESSION['user_id']);
        $user_stmt->execute();
        $user = $user_stmt->get_result()->fetch_assoc();
        
        $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        
        $userDetails = [
            'name' => $fullName ?: 'User',
            'first_name' => $user['first_name'] ?? 'User',
            'last_name' => $user['last_name'] ?? '',
            'phone' => $user['phone'] ?? '9999999999',
            'email' => $user['email'] ?? ('user_' . time() . '@example.com'),
            'address_1' => $user['address'] ?? 'Address not provided',
            'city' => $user['city'] ?? '',
            'state' => $user['state'] ?? '',
            'postcode' => $user['zip_code'] ?? '',
            'country' => 'IN'
        ];
    } else {
        // Ultimate fallback
        $userDetails = [
            'name' => 'Guest User',
            'first_name' => 'Guest',
            'last_name' => 'User',
            'phone' => '9999999999',
            'email' => 'guest_' . time() . '@example.com',
            'address_1' => 'Address from Magic Checkout',
            'city' => '',
            'state' => '',
            'postcode' => '',
            'country' => 'IN'
        ];
    }

    file_put_contents($debug_file, "Final user details: " . print_r($userDetails, true) . "\n", FILE_APPEND);

    // --- 6. Create/Get User ---
    $orderService = new OrderService($conn, $GLOBALS['site']);
    $userId = $orderService->getOrCreateUser($userDetails);
    if (!$userId) throw new Exception('Failed to create user');

    // --- 7. Calculate Totals from Razorpay ---
    $subtotal = ($orderArray['line_items_total'] ?? 0) / 100; // Convert from paise
    $shipping_fee = ($orderArray['shipping_fee'] ?? 0) / 100;
    $total = $orderArray['amount'] / 100;
    
    file_put_contents($debug_file, "Totals - Subtotal: $subtotal, Shipping: $shipping_fee, Total: $total\n", FILE_APPEND);

    // --- 8. Prepare Order Data (FIXED for name fields) ---
    
    // Helper function to split full name into first/last
    $firstName = '';
    $lastName = '';
    
    if (isset($userDetails['first_name'])) {
        // Already have first/last from user session
        $firstName = $userDetails['first_name'];
        $lastName = $userDetails['last_name'] ?? '';
    } elseif (isset($userDetails['name'])) {
        // Need to split full name from Razorpay
        $fullName = $userDetails['name'];
        $nameParts = explode(' ', $fullName, 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';
    } else {
        $firstName = 'Guest';
        $lastName = '';
    }
    
    $orderDbData = [
        'subtotal' => $subtotal,
        'discount' => 0,
        'shipping_fee' => $shipping_fee,
        'total' => $total,
        'tax' => 0,
        'billing_first_name' => $firstName,
        'billing_last_name' => $lastName,
        'billing_phone' => $userDetails['phone'] ?? '',
        'billing_email' => $userDetails['email'] ?? '',
        'billing_address_1' => $userDetails['address_1'] ?? '',
        'billing_address_2' => '',
        'billing_city' => $userDetails['city'] ?? '',
        'billing_state' => $userDetails['state'] ?? '',
        'billing_country' => $userDetails['country'] ?? 'IN',
        'billing_postcode' => $userDetails['postcode'] ?? '',
        'order_note' => 'Magic Checkout Order'
    ];

    file_put_contents($debug_file, "Order data prepared: " . print_r($orderDbData, true) . "\n", FILE_APPEND);

    // --- 9. Determine payment method ---
    if ($razorpay_order_status === 'placed') {
        $paymentMethod = 'COD';
    } elseif ($razorpay_order_status === 'paid') {
        $paymentMethod = 'Prepaid';
    } else {
        $paymentMethod = 'Unknown';
    }
    if ($is_cod || ($pendingOrder['payment_method'] ?? '') === 'cod') {
        $paymentMethod = 'cod';
    }
    
    file_put_contents($debug_file, "Payment method: $paymentMethod\n", FILE_APPEND);

    // --- 10. Create Order ---
    $orderResult = $orderService->createOrder(
        $orderDbData, 
        $userId, 
        $paymentMethod,
        $razorpay_order_id,
        $razorpay_created_at
    );
    
    if (!$orderResult) throw new Exception('Failed to create order in DB');
    file_put_contents($debug_file, "Order created: ID={$orderResult['order_id']}, Number={$orderResult['order_number']}\n", FILE_APPEND);

    // --- 11. Add Order Items ---
    $orderItems = [];

    if (isset($pendingOrder['cart_items']) && is_array($pendingOrder['cart_items'])) {
        // Multiple items from cart
        foreach ($pendingOrder['cart_items'] as $item_data) {
            $orderItems[] = [
                'product_id' => $item_data['product']['pro_id'],
                'product_name' => $item_data['product']['pro_name'],
                'quantity' => $item_data['cart_item']['quantity'],
                'unit_price' => $item_data['cart_item']['price'],
                'total_price' => $item_data['cart_item']['price'] * $item_data['cart_item']['quantity'],
                'color' => $item_data['cart_item']['color'] ?? '',
                'size' => $item_data['cart_item']['size'] ?? '',
                'variant_id' => $item_data['cart_item']['variant_id'] ?? 0
            ];
        }
    } else {
        // Single item from buy now
        $orderItems[] = [
            'product_id' => $pendingOrder['product_id'],
            'product_name' => $pendingOrder['product_name'],
            'quantity' => $pendingOrder['quantity'],
            'unit_price' => $pendingOrder['price'],
            'total_price' => $pendingOrder['price'] * $pendingOrder['quantity'],
            'color' => $pendingOrder['color'] ?? '',
            'size' => $pendingOrder['size'] ?? '',
            'variant_id' => $pendingOrder['variant_id'] ?? 0
        ];
    }

    $addItemsResult = $orderService->addOrderItems($orderResult['order_id'], $orderItems);
    file_put_contents($debug_file, "Add items result: " . ($addItemsResult ? 'Success' : 'Failed') . "\n", FILE_APPEND);

    // --- 12. Update Payment Status with Signature ---
    $payment_status = $razorpay_order_status ?? 'failed';
    
    $updateStmt = $conn->prepare("UPDATE orders SET 
        payment_status = ?, 
        order_status = 'confirmed', 
        razorpay_payment_id = ?,
        razorpay_signature = ? 
        WHERE order_id = ?");
    
    $updateStmt->bind_param("sssi", $payment_status, $payment_id, $signature, $orderResult['order_id']);
    $updateResult = $updateStmt->execute();
    file_put_contents($debug_file, "Payment status update: " . ($updateResult ? 'Success' : 'Failed') . "\n", FILE_APPEND);

    // --- 13. Clean Up Session ---
    unset($_SESSION['pending_magic_order'][$razorpay_order_id]);
    if (isset($_SESSION['buy_now'])) unset($_SESSION['buy_now']);
    if (isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    // --- 14. Trigger Email (Async) ---
    $ch = curl_init($GLOBALS['site'] . "cron/send-order-mails.php?order_id=" . $orderResult['order_id']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOSIGNAL, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    curl_close($ch);

    // --- 15. Return Success ---
    $response = [
        'success' => true,
        'order_id' => $orderResult['order_id'],
        'order_number' => $orderResult['order_number'],
        'confirmation_url' => $GLOBALS['site'] . 'order-confirmation/' . $orderResult['order_id']
    ];

    file_put_contents($debug_file, "Success response: " . print_r($response, true) . "\n", FILE_APPEND);
    echo json_encode($response);

} catch (SignatureVerificationError $e) {
    file_put_contents($debug_file, "Signature error: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Signature verification failed']);
} catch (Exception $e) {
    file_put_contents($debug_file, "Error: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>