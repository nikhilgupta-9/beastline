<?php
session_start();
require_once __DIR__ . '/../config/connect.php';
require_once __DIR__ . '/../models/OrderService.php';
require_once __DIR__ . '/../admin/models/PaymentSmtpSetting.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Log file
$logFile = __DIR__ . '/../logs/verify-debug.log';

function writeLog($msg, $data = null) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $log = "[$timestamp] $msg";
    if ($data !== null) {
        $log .= ": " . print_r($data, true);
    }
    file_put_contents($logFile, $log . "\n", FILE_APPEND);
    error_log($log);
}

writeLog("=== VERIFY PAYMENT HIT ===");

try {
    $rawInput = file_get_contents('php://input');
    writeLog("Raw Input", $rawInput);
    
    $payload = json_decode($rawInput, true);
    
    if (!$payload || isset($payload['test'])) {
        echo json_encode(['success' => true, 'test' => true]);
        exit;
    }
    
    $razorpay_payment_id = $payload['razorpay_payment_id'] ?? '';
    $razorpay_order_id = $payload['razorpay_order_id'] ?? '';
    $razorpay_signature = $payload['razorpay_signature'] ?? '';
    
    writeLog("Payment ID", $razorpay_payment_id);
    writeLog("Order ID", $razorpay_order_id);
    writeLog("Full Session", $_SESSION);
    
    // Get pending order
    if (!isset($_SESSION['pending_magic_order'][$razorpay_order_id])) {
        writeLog("ERROR: Pending order not found", array_keys($_SESSION['pending_magic_order'] ?? []));
        
        // Try to find by searching all pending orders
        foreach ($_SESSION['pending_magic_order'] as $key => $order) {
            if ($order['razorpay_order_id'] == $razorpay_order_id) {
                $pendingOrder = $order;
                $razorpay_order_id = $key;
                writeLog("Found pending order by value", $pendingOrder);
                break;
            }
        }
        
        if (!isset($pendingOrder)) {
            throw new Exception('Pending order not found');
        }
    } else {
        $pendingOrder = $_SESSION['pending_magic_order'][$razorpay_order_id];
    }
    
    writeLog("Found pending order", $pendingOrder);
    
    // Get customer data - CHECK IF IT EXISTS
    $customerData = null;
    if (isset($_SESSION['magic_customer_data'][$razorpay_order_id])) {
        $customerData = $_SESSION['magic_customer_data'][$razorpay_order_id];
        writeLog("Found customer data by ID", $customerData);
    } else {
        writeLog("WARNING: No customer data for order ID: $razorpay_order_id");
        writeLog("Available customer data keys", array_keys($_SESSION['magic_customer_data'] ?? []));
        
        // Try last_customer_data as fallback
        if (isset($_SESSION['last_customer_data']) && $_SESSION['last_customer_data']['order_id'] == $razorpay_order_id) {
            $customerData = $_SESSION['last_customer_data']['data'];
            writeLog("Using last_customer_data", $customerData);
        }
    }
    
    // Build user details
    $userDetails = [];
    
    if ($customerData) {
        $address = $customerData['addresses'][0] ?? [];
        $userDetails = [
            'first_name' => explode(' ', $address['name'] ?? 'Guest')[0],
            'last_name' => explode(' ', $address['name'] ?? 'User')[1] ?? 'User',
            'phone' => preg_replace('/[^0-9]/', '', $customerData['phone'] ?? '9999999999'),
            'email' => $customerData['email'] ?? ('guest_' . time() . '@example.com'),
            'address_1' => ($address['address'] ?? '') . ' ' . ($address['address2'] ?? ''),
            'city' => $address['city'] ?? 'City',
            'state' => $address['state_code'] ?? $address['state'] ?? 'State',
            'postcode' => $address['zipcode'] ?? $address['pincode'] ?? '000000'
        ];
    } elseif (isset($_SESSION['user_id'])) {
        // Use logged-in user
        $user_sql = "SELECT * FROM users WHERE id = ?";
        $user_stmt = $conn->prepare($user_sql);
        $user_stmt->bind_param("i", $_SESSION['user_id']);
        $user_stmt->execute();
        $user = $user_stmt->get_result()->fetch_assoc();
        
        $userDetails = [
            'first_name' => $user['first_name'] ?? 'User',
            'last_name' => $user['last_name'] ?? '',
            'phone' => $user['mobile'] ?? $user['phone'] ?? '9999999999',
            'email' => $user['email'] ?? 'user@example.com',
            'address_1' => $user['address'] ?? 'Default Address',
            'city' => $user['city'] ?? 'City',
            'state' => $user['state'] ?? 'State',
            'postcode' => $user['zip_code'] ?? $user['pincode'] ?? '000000'
        ];
    } else {
        // Emergency fallback
        $userDetails = [
            'first_name' => 'Guest',
            'last_name' => 'User',
            'phone' => '9999999999',
            'email' => 'guest_' . time() . '@example.com',
            'address_1' => 'Address Pending',
            'city' => 'City',
            'state' => 'State',
            'postcode' => '000000'
        ];
    }
    
    writeLog("User Details", $userDetails);
    
    // Create order
    $orderService = new OrderService($conn, $GLOBALS['site']);
    $userId = $orderService->getOrCreateUser($userDetails);
    
    $orderData = [
        'subtotal' => $pendingOrder['total'],
        'discount' => 0,
        'shipping_fee' => 0,
        'total' => $pendingOrder['total'],
        'tax' => 0,
        'billing_first_name' => $userDetails['first_name'],
        'billing_last_name' => $userDetails['last_name'],
        'billing_phone' => $userDetails['phone'],
        'billing_email' => $userDetails['email'],
        'billing_address_1' => $userDetails['address_1'],
        'billing_address_2' => '',
        'billing_city' => $userDetails['city'],
        'billing_state' => $userDetails['state'],
        'billing_country' => 'India',
        'billing_postcode' => $userDetails['postcode']
    ];
    
    $orderResult = $orderService->createOrder($orderData, $userId, 'razorpay', $razorpay_order_id);
    
    // Add order items
    $orderItems = [[
        'product_id' => $pendingOrder['product_id'],
        'product_name' => $pendingOrder['product_name'],
        'quantity' => $pendingOrder['quantity'],
        'unit_price' => $pendingOrder['price'],
        'total_price' => $pendingOrder['price'] * $pendingOrder['quantity'],
        'color' => $pendingOrder['color'] ?? '',
        'size' => $pendingOrder['size'] ?? '',
        'sku' => $pendingOrder['sku'] ?? '',
        'variant_id' => $pendingOrder['variant_id'] ?? 0
    ]];
    
    $orderService->addOrderItems($orderResult['order_id'], $orderItems);
    
    // Update payment status
    $updateStmt = $conn->prepare("UPDATE orders SET payment_status = 'completed', payment_transaction_id = ? WHERE order_id = ?");
    $updateStmt->bind_param("si", $razorpay_payment_id, $orderResult['order_id']);
    $updateStmt->execute();
    
    // Clear session
    unset($_SESSION['pending_magic_order'][$razorpay_order_id]);
    unset($_SESSION['magic_customer_data'][$razorpay_order_id]);
    
    writeLog("Order created successfully", $orderResult);
    
    echo json_encode([
        'success' => true,
        'order_id' => $orderResult['order_id'],
        'order_number' => $orderResult['order_number'],
        'confirmation_url' => $GLOBALS['site'] . 'order-confirmation/' . $orderResult['order_id']
    ]);
    
} catch (Exception $e) {
    writeLog("ERROR", $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>