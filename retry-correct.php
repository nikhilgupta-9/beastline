<?php
// Create logs directory
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

require_once __DIR__ . '/config/connect.php';
require_once __DIR__ . '/api/LogisticsApi.php';

header('Content-Type: application/json');

$orderId = $_GET['order_id'] ?? 0;

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

try {
    // Get order details
    $sql = "SELECT o.*, u.first_name, u.last_name, u.mobile, u.email 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            WHERE o.order_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    
    if (!$order) {
        throw new Exception('Order not found with ID: ' . $orderId);
    }
    
    // Get shipping address
    $shippingAddress = json_decode($order['shipping_address'], true);
    if (!$shippingAddress || json_last_error() !== JSON_ERROR_NONE) {
        $shippingAddress = [
            'name' => $order['first_name'] . ' ' . $order['last_name'],
            'address' => 'Address not specified',
            'city' => 'Unknown',
            'state' => 'Unknown',
            'postcode' => '000000'
        ];
    }
    
    // Get order items for product details
    $itemsSql = "SELECT * FROM order_items WHERE order_id = ?";
    $itemsStmt = $conn->prepare($itemsSql);
    $itemsStmt->bind_param("i", $orderId);
    $itemsStmt->execute();
    $items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Prepare order data for iThink
    $orderData = [
        'order_number' => $order['order_number'],
        'total_amount' => number_format($order['final_amount'], 2, '.', ''),
        'consignee_name' => $shippingAddress['name'] ?? ($order['first_name'] . ' ' . $order['last_name']),
        'consignee_address' => $shippingAddress['address'] ?? 'Unknown',
        'consignee_city' => $shippingAddress['city'] ?? 'Unknown',
        'consignee_state' => $shippingAddress['state'] ?? 'Unknown',
        'consignee_pincode' => $shippingAddress['postcode'] ?? '000000',
        'consignee_country' => $shippingAddress['country'] ?? 'India',
        'consignee_phone' => $order['mobile'] ?? '9876543210',
        'consignee_email' => $order['email'] ?? 'customer@example.com',
        'payment_type' => $order['payment_method'] == 'cod' ? 'cod' : 'prepaid',
        'cod_amount' => $order['payment_method'] == 'cod' ? $order['final_amount'] : 0,
        'product_name' => !empty($items) ? $items[0]['product_name'] : 'Order #' . $order['order_number'],
        'quantity' => !empty($items) ? array_sum(array_column($items, 'quantity')) : 1,
        'weight' => 0.5, // Default weight
        'length' => 15,
        'width' => 10,
        'height' => 5,
        'logistics' => 'delhivery' // Default courier
    ];
    
    // Initialize API
    $api = new LogisticsApi();
    
    // Create shipment
    $result = $api->createShipment($orderData);
    
    // Save result to log
    file_put_contents($logDir . '/retry-result-' . date('Y-m-d') . '.log', 
        date('Y-m-d H:i:s') . " - Order: " . $order['order_number'] . "\n" .
        "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n", 
        FILE_APPEND);
    
    if (isset($result['status']) && $result['status'] == 'success') {
        // Get tracking information
        $trackingData = null;
        if (isset($result['data']) && is_array($result['data'])) {
            $firstKey = array_key_first($result['data']);
            $trackingData = $result['data'][$firstKey] ?? null;
        }
        
        // Update order in database
        $updateSql = "UPDATE orders SET 
                     tracking_number = ?,
                     awb_number = ?,
                     courier_name = ?,
                     shipment_data = ?,
                     order_status = 'ready_to_dispatch',
                     updated_at = NOW()
                     WHERE order_id = ?";
        
        $stmt = $conn->prepare($updateSql);
        
        $trackingNumber = $trackingData['waybill'] ?? '';
        $awbNumber = $trackingData['waybill'] ?? '';
        $courierName = $trackingData['logistic_name'] ?? 'Delhivery';
        $shipmentJson = json_encode($result);
        
        $stmt->bind_param(
            "ssssi",
            $trackingNumber,
            $awbNumber,
            $courierName,
            $shipmentJson,
            $orderId
        );
        
        if ($stmt->execute()) {
            $response = [
                'success' => true,
                'message' => 'Order successfully synced to iThink Logistics!',
                'order_number' => $order['order_number'],
                'tracking_number' => $trackingNumber,
                'awb_number' => $awbNumber,
                'courier' => $courierName,
                'tracking_url' => $trackingData['tracking_url'] ?? 'https://ithinklogistics.com/track/' . $trackingNumber,
                'dashboard_url' => 'https://my.ithinklogistics.com/dashboard/orders'
            ];
            
            echo json_encode($response);
        } else {
            throw new Exception('Failed to update order in database: ' . $stmt->error);
        }
        
    } else {
        $errorMsg = $result['message'] ?? 'Unknown error';
        $htmlMsg = $result['html_message'] ?? '';
        
        throw new Exception("iThink API Error: $errorMsg" . ($htmlMsg ? " ($htmlMsg)" : ""));
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'order_id' => $orderId
    ]);
}
?>