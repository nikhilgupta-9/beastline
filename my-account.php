<?php
session_start();
include_once "config/connect.php";
include_once "util/function.php";

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: " . $site . "user-login/");
    exit();
}

$contact = contact_us();
$user_id = $_SESSION['user_id'];

// Get user details
$user_sql = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Get orders count
$orders_sql = "SELECT COUNT(*) as total_orders, 
               SUM(final_amount) as total_spent,
               COUNT(CASE WHEN order_status = 'delivered' THEN 1 END) as delivered_orders
               FROM orders WHERE user_id = ?";
$orders_stmt = $conn->prepare($orders_sql);
$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();
$order_stats = $orders_result->fetch_assoc();

// Get recent orders
$recent_orders_sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$recent_orders_stmt = $conn->prepare($recent_orders_sql);
$recent_orders_stmt->bind_param("i", $user_id);
$recent_orders_stmt->execute();
$recent_orders = $recent_orders_stmt->get_result();

// Get user addresses
$addresses_sql = "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC";
$addresses_stmt = $conn->prepare($addresses_sql);
$addresses_stmt->bind_param("i", $user_id);
$addresses_stmt->execute();
$addresses = $addresses_stmt->get_result();

// Get wishlist count
$wishlist_sql = "SELECT COUNT(*) as wishlist_count FROM wishlist WHERE user_id = ?";
$wishlist_stmt = $conn->prepare($wishlist_sql);
$wishlist_stmt->bind_param("i", $user_id);
$wishlist_stmt->execute();
$wishlist_result = $wishlist_stmt->get_result();
$wishlist_count = $wishlist_result->fetch_assoc()['wishlist_count'] ?? 0;

// Handle profile update
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile'] ?? '');
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;
    
    $update_sql = "UPDATE users SET first_name = ?, last_name = ?, mobile = ?, newsletter_subscribed = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("sssii", $first_name, $last_name, $mobile, $newsletter, $user_id);
    
    if($update_stmt->execute()) {
        $_SESSION['user_name'] = $first_name . ' ' . $last_name;
        $success = "Profile updated successfully!";
        
        // Refresh user data
        $user['first_name'] = $first_name;
        $user['last_name'] = $last_name;
        $user['mobile'] = $mobile;
        $user['newsletter_subscribed'] = $newsletter;
    } else {
        $error = "Failed to update profile. Please try again.";
    }
}

// Handle password change
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    if(password_verify($current_password, $user['password'])) {
        if($new_password === $confirm_password) {
            if(strlen($new_password) >= 6) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $password_sql = "UPDATE users SET password = ? WHERE id = ?";
                $password_stmt = $conn->prepare($password_sql);
                $password_stmt->bind_param("si", $hashed_password, $user_id);
                
                if($password_stmt->execute()) {
                    $success = "Password changed successfully!";
                } else {
                    $error = "Failed to change password. Please try again.";
                }
            } else {
                $error = "New password must be at least 6 characters.";
            }
        } else {
            $error = "New passwords do not match.";
        }
    } else {
        $error = "Current password is incorrect.";
    }
}

