<?php
require_once __DIR__ . '/../config/connect.php';
require_once __DIR__ . '/../config/ithink-api.php';
require_once __DIR__ . '/../models/OrderService.php';

// Initialize
$orderService = new OrderService($conn, $site);

// Get orders that need tracking updates (shipped but not delivered)
$sql = "SELECT order_id, tracking_number FROM orders 
        WHERE order_status IN ('shipped', 'out_for_delivery') 
        AND tracking_number IS NOT NULL
        AND (delivered_date IS NULL OR DATE(updated_at) < CURDATE())";
$stmt = $conn->prepare($sql);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($orders as $order) {
    // Fetch latest tracking from iThink
    $data = [
        'access_token' => ITHINK_ACCESS_TOKEN,
        'secret_key' => ITHINK_SECRET_KEY,
        'tracking_id' => $order['tracking_number']
    ];
    
    $ch = curl_init(ITHINK_API_URL . 'order/track.json');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if (isset($result['success']) && $result['success']) {
        $trackingData = $result['data'];
        
        // Update order status based on shipment status
        $shipmentStatus = strtolower($trackingData['status'] ?? '');
        
        if ($shipmentStatus == 'delivered') {
            $updateSql = "UPDATE orders SET 
                         order_status = 'delivered',
                         delivered_date = NOW(),
                         updated_at = NOW()
                         WHERE order_id = ?";
        } elseif ($shipmentStatus == 'out_for_delivery') {
            $updateSql = "UPDATE orders SET 
                         order_status = 'out_for_delivery',
                         updated_at = NOW()
                         WHERE order_id = ?";
        }
        
        if (isset($updateSql)) {
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("i", $order['order_id']);
            $updateStmt->execute();
        }
        
        // Store tracking data
        $orderService->storeTrackingData($order['order_id'], $trackingData);
    }
}

echo "Tracking sync completed at " . date('Y-m-d H:i:s');
?>