<?php
require_once '../config/connect.php';
require_once '../config/ithink-logistics.php';

// Verify webhook signature (iThink sends signature in headers)
$receivedSignature = $_SERVER['HTTP_X_ITHINK_SIGNATURE'] ?? '';
$payload = file_get_contents('php://input');
$expectedSignature = hash_hmac('sha256', $payload, ITHINK_SECRET_KEY);

if ($receivedSignature !== $expectedSignature) {
    http_response_code(401);
    die('Invalid signature');
}

$data = json_decode($payload, true);

if (isset($data['awb_number']) && isset($data['status'])) {
    $awb = $data['awb_number'];
    $status = $data['status'];
    $location = $data['location'] ?? '';
    $remarks = $data['remarks'] ?? '';
    
    // Find order by AWB
    $sql = "SELECT o.order_id FROM orders o WHERE o.awb_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $awb);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    
    if ($order) {
        // Update tracking
        $updateSql = "UPDATE shipment_tracking SET status = ?, updated_at = NOW() WHERE awb_number = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("ss", $status, $awb);
        $updateStmt->execute();
        
        // Add to history
        $historySql = "INSERT INTO shipment_history 
            (order_id, status, location, remarks, date_time)
            VALUES (?, ?, ?, ?, NOW())";
        $historyStmt = $conn->prepare($historySql);
        $historyStmt->bind_param("isss", $order['order_id'], $status, $location, $remarks);
        $historyStmt->execute();
        
        // Update order status if delivered
        if ($status == 'Delivered') {
            $orderUpdateSql = "UPDATE orders SET order_status = 'delivered' WHERE order_id = ?";
            $orderUpdateStmt = $conn->prepare($orderUpdateSql);
            $orderUpdateStmt->bind_param("i", $order['order_id']);
            $orderUpdateStmt->execute();
        }
    }
}

http_response_code(200);
echo 'OK';
?>