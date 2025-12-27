<?php include('auth_check.php'); ?>
<?php
include "db-conn.php";

// Get order id from URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $order_id = intval($_GET['id']);
    
    // Fetch order details from database
    $stmt = $conn->prepare("SELECT * FROM orders_new WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    
    if (!$order) {
        $_SESSION['error'] = "Order not found.";
        header("Location: orders.php");
        exit;
    }
    
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

} else {
    $_SESSION['error'] = "Invalid order id.";
    header("Location: orders.php");
    exit;
}

// Function to parse product string like "Hair Growth Serum (x1)"
function parseProductString($productString) {
    $products = [];
    
    // If it's a single product string with quantity
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

// Process form submission for updating the order
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_order'])) {
    // Get form data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $postal_code = trim($_POST['postal_code']);
    $country = trim($_POST['country']);
    $status = $_POST['status'];
    $order_total = floatval($_POST['order_total']);
    
    // Validate required fields
    $errors = [];
    if (empty($first_name)) $errors[] = "First name is required.";
    if (empty($last_name)) $errors[] = "Last name is required.";
    if (empty($email)) $errors[] = "Email is required.";
    if (empty($phone)) $errors[] = "Phone is required.";
    if (empty($address)) $errors[] = "Address is required.";
    if (empty($city)) $errors[] = "City is required.";
    if (empty($state)) $errors[] = "State is required.";
    if (empty($postal_code)) $errors[] = "Postal code is required.";
    if (empty($country)) $errors[] = "Country is required.";
    if ($order_total <= 0) $errors[] = "Order total must be greater than 0.";
    
    // Validate the status against ENUM values
    $allowed_statuses = ['Pending', 'Processing', 'Completed', 'Cancelled'];
    if (!in_array($status, $allowed_statuses)) {
        $errors[] = "Invalid status selected.";
    }
    
    if (empty($errors)) {
        // Update order in database
        $stmt = $conn->prepare("UPDATE orders_new SET 
            first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, 
            city = ?, state = ?, postal_code = ?, country = ?, status = ?, 
            order_total = ?, updated_at = NOW() 
            WHERE id = ?");
        
        $stmt->bind_param("ssssssssssdi", 
            $first_name, $last_name, $email, $phone, $address,
            $city, $state, $postal_code, $country, $status,
            $order_total, $order_id
        );
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Order updated successfully!";
            
            // Refresh order data
            $stmt = $conn->prepare("SELECT * FROM orders_new WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $order = $result->fetch_assoc();
            
            // Re-parse products after refresh
            $products_data = $order['products'];
            if (is_string($products_data)) {
                $decoded = json_decode($products_data, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $products = $decoded;
                } else {
                    $products = parseProductString($products_data);
                }
            } elseif (is_array($products_data)) {
                $products = $products_data;
            }
        } else {
            $_SESSION['error'] = "Failed to update order: " . $stmt->error;
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
    
    header("Location: edit_order.php?id=" . $order_id);
    exit;
}

?>

<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Edit Order #<?= htmlspecialchars($order['order_id']) ?> | Admin Panel</title>
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
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-processing { background: #cce7ff; color: #004085; }
        .badge-completed { background: #d4edda; color: #155724; }
        .badge-cancelled { background: #f8d7da; color: #721c24; }
        .badge-razorpay { background: #4361ee; color: white; }
        .badge-cod { background: #6c757d; color: white; }
        
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
        
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #495057;
            border-left: 3px solid #667eea;
            padding-left: 10px;
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

                        <div class="white_card card_height_100 mb_30">
                            <div class="card-header bg-white border-0 py-3">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <div class="mb-2 mb-md-0">
                                        <h2 class="mb-0 fw-bold">Edit Order #<?= htmlspecialchars($order['order_id']) ?></h2>
                                        <p class="text-muted mb-0">Update order information and status</p>
                                    </div>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="orders.php" class="btn btn-outline-primary">
                                            <i class="fas fa-arrow-left me-2"></i>Back to Orders
                                        </a>
                                        <a href="order_details.php?id=<?= $order['id'] ?>" class="btn btn-info">
                                            <i class="fas fa-eye me-2"></i>View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="white_card_body">
                                <!-- Success/Error Messages -->
                                <?php if (isset($_SESSION['error'])): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($_SESSION['success'])): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="edit_order.php?id=<?= $order_id ?>">
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
                                                            <p class="text-muted mb-0">Customer ID: <?= htmlspecialchars($order['user_id'] ?? 'Guest') ?></p>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="section-title">Contact Details</div>
                                                    <div class="mb-3">
                                                        <label class="form-label">First Name *</label>
                                                        <input type="text" class="form-control" name="first_name" 
                                                               value="<?= htmlspecialchars($order['first_name']) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Last Name *</label>
                                                        <input type="text" class="form-control" name="last_name" 
                                                               value="<?= htmlspecialchars($order['last_name']) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Email *</label>
                                                        <input type="email" class="form-control" name="email" 
                                                               value="<?= htmlspecialchars($order['email']) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Phone *</label>
                                                        <input type="text" class="form-control" name="phone" 
                                                               value="<?= htmlspecialchars($order['phone']) ?>" required>
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
                                                    <div class="mb-3">
                                                        <label class="form-label">Address *</label>
                                                        <textarea class="form-control" name="address" rows="3" required><?= htmlspecialchars($order['address']) ?></textarea>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">City *</label>
                                                            <input type="text" class="form-control" name="city" 
                                                                   value="<?= htmlspecialchars($order['city']) ?>" required>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">State *</label>
                                                            <input type="text" class="form-control" name="state" 
                                                                   value="<?= htmlspecialchars($order['state']) ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Postal Code *</label>
                                                            <input type="text" class="form-control" name="postal_code" 
                                                                   value="<?= htmlspecialchars($order['postal_code']) ?>" required>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Country *</label>
                                                            <input type="text" class="form-control" name="country" 
                                                                   value="<?= htmlspecialchars($order['country']) ?>" required>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Order Information -->
                                    <div class="row">
                                        <div class="col-lg-6 mb-4">
                                            <div class="card detail-card h-100">
                                                <div class="card-header bg-light">
                                                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Order Information</h5>
                                                </div>
                                                <div class="card-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Order Status *</label>
                                                        <select name="status" class="form-select" required>
                                                            <option value="Pending" <?= $order['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                            <option value="Processing" <?= $order['status'] == 'Processing' ? 'selected' : '' ?>>Processing</option>
                                                            <option value="Completed" <?= $order['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                                            <option value="Cancelled" <?= $order['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Order Total (₹) *</label>
                                                        <input type="number" step="0.01" class="form-control" name="order_total" 
                                                               value="<?= number_format($order['order_total'], 2) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Payment Method</label>
                                                        <input type="text" class="form-control" 
                                                               value="<?= htmlspecialchars($order['payment_method']) ?>" readonly>
                                                    </div>
                                                    <?php if ($order['payment_method'] === 'razorpay'): ?>
                                                        <div class="mb-3">
                                                            <label class="form-label">Razorpay Order ID</label>
                                                            <input type="text" class="form-control" 
                                                                   value="<?= htmlspecialchars($order['razorpay_order_id'] ?? 'N/A') ?>" readonly>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Razorpay Payment ID</label>
                                                            <input type="text" class="form-control" 
                                                                   value="<?= htmlspecialchars($order['razorpay_payment_id'] ?? 'N/A') ?>" readonly>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Order Items (Read-only) -->
                                        <div class="col-lg-6 mb-4">
                                            <div class="card detail-card h-100">
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
                                                                    <div class="col-md-3 col-12 text-md-end mt-md-0 mt-2">
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
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="card detail-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                                <div>
                                                    <small class="text-muted">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        Last updated: <?= date('F j, Y, g:i a', strtotime($order['created_at'])) ?>
                                                    </small>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <a href="orders.php" class="btn btn-outline-secondary">
                                                        <i class="fas fa-times me-2"></i>Cancel
                                                    </a>
                                                    <button type="submit" name="update_order" class="btn btn-primary">
                                                        <i class="fas fa-save me-2"></i>Update Order
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
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

        // Auto-format order total
        document.querySelector('input[name="order_total"]').addEventListener('blur', function() {
            this.value = parseFloat(this.value).toFixed(2);
        });
    </script>

</body>
</html>