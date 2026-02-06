<?php
require_once __DIR__ . '/config/connect.php';

// Get your specific order
$orderNumber = 'ORD202602046982B22C1F30A';

echo "<h2>Debug Order: $orderNumber</h2>";
echo "<pre>";

$sql = "SELECT * FROM orders WHERE order_number = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $orderNumber);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo "Order not found!\n";
    exit;
}

echo "=== ORDER DATA ===\n";
foreach ($order as $key => $value) {
    echo str_pad($key, 20) . ": " . (is_string($value) ? $value : json_encode($value)) . "\n";
}

echo "\n=== SHIPPING ADDRESS JSON ===\n";
$shippingAddress = json_decode($order['shipping_address'], true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON Error: " . json_last_error_msg() . "\n";
    echo "Raw: " . $order['shipping_address'] . "\n";
    
    // Try to fix common JSON issues
    $fixedJson = preg_replace('/[\x00-\x1F\x7F]/u', '', $order['shipping_address']);
    echo "Fixed: " . $fixedJson . "\n";
    $shippingAddress = json_decode($fixedJson, true);
}

if ($shippingAddress) {
    echo "Decoded successfully:\n";
    print_r($shippingAddress);
}

echo "\n=== TEST JSON DATA ===\n";
$testData = [
    'name' => 'Test Name',
    'address' => '123 Test St',
    'city' => 'Mumbai',
    'state' => 'MH',
    'postcode' => '400001'
];

$testJson = json_encode($testData);
echo "Test JSON: $testJson\n";
echo "Decode test: " . print_r(json_decode($testJson, true), true) . "\n";

echo "</pre>";

// Try to fix the shipping address
if (!empty($order['shipping_address']) && json_last_error() !== JSON_ERROR_NONE) {
    echo "<h3>Fix Shipping Address</h3>";
    
    // Extract data from string
    $addressString = $order['shipping_address'];
    
    // Try to parse as string first
    echo "Raw string: " . htmlspecialchars($addressString) . "<br>";
    
    // Create new JSON
    $newAddress = [
        'name' => 'Customer Name',
        'address' => $addressString,
        'city' => 'Unknown',
        'state' => 'Unknown',
        'country' => 'India',
        'postcode' => '000000'
    ];
    
    $newJson = json_encode($newAddress);
    echo "New JSON: " . htmlspecialchars($newJson) . "<br>";
    
    // Update in database
    $updateSql = "UPDATE orders SET shipping_address = ? WHERE order_id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("si", $newJson, $order['order_id']);
    if ($stmt->execute()) {
        echo "✅ Shipping address updated!\n";
    }
}
?>