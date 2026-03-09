<?php
session_start();
include_once "config/connect.php";
include_once "util/function.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: " . $site . "user-login/");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get order ID from URL
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$order_id) {
    header("Location: " . $site . "my-account/");
    exit();
}

// Get order details - Updated based on your table structure
$order_sql = "SELECT o.*, u.first_name, u.last_name, u.email, u.mobile 
              FROM orders o 
              LEFT JOIN users u ON o.user_id = u.id 
              WHERE o.order_id = ? AND o.user_id = ?";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param("ii", $order_id, $user_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();
$order = $order_result->fetch_assoc();

if (!$order) {
    header("Location: " . $site . "my-account/");
    exit();
}

// Get order items with product variants - Updated query
$items_sql = "SELECT 
                oi.*, 
                p.pro_id,
                p.pro_name, 
                p.pro_img, 
                p.slug_url, 
                p.selling_price,
                pv.color,
                pv.size,
                pv.image as variant_image,
                pv.sku as variant_sku
              FROM order_items oi 
              LEFT JOIN products p ON oi.product_id = p.pro_id 
              LEFT JOIN product_variants pv ON pv.product_id = p.pro_id AND JSON_CONTAINS(oi.attributes, JSON_OBJECT('color', pv.color, 'size', pv.size))
              WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$order_items = $items_stmt->get_result();

// Parse shipping address from JSON (if stored as JSON in shipping_address field)
$shipping_address = [];
if (!empty($order['shipping_address'])) {
    $shipping_address = json_decode($order['shipping_address'], true);
}

// Parse billing address from JSON (if stored as JSON in billing_address field)
$billing_address = [];
if (!empty($order['billing_address'])) {
    $billing_address = json_decode($order['billing_address'], true);
}

// Parse shipment data from JSON
$shipment_data = [];
if (!empty($order['shipment_data'])) {
    $shipment_data = json_decode($order['shipment_data'], true);
}

// Get order timeline - check if table exists or use status from orders table
$timeline_exists = false;
$timeline_result = null;

// Check if order_status_history table exists
$table_check_sql = "SHOW TABLES LIKE 'order_status_history'";
$table_check_result = $conn->query($table_check_sql);
if ($table_check_result->num_rows > 0) {
    $timeline_exists = true;
    $timeline_sql = "SELECT * FROM order_status_history 
                     WHERE order_id = ? 
                     ORDER BY created_at DESC";
    $timeline_stmt = $conn->prepare($timeline_sql);
    $timeline_stmt->bind_param("i", $order_id);
    $timeline_stmt->execute();
    $timeline_result = $timeline_stmt->get_result();
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['cancel_order']) && $order['order_status'] == 'pending') {
        $update_sql = "UPDATE orders SET order_status = 'cancelled', updated_at = NOW() WHERE order_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $order_id);
        if ($update_stmt->execute()) {
            // Add to status history if table exists
            if ($timeline_exists) {
                $history_sql = "INSERT INTO order_status_history (order_id, status, notes) VALUES (?, 'cancelled', 'Order cancelled by customer')";
                $history_stmt = $conn->prepare($history_sql);
                $history_stmt->bind_param("is", $order_id);
                $history_stmt->execute();
            }
            
            $success = "Order cancelled successfully.";
            $order['order_status'] = 'cancelled';
        }
    }
}

// Function to get status color
function getStatusColor($status) {
    $colors = [
        'pending' => 'warning',
        'confirmed' => 'info',
        'processing' => 'primary',
        'shipped' => 'info',
        'delivered' => 'success',
        'cancelled' => 'danger',
        'refunded' => 'secondary',
        'returned' => 'warning'
    ];
    return $colors[strtolower($status)] ?? 'secondary';
}

