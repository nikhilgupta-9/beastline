<?php
require_once '../config/connect.php';
require_once '../config/ithink-logistics.php';
require_once '../includes/ithink-logistics.php';

// Check admin authentication
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$ithink = new iThinkLogistics();

// Handle actions
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'create_shipment':
            if (isset($_GET['order_id'])) {
                $order_id = $_GET['order_id'];
                
                // Get order details
                $sql = "SELECT o.*, 
                        u.first_name, u.last_name, u.email, u.phone
                        FROM orders o
                        LEFT JOIN users u ON o.user_id = u.user_id
                        WHERE o.order_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $order_id);
                $stmt->execute();
                $order = $stmt->get_result()->fetch_assoc();
                
                if ($order) {
                    // Prepare iThink data
                    $shipping_info = json_decode($order['shipping_address'], true);
                    
                    $ithinkData = [
                        'order_id' => $order_id,
                        'order_number' => $order['order_number'],
                        'total_amount' => $order['total_amount'],
                        'final_amount' => $order['final_amount'],
                        'total_quantity' => 1, // You might need to calculate this
                        'shipping_name' => $shipping_info['first_name'] . ' ' . $shipping_info['last_name'],
                        'email' => $shipping_info['email'],
                        'phone' => $shipping_info['phone'],
                        'shipping_address' => $shipping_info['address_1'],
                        'city' => $shipping_info['city'],
                        'state' => $shipping_info['state'],
                        'pincode' => $shipping_info['postcode']
                    ];
                    
                    $response = $ithink->createShipment($ithinkData);
                    
                    if ($response['status'] && isset($response['data']['success']) && $response['data']['success']) {
                        // Update order with tracking info
                        $trackingNumber = $response['data']['data']['order_id'] ?? null;
                        $awbNumber = $response['data']['data']['awb_number'] ?? null;
                        
                        $updateSql = "UPDATE orders SET 
                            tracking_number = ?,
                            awb_number = ?,
                            ithink_order_id = ?,
                            order_status = 'processing',
                            shipment_data = ?
                            WHERE order_id = ?";
                        
                        $updateStmt = $conn->prepare($updateSql);
                        $shipmentData = json_encode($response['data']);
                        $updateStmt->bind_param("ssssi", 
                            $trackingNumber, 
                            $awbNumber, 
                            $trackingNumber, 
                            $shipmentData, 
                            $order_id
                        );
                        $updateStmt->execute();
                        
                        $_SESSION['success_message'] = "Shipment created successfully! AWB: " . $awbNumber;
                    } else {
                        $_SESSION['error_message'] = "Failed to create shipment: " . 
                            ($response['data']['message'] ?? 'Unknown error');
                    }
                }
            }
            break;
            
        case 'generate_awb':
            if (isset($_GET['order_id'])) {
                $order_id = $_GET['order_id'];
                $response = $ithink->generateAWB($order_id);
                
                if ($response['status'] && $response['data']['success']) {
                    $_SESSION['success_message'] = "AWB generated successfully!";
                } else {
                    $_SESSION['error_message'] = "Failed to generate AWB";
                }
            }
            break;
            
        case 'cancel_shipment':
            if (isset($_GET['order_id'])) {
                $order_id = $_GET['order_id'];
                $response = $ithink->cancelShipment($order_id);
                
                if ($response['status'] && $response['data']['success']) {
                    // Update order status
                    $updateSql = "UPDATE orders SET order_status = 'cancelled' WHERE order_id = ?";
                    $updateStmt = $conn->prepare($updateSql);
                    $updateStmt->bind_param("i", $order_id);
                    $updateStmt->execute();
                    
                    $_SESSION['success_message'] = "Shipment cancelled successfully!";
                } else {
                    $_SESSION['error_message'] = "Failed to cancel shipment";
                }
            }
            break;
            
        case 'refresh_tracking':
            if (isset($_GET['awb'])) {
                $awb = $_GET['awb'];
                $response = $ithink->getTracking($awb);
                
                if ($response['status'] && isset($response['data']['success']) && $response['data']['success']) {
                    // Update tracking data
                    $trackingData = json_encode($response['data']);
                    
                    $updateSql = "UPDATE shipment_tracking SET 
                        tracking_data = ?, 
                        status = ?,
                        updated_at = NOW()
                        WHERE awb_number = ?";
                    
                    $updateStmt = $conn->prepare($updateSql);
                    $status = $response['data']['data']['status'] ?? 'In Transit';
                    $updateStmt->bind_param("sss", $trackingData, $status, $awb);
                    $updateStmt->execute();
                    
                    $_SESSION['success_message'] = "Tracking updated successfully!";
                }
            }
            break;
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch orders with shipments
$sql = "SELECT o.*, 
        u.first_name, u.last_name,
        st.status as tracking_status
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.user_id
        LEFT JOIN shipment_tracking st ON o.order_id = st.order_id
        ORDER BY o.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Shipments - Admin Panel</title>
    <!-- Add your CSS/JS includes here -->
</head>
<body>
    <div class="container-fluid">
        <h1 class="h3 mb-4">Manage Shipments</h1>
        
        <?php if(isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Order Number</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>AWB Number</th>
                            <th>Tracking Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['order_id'] ?></td>
                            <td><?= $row['order_number'] ?></td>
                            <td><?= $row['first_name'] . ' ' . $row['last_name'] ?></td>
                            <td>₹<?= number_format($row['final_amount'], 2) ?></td>
                            <td>
                                <span class="badge bg-<?= 
                                    $row['order_status'] == 'pending' ? 'warning' : 
                                    ($row['order_status'] == 'processing' ? 'info' : 
                                    ($row['order_status'] == 'shipped' ? 'primary' : 
                                    ($row['order_status'] == 'delivered' ? 'success' : 'danger')))
                                ?>">
                                    <?= ucfirst($row['order_status']) ?>
                                </span>
                            </td>
                            <td><?= $row['awb_number'] ?: 'Not Generated' ?></td>
                            <td><?= $row['tracking_status'] ?: 'Not Tracked' ?></td>
                            <td>
                                <div class="btn-group">
                                    <?php if(!$row['awb_number']): ?>
                                        <a href="?action=create_shipment&order_id=<?= $row['order_id'] ?>" 
                                           class="btn btn-sm btn-primary"
                                           onclick="return confirm('Create shipment for this order?')">
                                            Create Shipment
                                        </a>
                                    <?php else: ?>
                                        <a href="?action=generate_awb&order_id=<?= $row['order_id'] ?>" 
                                           class="btn btn-sm btn-success"
                                           onclick="return confirm('Generate AWB?')">
                                            Generate AWB
                                        </a>
                                        <a href="?action=refresh_tracking&awb=<?= $row['awb_number'] ?>" 
                                           class="btn btn-sm btn-info">
                                            Refresh Tracking
                                        </a>
                                        <a href="?action=cancel_shipment&order_id=<?= $row['order_id'] ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Cancel this shipment?')">
                                            Cancel
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="view-order.php?id=<?= $row['order_id'] ?>" 
                                       class="btn btn-sm btn-secondary">
                                        View
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>