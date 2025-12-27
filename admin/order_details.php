<?php
session_start();
include "db-conn.php";

$id = $_GET['id'] ?? null;
$errors = [];

if ($id === null) {
    header("Location: orders.php");
    exit;
} else {
    $stmt = $conn->prepare("SELECT * FROM `orders_new` WHERE `id` = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Order not found!";
        header("Location: orders.php");
        exit;
    } else {
        $order = $result->fetch_assoc();

        // Handle different product data formats
        $products = [];
        $products_data = $order['products'];
        
        // Check if it's JSON format
        if (is_string($products_data)) {
            $decoded = json_decode($products_data, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // It's valid JSON array
                $products = $decoded;
            } else {
                // It's a string format like "Product Name (x1)"
                $products = parseProductString($products_data);
            }
        } elseif (is_array($products_data)) {
            // It's already an array
            $products = $products_data;
        }
    }
}

// Function to parse product string like "Hair Growth Serum (x1)"
function parseProductString($productString) {
    $products = [];
    
    // If it's a single product string
    if (preg_match('/(.+)\s\(x(\d+)\)/', $productString, $matches)) {
        $products[] = [
            'name' => trim($matches[1]),
            'quantity' => intval($matches[2]),
            'price' => 0, // You might need to get this from another source
            'image' => '' // You might need to get this from another source
        ];
    } else {
        // If it's just a product name without quantity
        $products[] = [
            'name' => trim($productString),
            'quantity' => 1,
            'price' => 0,
            'image' => ''
        ];
    }
    
    return $products;
}
?>

