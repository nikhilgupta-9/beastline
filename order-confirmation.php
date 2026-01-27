<?php
session_start();
include_once "config/connect.php";

if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: " . $site);
    exit();
}

$order_id = intval($_GET['id']);

// Get order details
$sql = "SELECT o.*, 
               COUNT(oi.id) as item_count
        FROM orders o
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        WHERE o.order_id = ?
        GROUP BY o.order_id";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if(!$order) {
    header("Location: " . $site);
    exit();
}

// Get order items
$items_sql = "SELECT * FROM order_items WHERE order_id = ?";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$order_items = $items_result->fetch_all(MYSQLI_ASSOC);

// Decode addresses
$billing_address = json_decode($order['billing_address'], true);
$shipping_address = json_decode($order['shipping_address'], true);

// Get COD details from notes
$cod_advance = 0;
$cod_remaining = 0;
if($order['payment_method'] == 'cod') {
    $notes = $order['notes'];
    if(preg_match('/\|cod_advance:(\d+(\.\d+)?)/', $notes, $matches)) {
        $cod_advance = floatval($matches[1]);
    }
    if(preg_match('/\|cod_remaining:(\d+(\.\d+)?)/', $notes, $matches)) {
        $cod_remaining = floatval($matches[1]);
    }
}
?>
<!doctype html>
<html class="no-js" lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Order Confirmation | Beastline</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSS -->
    <link rel="stylesheet" href="<?= $site ?>assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $site ?>assets/css/font.awesome.css">
    <link rel="stylesheet" href="<?= $site ?>assets/css/style.css">
    
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .order-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .order-detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eaeaea;
        }
        
        .address-section {
            margin-top: 20px;
            padding: 15px;
            background: #fff;
            border: 1px solid #eaeaea;
            border-radius: 8px;
        }
        
        .address-section h5 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .btn-continue {
            background: #e50010;
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .confirmation-container {
                margin: 20px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include_once "includes/header.php" ?>
    
    <div class="container">
        <div class="confirmation-container text-center">
            <div class="success-icon">
                <i class="fa fa-check-circle"></i>
            </div>
            
            <h1>Thank You for Your Order!</h1>
            <p class="lead">Your order has been placed successfully.</p>
            
            <div class="order-details">
                <div class="order-detail-row">
                    <span>Order Number:</span>
                    <strong><?= htmlspecialchars($order['order_number']) ?></strong>
                </div>
                <div class="order-detail-row">
                    <span>Date:</span>
                    <span><?= date('F d, Y', strtotime($order['created_at'])) ?></span>
                </div>
                <div class="order-detail-row">
                    <span>Total Amount:</span>
                    <strong>₹<?= number_format($order['total_amount'], 2) ?></strong>
                </div>
                <?php if($order['discount_amount'] > 0): ?>
                <div class="order-detail-row">
                    <span>Discount:</span>
                    <span style="color: #28a745;">-₹<?= number_format($order['discount_amount'], 2) ?></span>
                </div>
                <?php endif; ?>
                <div class="order-detail-row">
                    <span>Shipping:</span>
                    <span>₹<?= number_format($order['shipping_amount'], 2) ?></span>
                </div>
                <div class="order-detail-row">
                    <span>Final Amount:</span>
                    <strong>₹<?= number_format($order['final_amount'], 2) ?></strong>
                </div>
                <div class="order-detail-row">
                    <span>Payment Method:</span>
                    <span><?= strtoupper($order['payment_method']) ?></span>
                </div>
                <div class="order-detail-row">
                    <span>Status:</span>
                    <span class="badge bg-<?= $order['order_status'] == 'confirmed' ? 'success' : 'warning' ?>">
                        <?= ucfirst($order['order_status']) ?>
                    </span>
                </div>
                <?php if($order['payment_method'] == 'cod' && $cod_advance > 0): ?>
                <div class="order-detail-row">
                    <span>Advance Paid:</span>
                    <strong>₹<?= number_format($cod_advance, 2) ?></strong>
                </div>
                <div class="order-detail-row">
                    <span>To Pay on Delivery:</span>
                    <strong>₹<?= number_format($cod_remaining, 2) ?></strong>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Billing Address -->
            <div class="address-section">
                <h5>Billing Address</h5>
                <p>
                    <?= htmlspecialchars($billing_address['first_name'] . ' ' . $billing_address['last_name']) ?><br>
                    <?= htmlspecialchars($billing_address['address_1']) ?><br>
                    <?php if(!empty($billing_address['address_2'])): ?>
                        <?= htmlspecialchars($billing_address['address_2']) ?><br>
                    <?php endif; ?>
                    <?= htmlspecialchars($billing_address['city']) ?>, 
                    <?= htmlspecialchars($billing_address['state']) ?> - 
                    <?= htmlspecialchars($billing_address['postcode']) ?><br>
                    <?= htmlspecialchars($billing_address['country']) ?><br>
                    Phone: <?= htmlspecialchars($billing_address['phone']) ?><br>
                    Email: <?= htmlspecialchars($billing_address['email']) ?>
                </p>
            </div>
            
            <p>A confirmation email has been sent to <strong><?= htmlspecialchars($billing_address['email']) ?></strong></p>
            <p>You can track your order in the <a href="<?= $site ?>account/orders">My Orders</a> section.</p>
            
            <a href="<?= $site ?>" class="btn-continue">
                <i class="fa fa-arrow-left"></i> Continue Shopping
            </a>
        </div>
    </div>
    
    <?php include_once "includes/footer.php" ?>
</body>
</html>