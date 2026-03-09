<?php
session_start();
header('Content-Type: application/json');

// Force session to save
session_write_close();

// Log EVERYTHING to a file
$logFile = __DIR__ . '/../logs/shipping-debug.log';
$timestamp = date('Y-m-d H:i:s');
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

file_put_contents($logFile, "[$timestamp] ===== SHIPPING INFO HIT =====\n", FILE_APPEND);
file_put_contents($logFile, "[$timestamp] Headers: " . print_r(getallheaders(), true) . "\n", FILE_APPEND);
file_put_contents($logFile, "[$timestamp] Raw: $rawInput\n", FILE_APPEND);
file_put_contents($logFile, "[$timestamp] Session ID: " . session_id() . "\n", FILE_APPEND);
file_put_contents($logFile, "[$timestamp] Session before: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

// Handle test requests
if (isset($input['test']) || isset($input['debug'])) {
    echo json_encode(['test' => true, 'message' => 'Shipping info test working']);
    exit;
}

// Store data if we have order_id
if (isset($input['razorpay_order_id'])) {
    $order_id = $input['razorpay_order_id'];
    
    // Store in session with multiple keys for redundancy
    $_SESSION['magic_customer_data'][$order_id] = [
        'email' => $input['email'] ?? '',
        'phone' => $input['contact'] ?? '',
        'addresses' => $input['addresses'] ?? [],
        'timestamp' => time()
    ];
    
    // Also store in a global key for debugging
    $_SESSION['last_customer_data'] = [
        'order_id' => $order_id,
        'data' => $input,
        'time' => date('Y-m-d H:i:s')
    ];
    
    file_put_contents($logFile, "[$timestamp] STORED customer data for order: $order_id\n", FILE_APPEND);
    file_put_contents($logFile, "[$timestamp] Data stored: " . print_r($_SESSION['magic_customer_data'][$order_id], true) . "\n", FILE_APPEND);
} else {
    file_put_contents($logFile, "[$timestamp] WARNING: No razorpay_order_id in request\n", FILE_APPEND);
}

file_put_contents($logFile, "[$timestamp] Session after: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

// Magic Checkout response format
$response = [
    'addresses' => []
];

if (isset($input['addresses']) && is_array($input['addresses'])) {
    foreach ($input['addresses'] as $addr) {
        $response['addresses'][] = [
            'id' => $addr['id'] ?? '0',
            'zipcode' => $addr['zipcode'] ?? '560001',
            'country' => $addr['country'] ?? 'IN',
            'shipping_methods' => [
                [
                    'id' => 'standard',
                    'name' => 'Standard Delivery',
                    'description' => '3-5 business days',
                    'serviceable' => true,
                    'shipping_fee' => 0,
                    'cod' => true,
                    'cod_fee' => 0
                ]
            ]
        ];
    }
}

file_put_contents($logFile, "[$timestamp] Response: " . json_encode($response) . "\n", FILE_APPEND);
file_put_contents($logFile, "[$timestamp] ===== END SHIPPING INFO =====\n\n", FILE_APPEND);

echo json_encode($response);
?>