<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Order #<?= htmlspecialchars($order['order_id']) ?> | Admin Panel</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">

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

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-processing {
            background: #cce7ff;
            color: #004085;
        }

        .badge-completed {
            background: #d4edda;
            color: #155724;
        }

        .badge-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-razorpay {
            background: #4361ee;
            color: white;
        }

        .badge-cod {
            background: #6c757d;
            color: white;
        }

        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
        }

        .detail-card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            border: none;
            margin-bottom: 20px;
        }

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

        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            margin: 2px;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            transform: translateY(-1px);
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #495057;
            border-left: 3px solid #667eea;
            padding-left: 10px;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f8f9fa;
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 500;
            color: #6c757d;
        }
        
        .debug-info {
            background: #f8f9fa;
            border-left: 4px solid #dc3545;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>

<body class="crm_body_bg">

    <?php include "header.php"; ?>

    <section class="main_content dashboard_part large_header_bg">
        <div class="container-fluid g-0">
            <div class="row">
                <div class="col-lg-12 p-0">
                    <?php include "top_nav.php"; ?>
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

                        <!-- Debug Information (Remove in production) -->
                        <?php if (false): // Set to true to see debug info ?>
                        <div class="debug-info">
                            <strong>Products Data Debug:</strong><br>
                            Raw: <?= htmlspecialchars($order['products']) ?><br>
                            Type: <?= gettype($order['products']) ?><br>
                            Parsed Count: <?= count($products) ?><br>
                            Parsed: <?= print_r($products, true) ?>
                        </div>
                        <?php endif; ?>

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-xl-3 col-md-6">
                                <div class="stats-card">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h4 class="mb-1">#<?= htmlspecialchars($order['order_id']) ?></h4>
                                            <p class="mb-0 opacity-75">Order ID</p>
                                        </div>
                                        <div class="fs-1 opacity-50">
                                            <i class="fas fa-receipt"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h4 class="mb-1">₹<?= number_format($order['order_total'], 2) ?></h4>
                                                <p class="mb-0 opacity-75">Order Total</p>
                                            </div>
                                            <div class="fs-1 opacity-50">
                                                <i class="fas fa-rupee-sign"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h4 class="mb-1"><?= count($products) ?></h4>
                                                <p class="mb-0 opacity-75">Items</p>
                                            </div>
                                            <div class="fs-1 opacity-50">
                                                <i class="fas fa-cube"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h4 class="mb-1"><?= date('M d, Y', strtotime($order['created_at'])) ?></h4>
                                                <p class="mb-0 opacity-75">Order Date</p>
                                            </div>
                                            <div class="fs-1 opacity-50">
                                                <i class="fas fa-calendar"></i>
                                            </div>
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
                                        <p class="text-muted mb-0">Complete information for order #<?= htmlspecialchars($order['order_id']) ?></p>
                                    </div>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="orders.php" class="btn btn-outline-primary">
                                            <i class="fas fa-arrow-left me-2"></i>Back to Orders
                                        </a>
                                        <a href="edit_order.php?id=<?= $order['id'] ?>" class="btn btn-primary">
                                            <i class="fas fa-edit me-2"></i>Update Status
                                        </a>
                                        <a href="generate_invoice.php?order_id=<?= $order['order_id'] ?>" class="btn btn-success">
                                            <i class="fas fa-print me-2"></i>Print Invoice
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="white_card_body">
                                <!-- Order Status Alert -->
                                <div class="alert alert-info d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-info-circle me-2"></i>
                                        Order is currently
                                        <span class="status-badge badge-<?= strtolower($order['status']) ?> ms-2">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </div>
                                    <div class="text-muted small">
                                        Created: <?= date('F j, Y, g:i a', strtotime($order['created_at'])) ?>
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- Customer Information -->
                                    <div class="col-lg-6 mb-4">
                                        <div class="card detail-card h-100">
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
                                                        <p class="text-muted mb-0">Customer</p>
                                                    </div>
                                                </div>

                                                <div class="section-title">Contact Details</div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Email:</span>
                                                    <span><?= htmlspecialchars($order['email']) ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Phone:</span>
                                                    <span><?= htmlspecialchars($order['phone']) ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">User ID:</span>
                                                    <span><?= htmlspecialchars($order['user_id'] ?? 'Guest') ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Shipping Address -->
                                    <div class="col-lg-6 mb-4">
                                        <div class="card detail-card h-100">
                                            <div class="card-header bg-light">
                                                <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Shipping Address</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="section-title">Delivery Information</div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Address:</span>
                                                    <span><?= htmlspecialchars($order['address']) ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">City:</span>
                                                    <span><?= htmlspecialchars($order['city']) ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">State:</span>
                                                    <span><?= htmlspecialchars($order['state']) ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Postal Code:</span>
                                                    <span><?= htmlspecialchars($order['postal_code']) ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Country:</span>
                                                    <span><?= htmlspecialchars($order['country']) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Order Items -->
                                <div class="card detail-card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Order Items</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (count($products) > 0): ?>
                                            <?php foreach ($products as $index => $product): ?>
                                                <div class="product-card">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-1 col-2">
                                                            <div class="text-muted small">#<?= $index + 1 ?></div>
                                                        </div>
                                                        <div class="col-md-1 col-3">
                                                            <?php if (!empty($product['image'])): ?>
                                                                <img src="<?= htmlspecialchars($product['image']) ?>" alt="Product Image" class="product-image">
                                                            <?php else: ?>
                                                                <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                                                    <i class="fas fa-box-open text-muted"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="col-md-6 col-7">
                                                            <h6 class="mb-1"><?= htmlspecialchars($product['name'] ?? 'Unknown Product') ?></h6>
                                                            <p class="text-muted mb-1 small">Quantity: <?= htmlspecialchars($product['quantity'] ?? 1) ?></p>
                                                            <p class="text-muted mb-0 small">
                                                                <?php if (isset($product['price']) && $product['price'] > 0): ?>
                                                                    Unit Price: ₹<?= number_format($product['price'], 2) ?>
                                                                <?php else: ?>
                                                                    Price: Not specified
                                                                <?php endif; ?>
                                                            </p>
                                                        </div>
                                                        <div class="col-md-4 col-12 text-md-end mt-md-0 mt-2">
                                                            <?php if (isset($product['price']) && $product['price'] > 0): ?>
                                                                <strong class="text-primary">₹<?= number_format(($product['price'] ?? 0) * ($product['quantity'] ?? 1), 2) ?></strong>
                                                            <?php else: ?>
                                                                <span class="text-muted">Price not available</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="text-center py-4">
                                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No products found in this order.</p>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Order Summary -->
                                        <div class="row mt-4">
                                            <div class="col-md-6 offset-md-6">
                                                <div class="section-title">Order Summary</div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Subtotal:</span>
                                                    <span>₹<?= number_format($order['order_total'], 2) ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Shipping:</span>
                                                    <span>₹0.00</span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Tax:</span>
                                                    <span>₹0.00</span>
                                                </div>
                                                <div class="detail-item fs-5 fw-bold border-top pt-2">
                                                    <span class="detail-label">Total Amount:</span>
                                                    <span class="text-primary">₹<?= number_format($order['order_total'], 2) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment & Additional Information -->
                                <div class="row">
                                    <div class="col-lg-6 mb-4">
                                        <div class="card detail-card h-100">
                                            <div class="card-header bg-light">
                                                <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment Information</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="detail-item">
                                                    <span class="detail-label">Payment Method:</span>
                                                    <span class="status-badge badge-<?= strtolower($order['payment_method']) ?>">
                                                        <?= htmlspecialchars($order['payment_method']) ?>
                                                    </span>
                                                </div>
                                                <?php if ($order['payment_method'] === 'razorpay'): ?>
                                                    <div class="detail-item">
                                                        <span class="detail-label">Razorpay Order ID:</span>
                                                        <span class="font-monospace small"><?= htmlspecialchars($order['razorpay_order_id'] ?? 'N/A') ?></span>
                                                    </div>
                                                    <div class="detail-item">
                                                        <span class="detail-label">Razorpay Payment ID:</span>
                                                        <span class="font-monospace small"><?= htmlspecialchars($order['razorpay_payment_id'] ?? 'N/A') ?></span>
                                                    </div>
                                                    <?php if (!empty($order['razorpay_signature'])): ?>
                                                        <div class="detail-item">
                                                            <span class="detail-label">Payment Signature:</span>
                                                            <span class="font-monospace small text-truncate d-block" style="max-width: 200px;">
                                                                <?= htmlspecialchars($order['razorpay_signature']) ?>
                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 mb-4">
                                        <div class="card detail-card h-100">
                                            <div class="card-header bg-light">
                                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Additional Information</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="detail-item">
                                                    <span class="detail-label">User IP:</span>
                                                    <span class="font-monospace small"><?= htmlspecialchars($order['user_ip']) ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Session ID:</span>
                                                    <span class="font-monospace small text-truncate d-block" style="max-width: 200px;">
                                                        <?= htmlspecialchars($order['session_id']) ?>
                                                    </span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Database ID:</span>
                                                    <span><?= htmlspecialchars($order['id']) ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Created At:</span>
                                                    <span><?= date('F j, Y, g:i a', strtotime($order['created_at'])) ?></span>
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

        <?php include "footer.php"; ?>
    </section>

    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>

</body>
</html>