// Function to get payment status color
function getPaymentStatusColor($status) {
    $colors = [
        'pending' => 'warning',
        'success' => 'success',
        'failed' => 'danger',
        'refunded' => 'secondary'
    ];
    return $colors[strtolower($status)] ?? 'secondary';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= $order['order_number'] ?> | <?= $site_name ?></title>
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="<?= $site ?>assets/img/favicon/favicon.ico">

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

    <!--modernizr min js here-->
    <script src="<?= $site ?>assets/js/vendor/modernizr-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php include_once "includes/meta_pixel.php" ?>
    <style>
        :root {
            --primary-color: #000;
            --primary-dark: #1a191a;
            --secondary-color: #6c757d;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --border-color: #dee2e6;
            --success-color: #28a745;
            --info-color: #17a2b8;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --white: #ffffff;
            --black: #000000;
        }

        body {
            background-color: #f5f7fa;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #333;
            line-height: 1.6;
        }

        .order-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 15px;
        }

        @media (max-width: 768px) {
            .order-container {
                margin: 15px auto;
                padding: 0 10px;
            }
        }

        .order-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        @media (max-width: 768px) {
            .order-header {
                padding: 15px;
            }
        }

        .order-header h1 {
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 10px 0;
            color: white;
        }

        @media (min-width: 768px) {
            .order-header h1 {
                font-size: 28px;
                color: white;
            }
        }

        .order-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            font-size: 14px;
            opacity: 0.9;
        }

        @media (max-width: 576px) {
            .order-meta {
                flex-direction: column;
                gap: 8px;
            }
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        @media (min-width: 768px) {
            .status-badge {
                padding: 8px 16px;
                font-size: 14px;
            }
        }

        .badge-warning { background-color: rgba(255, 193, 7, 0.1); color: #ffc107; border: 1px solid rgba(255, 193, 7, 0.3); }
        .badge-info { background-color: rgba(23, 162, 184, 0.1); color: #17a2b8; border: 1px solid rgba(23, 162, 184, 0.3); }
        .badge-primary { background-color: rgba(0, 123, 255, 0.1); color: #007bff; border: 1px solid rgba(0, 123, 255, 0.3); }
        .badge-success { background-color: rgba(40, 167, 69, 0.1); color: #28a745; border: 1px solid rgba(40, 167, 69, 0.3); }
        .badge-danger { background-color: rgba(220, 53, 69, 0.1); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.3); }
        .badge-secondary { background-color: rgba(108, 117, 125, 0.1); color: #6c757d; border: 1px solid rgba(108, 117, 125, 0.3); }

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .btn {
            padding: 10px 16px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        @media (min-width: 768px) {
            .btn {
                padding: 12px 20px;
                font-size: 16px;
            }
        }

        .btn-primary { background-color: var(--primary-color); color: white; }
        .btn-primary:hover { background-color: var(--primary-dark); transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.15); }
        .btn-secondary { background-color: var(--secondary-color); color: white; }
        .btn-secondary:hover { background-color: #5a6268; transform: translateY(-2px); }
        .btn-danger { background-color: var(--danger-color); color: white; }
        .btn-danger:hover { background-color: #c82333; transform: translateY(-2px); }
        .btn-outline-primary { background-color: transparent; border: 2px solid var(--primary-color); color: var(--primary-color); }
        .btn-outline-primary:hover { background-color: var(--primary-color); color: white; }

        .order-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        @media (min-width: 992px) {
            .order-content {
                flex-direction: row;
                gap: 30px;
            }
        }

        .order-main {
            flex: 1;
        }

        .order-sidebar {
            width: 100%;
        }

        @media (min-width: 992px) {
            .order-sidebar {
                width: 350px;
            }
        }

        .order-card {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .order-card {
                padding: 15px;
            }
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border-color);
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media (min-width: 768px) {
            .card-title {
                font-size: 20px;
            }
        }

        .order-item {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
            gap: 15px;
        }

        @media (max-width: 576px) {
            .order-item {
                flex-direction: column;
                gap: 10px;
            }
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
        }

        @media (min-width: 768px) {
            .item-image {
                width: 100px;
                height: 100px;
            }
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark-color);
        }

        .item-name a {
            color: inherit;
            text-decoration: none;
            transition: color 0.3s;
        }

        .item-name a:hover {
            color: var(--primary-color);
        }

        .item-variant {
            font-size: 14px;
            color: var(--secondary-color);
            margin-bottom: 5px;
        }

        .item-price {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 16px;
        }

        .item-quantity {
            background: var(--light-color);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 14px;
            color: var(--secondary-color);
            display: inline-block;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }

        @media (min-width: 768px) {
            .summary-row {
                font-size: 16px;
            }
        }

        .summary-row.total {
            font-weight: 700;
            font-size: 18px;
            background: var(--light-color);
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            border-bottom: none;
        }

        .address-card {
            background: var(--light-color);
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
        }

        .address-name {
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark-color);
        }

        .address-details {
            color: var(--secondary-color);
            line-height: 1.6;
            font-size: 14px;
        }

        .timeline {
            position: relative;
            padding-left: 20px;
        }

        @media (min-width: 768px) {
            .timeline {
                padding-left: 30px;
            }
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, var(--primary-color), var(--primary-dark));
        }

        .timeline-item {
            position: relative;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px dashed var(--border-color);
        }

        .timeline-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -25px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--white);
            border: 3px solid var(--primary-color);
        }

        @media (min-width: 768px) {
            .timeline-item::before {
                left: -35px;
            }
        }

        .timeline-date {
            font-size: 12px;
            color: var(--secondary-color);
            margin-bottom: 5px;
        }

        .timeline-status {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 5px;
        }

        .timeline-notes {
            font-size: 14px;
            color: var(--secondary-color);
        }

        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            border: 1px solid transparent;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            border-color: rgba(40, 167, 69, 0.3);
            color: #155724;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            border-color: rgba(220, 53, 69, 0.3);
            color: #721c24;
        }

        .empty-state {
            text-align: center;
            padding: 30px 15px;
            color: var(--secondary-color);
        }

        .empty-state i {
            font-size: 40px;
            margin-bottom: 15px;
            color: var(--border-color);
        }

        .payment-details {
            background: var(--light-color);
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }

        .shipment-info {
            background: var(--light-color);
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            border-left: 4px solid var(--info-color);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include_once "includes/header.php" ?>

    <div class="order-container">
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <!-- Order Header -->
        <div class="order-header">
            <h1>Order #<?= $order['order_number'] ?></h1>
            <div class="order-meta">
                <div>
                    <i class="fas fa-calendar-alt me-1"></i>
                    <?= date('F d, Y', strtotime($order['created_at'])) ?>
                </div>
                <div>
                    <i class="fas fa-clock me-1"></i>
                    <?= date('h:i A', strtotime($order['created_at'])) ?>
                </div>
                <div>
                    <span class="status-badge badge-<?= getStatusColor($order['order_status']) ?>">
                        <i class="fas fa-tag"></i>
                        <?= ucfirst($order['order_status']) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="<?= $site ?>my-account/" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Account
            </a>
            
            <?php if ($order['order_status'] == 'pending' || $order['order_status'] == 'processing'): ?>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="cancel_order" class="btn btn-danger" 
                            onclick="return confirm('Are you sure you want to cancel this order?')">
                        <i class="fas fa-times"></i> Cancel Order
                    </button>
                </form>
            <?php endif; ?>
            
            <?php if ($order['order_status'] == 'delivered'): ?>
                <a href="<?= $site ?>return-order/<?= $order_id ?>/" class="btn btn-outline-primary">
                    <i class="fas fa-undo"></i> Request Return
                </a>
            <?php endif; ?>
            
            <?php if (!empty($order['tracking_number'])): ?>
                <a href="https://beastline.ithinklogistics.co.in/track/<?= $order['tracking_number'] ?>" 
                   class="btn btn-primary" target="_blank">
                    <i class="fas fa-truck"></i> Track Order
                </a>
            <?php endif; ?>
        </div>

        <div class="order-content">
            <!-- Main Content -->
            <div class="order-main">
                <!-- Order Items -->
                <div class="order-card">
                    <h3 class="card-title">
                        <i class="fas fa-shopping-bag"></i>
                        Order Items (<?= $order_items->num_rows ?>)
                    </h3>
                    
                    <?php if ($order_items->num_rows > 0): ?>
                        <?php while ($item = $order_items->fetch_assoc()): ?>
                            <div class="order-item">
                                <div class="item-image">
                                    <?php 
                                    // Determine which image to show
                                    $image_path = 'assets/img/product/product2.jpg';
                                    if (!empty($item['variant_image'])) {
                                        $image_path = 'assets/img/uploads/variants/' . $item['variant_image'];
                                    } elseif (!empty($item['pro_img'])) {
                                        $image_path = 'assets/img/uploads/' . $item['pro_img'];
                                    }
                                    ?>
                                    <img src="<?= $site . $image_path ?>" 
                                         alt="<?= htmlspecialchars($item['product_name'] ?? $item['pro_name']) ?>"
                                         onerror="this.src='<?= $site ?>assets/img/product/product2.jpg'">
                                </div>
                                <div class="item-details">
                                    <div class="item-name">
                                        <?php if (!empty($item['slug_url'])): ?>
                                            <a href="<?= $site ?>product-details/<?= $item['slug_url'] ?>/">
                                                <?= htmlspecialchars($item['product_name'] ?? $item['pro_name']) ?>
                                            </a>
                                        <?php else: ?>
                                            <?= htmlspecialchars($item['product_name'] ?? $item['pro_name']) ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Parse attributes from JSON -->
                                    <?php if (!empty($item['attributes'])): 
                                        $attributes = json_decode($item['attributes'], true);
                                        if (is_array($attributes) && !empty($attributes)): ?>
                                            <div class="item-variant">
                                                <?php foreach ($attributes as $key => $value): ?>
                                                    <span class="me-2"><?= ucfirst($key) ?>: <?= htmlspecialchars($value) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php elseif (!empty($item['color']) || !empty($item['size'])): ?>
                                        <div class="item-variant">
                                            <?php if (!empty($item['color'])): ?>
                                                <span>Color: <?= $item['color'] ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($item['size'])): ?>
                                                <span class="ms-2">Size: <?= $item['size'] ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($item['variant_sku'])): ?>
                                        <div class="item-sku small text-muted mb-2">
                                            SKU: <?= $item['variant_sku'] ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <div class="item-price">
                                            ₹<?= number_format($item['unit_price'], 2) ?>
                                            x <?= $item['quantity'] ?> = 
                                            <strong>₹<?= number_format($item['total_price'], 2) ?></strong>
                                        </div>
                                        <div class="item-quantity">
                                            Qty: <?= $item['quantity'] ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-bag"></i>
                            <p>No items found in this order.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Order Timeline -->
                <div class="order-card">
                    <h3 class="card-title">
                        <i class="fas fa-history"></i>
                        Order Status Timeline
                    </h3>
                    
                    <?php if ($timeline_exists && $timeline_result && $timeline_result->num_rows > 0): ?>
                        <div class="timeline">
                            <?php while ($timeline = $timeline_result->fetch_assoc()): ?>
                                <div class="timeline-item">
                                    <div class="timeline-date">
                                        <i class="far fa-clock me-1"></i>
                                        <?= date('d M Y, h:i A', strtotime($timeline['created_at'])) ?>
                                    </div>
                                    <div class="timeline-status">
                                        <span class="status-badge badge-<?= getStatusColor($timeline['status']) ?>">
                                            <?= ucfirst($timeline['status']) ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($timeline['notes'])): ?>
                                        <div class="timeline-notes">
                                            <?= htmlspecialchars($timeline['notes']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-date">
                                    <i class="far fa-clock me-1"></i>
                                    <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?>
                                </div>
                                <div class="timeline-status">
                                    <span class="status-badge badge-<?= getStatusColor($order['order_status']) ?>">
                                        <?= ucfirst($order['order_status']) ?>
                                    </span>
                                </div>
                                <div class="timeline-notes">
                                    Order was placed
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="order-sidebar">
                <!-- Order Summary -->
                <div class="order-card">
                    <h3 class="card-title">
                        <i class="fas fa-receipt"></i>
                        Order Summary
                    </h3>
                    
                    <div class="order-summary">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>₹<?= number_format($order['total_amount'], 2) ?></span>
                        </div>
                        
                        <?php if ($order['discount_amount'] > 0): ?>
                            <div class="summary-row">
                                <span>Discount</span>
                                <span class="text-success">-₹<?= number_format($order['discount_amount'], 2) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($order['tax_amount'] > 0): ?>
                            <div class="summary-row">
                                <span>Tax</span>
                                <span>₹<?= number_format($order['tax_amount'], 2) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($order['shipping_amount'] > 0): ?>
                            <div class="summary-row">
                                <span>Shipping Charges</span>
                                <span>₹<?= number_format($order['shipping_amount'], 2) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="summary-row total">
                            <span>Total Amount</span>
                            <span>₹<?= number_format($order['final_amount'], 2) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="order-card">
                    <h3 class="card-title">
                        <i class="fas fa-credit-card"></i>
                        Payment Information
                    </h3>
                    
                    <div class="summary-row">
                        <span>Payment Method</span>
                        <span><?= strtoupper(str_replace('_', ' ', $order['payment_method'] ?? 'Unknown')) ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Payment Status</span>
                        <span class="status-badge badge-<?= getPaymentStatusColor($order['payment_status']) ?>">
                            <?= ucfirst($order['payment_status'] ?? 'Pending') ?>
                        </span>
                    </div>
                    
                    <?php if (!empty($order['razorpay_payment_id'])): ?>
                        <div class="payment-details">
                            <div class="summary-row">
                                <span>Razorpay Payment ID</span>
                                <span class="small"><?= $order['razorpay_payment_id'] ?></span>
                            </div>
                            <?php if (!empty($order['razorpay_order_id'])): ?>
                                <div class="summary-row">
                                    <span>Razorpay Order ID</span>
                                    <span class="small"><?= $order['razorpay_order_id'] ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Shipping & Tracking Information -->
                <div class="order-card">
                    <h3 class="card-title">
                        <i class="fas fa-truck"></i>
                        Shipping & Tracking
                    </h3>
                    
                    <?php if (!empty($order['tracking_number']) || !empty($order['courier_name'])): ?>
                        <div class="shipment-info">
                            <?php if (!empty($order['tracking_number'])): ?>
                                <div class="summary-row">
                                    <span>Tracking Number</span>
                                    <span><strong><?= $order['tracking_number'] ?></strong></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($order['awb_number'])): ?>
                                <div class="summary-row">
                                    <span>AWB Number</span>
                                    <span><?= $order['awb_number'] ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($order['courier_name'])): ?>
                                <div class="summary-row">
                                    <span>Courier Partner</span>
                                    <span><?= $order['courier_name'] ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($order['ithink_order_id'])): ?>
                                <div class="summary-row">
                                    <span>iThink Order ID</span>
                                    <span><?= $order['ithink_order_id'] ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Shipping information will be updated once your order is processed.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Shipping Address -->
                <div class="order-card">
                    <h3 class="card-title">
                        <i class="fas fa-map-marker-alt"></i>
                        Shipping Address
                    </h3>
                    
                    <?php if (!empty($shipping_address)): ?>
                        <div class="address-card">
                            <div class="address-name">
                                <?= htmlspecialchars($shipping_address['full_name'] ?? '') ?>
                            </div>
                            <div class="address-details">
                                <p class="mb-1"><?= htmlspecialchars($shipping_address['address_line1'] ?? '') ?></p>
                                <?php if (!empty($shipping_address['address_line2'])): ?>
                                    <p class="mb-1"><?= htmlspecialchars($shipping_address['address_line2']) ?></p>
                                <?php endif; ?>
                                <p class="mb-1">
                                    <?= htmlspecialchars($shipping_address['city'] ?? '') ?>, 
                                    <?= htmlspecialchars($shipping_address['state'] ?? '') ?> - 
                                    <?= htmlspecialchars($shipping_address['pincode'] ?? '') ?>
                                </p>
                                <p class="mb-1"><?= htmlspecialchars($shipping_address['country'] ?? '') ?></p>
                                <p class="mb-0">
                                    <i class="fas fa-phone me-1"></i>
                                    <?= htmlspecialchars($shipping_address['phone'] ?? '') ?>
                                </p>
                            </div>
                        </div>
                    <?php elseif (!empty($order['shipping_address']) && is_string($order['shipping_address'])): ?>
                        <!-- Display as string if not JSON -->
                        <div class="address-card">
                            <div class="address-details">
                                <?= nl2br(htmlspecialchars($order['shipping_address'])) ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-map-marker-alt"></i>
                            <p>No shipping address found.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Billing Address -->
                <?php if (!empty($billing_address) || (!empty($order['billing_address']) && is_string($order['billing_address']))): ?>
                    <div class="order-card">
                        <h3 class="card-title">
                            <i class="fas fa-file-invoice"></i>
                            Billing Address
                        </h3>
                        
                        <?php if (!empty($billing_address)): ?>
                            <div class="address-card">
                                <div class="address-name">
                                    <?= htmlspecialchars($billing_address['full_name'] ?? '') ?>
                                </div>
                                <div class="address-details">
                                    <p class="mb-1"><?= htmlspecialchars($billing_address['address_line1'] ?? '') ?></p>
                                    <?php if (!empty($billing_address['address_line2'])): ?>
                                        <p class="mb-1"><?= htmlspecialchars($billing_address['address_line2']) ?></p>
                                    <?php endif; ?>
                                    <p class="mb-1">
                                        <?= htmlspecialchars($billing_address['city'] ?? '') ?>, 
                                        <?= htmlspecialchars($billing_address['state'] ?? '') ?> - 
                                        <?= htmlspecialchars($billing_address['pincode'] ?? '') ?>
                                    </p>
                                    <p class="mb-1"><?= htmlspecialchars($billing_address['country'] ?? '') ?></p>
                                    <p class="mb-0">
                                        <i class="fas fa-phone me-1"></i>
                                        <?= htmlspecialchars($billing_address['phone'] ?? '') ?>
                                    </p>
                                </div>
                            </div>
                        <?php elseif (!empty($order['billing_address']) && is_string($order['billing_address'])): ?>
                            <!-- Display as string if not JSON -->
                            <div class="address-card">
                                <div class="address-details">
                                    <?= nl2br(htmlspecialchars($order['billing_address'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Order Notes -->
                <?php if (!empty($order['notes'])): ?>
                    <div class="order-card">
                        <h3 class="card-title">
                            <i class="fas fa-sticky-note"></i>
                            Order Notes
                        </h3>
                        <div class="alert alert-info">
                            <?= nl2br(htmlspecialchars($order['notes'])) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include_once "includes/footer.php" ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.remove();
                }, 500);
            });
        }, 5000);

        // Copy tracking number to clipboard
        function copyTrackingNumber() {
            const trackingNumber = "<?= $order['tracking_number'] ?? '' ?>";
            if (trackingNumber) {
                navigator.clipboard.writeText(trackingNumber).then(() => {
                    alert('Tracking number copied to clipboard!');
                });
            }
        }
    </script>
    <?php include_once "includes/footer-link.php" ?>
</body>
</html>