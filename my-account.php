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

$user_id = $_SESSION['user_id'];

// Get user details
$user_sql = "SELECT id, first_name, last_name, email, mobile, created_at FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Get orders count
$orders_sql = "SELECT COUNT(*) as total_orders FROM orders WHERE user_id = ?";
$orders_stmt = $conn->prepare($orders_sql);
$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();
$order_stats = $orders_result->fetch_assoc();

// Get recent orders
$recent_orders_sql = "SELECT order_id, order_number, final_amount, order_status, created_at 
                      FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$recent_orders_stmt = $conn->prepare($recent_orders_sql);
$recent_orders_stmt->bind_param("i", $user_id);
$recent_orders_stmt->execute();
$recent_orders = $recent_orders_stmt->get_result();

// Handle profile update
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['update_profile'])) {
        $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
        $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
        $mobile = mysqli_real_escape_string($conn, $_POST['mobile'] ?? '');
        
        $update_sql = "UPDATE users SET first_name = ?, last_name = ?, mobile = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sssi", $first_name, $last_name, $mobile, $user_id);
        
        if($update_stmt->execute()) {
            $success = "Profile updated successfully!";
            $user['first_name'] = $first_name;
            $user['last_name'] = $last_name;
            $user['mobile'] = $mobile;
        }
    }
    
    if(isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Get current password
        $password_sql = "SELECT password FROM users WHERE id = ?";
        $password_stmt = $conn->prepare($password_sql);
        $password_stmt->bind_param("i", $user_id);
        $password_stmt->execute();
        $password_result = $password_stmt->get_result();
        $db_password = $password_result->fetch_assoc()['password'];
        
        if(password_verify($current_password, $db_password)) {
            if($new_password === $confirm_password) {
                if(strlen($new_password) >= 6) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_pass_sql = "UPDATE users SET password = ? WHERE id = ?";
                    $update_pass_stmt = $conn->prepare($update_pass_sql);
                    $update_pass_stmt->bind_param("si", $hashed_password, $user_id);
                    
                    if($update_pass_stmt->execute()) {
                        $success = "Password changed successfully!";
                    }
                } else {
                    $error = "Password must be at least 6 characters.";
                }
            } else {
                $error = "Passwords do not match.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }
}

// Track order API function
function trackOrder($awb_number) {
    $api_url = "https://prealpha.ithinklogistics.com/api_v3/order/track.json";
    $access_token = "5a7b40197cd919337501dd6e9a3aad9a";
    $secret_key = "2b54c373427be180d1899400eeb21aab";
    
    $data = [
        'access_token' => $access_token,
        'secret_key' => $secret_key,
        'awb' => $awb_number
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// Handle order tracking
$tracking_info = null;
if(isset($_GET['track']) && !empty($_GET['awb'])) {
    $tracking_info = trackOrder($_GET['awb']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account | <?= htmlspecialchars($user['first_name']) ?></title>
     <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="<?= $site ?>assets/img/favicon/favicon.ico">

    <!-- CSS -->
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
    <style>
        :root {
            --primary: #dc3545;
            --primary-dark: #c82333;
            --light: #f8f9fa;
            --dark: #343a40;
            --border: #dee2e6;
        }
        
        body {
            background: #f5f5f5;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        .account-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 15px;
        }
        
        .account-sidebar {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 0;
            overflow: hidden;
        }
        
        .user-info {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: var(--primary);
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .nav-item {
            border-bottom: 1px solid var(--border);
        }
        
        .nav-item:last-child {
            border-bottom: none;
        }
        
        .nav-link {
            padding: 15px 20px;
            color: var(--dark);
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .nav-link:hover, .nav-link.active {
            background: var(--light);
            color: var(--primary);
            border-left-color: var(--primary);
        }
        
        .nav-link i {
            width: 24px;
            margin-right: 10px;
            text-align: center;
        }
        
        .account-content {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            min-height: 500px;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light);
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--light);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid var(--primary);
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .order-card {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .order-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .order-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-shipped { background: #d1ecf1; color: #0c5460; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .btn-custom {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-custom:hover {
            background: var(--primary-dark);
            color: white;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        .tracking-form {
            background: var(--light);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .tracking-info {
            background: white;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
            margin-top: 20px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--primary);
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
            padding-left: 20px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -10px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary);
            border: 2px solid white;
            box-shadow: 0 0 0 3px var(--primary);
        }
        
        @media (max-width: 768px) {
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .account-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include_once "includes/header.php" ?>
    
    <div class="account-container">
        <div class="row g-4">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="account-sidebar">
                    <div class="user-info">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <h5 class="mb-1"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h5>
                        <small><?= htmlspecialchars($user['email']) ?></small>
                    </div>
                    
                    <div class="sidebar-nav">
                        <div class="nav-item">
                            <a href="#dashboard" class="nav-link active" data-tab="dashboard">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </div>
                        <div class="nav-item">
                            <a href="#orders" class="nav-link" data-tab="orders">
                                <i class="fas fa-shopping-bag"></i> My Orders
                            </a>
                        </div>
                        <div class="nav-item">
                            <a href="#track" class="nav-link" data-tab="track">
                                <i class="fas fa-truck"></i> Track Order
                            </a>
                        </div>
                        <div class="nav-item">
                            <a href="#profile" class="nav-link" data-tab="profile">
                                <i class="fas fa-user-cog"></i> Profile
                            </a>
                        </div>
                        <div class="nav-item">
                            <a href="#security" class="nav-link" data-tab="security">
                                <i class="fas fa-lock"></i> Security
                            </a>
                        </div>
                        <div class="nav-item">
                            <a href="<?= $site ?>logout/" class="nav-link text-danger">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="account-content">
                    <?php if(isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Dashboard Tab -->
                    <div id="dashboard" class="tab-pane active">
                        <h3 class="section-title">Dashboard</h3>
                        
                        <div class="stats-cards">
                            <div class="stat-card">
                                <div class="stat-number"><?= $order_stats['total_orders'] ?? 0 ?></div>
                                <div class="stat-label">Total Orders</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number">
                                    <?php 
                                    $member_since = !empty($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : 'N/A';
                                    echo $member_since;
                                    ?>
                                </div>
                                <div class="stat-label">Member Since</div>
                            </div>
                        </div>
                        
                        <h5 class="mb-3">Recent Orders</h5>
                        <?php if($recent_orders->num_rows > 0): ?>
                            <?php while($order = $recent_orders->fetch_assoc()): ?>
                            <div class="order-card">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <strong>#<?= $order['order_number'] ?></strong>
                                    </div>
                                    <div class="col-md-3">
                                        <?= date('d M Y', strtotime($order['created_at'])) ?>
                                    </div>
                                    <div class="col-md-3">
                                        <span class="order-status status-<?= $order['order_status'] ?>">
                                            <?= ucfirst($order['order_status']) ?>
                                        </span>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <strong>₹<?= number_format($order['final_amount'], 2) ?></strong>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                            <div class="text-center mt-3">
                                <a href="#orders" class="btn btn-outline-primary btn-sm switch-tab">View All Orders</a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                                <p>No orders yet</p>
                                <a href="<?= $site ?>shop/" class="btn btn-custom">Start Shopping</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Orders Tab -->
                    <div id="orders" class="tab-pane" style="display: none;">
                        <h3 class="section-title">My Orders</h3>
                        
                        <?php
                        // Get all orders
                        $all_orders_sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
                        $all_orders_stmt = $conn->prepare($all_orders_sql);
                        $all_orders_stmt->bind_param("i", $user_id);
                        $all_orders_stmt->execute();
                        $all_orders = $all_orders_stmt->get_result();
                        ?>
                        
                        <?php if($all_orders->num_rows > 0): ?>
                            <?php while($order = $all_orders->fetch_assoc()): ?>
                            <div class="order-card">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <strong>#<?= $order['order_number'] ?></strong>
                                    </div>
                                    <div class="col-md-2">
                                        <?= date('d M Y', strtotime($order['created_at'])) ?>
                                    </div>
                                    <div class="col-md-2">
                                        <span class="order-status status-<?= $order['order_status'] ?>">
                                            <?= ucfirst($order['order_status']) ?>
                                        </span>
                                    </div>
                                    <div class="col-md-3">
                                        <?php
                                        // Get tracking info if available
                                        if(!empty($order['tracking_number'])): ?>
                                        <small class="text-muted">Tracking: <?= $order['tracking_number'] ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <strong>₹<?= number_format($order['final_amount'], 2) ?></strong>
                                        <a href="<?= $site ?>order-details/<?= $order['order_id'] ?>/" 
                                           class="btn btn-sm btn-outline-primary ms-2">
                                            View
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                                <p>No orders yet</p>
                                <a href="<?= $site ?>shop/" class="btn btn-custom">Start Shopping</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Track Order Tab -->
                    <div id="track" class="tab-pane" style="display: none;">
                        <h3 class="section-title">Track Order</h3>
                        
                        <div class="tracking-form">
                            <form method="GET" action="">
                                <input type="hidden" name="track" value="1">
                                <div class="row g-3">
                                    <div class="col-md-9">
                                        <input type="text" 
                                               name="awb" 
                                               class="form-control" 
                                               placeholder="Enter AWB/Tracking Number"
                                               value="<?= $_GET['awb'] ?? '' ?>"
                                               required>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-custom w-100">
                                            <i class="fas fa-search"></i> Track
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <?php if($tracking_info): ?>
                        <div class="tracking-info">
                            <?php if(isset($tracking_info['status']) && $tracking_info['status']): ?>
                                <div class="alert alert-success">
                                    <h5 class="mb-0">
                                        <i class="fas fa-check-circle"></i> Tracking Found
                                    </h5>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>AWB:</strong> <?= $tracking_info['data']['awb'] ?? 'N/A' ?></p>
                                        <p><strong>Status:</strong> <?= $tracking_info['data']['status'] ?? 'N/A' ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Origin:</strong> <?= $tracking_info['data']['origin'] ?? 'N/A' ?></p>
                                        <p><strong>Destination:</strong> <?= $tracking_info['data']['destination'] ?? 'N/A' ?></p>
                                    </div>
                                </div>
                                
                                <?php if(isset($tracking_info['data']['tracking']) && !empty($tracking_info['data']['tracking'])): ?>
                                <h6 class="mt-4 mb-3">Tracking History</h6>
                                <div class="timeline">
                                    <?php foreach($tracking_info['data']['tracking'] as $track): ?>
                                    <div class="timeline-item">
                                        <div class="card">
                                            <div class="card-body p-3">
                                                <p class="mb-1"><strong><?= $track['status'] ?? 'N/A' ?></strong></p>
                                                <small class="text-muted">
                                                    <?= $track['location'] ?? '' ?> • 
                                                    <?= $track['date_time'] ?? '' ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                
                            <?php else: ?>
                                <div class="alert alert-danger">
                                    <h5 class="mb-0">
                                        <i class="fas fa-exclamation-triangle"></i> 
                                        <?= $tracking_info['message'] ?? 'Tracking information not found' ?>
                                    </h5>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Profile Tab -->
                    <div id="profile" class="tab-pane" style="display: none;">
                        <h3 class="section-title">Profile Settings</h3>
                        
                        <form method="POST">
                            <input type="hidden" name="update_profile" value="1">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <input type="text" 
                                           name="first_name" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($user['first_name']) ?>" 
                                           required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" 
                                           name="last_name" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($user['last_name']) ?>" 
                                           required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($user['email']) ?>" 
                                           disabled>
                                    <small class="text-muted">Email cannot be changed</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Mobile Number</label>
                                    <input type="tel" 
                                           name="mobile" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($user['mobile'] ?? '') ?>">
                                </div>
                                <div class="col-12 mt-3">
                                    <button type="submit" class="btn btn-custom">Update Profile</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Security Tab -->
                    <div id="security" class="tab-pane" style="display: none;">
                        <h3 class="section-title">Change Password</h3>
                        
                        <form method="POST">
                            <input type="hidden" name="change_password" value="1">
                            
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Current Password</label>
                                    <div class="input-group">
                                        <input type="password" 
                                               name="current_password" 
                                               id="currentPassword"
                                               class="form-control" 
                                               required>
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('currentPassword')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">New Password</label>
                                    <div class="input-group">
                                        <input type="password" 
                                               name="new_password" 
                                               id="newPassword"
                                               class="form-control" 
                                               required minlength="6">
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('newPassword')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" 
                                               name="confirm_password" 
                                               id="confirmPassword"
                                               class="form-control" 
                                               required>
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirmPassword')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div id="passwordMatch" class="form-text"></div>
                                </div>
                                <div class="col-12 mt-3">
                                    <button type="submit" class="btn btn-custom">Change Password</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include_once "includes/footer.php" ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tab Switching
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                if(this.getAttribute('href').startsWith('#')) {
                    e.preventDefault();
                    
                    // Remove active class from all
                    document.querySelectorAll('.nav-link').forEach(n => n.classList.remove('active'));
                    document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');
                    
                    // Add active to clicked
                    this.classList.add('active');
                    
                    // Show corresponding tab
                    const tabId = this.getAttribute('href').substring(1);
                    document.getElementById(tabId).style.display = 'block';
                    
                    // Update URL hash
                    window.location.hash = tabId;
                }
            });
        });
        
        // Switch tab from dashboard
        document.querySelectorAll('.switch-tab').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const tabId = this.getAttribute('href').substring(1);
                
                document.querySelectorAll('.nav-link').forEach(n => n.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');
                
                document.querySelector(`.nav-link[href="#${tabId}"]`).classList.add('active');
                document.getElementById(tabId).style.display = 'block';
            });
        });
        
        // Check URL hash on load
        const hash = window.location.hash.substring(1);
        if(hash && document.getElementById(hash)) {
            document.querySelectorAll('.nav-link').forEach(n => n.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');
            
            document.querySelector(`.nav-link[href="#${hash}"]`).classList.add('active');
            document.getElementById(hash).style.display = 'block';
        }
        
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = event.currentTarget.querySelector('i');
            
            if(field.type === 'password') {
                field.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                field.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
        
        // Check password match
        document.getElementById('newPassword')?.addEventListener('input', checkPasswordMatch);
        document.getElementById('confirmPassword')?.addEventListener('input', checkPasswordMatch);
        
        function checkPasswordMatch() {
            const newPass = document.getElementById('newPassword')?.value;
            const confirmPass = document.getElementById('confirmPassword')?.value;
            const matchElement = document.getElementById('passwordMatch');
            
            if(!matchElement) return;
            
            if(confirmPass.length > 0) {
                if(newPass === confirmPass) {
                    matchElement.textContent = '✓ Passwords match';
                    matchElement.style.color = 'green';
                } else {
                    matchElement.textContent = '✗ Passwords do not match';
                    matchElement.style.color = 'red';
                }
            }
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>