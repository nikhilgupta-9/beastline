<?php
require_once 'config/connect.php';
require_once 'config/ithink-logistics.php';
require_once 'includes/ithink-logistics.php';

$tracking_info = null;

if (isset($_GET['order_id']) && isset($_GET['email'])) {
    $order_id = $_GET['order_id'];
    $email = $_GET['email'];
    
    // Verify order belongs to user
    $sql = "SELECT o.*, u.email 
            FROM orders o
            JOIN users u ON o.user_id = u.user_id
            WHERE o.order_id = ? AND u.email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $order_id, $email);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    
    if ($order && !empty($order['awb_number'])) {
        $ithink = new iThinkLogistics();
        $response = $ithink->getTracking($order['awb_number']);
        
        if ($response['status'] && isset($response['data']['success']) && $response['data']['success']) {
            $tracking_info = [
                'status' => true,
                'data' => $response['data']['data']
            ];
            
            // Update local tracking data
            if (isset($response['data']['data']['tracking'])) {
                $trackingData = json_encode($response['data']);
                $updateSql = "UPDATE shipment_tracking SET 
                    tracking_data = ?, 
                    status = ?,
                    updated_at = NOW()
                    WHERE awb_number = ?";
                
                $updateStmt = $conn->prepare($updateSql);
                $status = $response['data']['data']['status'] ?? 'In Transit';
                $updateStmt->bind_param("sss", $trackingData, $status, $order['awb_number']);
                $updateStmt->execute();
            }
        } else {
            $tracking_info = [
                'status' => false,
                'message' => 'Tracking information not available'
            ];
        }
    } else {
        $tracking_info = [
            'status' => false,
            'message' => 'Order not found or no tracking available'
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Order</title>
    <!-- Add your CSS includes here -->
</head>
<body>
    <div class="container">
        <h1>Track Your Order</h1>
        
        <div class="tracking-form mb-4">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label for="order_id" class="form-label">Order ID</label>
                    <input type="text" class="form-control" id="order_id" name="order_id" 
                           value="<?= $_GET['order_id'] ?? '' ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?= $_GET['email'] ?? '' ?>" required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Track Order</button>
                </div>
            </form>
        </div>
        
        <?php if($tracking_info): ?>
        <div class="tracking-info">
            <?php if($tracking_info['status']): ?>
                <!-- Your existing tracking display HTML -->
                <?= $tracking_info_display_html ?>
            <?php else: ?>
                <div class="alert alert-danger">
                    <?= $tracking_info['message'] ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>