// Get cart count for header
$cart_count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
?>
<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>My Account | Beastline</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="<?= $site ?>assets/img/favicon/favicon.ico">

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

    <!--modernizr min js here-->
    <script src="<?= $site ?>assets/js/vendor/modernizr-3.7.1.min.js"></script>

    <style>
        :root {
            --hm-red: #E50010;
            --hm-dark: #222222;
            --hm-light: #F5F5F5;
            --hm-gray: #767676;
            --hm-border: #E5E5E5;
        }
        
        .hm-dashboard {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--hm-dark);
        }
        
        .hm-dashboard-header {
            background: white;
            padding: 40px 0 20px;
            border-bottom: 1px solid var(--hm-border);
        }
        
        .hm-welcome {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .hm-welcome h1 {
            font-size: 32px;
            font-weight: 400;
            margin: 0;
        }
        
        .hm-welcome p {
            color: var(--hm-gray);
            margin: 5px 0 0 0;
        }
        
        .hm-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .hm-stat-card {
            background: white;
            border: 1px solid var(--hm-border);
            border-radius: 4px;
            padding: 24px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .hm-stat-card:hover {
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .hm-stat-number {
            font-size: 32px;
            font-weight: 500;
            color: var(--hm-dark);
            margin-bottom: 8px;
        }
        
        .hm-stat-label {
            font-size: 14px;
            color: var(--hm-gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .hm-dashboard-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 40px;
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .hm-sidebar {
            background: white;
            border: 1px solid var(--hm-border);
            border-radius: 4px;
            padding: 20px 0;
        }
        
        .hm-nav-item {
            padding: 16px 24px;
            display: flex;
            align-items: center;
            text-decoration: none;
            color: var(--hm-dark);
            border-left: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .hm-nav-item:hover, .hm-nav-item.active {
            background: var(--hm-light);
            border-left-color: var(--hm-red);
            color: var(--hm-red);
        }
        
        .hm-nav-item i {
            width: 20px;
            margin-right: 12px;
            text-align: center;
        }
        
        .hm-content {
            background: white;
            border: 1px solid var(--hm-border);
            border-radius: 4px;
            padding: 32px;
        }
        
        .hm-section-title {
            font-size: 24px;
            font-weight: 400;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--hm-border);
        }
        
        .hm-orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .hm-orders-table th {
            text-align: left;
            padding: 12px 16px;
            border-bottom: 2px solid var(--hm-border);
            font-weight: 500;
            color: var(--hm-gray);
            font-size: 14px;
        }
        
        .hm-orders-table td {
            padding: 16px;
            border-bottom: 1px solid var(--hm-border);
        }
        
        .hm-status {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .hm-status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .hm-status-processing {
            background: #cce5ff;
            color: #004085;
        }
        
        .hm-status-delivered {
            background: #d4edda;
            color: #155724;
        }
        
        .hm-status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .hm-order-link {
            color: var(--hm-red);
            text-decoration: none;
            font-weight: 500;
        }
        
        .hm-order-link:hover {
            text-decoration: underline;
        }
        
        .hm-address-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
        }
        
        .hm-address-card {
            border: 1px solid var(--hm-border);
            border-radius: 4px;
            padding: 24px;
            position: relative;
        }
        
        .hm-address-card.default {
            border-color: var(--hm-red);
        }
        
        .hm-address-default {
            position: absolute;
            top: 16px;
            right: 16px;
            background: var(--hm-red);
            color: white;
            padding: 2px 8px;
            border-radius: 2px;
            font-size: 12px;
        }
        
        .hm-address-actions {
            margin-top: 16px;
            display: flex;
            gap: 12px;
        }
        
        .hm-form-group {
            margin-bottom: 24px;
        }
        
        .hm-form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
        }
        
        .hm-form-input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--hm-border);
            border-radius: 4px;
            font-size: 16px;
        }
        
        .hm-form-input:focus {
            outline: none;
            border-color: var(--hm-dark);
            box-shadow: 0 0 0 1px var(--hm-dark);
        }
        
        .hm-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .hm-btn-primary {
            background: var(--hm-red);
            color: white;
        }
        
        .hm-btn-primary:hover {
            background: #cc000e;
        }
        
        .hm-btn-secondary {
            background: white;
            color: var(--hm-dark);
            border: 1px solid var(--hm-border);
        }
        
        .hm-btn-secondary:hover {
            background: var(--hm-light);
        }
        
        .hm-alert {
            padding: 16px;
            border-radius: 4px;
            margin-bottom: 24px;
        }
        
        .hm-alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .hm-alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .hm-empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .hm-empty-icon {
            font-size: 48px;
            color: var(--hm-border);
            margin-bottom: 20px;
        }
        
        .hm-empty-title {
            font-size: 20px;
            margin-bottom: 8px;
            color: var(--hm-dark);
        }
        
        .hm-empty-text {
            color: var(--hm-gray);
            margin-bottom: 24px;
        }
        
        @media (max-width: 768px) {
            .hm-dashboard-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .hm-stats-grid {
                grid-template-columns: 1fr;
            }
            
            .hm-sidebar {
                overflow-x: auto;
                display: flex;
                padding: 0;
            }
            
            .hm-nav-item {
                white-space: nowrap;
                border-left: none;
                border-bottom: 3px solid transparent;
            }
            
            .hm-nav-item:hover, .hm-nav-item.active {
                border-left: none;
                border-bottom-color: var(--hm-red);
            }
        }
        
        .hm-quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 32px;
        }
        
        .hm-quick-action {
            display: flex;
            align-items: center;
            padding: 20px;
            background: var(--hm-light);
            border-radius: 4px;
            text-decoration: none;
            color: var(--hm-dark);
            transition: all 0.3s;
        }
        
        .hm-quick-action:hover {
            background: #e9ecef;
        }
        
        .hm-quick-action i {
            font-size: 24px;
            margin-right: 16px;
            color: var(--hm-red);
        }
    </style>
</head>

<body class="hm-dashboard">

    <!--header area start-->
    <?php include_once "includes/header.php" ?>
    <!--header area end-->
    
    <!-- Dashboard Header -->
    <div class="hm-dashboard-header">
        <div class="container">
            <div class="hm-welcome">
                <div>
                    <h1>Welcome back, <?= htmlspecialchars($user['first_name']) ?>!</h1>
                    <p>Member since 
                        <?php
                        if(!empty($user['created_at'])){
                            echo date('F Y', strtotime($user['created_at']));
                        }else{
                            echo 'N/A';
                        }
                        ?>
                        
                    </p>
                </div>
                <div>
                    <a href="<?= $site ?>shop/" class="hm-btn hm-btn-primary">Continue Shopping</a>
                </div>
            </div>
            
            <!-- Stats Grid -->
            <div class="hm-stats-grid">
                <div class="hm-stat-card">
                    <div class="hm-stat-number"><?= $order_stats['total_orders'] ?? 0 ?></div>
                    <div class="hm-stat-label">Total Orders</div>
                </div>
                
                <div class="hm-stat-card">
                    <div class="hm-stat-number">₹<?= number_format($order_stats['total_spent'] ?? 0, 2) ?></div>
                    <div class="hm-stat-label">Total Spent</div>
                </div>
                
                <div class="hm-stat-card">
                    <div class="hm-stat-number"><?= $order_stats['delivered_orders'] ?? 0 ?></div>
                    <div class="hm-stat-label">Delivered Orders</div>
                </div>
                
                <div class="hm-stat-card">
                    <div class="hm-stat-number"><?= $wishlist_count ?></div>
                    <div class="hm-stat-label">Wishlist Items</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="hm-dashboard-container">
        <!-- Sidebar -->
        <div class="hm-sidebar">
            <a href="#dashboard" class="hm-nav-item active" data-tab="dashboard">
                <i class="fa fa-home"></i> Dashboard
            </a>
            <a href="#orders" class="hm-nav-item" data-tab="orders">
                <i class="fa fa-shopping-bag"></i> My Orders
            </a>
            <a href="#addresses" class="hm-nav-item" data-tab="addresses">
                <i class="fa fa-map-marker"></i> Addresses
            </a>
            <a href="#wishlist" class="hm-nav-item" data-tab="wishlist">
                <i class="fa fa-heart"></i> Wishlist
            </a>
            <a href="#profile" class="hm-nav-item" data-tab="profile">
                <i class="fa fa-user"></i> Profile
            </a>
            <a href="#security" class="hm-nav-item" data-tab="security">
                <i class="fa fa-lock"></i> Security
            </a>
            <a href="<?= $site ?>logout/" class="hm-nav-item">
                <i class="fa fa-sign-out"></i> Logout
            </a>
        </div>

        <!-- Main Content -->
        <div class="hm-content">
            <?php if(isset($success)): ?>
            <div class="hm-alert hm-alert-success">
                <?= htmlspecialchars($success) ?>
            </div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
            <div class="hm-alert hm-alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <!-- Dashboard Tab -->
            <div id="dashboard" class="hm-tab-content active">
                <h2 class="hm-section-title">Dashboard Overview</h2>
                
                <div class="hm-quick-actions">
                    <a href="<?= $site ?>shop/" class="hm-quick-action">
                        <i class="fa fa-shopping-cart"></i>
                        <div>
                            <strong>Continue Shopping</strong>
                            <p>Browse our latest collections</p>
                        </div>
                    </a>
                    
                    <a href="#orders" class="hm-quick-action switch-tab">
                        <i class="fa fa-history"></i>
                        <div>
                            <strong>Order History</strong>
                            <p>View all your orders</p>
                        </div>
                    </a>
                    
                    <a href="#addresses" class="hm-quick-action switch-tab">
                        <i class="fa fa-address-book"></i>
                        <div>
                            <strong>Manage Addresses</strong>
                            <p>Update shipping addresses</p>
                        </div>
                    </a>
                    
                    <a href="#profile" class="hm-quick-action switch-tab">
                        <i class="fa fa-cog"></i>
                        <div>
                            <strong>Update Profile</strong>
                            <p>Edit personal information</p>
                        </div>
                    </a>
                </div>

                <!-- Recent Orders -->
                <h3 style="margin-top: 40px; margin-bottom: 20px;">Recent Orders</h3>
                <?php if($recent_orders->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="hm-orders-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($order = $recent_orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $order['order_number'] ?></td>
                                <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <span class="hm-status hm-status-<?= $order['order_status'] ?>">
                                        <?= ucfirst($order['order_status']) ?>
                                    </span>
                                </td>
                                <td>₹<?= number_format($order['final_amount'], 2) ?></td>
                                <td>
                                    <a href="<?= $site ?>order-details/<?= $order['order_id'] ?>/" class="hm-order-link">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="hm-empty-state">
                    <div class="hm-empty-icon">
                        <i class="fa fa-shopping-bag"></i>
                    </div>
                    <h3 class="hm-empty-title">No orders yet</h3>
                    <p class="hm-empty-text">You haven't placed any orders yet. Start shopping now!</p>
                    <a href="<?= $site ?>shop/" class="hm-btn hm-btn-primary">Start Shopping</a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Orders Tab -->
            <div id="orders" class="hm-tab-content">
                <h2 class="hm-section-title">My Orders</h2>
                
                <?php
                // Get all orders
                $all_orders_sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
                $all_orders_stmt = $conn->prepare($all_orders_sql);
                $all_orders_stmt->bind_param("i", $user_id);
                $all_orders_stmt->execute();
                $all_orders = $all_orders_stmt->get_result();
                ?>
                
                <?php if($all_orders->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="hm-orders-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($order = $all_orders->fetch_assoc()): 
                                // Get item count for this order
                                $items_sql = "SELECT COUNT(*) as item_count FROM order_items WHERE order_id = ?";
                                $items_stmt = $conn->prepare($items_sql);
                                $items_stmt->bind_param("i", $order['order_id']);
                                $items_stmt->execute();
                                $items_result = $items_stmt->get_result();
                                $item_count = $items_result->fetch_assoc()['item_count'];
                            ?>
                            <tr>
                                <td>#<?= $order['order_number'] ?></td>
                                <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                                <td><?= $item_count ?> item(s)</td>
                                <td>
                                    <span class="hm-status hm-status-<?= $order['order_status'] ?>">
                                        <?= ucfirst($order['order_status']) ?>
                                    </span>
                                </td>
                                <td>₹<?= number_format($order['final_amount'], 2) ?></td>
                                <td>
                                    <a href="<?= $site ?>order-details/<?= $order['order_id'] ?>/" class="hm-order-link">
                                        View Details
                                    </a>
                                    <?php if($order['order_status'] == 'pending'): ?>
                                    <a href="<?= $site ?>cancel-order/<?= $order['order_id'] ?>/" 
                                       class="hm-order-link" 
                                       onclick="return confirm('Are you sure you want to cancel this order?')">
                                        Cancel
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="hm-empty-state">
                    <div class="hm-empty-icon">
                        <i class="fa fa-shopping-bag"></i>
                    </div>
                    <h3 class="hm-empty-title">No orders yet</h3>
                    <p class="hm-empty-text">You haven't placed any orders yet. Start shopping now!</p>
                    <a href="<?= $site ?>shop/" class="hm-btn hm-btn-primary">Start Shopping</a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Addresses Tab -->
            <div id="addresses" class="hm-tab-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <h2 class="hm-section-title" style="margin: 0;">My Addresses</h2>
                    <button class="hm-btn hm-btn-primary" onclick="showAddressForm()">
                        <i class="fa fa-plus"></i> Add New Address
                    </button>
                </div>
                
                <!-- Address Form (Hidden by default) -->
                <div id="addressForm" style="display: none; margin-bottom: 32px;">
                    <h3>Add New Address</h3>
                    <form method="POST" action="<?= $site ?>ajax/save-address.php">
                        <input type="hidden" name="user_id" value="<?= $user_id ?>">
                        
                        <div class="hm-form-group">
                            <label class="hm-form-label">Address Type</label>
                            <select name="address_type" class="hm-form-input" required>
                                <option value="">Select type</option>
                                <option value="shipping">Shipping Address</option>
                                <option value="billing">Billing Address</option>
                                <option value="both">Both Shipping & Billing</option>
                            </select>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="hm-form-group">
                                <label class="hm-form-label">First Name *</label>
                                <input type="text" name="first_name" class="hm-form-input" required>
                            </div>
                            <div class="hm-form-group">
                                <label class="hm-form-label">Last Name *</label>
                                <input type="text" name="last_name" class="hm-form-input" required>
                            </div>
                        </div>
                        
                        <div class="hm-form-group">
                            <label class="hm-form-label">Mobile Number *</label>
                            <input type="tel" name="mobile" class="hm-form-input" required>
                        </div>
                        
                        <div class="hm-form-group">
                            <label class="hm-form-label">Address Line 1 *</label>
                            <input type="text" name="address_line1" class="hm-form-input" required>
                        </div>
                        
                        <div class="hm-form-group">
                            <label class="hm-form-label">Address Line 2</label>
                            <input type="text" name="address_line2" class="hm-form-input">
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="hm-form-group">
                                <label class="hm-form-label">City *</label>
                                <input type="text" name="city" class="hm-form-input" required>
                            </div>
                            <div class="hm-form-group">
                                <label class="hm-form-label">State *</label>
                                <input type="text" name="state" class="hm-form-input" required>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="hm-form-group">
                                <label class="hm-form-label">Zip Code *</label>
                                <input type="text" name="zip_code" class="hm-form-input" required>
                            </div>
                            <div class="hm-form-group">
                                <label class="hm-form-label">Country</label>
                                <input type="text" name="country" class="hm-form-input" value="India" readonly>
                            </div>
                        </div>
                        
                        <div class="hm-form-group">
                            <label>
                                <input type="checkbox" name="is_default" value="1">
                                Set as default address
                            </label>
                        </div>
                        
                        <div style="display: flex; gap: 12px;">
                            <button type="submit" class="hm-btn hm-btn-primary">Save Address</button>
                            <button type="button" class="hm-btn hm-btn-secondary" onclick="hideAddressForm()">Cancel</button>
                        </div>
                    </form>
                </div>
                
                <!-- Address List -->
                <div class="hm-address-grid">
                    <?php if($addresses->num_rows > 0): 
                        $addresses->data_seek(0); // Reset pointer
                        while($address = $addresses->fetch_assoc()):
                    ?>
                    <div class="hm-address-card <?= $address['is_default'] ? 'default' : '' ?>">
                        <?php if($address['is_default']): ?>
                        <span class="hm-address-default">Default</span>
                        <?php endif; ?>
                        
                        <h4><?= htmlspecialchars($address['first_name'] . ' ' . $address['last_name']) ?></h4>
                        <p><?= htmlspecialchars($address['mobile']) ?></p>
                        <p><?= htmlspecialchars($address['address_line1']) ?></p>
                        <?php if($address['address_line2']): ?>
                        <p><?= htmlspecialchars($address['address_line2']) ?></p>
                        <?php endif; ?>
                        <p><?= htmlspecialchars($address['city']) ?>, <?= htmlspecialchars($address['state']) ?> - <?= htmlspecialchars($address['zip_code']) ?></p>
                        <p><?= htmlspecialchars($address['country']) ?></p>
                        
                        <div class="hm-address-actions">
                            <button class="hm-btn hm-btn-secondary" onclick="editAddress(<?= $address['id'] ?>)">
                                Edit
                            </button>
                            <button class="hm-btn hm-btn-secondary" 
                                    onclick="deleteAddress(<?= $address['id'] ?>)">
                                Delete
                            </button>
                            <?php if(!$address['is_default']): ?>
                            <button class="hm-btn hm-btn-secondary" 
                                    onclick="setDefaultAddress(<?= $address['id'] ?>)">
                                Set Default
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <div class="hm-empty-state">
                        <div class="hm-empty-icon">
                            <i class="fa fa-map-marker"></i>
                        </div>
                        <h3 class="hm-empty-title">No addresses saved</h3>
                        <p class="hm-empty-text">Add your first address for faster checkout</p>
                        <button class="hm-btn hm-btn-primary" onclick="showAddressForm()">
                            Add Address
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Wishlist Tab -->
            <div id="wishlist" class="hm-tab-content">
                <h2 class="hm-section-title">My Wishlist (<?= $wishlist_count ?>)</h2>
                
                <?php
                // Get wishlist items
                $wishlist_items_sql = "SELECT w.*, p.pro_name, p.pro_img, p.mrp, p.selling_price 
                                      FROM wishlist w 
                                      JOIN products p ON w.product_id = p.pro_id 
                                      WHERE w.user_id = ? 
                                      ORDER BY w.added_at DESC";
                $wishlist_items_stmt = $conn->prepare($wishlist_items_sql);
                $wishlist_items_stmt->bind_param("i", $user_id);
                $wishlist_items_stmt->execute();
                $wishlist_items = $wishlist_items_stmt->get_result();
                ?>
                
                <?php if($wishlist_items->num_rows > 0): ?>
                <div class="row">
                    <?php while($item = $wishlist_items->fetch_assoc()): 
                        $final_price = $item['selling_price'] > 0 ? $item['selling_price'] : $item['price'];
                    ?>
                    <div class="col-md-4 col-sm-6">
                        <div class="hm-address-card">
                            <img src="<?= $site ?>admin/assets/img/uploads/<?= $item['pro_img'] ?>" 
                                 alt="<?= htmlspecialchars($item['pro_name']) ?>" 
                                 style="width: 100%; height: 200px; object-fit: cover; margin-bottom: 16px;">
                            
                            <h4><?= htmlspecialchars($item['pro_title']) ?></h4>
                            <p style="font-weight: 500; color: var(--hm-red); margin: 8px 0;">
                                ₹<?= number_format($final_price, 2) ?>
                                <?php if($item['sale_price'] > 0): ?>
                                <span style="text-decoration: line-through; color: var(--hm-gray); margin-left: 8px;">
                                    ₹<?= number_format($item['price'], 2) ?>
                                </span>
                                <?php endif; ?>
                            </p>
                            
                            <div class="hm-address-actions">
                                <a href="<?= $site ?>product-details/<?= $item['product_id'] ?>/" 
                                   class="hm-btn hm-btn-primary">
                                    View Product
                                </a>
                                <button class="hm-btn hm-btn-secondary" 
                                        onclick="removeFromWishlist(<?= $item['product_id'] ?>)">
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="hm-empty-state">
                    <div class="hm-empty-icon">
                        <i class="fa fa-heart"></i>
                    </div>
                    <h3 class="hm-empty-title">Your wishlist is empty</h3>
                    <p class="hm-empty-text">Save items you love for later</p>
                    <a href="<?= $site ?>shop/" class="hm-btn hm-btn-primary">Start Shopping</a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Profile Tab -->
            <div id="profile" class="hm-tab-content">
                <h2 class="hm-section-title">My Profile</h2>
                
                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="hm-form-group">
                            <label class="hm-form-label">First Name *</label>
                            <input type="text" name="first_name" class="hm-form-input" 
                                   value="<?= htmlspecialchars($user['first_name']) ?>" required>
                        </div>
                        
                        <div class="hm-form-group">
                            <label class="hm-form-label">Last Name *</label>
                            <input type="text" name="last_name" class="hm-form-input" 
                                   value="<?= htmlspecialchars($user['last_name']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="hm-form-group">
                        <label class="hm-form-label">Email Address</label>
                        <input type="email" class="hm-form-input" 
                               value="<?= htmlspecialchars($user['email']?? 'N/A') ?>" readonly>
                        <small class="text-muted">Email cannot be changed</small>
                    </div>
                    
                    <div class="hm-form-group">
                        <label class="hm-form-label">Mobile Number</label>
                        <input type="tel" name="mobile" class="hm-form-input" 
                               value="<?= htmlspecialchars($user['mobile'] ?? '') ?>">
                    </div>
                    
                    <div class="hm-form-group">
                        <label class="hm-form-label">Account Created</label>
                        <input type="text" class="hm-form-input" 
                               value="<?= date('F d, Y', strtotime($user['created_at'] ?? 'N/A')) ?>" readonly>
                    </div>
                    
                    <div class="hm-form-group">
                        <label>
                            <!-- <input type="checkbox" name="newsletter" value="1" 
                                <?= $user['newsletter_subscribed'] ? 'checked' : '' ?>>
                            Subscribe to newsletter for exclusive offers and updates
                        </label> -->
                    </div>
                    
                    <button type="submit" class="hm-btn hm-btn-primary">
                        Update Profile
                    </button>
                </form>
            </div>

            <!-- Security Tab -->
            <div id="security" class="hm-tab-content">
                <h2 class="hm-section-title">Security</h2>
                
                <form method="POST">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="hm-form-group">
                        <label class="hm-form-label">Current Password *</label>
                        <div class="password-toggle">
                            <input type="password" name="current_password" id="currentPassword" 
                                   class="hm-form-input" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('currentPassword')">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="hm-form-group">
                        <label class="hm-form-label">New Password *</label>
                        <div class="password-toggle">
                            <input type="password" name="new_password" id="newPassword" 
                                   class="hm-form-input" required minlength="6">
                            <button type="button" class="toggle-password" onclick="togglePassword('newPassword')">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    
                    <div class="hm-form-group">
                        <label class="hm-form-label">Confirm New Password *</label>
                        <div class="password-toggle">
                            <input type="password" name="confirm_password" id="confirmPassword" 
                                   class="hm-form-input" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('confirmPassword')">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                        <div id="passwordMatch" style="font-size: 12px; margin-top: 5px;"></div>
                    </div>
                    
                    <button type="submit" class="hm-btn hm-btn-primary">
                        Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
    
   <!--footer area start-->
    <?php include_once "includes/footer.php"; ?>
    <?php include_once "includes/footer-link.php"; ?>

    <!-- JavaScript -->
    <!-- <script src="<?= $site ?>assets/js/vendor/jquery-3.5.1.min.js"></script> -->
    <script>
    // Tab switching
    document.querySelectorAll('.hm-nav-item').forEach(item => {
        item.addEventListener('click', function(e) {
            if(this.href.includes('#') && !this.href.includes('logout')) {
                e.preventDefault();
                
                // Remove active class from all tabs
                document.querySelectorAll('.hm-nav-item').forEach(nav => nav.classList.remove('active'));
                document.querySelectorAll('.hm-tab-content').forEach(content => content.classList.remove('active'));
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Show corresponding content
                const tabId = this.getAttribute('href').substring(1);
                document.getElementById(tabId).classList.add('active');
                
                // Update URL
                window.history.pushState(null, null, '#' + tabId);
            }
        });
    });
    
    // Switch tab from quick actions
    document.querySelectorAll('.switch-tab').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const tabId = this.getAttribute('href').substring(1);
            
            // Update navigation
            document.querySelectorAll('.hm-nav-item').forEach(nav => nav.classList.remove('active'));
            document.querySelectorAll('.hm-tab-content').forEach(content => content.classList.remove('active'));
            
            // Activate target tab
            document.querySelector(`.hm-nav-item[href="#${tabId}"]`).classList.add('active');
            document.getElementById(tabId).classList.add('active');
            
            // Update URL
            window.history.pushState(null, null, '#' + tabId);
        });
    });
    
    // Check URL hash on load
    const hash = window.location.hash.substring(1);
    if(hash && document.getElementById(hash)) {
        document.querySelectorAll('.hm-nav-item').forEach(nav => nav.classList.remove('active'));
        document.querySelectorAll('.hm-tab-content').forEach(content => content.classList.remove('active'));
        
        document.querySelector(`.hm-nav-item[href="#${hash}"]`).classList.add('active');
        document.getElementById(hash).classList.add('active');
    }
    
    // Toggle password visibility
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = event.currentTarget.querySelector('i');
        
        if(field.type === 'password') {
            field.type = 'text';
            icon.className = 'fa fa-eye-slash';
        } else {
            field.type = 'password';
            icon.className = 'fa fa-eye';
        }
    }
    
    // Check password match
    document.getElementById('newPassword')?.addEventListener('input', checkPasswordMatch);
    document.getElementById('confirmPassword')?.addEventListener('input', checkPasswordMatch);
    
    function checkPasswordMatch() {
        const newPassword = document.getElementById('newPassword')?.value;
        const confirmPassword = document.getElementById('confirmPassword')?.value;
        const matchElement = document.getElementById('passwordMatch');
        
        if(!matchElement) return;
        
        if(confirmPassword.length > 0) {
            if(newPassword === confirmPassword) {
                matchElement.textContent = '✓ Passwords match';
                matchElement.style.color = '#28a745';
            } else {
                matchElement.textContent = '✗ Passwords do not match';
                matchElement.style.color = '#dc3545';
            }
        } else {
            matchElement.textContent = '';
        }
    }
    
    // Address form
    function showAddressForm() {
        document.getElementById('addressForm').style.display = 'block';
        window.scrollTo({top: document.getElementById('addressForm').offsetTop - 100, behavior: 'smooth'});
    }
    
    function hideAddressForm() {
        document.getElementById('addressForm').style.display = 'none';
    }
    
    // Address actions
    function editAddress(addressId) {
        alert('Edit address ' + addressId + ' - This would open an edit form');
        // Implement AJAX edit functionality
    }
    
    function deleteAddress(addressId) {
        if(confirm('Are you sure you want to delete this address?')) {
            // AJAX delete request
            fetch('<?= $site ?>ajax/delete-address.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({address_id: addressId})
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    location.reload();
                } else {
                    alert('Failed to delete address: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to delete address');
            });
        }
    }
    
    function setDefaultAddress(addressId) {
        fetch('<?= $site ?>ajax/set-default-address.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({address_id: addressId})
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                location.reload();
            } else {
                alert('Failed to set default address: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to set default address');
        });
    }
    
    // Wishlist actions
    function removeFromWishlist(productId) {
        if(confirm('Remove from wishlist?')) {
            fetch('<?= $site ?>ajax/remove-wishlist.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({product_id: productId})
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    location.reload();
                } else {
                    alert('Failed to remove from wishlist: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to remove from wishlist');
            });
        }
    }
    
    // Form validation
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if(!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#dc3545';
                } else {
                    field.style.borderColor = '';
                }
            });
            
            if(!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });
    </script>
</body>
</html>