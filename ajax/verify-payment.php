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

    $razorpay_payment_id = $input['razorpay_payment_id'] ?? '';
    $razorpay_order_id = $input['razorpay_order_id'] ?? '';
    $razorpay_signature = $input['razorpay_signature'] ?? '';
    $is_cod = $input['is_cod'] ?? false;

    if (!$razorpay_payment_id || !$razorpay_order_id || !$razorpay_signature) {
        throw new Exception('Missing payment details');
    }

    // --- 2. Verify Signature ---
    $paymentSetting = new PaymentSmtpSetting($conn);
    $api_key = $paymentSetting->getSetting('razorpay', 'api_key');
    $api_secret = $paymentSetting->getSetting('razorpay', 'api_secret');

    $api = new Api($api_key, $api_secret);
    
    try {
        $api->utility->verifyPaymentSignature([
            'razorpay_order_id' => $razorpay_order_id,
            'razorpay_payment_id' => $razorpay_payment_id,
            'razorpay_signature' => $razorpay_signature
        ]);
        file_put_contents($debug_file, "✅ Signature verified\n", FILE_APPEND);
    } catch (SignatureVerificationError $e) {
        // Manual verification as fallback
        $generated_signature = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, $api_secret);
        if ($generated_signature !== $razorpay_signature) {
            throw new Exception('Invalid signature');
        }
        file_put_contents($debug_file, "✅ Manual signature verified\n", FILE_APPEND);
    }

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

    // --- 4. Fetch Complete Order Details from Razorpay ---
    $order = $api->order->fetch($razorpay_order_id);
    $orderArray = $order->toArray();
    file_put_contents($debug_file, "Razorpay order: " . print_r($orderArray, true) . "\n", FILE_APPEND);

    $razorpay_order_status = $orderArray['status'] ?? 'unknown';
    $razorpay_created_at = $orderArray['created_at'] ?? null;
    $customerDetails = $orderArray['customer_details'] ?? null;

    if ($customerDetails) {
        file_put_contents($debug_file, "CUSTOMER DETAILS: " . print_r($customerDetails, true) . "\n", FILE_APPEND);
    }

    // --- 5. Verify Amount ---
    $payment = $api->payment->fetch($razorpay_payment_id);
    
    if ($payment->amount != ($pendingOrder['total'] * 100)) {
        throw new Exception('Payment amount mismatch');
    }

    // --- 6. Extract User Details ---
    $userDetails = extractUserDetails($customerDetails, $pendingOrder);
    file_put_contents($debug_file, "User details: " . print_r($userDetails, true) . "\n", FILE_APPEND);

    // --- 7. Create/Get User ---
    $orderService = new OrderService($conn, $GLOBALS['site']);
    $userId = $orderService->getOrCreateUser($userDetails);
    if (!$userId) throw new Exception('Failed to create user');

    // --- 8. Prepare Order Data ---
    $orderDbData = [
        'subtotal' => $pendingOrder['subtotal'],
        'discount' => 0,
        'shipping_fee' => $pendingOrder['shipping_fee'] ?? 0,
        'total' => $pendingOrder['total'],
        'tax' => 0,
        'billing_first_name' => $userDetails['first_name'] ?? $userDetails['name'] ?? 'Guest',
        'billing_last_name' => $userDetails['last_name'] ?? '',
        'billing_phone' => $userDetails['phone'] ?? '',
        'billing_email' => $userDetails['email'] ?? '',
        'billing_address_1' => $userDetails['address_1'] ?? $userDetails['address'] ?? '',
        'billing_address_2' => $userDetails['address_2'] ?? '',
        'billing_city' => $userDetails['city'] ?? '',
        'billing_state' => $userDetails['state'] ?? '',
        'billing_country' => $userDetails['country'] ?? 'IN',
        'billing_postcode' => $userDetails['postcode'] ?? '',
        'order_note' => 'Magic Checkout Order'
    ];

    // --- 9. Determine Payment Method ---
    if ($razorpay_order_status === 'placed') {
        $paymentMethod = 'COD';
    } elseif ($razorpay_order_status === 'paid') {
        $paymentMethod = 'Prepaid';
    } else {
        $paymentMethod = 'Unknown';
    }
    
    file_put_contents($debug_file, "Payment method: $paymentMethod\n", FILE_APPEND);

    // --- 10. Create Order with Payment Details ---
    $paymentDetails = [
        'razorpay_payment_id' => $razorpay_payment_id,
        'razorpay_order_id' => $razorpay_order_id,
        'razorpay_signature' => $razorpay_signature,
        'method' => $paymentMethod,
        'amount' => $orderDbData['total'],
        'status' => 'paid'
    ];

    if ($paymentMethod === 'cod') {
        $cod_advance = 200;
        $cod_remaining = $orderDbData['total'] - $cod_advance;
        $paymentDetails['cod_advance'] = $cod_advance;
        $paymentDetails['cod_remaining'] = $cod_remaining;
        $paymentDetails['status'] = 'cod_advance_paid';
    }

    $orderDbData['payment_details'] = $paymentDetails;

    $orderResult = $orderService->createOrder(
        $orderDbData, 
        $userId, 
        $paymentMethod,
        $razorpay_order_id,
        $razorpay_created_at
    );
    
    if (!$orderResult) throw new Exception('Failed to create order in DB');

    // --- 11. Add Order Items ---
    $orderItems = [];
    
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

    $addItemsResult = $orderService->addOrderItems($orderResult['order_id'], $orderItems);
    file_put_contents($debug_file, "Add items result: " . ($addItemsResult ? 'Success' : 'Failed') . "\n", FILE_APPEND);

    // --- 12. Update Payment Status with Signature ---
    $updateStmt = $conn->prepare("UPDATE orders SET 
        payment_status = ?, 
        order_status = 'confirmed', 
        razorpay_payment_id = ?,
        razorpay_signature = ?,
        notes = ?
        WHERE order_id = ?");
    
    $payment_status = ($paymentMethod === 'cod') ? 'cod_advance_paid' : 'paid';
    $notes = json_encode(['payment_details' => $paymentDetails]);
    
    $updateStmt->bind_param("ssssi", 
        $payment_status, 
        $razorpay_payment_id, 
        $razorpay_signature, 
        $notes,
        $orderResult['order_id']
    );
    $updateStmt->execute();

    // --- 13. Clear Session ---
    unset($_SESSION['pending_magic_order'][$razorpay_order_id]);
    if (isset($_SESSION['buy_now'])) unset($_SESSION['buy_now']);
    $_SESSION['cart'] = [];

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

/**
 * Extract user details from various sources
 */
function extractUserDetails($customerDetails, $pendingOrder) {
    $userDetails = [];
    
    // Try from Razorpay customer details first
    if ($customerDetails) {
        $fullName = $customerDetails['name'] ?? 'Guest User';
        $nameParts = explode(' ', $fullName, 2);
        
        $address = $customerDetails['shipping_address'] ?? $customerDetails['billing_address'] ?? [];
        
        // Clean phone number
        $phone = preg_replace('/[^0-9]/', '', $customerDetails['contact'] ?? '');
        if (strlen($phone) > 10) $phone = substr($phone, -10);
        
        $userDetails = [
            'first_name' => $nameParts[0],
            'last_name' => $nameParts[1] ?? '',
            'name' => $fullName,
            'phone' => $phone ?: '9999999999',
            'email' => $customerDetails['email'] ?? ('guest_' . time() . '@example.com'),
            'address_1' => trim(($address['line1'] ?? '') . ' ' . ($address['line2'] ?? '')),
            'city' => $address['city'] ?? '',
            'state' => $address['state'] ?? '',
            'postcode' => $address['zipcode'] ?? '',
            'country' => strtoupper($address['country'] ?? 'IN')
        ];
    } 
    // Try from session
    elseif (isset($_SESSION['user_id'])) {
        global $conn;
        $user_sql = "SELECT first_name, last_name, email, phone, address, city, state, zip_code FROM users WHERE id = ?";
        $user_stmt = $conn->prepare($user_sql);
        $user_stmt->bind_param("i", $_SESSION['user_id']);
        $user_stmt->execute();
        $user = $user_stmt->get_result()->fetch_assoc();
        
        $userDetails = [
            'first_name' => $user['first_name'] ?? 'User',
            'last_name' => $user['last_name'] ?? '',
            'phone' => $user['phone'] ?? '9999999999',
            'email' => $user['email'] ?? ('user_' . time() . '@example.com'),
            'address_1' => $user['address'] ?? '',
            'city' => $user['city'] ?? '',
            'state' => $user['state'] ?? '',
            'postcode' => $user['zip_code'] ?? '',
            'country' => 'IN'
        ];
    }
    // Fallback to pending order data
    else {
        $userDetails = [
            'first_name' => $pendingOrder['user_name'] ?? 'Guest',
            'last_name' => '',
            'name' => $pendingOrder['user_name'] ?? 'Guest User',
            'phone' => $pendingOrder['user_phone'] ?? '9999999999',
            'email' => $pendingOrder['user_email'] ?? ('guest_' . time() . '@example.com'),
            'address_1' => $pendingOrder['address'] ?? '',
            'city' => $pendingOrder['city'] ?? '',
            'state' => $pendingOrder['state'] ?? '',
            'postcode' => $pendingOrder['postcode'] ?? '',
            'country' => 'IN'
        ];
    }
    
    return $userDetails;
}
?>