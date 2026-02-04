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

// Decode addresses - fix the decoding
$billing_address = [];
$shipping_address = [];

// Try to decode billing address
if (!empty($order['billing_address'])) {
    $billing_address = json_decode($order['billing_address'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // If JSON decode fails, check if it's stored differently
        $billing_address = $this->parseAddressFromString($order['billing_address']);
    }
}

// Try to decode shipping address
if (!empty($order['shipping_address'])) {
    $shipping_address = json_decode($order['shipping_address'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $shipping_address = $this->parseAddressFromString($order['shipping_address']);
    }
}

// If addresses are empty, create default structure
if (empty($billing_address)) {
    $billing_address = [
        'name' => 'N/A',
        'phone' => 'N/A',
        'address' => 'N/A',
        'address2' => '',
        'city' => 'N/A',
        'state' => 'N/A',
        'country' => 'N/A',
        'postcode' => 'N/A',
        'email' => $order['email'] ?? 'N/A'
    ];
}

if (empty($shipping_address)) {
    $shipping_address = $billing_address;
}

// Get COD details from notes - fix parsing
$cod_advance = 0;
$cod_remaining = 0;
if($order['payment_method'] == 'cod') {
    $notes = $order['notes'];
    
    // Try JSON decode first
    $notes_data = json_decode($notes, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($notes_data)) {
        // Check if payment_details exists in notes
        if (isset($notes_data['payment_details']['cod_advance'])) {
            $cod_advance = floatval($notes_data['payment_details']['cod_advance']);
        }
        if (isset($notes_data['payment_details']['cod_remaining'])) {
            $cod_remaining = floatval($notes_data['payment_details']['cod_remaining']);
        }
    } else {
        // Try regex parsing as fallback
        if(preg_match('/cod_advance[:\s]*(\d+(\.\d+)?)/i', $notes, $matches)) {
            $cod_advance = floatval($matches[1]);
        }
        if(preg_match('/cod_remaining[:\s]*(\d+(\.\d+)?)/i', $notes, $matches)) {
            $cod_remaining = floatval($matches[1]);
        }
    }
    
    // If still zero, calculate from final_amount
    if ($cod_advance == 0 && $cod_remaining == 0 && $order['final_amount'] > 200) {
        $cod_advance = 200;
        $cod_remaining = $order['final_amount'] - $cod_advance;
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
       <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="<?= $site ?>assets/img/favicon/favicon.ico">

    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="<?= $site ?>assets/img/favicon/favicon.ico">

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <!-- CSS 
    ========================= -->
    <!--bootstrap min css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/bootstrap.min.css">
    <!--owl carousel min css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/owl.carousel.min.css">
    <!--slick min css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/slick.css">
    <!--magnific popup min css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/magnific-popup.css">
    <!--font awesome css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/font.awesome.css">
    <!--ionicons css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/ionicons.min.css">
    <!--7 stroke icons css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/pe-icon-7-stroke.css">
    <!--animate css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/animate.css">
    <!--jquery ui min css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/jquery-ui.min.css">
    <!--plugins css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/plugins.css">

    <!-- Main Style CSS -->
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
        
        .btn-track {
            background: #007bff;
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            margin-left: 10px;
        }
        
        .order-items {
            margin: 20px 0;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .item-name {
            flex: 2;
        }
        
        .item-qty {
            flex: 1;
            text-align: center;
        }
        
        .item-price {
            flex: 1;
            text-align: right;
        }
        
        @media (max-width: 768px) {
            .confirmation-container {
                margin: 20px;
                padding: 20px;
            }
            
            .order-detail-row {
                flex-direction: column;
            }
            
            .order-detail-row span:first-child {
                font-weight: 600;
                margin-bottom: 5px;
            }
            
            .order-item {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include_once "includes/header.php" ?>
    
    <div class="container">
        <div class="confirmation-container">
            <div class="text-center">
                <div class="success-icon">
                    <i class="fa fa-check-circle"></i>
                </div>
                
                <h1>Thank You for Your Order!</h1>
                <p class="lead">Your order has been placed successfully.</p>
            </div>
            
            <div class="order-details">
                <div class="order-detail-row">
                    <span>Order Number:</span>
                    <strong><?= htmlspecialchars($order['order_number']) ?></strong>
                </div>
                <div class="order-detail-row">
                    <span>Date:</span>
                    <span><?= date('F d, Y h:i A', strtotime($order['created_at'])) ?></span>
                </div>
                <div class="order-detail-row">
                    <span>Order Status:</span>
                    <span class="badge bg-<?= 
                        $order['order_status'] == 'confirmed' ? 'success' : 
                        ($order['order_status'] == 'pending' ? 'warning' : 
                        ($order['order_status'] == 'shipped' ? 'info' : 
                        ($order['order_status'] == 'delivered' ? 'success' : 'secondary'))) 
                    ?>">
                        <?= ucfirst(str_replace('_', ' ', $order['order_status'])) ?>
                    </span>
                </div>
                <div class="order-detail-row">
                    <span>Payment Method:</span>
                    <span class="text-uppercase"><?= htmlspecialchars($order['payment_method']) ?></span>
                </div>
                <div class="order-detail-row">
                    <span>Payment Status:</span>
                    <span class="badge bg-<?= 
                        $order['payment_status'] == 'paid' ? 'success' : 
                        ($order['payment_status'] == 'cod_advance_paid' ? 'warning' : 'secondary') 
                    ?>">
                        <?= ucfirst(str_replace('_', ' ', $order['payment_status'])) ?>
                    </span>
                </div>
                
                <?php if(!empty($order_items)): ?>
                <div class="order-items mt-4">
                    <h5>Order Items (<?= count($order_items) ?>)</h5>
                    <?php foreach($order_items as $item): 
                        $attributes = json_decode($item['attributes'] ?? '', true);
                    ?>
                    <div class="order-item">
                        <div class="item-name">
                            <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                            <?php if($attributes && !empty($attributes['color'])): ?>
                                <br><small>Color: <?= htmlspecialchars($attributes['color']) ?></small>
                            <?php endif; ?>
                            <?php if($attributes && !empty($attributes['size'])): ?>
                                <br><small>Size: <?= htmlspecialchars($attributes['size']) ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="item-qty">
                            Qty: <?= htmlspecialchars($item['quantity']) ?>
                        </div>
                        <div class="item-price">
                            ₹<?= number_format($item['total_price'], 2) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div class="mt-4">
                    <div class="order-detail-row">
                        <span>Subtotal:</span>
                        <span>₹<?= number_format($order['total_amount'], 2) ?></span>
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
                    <?php if($order['tax_amount'] > 0): ?>
                    <div class="order-detail-row">
                        <span>Tax:</span>
                        <span>₹<?= number_format($order['tax_amount'], 2) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="order-detail-row" style="border-bottom: none; font-size: 1.2em;">
                        <span><strong>Total Amount:</strong></span>
                        <strong>₹<?= number_format($order['final_amount'], 2) ?></strong>
                    </div>
                    
                    <?php if($order['payment_method'] == 'cod' && $cod_advance > 0): ?>
                    <div class="mt-3 p-3 bg-light border rounded">
                        <h6>Cash on Delivery Details:</h6>
                        <div class="order-detail-row">
                            <span>Advance Paid:</span>
                            <strong>₹<?= number_format($cod_advance, 2) ?></strong>
                        </div>
                        <div class="order-detail-row" style="border-bottom: none;">
                            <span>To Pay on Delivery:</span>
                            <strong>₹<?= number_format($cod_remaining, 2) ?></strong>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Billing Address -->
            <div class="address-section">
                <h5>Billing Address</h5>
                <p>
                    <strong><?= htmlspecialchars($billing_address['name'] ?? 'N/A') ?></strong><br>
                    <?= htmlspecialchars($billing_address['address'] ?? $billing_address['address_1'] ?? 'N/A') ?><br>
                    <?php if(!empty($billing_address['address2'])): ?>
                        <?= htmlspecialchars($billing_address['address2']) ?><br>
                    <?php endif; ?>
                    <?= htmlspecialchars($billing_address['city'] ?? 'N/A') ?>, 
                    <?= htmlspecialchars($billing_address['state'] ?? 'N/A') ?> - 
                    <?= htmlspecialchars($billing_address['postcode'] ?? 'N/A') ?><br>
                    <?= htmlspecialchars($billing_address['country'] ?? 'N/A') ?><br>
                    Phone: <?= htmlspecialchars($billing_address['phone'] ?? 'N/A') ?><br>
                    Email: <?= htmlspecialchars($billing_address['email'] ?? 'N/A') ?>
                </p>
            </div>
            
            <!-- Shipping Address (if different) -->
            <?php if(json_encode($shipping_address) !== json_encode($billing_address)): ?>
            <div class="address-section mt-3">
                <h5>Shipping Address</h5>
                <p>
                    <strong><?= htmlspecialchars($shipping_address['name'] ?? 'N/A') ?></strong><br>
                    <?= htmlspecialchars($shipping_address['address'] ?? $shipping_address['address_1'] ?? 'N/A') ?><br>
                    <?php if(!empty($shipping_address['address2'])): ?>
                        <?= htmlspecialchars($shipping_address['address2']) ?><br>
                    <?php endif; ?>
                    <?= htmlspecialchars($shipping_address['city'] ?? 'N/A') ?>, 
                    <?= htmlspecialchars($shipping_address['state'] ?? 'N/A') ?> - 
                    <?= htmlspecialchars($shipping_address['postcode'] ?? 'N/A') ?><br>
                    <?= htmlspecialchars($shipping_address['country'] ?? 'N/A') ?><br>
                    Phone: <?= htmlspecialchars($shipping_address['phone'] ?? 'N/A') ?>
                </p>
            </div>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <p>A confirmation email has been sent to <strong><?= htmlspecialchars($billing_address['email'] ?? 'N/A') ?></strong></p>
                <p>You can track your order in the <a href="<?= $site ?>account/orders">My Orders</a> section.</p>
                
                <?php if($order['tracking_number']): ?>
                <p class="text-success">
                    <i class="fa fa-truck"></i> Your order has been shipped! 
                    Tracking Number: <strong><?= htmlspecialchars($order['tracking_number']) ?></strong>
                </p>
                <?php endif; ?>
                
                <div class="mt-3">
                    <a href="<?= $site ?>" class="btn-continue">
                        <i class="fa fa-arrow-left"></i> Continue Shopping
                    </a>
                    
                    <a href="<?= $site ?>track-order.php?order_id=<?= $order_id ?>" class="btn-track">
                        <i class="fa fa-truck"></i> Track Order
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <?php include_once "includes/footer.php" ?>
    
    <script>
    // Print order confirmation
    function printOrder() {
        window.print();
    }
    </script>
</body>
</html>
<?php
// Helper function to parse address from string
function parseAddressFromString($address_string) {
    // Try to extract address components
    $address = [];
    
    // Common patterns
    $patterns = [
        'name' => '/([A-Za-z\s]+)/',
        'phone' => '/(\d{10,})/',
        'address' => '/([^,]+),/',
        'city' => '/,\s*([A-Za-z\s]+),/',
        'state' => '/,\s*[A-Za-z\s]+,\s*([A-Za-z\s]+)\s*-\s*\d+/',
        'postcode' => '/-\s*(\d+)/',
        'country' => '/,\s*([A-Za-z\s]+)$/'
    ];
    
    foreach ($patterns as $key => $pattern) {
        if (preg_match($pattern, $address_string, $matches)) {
            $address[$key] = trim($matches[1]);
        }
    }
    
    return $address;
}
?>