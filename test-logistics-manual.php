<?php
require_once __DIR__ . '/config/connect.php';
require_once __DIR__ . '/api/LogisticsApi.php';

echo "<h2>Manual iThink Test</h2>";
echo "<pre>";

// Test with sample data
$sampleOrder = [
    'consignee_name' => 'Test Customer',
    'consignee_address' => '123 Test Street, Test Area',
    'consignee_city' => 'Mumbai',
    'consignee_state' => 'Maharashtra',
    'consignee_country' => 'India',
    'consignee_pincode' => '400001',
    'consignee_phone' => '9876543210',
    'consignee_email' => 'test@example.com',
    'order_number' => 'TEST-' . time(),
    'invoice_value' => 100,
    'cod_amount' => 0,
    'payment_type' => 'prepaid',
    'product_name' => 'Test Product',
    'quantity' => 1,
    'weight' => 0.5
];

try {
    $api = new LogisticsApi($conn);
    
    echo "Sending test order to iThink...\n";
    echo "Order Number: " . $sampleOrder['order_number'] . "\n";
    
    $result = $api->createShipment($sampleOrder);
    
    echo "\n=== API RESPONSE ===\n";
    print_r($result);
    
    if (isset($result['success']) && $result['success']) {
        echo "\n✅ SUCCESS! Order created in iThink Dashboard.\n";
        echo "Tracking ID: " . ($result['data']['tracking_id'] ?? 'N/A') . "\n";
        echo "AWB Number: " . ($result['data']['awb_number'] ?? 'N/A') . "\n";
        
        // Save to test table
        $sql = "INSERT INTO test_logistics (order_data, response, created_at) 
                VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", 
            json_encode($sampleOrder),
            json_encode($result)
        );
        $stmt->execute();
        
    } else {
        echo "\n❌ FAILED!\n";
        echo "Error: " . ($result['message'] ?? 'Unknown error') . "\n";
        
        // Check specific error cases
        if (isset($result['error_code'])) {
            echo "Error Code: " . $result['error_code'] . "\n";
        }
        if (isset($result['data']['error'])) {
            echo "API Error: " . $result['data']['error'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>";
echo "<a href='debug-logistics.php'>← Back to Debug</a>";
?>