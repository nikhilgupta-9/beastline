<?php
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';
require_once __DIR__ . '/models/PaymentSmtpSetting.php';
require_once __DIR__ . '/models/Setting.php';

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
                   pv.color, pv.size, pv.sku, pv.quantity as variant_quantity
            FROM `order_items` oi
            LEFT JOIN `products` p ON oi.product_id = p.id
            LEFT JOIN `product_variants` pv ON (
                oi.product_id = pv.product_id 
                AND (oi.attributes LIKE CONCAT('%\"color\":\"', pv.color, '\"%') 
                     OR oi.attributes LIKE CONCAT('%\"size\":\"', pv.size, '\"%')
                     OR oi.product_name LIKE CONCAT('%', pv.sku, '%'))
            )
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
        :root {
            --primary-color: #4e73df;
            --primary-light: #5d7ce0;
            --success-color: #1cc88a;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --info-color: #36b9cc;
            --light-bg: #f8f9fc;
            --dark-text: #5a5c69;
            --muted-text: #858796;
        }

        body {
            background-color: #f5f7fb;
            color: var(--dark-text);
            font-family: 'Nunito', sans-serif;
        }

        .order-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }

        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-pending {
            background-color: rgba(246, 194, 62, 0.2);
            color: var(--warning-color);
        }

        .badge-processing {
            background-color: rgba(54, 185, 204, 0.2);
            color: var(--info-color);
        }

        .badge-completed {
            background-color: rgba(28, 200, 138, 0.2);
            color: var(--success-color);
        }

        .badge-cancelled {
            background-color: rgba(231, 74, 59, 0.2);
            color: var(--danger-color);
        }

        .badge-refunded {
            background-color: rgba(108, 117, 125, 0.2);
            color: #6c757d;
        }

        .badge-shipping {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        .badge-payment-pending {
            background-color: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }

        .badge-payment-completed {
            background-color: rgba(25, 135, 84, 0.2);
            color: #198754;
        }

        .stats-card {
            background: white;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            padding: 1.5rem;
            height: 100%;
        }

        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 0.35rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .icon-primary {
            background: rgba(78, 115, 223, 0.1);
            color: var(--primary-color);
        }

        .icon-success {
            background: rgba(28, 200, 138, 0.1);
            color: var(--success-color);
        }

        .icon-info {
            background: rgba(54, 185, 204, 0.1);
            color: var(--info-color);
        }

        .icon-warning {
            background: rgba(246, 194, 62, 0.1);
            color: var(--warning-color);
        }

        .detail-card {
            background: white;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            border: none;
            margin-bottom: 1.5rem;
            height: 100%;
        }

        .card-header-light {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.35rem;
            border-radius: 0.35rem 0.35rem 0 0 !important;
        }

        .product-card {
            border: 1px solid #e3e6f0;
            border-radius: 0.35rem;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .product-card:hover {
            border-color: var(--primary-light);
            box-shadow: 0 0.125rem 0.25rem rgba(78, 115, 223, 0.1);
        }

        .product-image {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 0.25rem;
            background-color: #f8f9fc;
        }

        .variant-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border-radius: 0.25rem;
            margin-right: 0.5rem;
            margin-bottom: 0.25rem;
            background-color: #f8f9fc;
            color: var(--muted-text);
        }

        .variant-badge.color {
            background-color: #e8f4ff;
            color: #0066cc;
        }

        .variant-badge.size {
            background-color: #f0f8ff;
            color: #0052a3;
        }

        .variant-badge.sku {
            background-color: #f9f9f9;
            color: var(--dark-text);
            font-family: monospace;
        }

        .section-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--muted-text);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e3e6f0;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f8f9fa;
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: var(--muted-text);
            min-width: 140px;
        }

        .detail-value {
            color: var(--dark-text);
            text-align: right;
            flex: 1;
        }

        .amount-cell {
            font-weight: 700;
            color: var(--dark-text);
        }

        .customer-name {
            font-weight: 600;
            color: var(--dark-text);
        }

        .customer-email {
            color: var(--primary-color);
            font-size: 0.85rem;
            word-break: break-all;
        }

        .address-box {
            background: #f8f9fc;
            border-radius: 0.25rem;
            padding: 1rem;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline:before {
            content: '';
            position: absolute;
            left: 7px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e3e6f0;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-dot {
            position: absolute;
            left: -2rem;
            top: 0;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: var(--primary-color);
            border: 3px solid white;
            box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.2);
        }

        .timeline-content {
            padding-bottom: 0.5rem;
        }

        .timeline-date {
            font-size: 0.75rem;
            color: var(--muted-text);
            margin-bottom: 0.25rem;
        }

        @media (max-width: 768px) {
            .detail-item {
                flex-direction: column;
            }

            .detail-label {
                margin-bottom: 0.25rem;
            }

            .detail-value {
                text-align: left;
            }

            .stats-card {
                margin-bottom: 1rem;
            }

            .variant-badge {
                display: block;
                margin-right: 0;
                margin-bottom: 0.5rem;
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
                            <div class="col-xl-3 col-md-6">
                                <div class="stats-card">
                                    <div class="d-flex align-items-start">
                                        <div class="stats-icon icon-primary">
                                            <i class="fas fa-receipt"></i>
                                        </div>
                                        <div class="ms-3">
                                            <h5 class="mb-1">Order #<?= htmlspecialchars($order['order_number']) ?></h5>
                                            <p class="text-muted mb-0">Order Number</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="stats-card">
                                    <div class="d-flex align-items-start">
                                        <div class="stats-icon icon-success">
                                            <i class="fas fa-rupee-sign"></i>
                                        </div>
                                        <div class="ms-3">
                                            <h5 class="mb-1">₹<?= number_format($order['final_amount'], 2) ?></h5>
                                            <p class="text-muted mb-0">Final Amount</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="stats-card">
                                    <div class="d-flex align-items-start">
                                        <div class="stats-icon icon-info">
                                            <i class="fas fa-cube"></i>
                                        </div>
                                        <div class="ms-3">
                                            <h5 class="mb-1"><?= count($order_items) ?></h5>
                                            <p class="text-muted mb-0">Items</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="stats-card">
                                    <div class="d-flex align-items-start">
                                        <div class="stats-icon icon-warning">
                                            <i class="fas fa-calendar"></i>
                                        </div>
                                        <div class="ms-3">
                                            <h5 class="mb-1"><?= date('M d, Y', strtotime($order['created_at'])) ?></h5>
                                            <p class="text-muted mb-0">Order Date</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="white_card card_height_100 mb_30">
                            <div class="card-header card-header-light">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <div class="mb-2 mb-md-0">
                                        <h3 class="mb-0 fw-bold">Order Details</h3>
                                        <p class="text-muted mb-0">Complete information for order #<?= htmlspecialchars($order['order_number']) ?></p>
                                    </div>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="orders.php" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-arrow-left me-2"></i>Back to Orders
                                        </a>
                                        <!-- <a href="edit_order.php?id=<?= $order['order_id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit me-2"></i>Edit Order -->
                                        </a>
                                        <!-- <a href="generate_invoice.php?order_id=<?= $order['order_id'] ?>" class="btn btn-success btn-sm">
                                            <i class="fas fa-print me-2"></i>Print Invoice -->
                                        </a>
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
                                    case 'refunded':
                                        $orderStatusClass = 'badge-refunded';
                                        break;
                                    case 'shipped':
                                        $orderStatusClass = 'badge-shipping';
                                        break;
                                }

                                $paymentStatusClass = 'badge-payment-pending';
                                if (
                                    strtolower($order['payment_status']) === 'completed' ||
                                    strtolower($order['payment_status']) === 'paid'
                                ) {
                                    $paymentStatusClass = 'badge-payment-completed';
                                }
                                ?>

                                <div class="alert alert-light d-flex justify-content-between align-items-center mb-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div>
                                            <span class="text-muted">Order Status:</span>
                                            <span class="status-badge <?= $orderStatusClass ?> ms-2">
                                                <?= ucfirst($order['order_status']) ?>
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-muted">Payment Status:</span>
                                            <span class="status-badge <?= $paymentStatusClass ?> ms-2">
                                                <?= ucfirst($order['payment_status']) ?>
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
                                        <div class="detail-card">
                                            <div class="card-header card-header-light">
                                                <h5 class="mb-0"><i class="fas fa-user me-2"></i>Customer Information</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="d-flex align-items-center mb-3">
                                                    <div class="order-avatar me-3">
                                                        <?= strtoupper(substr($order['first_name'], 0, 1)) ?>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-1 customer-name"><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></h6>
                                                        <p class="text-muted mb-0 small">Customer ID: <?= htmlspecialchars($order['user_id']) ?></p>
                                                    </div>
                                                </div>

                                                <div class="section-title">Contact Details</div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Email:</span>
                                                    <span class="detail-value">
                                                        <a href="mailto:<?= htmlspecialchars($order['email']) ?>" class="customer-email">
                                                            <?= htmlspecialchars($order['email']) ?>
                                                        </a>
                                                    </span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">mobile:</span>
                                                    <span class="detail-value">
                                                        <a href="tel:<?= htmlspecialchars($order['mobile']) ?>" class="customer-email">
                                                            <?= htmlspecialchars($order['mobile']) ?>
                                                        </a>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Shipping Address -->
                                    <?php if (!empty($billing_address) && $billing_address != $shipping_address): ?>
                                        <div class="col-lg-4 mb-4">
                                            <div class="detail-card">
                                                <div class="card-header card-header-light">
                                                    <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Billing Address</h5>
                                                </div>
                                                <div class="card-body">
                                                    <div class="address-box">
                                                        <div class="mb-2">
                                                            <strong><?= htmlspecialchars($billing_address['first_name'] ?? '') ?> <?= htmlspecialchars($billing_address['last_name'] ?? '') ?></strong>
                                                        </div>

                                                        <?php if (!empty($billing_address['address_1'])): ?>
                                                            <p class="mb-1"><?= htmlspecialchars($billing_address['address_1']) ?></p>
                                                        <?php endif; ?>

                                                        <?php if (!empty($billing_address['address_2'])): ?>
                                                            <p class="mb-1"><?= htmlspecialchars($billing_address['address_2']) ?></p>
                                                        <?php endif; ?>

                                                        <div class="row small mt-2">
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

                                                            <?php if (!empty($billing_address['postcode'])): ?>
                                                                <div class="col-6">
                                                                    <strong>Postal Code:</strong> <?= htmlspecialchars($billing_address['postcode']) ?>
                                                                </div>
                                                            <?php endif; ?>

                                                            <?php if (!empty($billing_address['country'])): ?>
                                                                <div class="col-6">
                                                                    <strong>Country:</strong> <?= htmlspecialchars($billing_address['country']) ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>

                                                        <?php if (!empty($billing_address['phone'])): ?>
                                                            <div class="mt-2">
                                                                <strong>Phone:</strong> <?= htmlspecialchars($billing_address['phone']) ?>
                                                            </div>
                                                        <?php endif; ?>

                                                        <?php if (!empty($billing_address['email'])): ?>
                                                            <div class="mt-1">
                                                                <strong>Email:</strong> <?= htmlspecialchars($billing_address['email']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Payment Information -->
                                    <div class="col-lg-4 mb-4">
                                        <div class="detail-card">
                                            <div class="card-header card-header-light">
                                                <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment Information</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="detail-item">
                                                    <span class="detail-label">Method:</span>
                                                    <span class="detail-value">
                                                        <?= ucfirst(htmlspecialchars($order['payment_method'])) ?>
                                                    </span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Status:</span>
                                                    <span class="detail-value">
                                                        <span class="status-badge <?= $paymentStatusClass ?>">
                                                            <?= ucfirst($order['payment_status']) ?>
                                                        </span>
                                                    </span>
                                                </div>
                                                <?php if (!empty($order['razorpay_order_id'])): ?>
                                                    <div class="detail-item">
                                                        <span class="detail-label">Razorpay ID:</span>
                                                        <span class="detail-value small">
                                                            <?= htmlspecialchars($order['razorpay_order_id']) ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Order Items -->
                                <div class="detail-card mb-4">
                                    <div class="card-header card-header-light">
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
                                                        <div class="col-md-4 col-12">
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
                                                                    <div class="amount-cell">₹<?= number_format($item['total_price'], 2) ?></div>
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
                                        <div class="detail-card">
                                            <div class="card-header card-header-light">
                                                <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>Order Summary</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="section-title">Amount Breakdown</div>
                                                        <div class="detail-item">
                                                            <span class="detail-label">Subtotal:</span>
                                                            <span class="detail-value">₹<?= number_format($order['total_amount'], 2) ?></span>
                                                        </div>
                                                        <div class="detail-item">
                                                            <span class="detail-label">Discount:</span>
                                                            <span class="detail-value text-success">-₹<?= number_format($order['discount_amount'], 2) ?></span>
                                                        </div>
                                                        <div class="detail-item">
                                                            <span class="detail-label">Shipping:</span>
                                                            <span class="detail-value text-info">+₹<?= number_format($order['shipping_amount'], 2) ?></span>
                                                        </div>
                                                        <div class="detail-item">
                                                            <span class="detail-label">Tax:</span>
                                                            <span class="detail-value text-warning">+₹<?= number_format($order['tax_amount'], 2) ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="section-title">Final Calculation</div>
                                                        <div class="detail-item">
                                                            <span class="detail-label">Items Total:</span>
                                                            <span class="detail-value">₹<?= number_format($order['total_amount'] - $order['discount_amount'], 2) ?></span>
                                                        </div>
                                                        <div class="detail-item">
                                                            <span class="detail-label">Shipping + Tax:</span>
                                                            <span class="detail-value">₹<?= number_format($order['shipping_amount'] + $order['tax_amount'], 2) ?></span>
                                                        </div>
                                                        <div class="detail-item border-top pt-2">
                                                            <span class="detail-label fw-bold fs-5">Final Amount:</span>
                                                            <span class="detail-value fw-bold fs-5 text-primary">₹<?= number_format($order['final_amount'], 2) ?></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <?php if (!empty($order['notes'])): ?>
                                                    <div class="section-title mt-4">Order Notes</div>
                                                    <div class="alert alert-light">
                                                        <?= nl2br(htmlspecialchars($order['notes'])) ?>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($billing_address)): ?>
                                                    <div class="section-title mt-4">Billing Address</div>
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
                                        <div class="detail-card">
                                            <div class="card-header card-header-light">
                                                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Order Timeline</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="timeline">
                                                    <div class="timeline-item">
                                                        <div class="timeline-dot"></div>
                                                        <div class="timeline-content">
                                                            <div class="timeline-date"><?= date('M d, Y H:i', strtotime($order['created_at'])) ?></div>
                                                            <div class="fw-bold">Order Created</div>
                                                            <p class="small text-muted mb-0">Order #<?= htmlspecialchars($order['order_number']) ?> was placed</p>
                                                        </div>
                                                    </div>

                                                    <?php if (!empty($order['updated_at']) && $order['updated_at'] != $order['created_at']): ?>
                                                        <div class="timeline-item">
                                                            <div class="timeline-dot"></div>
                                                            <div class="timeline-content">
                                                                <div class="timeline-date"><?= date('M d, Y H:i', strtotime($order['updated_at'])) ?></div>
                                                                <div class="fw-bold">Order Updated</div>
                                                                <p class="small text-muted mb-0">Status changed to <?= ucfirst($order['order_status']) ?></p>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <div class="timeline-item">
                                                        <div class="timeline-dot"></div>
                                                        <div class="timeline-content">
                                                            <div class="timeline-date">Current</div>
                                                            <div class="fw-bold">Order Status</div>
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