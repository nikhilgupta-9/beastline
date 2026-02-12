<?php
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';
require_once __DIR__ . '/models/PaymentSmtpSetting.php';
require_once __DIR__ . '/models/setting.php';

// Initialize Settings
$setting = new Setting($conn);

$order_id = $_GET['id'] ?? null;
$errors = [];

if ($order_id === null) {
    header("Location: orders.php");
    exit;
} else {
    // Query to get order details with user information
    $stmt = $conn->prepare("
        SELECT o.*, 
               u.first_name, u.last_name, u.email, u.mobile
        FROM `orders` o
        LEFT JOIN `users` u ON o.user_id = u.id
        WHERE o.order_id = ?
    ");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Order not found!";
        header("Location: orders.php");
        exit;
    } else {
        $order = $result->fetch_assoc();
        
        // Get order items with product variant information
        $stmt_items = $conn->prepare("
            SELECT oi.*, 
                   p.pro_img as product_image,
                   pv.color, pv.size, pv.sku
            FROM `order_items` oi
            LEFT JOIN `products` p ON oi.product_id = p.id
            LEFT JOIN `product_variants` pv ON oi.product_id = pv.product_id
            WHERE oi.order_id = ?
            ORDER BY oi.id
        ");
        $stmt_items->bind_param("s", $order_id);
        $stmt_items->execute();
        $items_result = $stmt_items->get_result();
        $order_items = [];
        $items_total = 0;
        
        while ($item = $items_result->fetch_assoc()) {
            $order_items[] = $item;
            $items_total += $item['total_price'];
        }
        
        // Parse shipping address (assuming JSON)
        $shipping_address = json_decode($order['shipping_address'], true);
        if (!is_array($shipping_address)) {
            $shipping_address = [
                'address' => $order['shipping_address'],
                'city' => '',
                'state' => '',
                'postal_code' => '',
                'country' => ''
            ];
        }
        
        // Parse billing address if exists
        $billing_address = [];
        if (!empty($order['billing_address'])) {
            $billing_address = json_decode($order['billing_address'], true);
            if (!is_array($billing_address)) {
                $billing_address = [
                    'address' => $order['billing_address'],
                    'city' => '',
                    'state' => '',
                    'postal_code' => '',
                    'country' => ''
                ];
            }
        }
        
        // Parse order attributes from order_items
        foreach ($order_items as &$item) {
            if (!empty($item['attributes'])) {
                $attributes = json_decode($item['attributes'], true);
                if (is_array($attributes)) {
                    $item['parsed_attributes'] = $attributes;
                } else {
                    $item['parsed_attributes'] = [];
                }
            } else {
                $item['parsed_attributes'] = [];
            }
        }
        unset($item); // Unset reference
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Order #<?= htmlspecialchars($order['order_number']) ?> | Admin Panel</title>
    <link rel="icon" href="<?php echo htmlspecialchars($setting->get('favicon')); ?>" type="image/png">
    
    <?php include "links.php"; ?>
    
    <style>
        .order-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }
        
        .status-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-processing { background: #cce7ff; color: #004085; }
        .badge-completed { background: #d4edda; color: #155724; }
        .badge-cancelled { background: #f8d7da; color: #721c24; }
        
        .badge-payment-pending { background: #f8d7da; color: #721c24; }
        .badge-payment-completed { background: #d4edda; color: #155724; }
        .badge-razorpay { background: #4361ee; color: white; }
        .badge-cod { background: #6c757d; color: white; }
        
        .product-card {
            border: 1px solid #eaeaea;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
        }
        
        .variant-badge {
            display: inline-block;
            padding: 4px 8px;
            font-size: 11px;
            border-radius: 4px;
            margin-right: 5px;
            margin-bottom: 5px;
            background-color: #f8f9fa;
            color: #6c757d;
        }
        
        .variant-badge.color { background-color: #e8f4ff; color: #0066cc; }
        .variant-badge.size { background-color: #f0f8ff; color: #0052a3; }
        .variant-badge.sku { background-color: #f9f9f9; color: #495057; font-family: monospace; }
        
        .address-box {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            margin-top: 10px;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline:before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        
        .timeline-item:last-child {
            margin-bottom: 0;
        }
        
        .timeline-dot {
            position: absolute;
            left: -26px;
            top: 5px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #667eea;
            border: 3px solid white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }
        
        .stats-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 20px;
            height: 100%;
        }
        
        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        @media (max-width: 768px) {
            .timeline {
                padding-left: 20px;
            }
            
            .timeline-dot {
                left: -16px;
            }
            
            .product-image {
                width: 50px;
                height: 50px;
            }
        }
    </style>
</head>

<body class="crm_body_bg">

    <?php include "includes/header.php"; ?>

    <section class="main_content dashboard_part large_header_bg">
        <div class="container-fluid g-0">
            <div class="row">
                <div class="col-lg-12 p-0">
                    <?php include "includes/top_nav.php"; ?>
                </div>
            </div>
        </div>

        <div class="main_content_iner">
            <div class="container-fluid p-0">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <!-- Success/Error Messages -->
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?= $_SESSION['error'];
                                unset($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="stats-card">
                                    <div class="d-flex align-items-start">
                                        <div class="stats-icon me-3">
                                            <i class="fas fa-receipt"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-1">Order #<?= htmlspecialchars($order['order_number']) ?></h5>
                                            <p class="text-muted mb-0">Order Number</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="stats-card">
                                    <div class="d-flex align-items-start">
                                        <div class="stats-icon me-3" style="background: linear-gradient(135deg, #1cc88a 0%, #17a673 100%);">
                                            <i class="fas fa-rupee-sign"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-1">₹<?= number_format($order['final_amount'], 2) ?></h5>
                                            <p class="text-muted mb-0">Final Amount</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="stats-card">
                                    <div class="d-flex align-items-start">
                                        <div class="stats-icon me-3" style="background: linear-gradient(135deg, #36b9cc 0%, #2c9faf 100%);">
                                            <i class="fas fa-cube"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-1"><?= count($order_items) ?></h5>
                                            <p class="text-muted mb-0">Items</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="stats-card">
                                    <div class="d-flex align-items-start">
                                        <div class="stats-icon me-3" style="background: linear-gradient(135deg, #f6c23e 0%, #f4b619 100%);">
                                            <i class="fas fa-calendar"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-1"><?= date('M d, Y', strtotime($order['created_at'])) ?></h5>
                                            <p class="text-muted mb-0">Order Date</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="white_card card_height_100 mb_30">
                            <div class="card-header bg-white border-0 py-3">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <div class="mb-2 mb-md-0">
                                        <h2 class="mb-0 fw-bold">Order Details</h2>
                                        <p class="text-muted mb-0">Complete information for order #<?= htmlspecialchars($order['order_number']) ?></p>
                                    </div>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="orders.php" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-arrow-left me-2"></i>Back to Orders
                                        </a>
                                        <a href="edit_order.php?id=<?= $order['order_id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit me-2"></i>Edit Order
                                        </a>
                                        <!-- <a href="generate_invoice.php?order_id=<?= $order['order_id'] ?>" class="btn btn-success btn-sm">
                                            <i class="fas fa-print me-2"></i>Print Invoice
                                        </a> -->
                                    </div>
                                </div>
                            </div>

                            <div class="white_card_body">
                                <!-- Order Status Alert -->
                                <?php
                                $orderStatusClass = 'badge-pending';
                                switch (strtolower($order['order_status'])) {
                                    case 'processing':
                                        $orderStatusClass = 'badge-processing';
                                        break;
                                    case 'completed':
                                        $orderStatusClass = 'badge-completed';
                                        break;
                                    case 'cancelled':
                                        $orderStatusClass = 'badge-cancelled';
                                        break;
                                }
                                
                                $paymentStatusClass = 'badge-payment-pending';
                                if (strtolower($order['payment_status']) === 'completed' || 
                                    strtolower($order['payment_status']) === 'paid') {
                                    $paymentStatusClass = 'badge-payment-completed';
                                }
                                
                                $paymentMethodClass = $order['payment_method'] === 'razorpay' ? 'badge-razorpay' : 'badge-cod';
                                ?>
                                
                                <div class="alert alert-light d-flex justify-content-between align-items-center mb-4">
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <div>
                                            <span class="text-muted">Order Status:</span>
                                            <span class="status-badge <?= $orderStatusClass ?> ms-2">
                                                <?= ucfirst($order['order_status']) ?>
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-muted">Payment:</span>
                                            <span class="status-badge <?= $paymentStatusClass ?> ms-2">
                                                <?= ucfirst($order['payment_status']) ?>
                                            </span>
                                            <span class="status-badge <?= $paymentMethodClass ?> ms-2">
                                                <?= strtoupper($order['payment_method']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-muted small">
                                        <i class="fas fa-clock me-1"></i>
                                        Created: <?= date('F j, Y, g:i a', strtotime($order['created_at'])) ?>
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- Customer Information -->
                                    <div class="col-lg-4 mb-4">
                                        <div class="card h-100">
                                            <div class="card-header bg-light">
                                                <h5 class="mb-0"><i class="fas fa-user me-2"></i>Customer Information</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="d-flex align-items-center mb-3">
                                                    <div class="order-avatar me-3">
                                                        <?= strtoupper(substr($order['first_name'], 0, 1)) ?>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-1"><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></h6>
                                                        <p class="text-muted mb-0 small">Customer ID: <?= htmlspecialchars($order['user_id']) ?></p>
                                                    </div>
                                                </div>

                                                <h6 class="mb-3 text-muted"><i class="fas fa-address-card me-2"></i>Contact Details</h6>
                                                <div class="mb-2">
                                                    <small class="text-muted">Email:</small>
                                                    <p class="mb-1">
                                                        <a href="mailto:<?= htmlspecialchars($order['email']) ?>" class="text-primary">
                                                            <?= htmlspecialchars($order['email']) ?>
                                                        </a>
                                                    </p>
                                                </div>
                                                <div class="mb-2">
                                                    <small class="text-muted">Mobile:</small>
                                                    <p class="mb-0">
                                                        <a href="tel:<?= htmlspecialchars($order['mobile']) ?>" class="text-primary">
                                                            <?= htmlspecialchars($order['mobile']) ?>
                                                        </a>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Shipping Address -->
                                    <div class="col-lg-4 mb-4">
                                        <div class="card h-100">
                                            <div class="card-header bg-light">
                                                <h5 class="mb-0"><i class="fas fa-truck me-2"></i>Shipping Address</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="address-box">
                                                    <?php if (!empty($shipping_address['address'])): ?>
                                                        <p class="mb-2"><?= htmlspecialchars($shipping_address['address']) ?></p>
                                                    <?php endif; ?>
                                                    <div class="row small">
                                                        <?php if (!empty($shipping_address['city'])): ?>
                                                            <div class="col-6">
                                                                <strong>City:</strong> <?= htmlspecialchars($shipping_address['city']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($shipping_address['state'])): ?>
                                                            <div class="col-6">
                                                                <strong>State:</strong> <?= htmlspecialchars($shipping_address['state']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($shipping_address['postal_code'])): ?>
                                                            <div class="col-6">
                                                                <strong>Postal Code:</strong> <?= htmlspecialchars($shipping_address['postal_code']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($shipping_address['country'])): ?>
                                                            <div class="col-6">
                                                                <strong>Country:</strong> <?= htmlspecialchars($shipping_address['country']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Payment Information -->
                                    <div class="col-lg-4 mb-4">
                                        <div class="card h-100">
                                            <div class="card-header bg-light">
                                                <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment Information</h5>
                                            </div>
                                            <div class="card-body">
                                                <h6 class="mb-3 text-muted"><i class="fas fa-info-circle me-2"></i>Payment Details</h6>
                                                <div class="mb-2">
                                                    <small class="text-muted">Method:</small>
                                                    <p class="mb-1">
                                                        <span class="status-badge <?= $paymentMethodClass ?>">
                                                            <?= ucfirst($order['payment_method']) ?>
                                                        </span>
                                                    </p>
                                                </div>
                                                <div class="mb-2">
                                                    <small class="text-muted">Status:</small>
                                                    <p class="mb-1">
                                                        <span class="status-badge <?= $paymentStatusClass ?>">
                                                            <?= ucfirst($order['payment_status']) ?>
                                                        </span>
                                                    </p>
                                                </div>
                                                <?php if (!empty($order['razorpay_order_id'])): ?>
                                                <div class="mb-2">
                                                    <small class="text-muted">Razorpay Order ID:</small>
                                                    <p class="mb-1 small text-muted"><?= htmlspecialchars($order['razorpay_order_id']) ?></p>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Order Items -->
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Order Items</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (count($order_items) > 0): ?>
                                            <?php foreach ($order_items as $index => $item): ?>
                                                <div class="product-card">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-1 col-2 text-center">
                                                            <span class="text-muted small">#<?= $index + 1 ?></span>
                                                        </div>
                                                        <div class="col-md-1 col-3">
                                                            <?php if (!empty($item['product_image'])): ?>
                                                                <img src="<?= htmlspecialchars($item['product_image']) ?>" 
                                                                     alt="Product Image" class="product-image">
                                                            <?php else: ?>
                                                                <div class="product-image d-flex align-items-center justify-content-center bg-light">
                                                                    <i class="fas fa-box text-muted"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="col-md-6 col-7">
                                                            <h6 class="mb-1"><?= htmlspecialchars($item['product_name']) ?></h6>
                                                            <p class="text-muted mb-1 small">
                                                                Product ID: <?= htmlspecialchars($item['product_id']) ?>
                                                            </p>
                                                            
                                                            <!-- Product Variant Information -->
                                                            <div class="variant-info mt-2">
                                                                <?php if (!empty($item['color'])): ?>
                                                                    <span class="variant-badge color">
                                                                        <i class="fas fa-palette me-1"></i>Color: <?= htmlspecialchars($item['color']) ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                                
                                                                <?php if (!empty($item['size'])): ?>
                                                                    <span class="variant-badge size">
                                                                        <i class="fas fa-ruler me-1"></i>Size: <?= htmlspecialchars($item['size']) ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                                
                                                                <?php if (!empty($item['sku'])): ?>
                                                                    <span class="variant-badge sku">
                                                                        <i class="fas fa-barcode me-1"></i>SKU: <?= htmlspecialchars($item['sku']) ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                                
                                                                <!-- Display attributes from JSON if no variant data found -->
                                                                <?php if (empty($item['color']) && empty($item['size']) && !empty($item['parsed_attributes'])): ?>
                                                                    <?php foreach ($item['parsed_attributes'] as $attr_key => $attr_value): ?>
                                                                        <?php if (!empty($attr_value)): ?>
                                                                            <span class="variant-badge <?= htmlspecialchars($attr_key) ?>">
                                                                                <?= ucfirst(htmlspecialchars($attr_key)) ?>: <?= htmlspecialchars($attr_value) ?>
                                                                            </span>
                                                                        <?php endif; ?>
                                                                    <?php endforeach; ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4 col-12 mt-md-0 mt-2">
                                            <div class="row text-md-end">
                                                <div class="col-4">
                                                    <div class="small text-muted">Qty:</div>
                                                    <div><?= htmlspecialchars($item['quantity']) ?></div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="small text-muted">Unit Price:</div>
                                                    <div>₹<?= number_format($item['unit_price'], 2) ?></div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="small text-muted">Total:</div>
                                                    <div class="fw-bold">₹<?= number_format($item['total_price'], 2) ?></div>
                                                </div>
                                            </div>
                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="text-center py-4">
                                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No items found in this order.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Order Summary & Timeline -->
                                <div class="row">
                                    <!-- Order Summary -->
                                    <div class="col-lg-8 mb-4">
                                        <div class="card h-100">
                                            <div class="card-header bg-light">
                                                <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>Order Summary</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6 mb-4">
                                                        <h6 class="mb-3 text-muted"><i class="fas fa-receipt me-2"></i>Amount Breakdown</h6>
                                                        <div class="mb-2 d-flex justify-content-between">
                                                            <span class="text-muted">Subtotal:</span>
                                                            <span>₹<?= number_format($order['total_amount'], 2) ?></span>
                                                        </div>
                                                        <div class="mb-2 d-flex justify-content-between">
                                                            <span class="text-muted">Discount:</span>
                                                            <span class="text-success">-₹<?= number_format($order['discount_amount'], 2) ?></span>
                                                        </div>
                                                        <div class="mb-2 d-flex justify-content-between">
                                                            <span class="text-muted">Shipping:</span>
                                                            <span class="text-info">+₹<?= number_format($order['shipping_amount'], 2) ?></span>
                                                        </div>
                                                        <div class="mb-2 d-flex justify-content-between">
                                                            <span class="text-muted">Tax:</span>
                                                            <span class="text-warning">+₹<?= number_format($order['tax_amount'], 2) ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 mb-4">
                                                        <h6 class="mb-3 text-muted"><i class="fas fa-calculator me-2"></i>Final Calculation</h6>
                                                        <div class="mb-2 d-flex justify-content-between">
                                                            <span class="text-muted">Items Total:</span>
                                                            <span>₹<?= number_format($order['total_amount'] - $order['discount_amount'], 2) ?></span>
                                                        </div>
                                                        <div class="mb-2 d-flex justify-content-between">
                                                            <span class="text-muted">Shipping + Tax:</span>
                                                            <span>₹<?= number_format($order['shipping_amount'] + $order['tax_amount'], 2) ?></span>
                                                        </div>
                                                        <div class="mb-3 pt-2 border-top d-flex justify-content-between">
                                                            <span class="fw-bold">Final Amount:</span>
                                                            <span class="fw-bold text-primary">₹<?= number_format($order['final_amount'], 2) ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <?php if (!empty($order['notes'])): ?>
                                                    <h6 class="mb-3 text-muted"><i class="fas fa-sticky-note me-2"></i>Order Notes</h6>
                                                    <div class="alert alert-light">
                                                        <?= nl2br(htmlspecialchars($order['notes'])) ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($billing_address) && $billing_address != $shipping_address): ?>
                                                    <h6 class="mb-3 text-muted"><i class="fas fa-file-invoice me-2"></i>Billing Address</h6>
                                                    <div class="address-box">
                                                        <?php if (!empty($billing_address['address'])): ?>
                                                            <p class="mb-2"><?= htmlspecialchars($billing_address['address']) ?></p>
                                                        <?php endif; ?>
                                                        <div class="row small">
                                                            <?php if (!empty($billing_address['city'])): ?>
                                                                <div class="col-6">
                                                                    <strong>City:</strong> <?= htmlspecialchars($billing_address['city']) ?>
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($billing_address['state'])): ?>
                                                                <div class="col-6">
                                                                    <strong>State:</strong> <?= htmlspecialchars($billing_address['state']) ?>
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($billing_address['postal_code'])): ?>
                                                                <div class="col-6">
                                                                    <strong>Postal Code:</strong> <?= htmlspecialchars($billing_address['postal_code']) ?>
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($billing_address['country'])): ?>
                                                                <div class="col-6">
                                                                    <strong>Country:</strong> <?= htmlspecialchars($billing_address['country']) ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Order Timeline -->
                                    <div class="col-lg-4 mb-4">
                                        <div class="card h-100">
                                            <div class="card-header bg-light">
                                                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Order Timeline</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="timeline">
                                                    <div class="timeline-item">
                                                        <div class="timeline-dot"></div>
                                                        <div class="timeline-content">
                                                            <div class="small text-muted"><?= date('M d, Y H:i', strtotime($order['created_at'])) ?></div>
                                                            <div class="fw-bold">Order Created</div>
                                                            <p class="small text-muted mb-0">Order #<?= htmlspecialchars($order['order_number']) ?> was placed</p>
                                                        </div>
                                                    </div>
                                                    
                                                    <?php if (!empty($order['updated_at']) && $order['updated_at'] != $order['created_at']): ?>
                                                    <div class="timeline-item">
                                                        <div class="timeline-dot"></div>
                                                        <div class="timeline-content">
                                                            <div class="small text-muted"><?= date('M d, Y H:i', strtotime($order['updated_at'])) ?></div>
                                                            <div class="fw-bold">Order Updated</div>
                                                            <p class="small text-muted mb-0">Status changed to <?= ucfirst($order['order_status']) ?></p>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>
                                                    
                                                    <div class="timeline-item">
                                                        <div class="timeline-dot"></div>
                                                        <div class="timeline-content">
                                                            <div class="small text-muted">Current</div>
                                                            <div class="fw-bold">Current Status</div>
                                                            <p class="small text-muted mb-0">
                                                                <span class="status-badge <?= $orderStatusClass ?>">
                                                                    <?= ucfirst($order['order_status']) ?>
                                                                </span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include "includes/footer.php"; ?>
    </section>

    <script>
        // Initialize tooltips
        $(document).ready(function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    </script>

</body>
</html>