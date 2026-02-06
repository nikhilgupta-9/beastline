<?php
require_once __DIR__ . '/config/connect.php';
require_once __DIR__ . '/api/LogisticsApi.php';

echo "<h2>Test with Real Order from Database</h2>";
echo "<pre>";

// Get your specific order
$orderNumber = 'ORD202602046982B22C1F30A'; // Your order number

$sql = "SELECT o.*, u.first_name, u.last_name, u.mobile, u.email, u.mobile as user_phone
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.order_number = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $orderNumber);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo "Order not found!\n";
    exit;
}

echo "=== Order Details ===\n";
echo "Order Number: " . $order['order_number'] . "\n";
echo "Customer: " . $order['first_name'] . " " . $order['last_name'] . "\n";
echo "Email: " . $order['email'] . "\n";
echo "Mobile (from users): " . ($order['mobile'] ?? 'NULL') . "\n";
echo "Phone (from users): " . ($order['user_phone'] ?? 'NULL') . "\n";

// Check shipping address
$shippingAddress = json_decode($order['shipping_address'], true);
echo "\n=== Shipping Address ===\n";
if ($shippingAddress && is_array($shippingAddress)) {
    print_r($shippingAddress);
    
    // Check if phone is in shipping address
    if (isset($shippingAddress['phone'])) {
        echo "Phone in shipping address: " . $shippingAddress['phone'] . "\n";
    }
} else {
    echo "No valid shipping address found\n";
}

// error_log('FINAL PHONE TYPE: ' . gettype($shipment['phone']));
// error_log('FINAL PHONE VALUE: ' . $shipment['phone']);


// Test API
echo "\n=== Testing API ===\n";

$api = new LogisticsApi();

// Use phone from shipping address first, then from users table
$phone = $shippingAddress['phone'] ?? $order['mobile'] ?? $order['user_phone'] ?? '9876543210';

$orderData = [
    'order_number' => $order['order_number'],
    'total_amount' => number_format($order['final_amount'], 2, '.', ''),
    'consignee_name' => $shippingAddress['name'] ?? ($order['first_name'] . ' ' . $order['last_name']),
    'consignee_address' => $shippingAddress['address'] ?? 'Unknown Address',
    'consignee_city' => $shippingAddress['city'] ?? 'Mumbai',
    'consignee_state' => $shippingAddress['state'] ?? 'Maharashtra',
    'consignee_pincode' => $shippingAddress['postcode'] ?? '400001',
    'consignee_country' => $shippingAddress['country'] ?? 'India',
    'consignee_phone' => $phone,
    'consignee_alt_phone' => $phone,
    'consignee_email' => $order['email'],
    'payment_type' => $order['payment_method'] == 'cod' ? 'cod' : 'prepaid',
    'cod_amount' => $order['payment_method'] == 'cod' ? $order['final_amount'] : 0,
    'product_name' => 'Order #' . $order['order_number'],
    'quantity' => 1,
    'weight' => 0.5,
    'logistics' => 'delhivery'
];

echo "Using phone: $phone\n";

$result = $api->createShipment($orderData);

echo "\nAPI Result:\n";
print_r($result);

if (isset($result['status']) && $result['status'] == 'success') {
    if (isset($result['data']) && is_array($result['data'])) {
        foreach ($result['data'] as $key => $shipment) {
            if (($shipment['status'] ?? '') == 'Success') {
                echo "\n🎉 SUCCESS! Order created in iThink!\n";
                echo "Waybill: " . ($shipment['waybill'] ?? '') . "\n";
                echo "Tracking URL: " . ($shipment['tracking_url'] ?? '') . "\n";
                
                // Update database
                $updateSql = "UPDATE orders SET 
                             tracking_number = ?,
                             awb_number = ?,
                             courier_name = ?,
                             shipment_data = ?,
                             order_status = 'ready_to_dispatch'
                             WHERE order_number = ?";
                
                $stmt = $conn->prepare($updateSql);
                $waybill = $shipment['waybill'] ?? '';
                $courier = $shipment['logistic_name'] ?? 'Delhivery';
                $shipmentJson = json_encode($result);
                
                $stmt->bind_param("sssss", 
                    $waybill, 
                    $waybill, 
                    $courier, 
                    $shipmentJson, 
                    $order['order_number']
                );
                
                if ($stmt->execute()) {
                    echo "✅ Database updated!\n";
                }
            } else {
                echo "\n❌ Error: " . ($shipment['remark'] ?? 'Unknown') . "\n";
            }
        }
    }
}

echo "</pre>";
?>