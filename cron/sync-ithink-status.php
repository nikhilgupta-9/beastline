<?php
require_once '../config/connect.php';
require_once '../config/ithink-logistics.php';
require_once '../includes/ithink-logistics.php';

$ithink = new iThinkLogistics();

// Get orders with AWB numbers
$sql = "SELECT o.order_id, o.awb_number, o.order_status, st.status as tracking_status
        FROM orders o
        JOIN shipment_tracking st ON o.order_id = st.order_id
        WHERE o.awb_number IS NOT NULL 
        AND o.order_status NOT IN ('delivered', 'cancelled')
        AND st.updated_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)";
$result = $conn->query($sql);

while ($order = $result->fetch_assoc()) {
    $response = $ithink->getTracking($order['awb_number']);
    
    if ($response['status'] && isset($response['data']['success']) && $response['data']['success']) {
        $trackingData = $response['data']['data'];
        $currentStatus = $trackingData['status'] ?? null;
        
        if ($currentStatus) {
            // Map iThink status to your order status
            $statusMap = [
                'In Transit' => 'shipped',
                'Out for Delivery' => 'shipped',
                'Delivered' => 'delivered',
                'Exception' => 'processing',
                'Returned' => 'cancelled'
            ];
            
            $newOrderStatus = $statusMap[$currentStatus] ?? $order['order_status'];
            
            // Update if status changed
            if ($newOrderStatus != $order['order_status']) {
                $updateSql = "UPDATE orders SET order_status = ? WHERE order_id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("si", $newOrderStatus, $order['order_id']);
                $updateStmt->execute();
                
                // Log status change
                if ($newOrderStatus == 'delivered') {
                    $historySql = "INSERT INTO shipment_history 
                        (order_id, status, location, remarks, date_time)
                        VALUES (?, 'Delivered', 'Auto-sync', 'Automatically updated via iThink API', NOW())";
                    $historyStmt = $conn->prepare($historySql);
                    $historyStmt->bind_param("i", $order['order_id']);
                    $historyStmt->execute();
                }
            }
            
            // Update tracking data
            $trackingDataJson = json_encode($response['data']);
            $trackingUpdateSql = "UPDATE shipment_tracking SET 
                tracking_data = ?, 
                status = ?,
                updated_at = NOW()
                WHERE order_id = ?";
            
            $trackingUpdateStmt = $conn->prepare($trackingUpdateSql);
            $trackingUpdateStmt->bind_param("ssi", $trackingDataJson, $currentStatus, $order['order_id']);
            $trackingUpdateStmt->execute();
        }
    }
}

echo "Status sync completed at " . date('Y-m-d H:i:s');
?>