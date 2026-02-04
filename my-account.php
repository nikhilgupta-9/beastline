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
$recent_orders_sql = "SELECT order_id, order_number, final_amount, order_status, created_at, tracking_number 
                      FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$recent_orders_stmt = $conn->prepare($recent_orders_sql);
$recent_orders_stmt->bind_param("i", $user_id);
$recent_orders_stmt->execute();
$recent_orders = $recent_orders_stmt->get_result();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
        $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
        $mobile = mysqli_real_escape_string($conn, $_POST['mobile'] ?? '');

        $update_sql = "UPDATE users SET first_name = ?, last_name = ?, mobile = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sssi", $first_name, $last_name, $mobile, $user_id);

        if ($update_stmt->execute()) {
            $success = "Profile updated successfully!";
            $user['first_name'] = $first_name;
            $user['last_name'] = $last_name;
            $user['mobile'] = $mobile;
        }
    }

    if (isset($_POST['change_password'])) {
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

        if (password_verify($current_password, $db_password)) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_pass_sql = "UPDATE users SET password = ? WHERE id = ?";
                    $update_pass_stmt = $conn->prepare($update_pass_sql);
                    $update_pass_stmt->bind_param("si", $hashed_password, $user_id);

                    if ($update_pass_stmt->execute()) {
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account | <?= htmlspecialchars($user['first_name']) ?></title>
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
    
    <style>
        :root {
            --primary-color: #000000;
            --primary-dark: #332e2e;
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

        .account-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 15px;
        }

        .account-header {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        @media (min-width: 768px) {
            .account-header {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
        }

        .account-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
        }

        .theme-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .theme-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }

        .theme-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .theme-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--border-color);
            transition: .4s;
            border-radius: 34px;
        }

        .theme-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .theme-slider {
            background-color: var(--primary-color);
        }

        input:checked + .theme-slider:before {
            transform: translateX(24px);
        }

        .account-wrapper {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        @media (min-width: 992px) {
            .account-wrapper {
                flex-direction: row;
                gap: 30px;
            }
        }

        .account-sidebar {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            flex-shrink: 0;
        }

        @media (min-width: 992px) {
            .account-sidebar {
                width: 280px;
                position: sticky;
                top: 100px;
                height: fit-content;
            }
        }

        .user-profile-card {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            padding: 20px;
            text-align: center;
            color: white;
        }

        @media (min-width: 768px) {
            .user-profile-card {
                padding: 30px 20px;
            }
        }

        .user-avatar {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }

        @media (min-width: 768px) {
            .user-avatar {
                width: 90px;
                height: 90px;
                font-size: 36px;
            }
        }

        .user-name {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        @media (min-width: 768px) {
            .user-name {
                font-size: 18px;
            }
        }

        .user-email {
            font-size: 12px;
            opacity: 0.9;
            word-break: break-all;
        }

        @media (min-width: 768px) {
            .user-email {
                font-size: 14px;
            }
        }

        .account-menu {
            padding: 15px 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: var(--dark-color);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .nav-link:hover,
        .nav-link.active {
            background-color: var(--light-color);
            color: var(--primary-color);
            border-left-color: var(--primary-color);
        }

        .nav-link i {
            width: 20px;
            font-size: 16px;
            text-align: center;
        }

        .nav-link.logout {
            color: var(--danger-color);
        }

        .account-content {
            flex: 1;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            min-height: 500px;
        }

        @media (min-width: 768px) {
            .account-content {
                padding: 30px;
            }
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
            color: var(--dark-color);
        }

        @media (min-width: 768px) {
            .section-title {
                font-size: 24px;
                margin-bottom: 25px;
            }
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        @media (min-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
        }

        .stat-card {
            background: var(--white);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            border: 1px solid var(--border-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            color: white;
            font-size: 16px;
        }

        @media (min-width: 768px) {
            .stat-icon {
                width: 50px;
                height: 50px;
                font-size: 20px;
                margin-bottom: 15px;
            }
        }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            line-height: 1;
            margin-bottom: 5px;
        }

        @media (min-width: 768px) {
            .stat-number {
                font-size: 32px;
            }
        }

        .stat-label {
            font-size: 12px;
            color: var(--secondary-color);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        @media (min-width: 768px) {
            .stat-label {
                font-size: 14px;
            }
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .orders-table {
            width: 100%;
            min-width: 600px;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .orders-table th {
            background-color: var(--light-color);
            color: var(--dark-color);
            font-weight: 600;
            padding: 12px 10px;
            text-align: left;
            border-bottom: 2px solid var(--border-color);
            font-size: 14px;
        }

        @media (min-width: 768px) {
            .orders-table th {
                padding: 12px 15px;
                font-size: 16px;
            }
        }

        .orders-table td {
            padding: 12px 10px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
            font-size: 14px;
        }

        @media (min-width: 768px) {
            .orders-table td {
                padding: 15px;
                font-size: 16px;
            }
        }

        .orders-table tr:hover {
            background-color: rgba(0,0,0,0.02);
        }

        .order-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        @media (min-width: 768px) {
            .order-badge {
                padding: 5px 12px;
                font-size: 12px;
            }
        }

        .badge-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-processing {
            background-color: #cce5ff;
            color: #004085;
        }

        .badge-shipped {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .badge-delivered {
            background-color: #d4edda;
            color: #155724;
        }

        .badge-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        @media (min-width: 768px) {
            .btn {
                padding: 10px 20px;
                font-size: 16px;
                gap: 8px;
            }
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        @media (min-width: 768px) {
            .btn-sm {
                padding: 6px 12px;
                font-size: 14px;
            }
        }

        .form-group {
            margin-bottom: 15px;
        }

        @media (min-width: 768px) {
            .form-group {
                margin-bottom: 20px;
            }
        }

        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: var(--dark-color);
            font-size: 14px;
        }

        @media (min-width: 768px) {
            .form-label {
                font-size: 16px;
                margin-bottom: 8px;
            }
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            background-color: var(--white);
            color: var(--dark-color);
        }

        @media (min-width: 768px) {
            .form-control {
                padding: 10px 15px;
                font-size: 16px;
            }
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
        }

        .input-group {
            display: flex;
            position: relative;
        }

        .input-group .form-control {
            flex: 1;
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

        @media (min-width: 768px) {
            .alert {
                padding: 15px 20px;
                font-size: 16px;
            }
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

        @media (min-width: 768px) {
            .empty-state {
                padding: 40px 20px;
            }
        }

        .empty-state i {
            font-size: 40px;
            margin-bottom: 15px;
            color: var(--border-color);
        }

        @media (min-width: 768px) {
            .empty-state i {
                font-size: 48px;
            }
        }

        .empty-state h4 {
            font-size: 18px;
            margin-bottom: 10px;
            color: var(--dark-color);
        }

        @media (min-width: 768px) {
            .empty-state h4 {
                font-size: 20px;
            }
        }

        .mobile-menu-toggle {
            display: block;
            width: 100%;
            padding: 12px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        @media (min-width: 992px) {
            .mobile-menu-toggle {
                display: none;
            }
        }

        .mobile-menu-toggle i {
            transition: transform 0.3s ease;
        }

        .mobile-menu-toggle.active i {
            transform: rotate(180deg);
        }

        .account-sidebar-mobile {
            display: none;
        }

        .account-sidebar-mobile.show {
            display: block;
        }

        @media (min-width: 992px) {
            .account-sidebar-mobile {
                display: none;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <?php include_once "includes/header.php" ?>
<!--breadcrumbs area start-->
    <div class="breadcrumbs_area">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="breadcrumb_content">
                        <h3>My Account</h3>
                        <ul>
                            <li><a href="<?= $site ?>">home</a></li>
                            <li>My Account</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--breadcrumbs area end-->
    <div class="account-container">
        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" id="mobileMenuToggle">
            <span>Menu</span>
            <i class="fas fa-chevron-down"></i>
        </button>

        <!-- <div class="account-header">
            <h1 class="account-title">My Account</h1>
            <div class="theme-toggle">
                <span><i class="fas fa-sun"></i></span>
                <label class="theme-switch">
                    <input type="checkbox" id="themeToggle">
                    <span class="theme-slider"></span>
                </label>
                <span><i class="fas fa-moon"></i></span>
            </div>
        </div> -->

        <div class="account-wrapper">
            <!-- Mobile Sidebar -->
            <div class="account-sidebar-mobile" id="mobileSidebar">
                <div class="account-sidebar">
                    <div class="user-profile-card">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                        <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
                    </div>

                    <div class="account-menu">
                        <a href="#dashboard" class="nav-link active" data-tab="dashboard">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="#orders" class="nav-link" data-tab="orders">
                            <i class="fas fa-shopping-bag"></i>
                            <span>My Orders</span>
                        </a>
                        <a href="#addresses" class="nav-link" data-tab="addresses">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>My Addresses</span>
                        </a>
                        <a href="#profile" class="nav-link" data-tab="profile">
                            <i class="fas fa-user-cog"></i>
                            <span>Profile Settings</span>
                        </a>
                        <a href="#security" class="nav-link" data-tab="security">
                            <i class="fas fa-lock"></i>
                            <span>Security</span>
                        </a>
                        <a href="#wishlist" class="nav-link" data-tab="wishlist">
                            <i class="fas fa-heart"></i>
                            <span>Wishlist</span>
                        </a>
                        <a href="<?= $site ?>logout/" class="nav-link logout">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Desktop Sidebar -->
            <div class="account-sidebar d-none d-lg-block">
                <div class="user-profile-card">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                    <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
                </div>

                <div class="account-menu">
                    <a href="#dashboard" class="nav-link active" data-tab="dashboard">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="#orders" class="nav-link" data-tab="orders">
                        <i class="fas fa-shopping-bag"></i>
                        <span>My Orders</span>
                    </a>
                    <a href="#addresses" class="nav-link" data-tab="addresses">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>My Addresses</span>
                    </a>
                    <a href="#profile" class="nav-link" data-tab="profile">
                        <i class="fas fa-user-cog"></i>
                        <span>Profile Settings</span>
                    </a>
                    <a href="#security" class="nav-link" data-tab="security">
                        <i class="fas fa-lock"></i>
                        <span>Security</span>
                    </a>
                    <a href="#wishlist" class="nav-link" data-tab="wishlist">
                        <i class="fas fa-heart"></i>
                        <span>Wishlist</span>
                    </a>
                    <a href="<?= $site ?>logout/" class="nav-link logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="account-content">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <!-- Dashboard Tab -->
                <div id="dashboard" class="tab-pane active">
                    <h2 class="section-title">Dashboard</h2>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <div class="stat-number"><?= $order_stats['total_orders'] ?? 0 ?></div>
                            <div class="stat-label">Total Orders</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="stat-number">
                                <?= !empty($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : 'N/A' ?>
                            </div>
                            <div class="stat-label">Member Since</div>
                        </div>
                    </div>

                    <h3 class="mb-3">Recent Orders</h3>
                    <?php if ($recent_orders->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="orders-table">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Amount</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?= $order['order_number'] ?></strong></td>
                                            <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                                            <td>
                                                <span class="order-badge badge-<?= $order['order_status'] ?>">
                                                    <?= ucfirst($order['order_status']) ?>
                                                </span>
                                            </td>
                                            <td><strong>₹<?= number_format($order['final_amount'], 2) ?></strong></td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <a href="<?= $site ?>order-details/<?= $order['order_id'] ?>/" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye text-light"></i>
                                                    </a>
                                                    <?php if (!empty($order['tracking_number'])): ?>
                                                        <a href="<?= $site ?>track-order/<?= $order['order_id'] ?>/" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-truck"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="#orders" class="btn btn-primary switch-tab">
                                <i class="fas fa-list"></i> View All Orders
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-bag"></i>
                            <h4>No orders yet</h4>
                            <p>You haven't placed any orders yet.</p>
                            <a href="<?= $site ?>shop/" class="btn btn-primary">
                                <i class="fas fa-shopping-cart"></i> Start Shopping
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Orders Tab -->
                <div id="orders" class="tab-pane" style="display: none;">
                    <h2 class="section-title">My Orders</h2>
                    <?php
                    // Get all orders
                    $all_orders_sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
                    $all_orders_stmt = $conn->prepare($all_orders_sql);
                    $all_orders_stmt->bind_param("i", $user_id);
                    $all_orders_stmt->execute();
                    $all_orders = $all_orders_stmt->get_result();
                    ?>

                    <?php if ($all_orders->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="orders-table">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Amount</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($order = $all_orders->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?= $order['order_number'] ?></strong></td>
                                            <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                                            <td>
                                                <span class="order-badge badge-<?= $order['order_status'] ?>">
                                                    <?= ucfirst($order['order_status']) ?>
                                                </span>
                                            </td>
                                            <td><strong>₹<?= number_format($order['final_amount'], 2) ?></strong></td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <a href="<?= $site ?>order-details/<?= $order['order_id'] ?>/" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye text-light"></i>
                                                    </a>
                                                    <?php if (!empty($order['tracking_number'])): ?>
                                                        <a href="<?= $site ?>track-order/<?= $order['order_id'] ?>/" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-truck"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($order['order_status'] == 'pending' || $order['order_status'] == 'processing'): ?>
                                                        <button class="btn btn-sm btn-danger" 
                                                                onclick="cancelOrder(<?= $order['order_id'] ?>)">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-bag"></i>
                            <h4>No orders found</h4>
                            <p>You haven't placed any orders yet.</p>
                            <a href="<?= $site ?>shop/" class="btn btn-primary">
                                <i class="fas fa-shopping-cart"></i> Start Shopping
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Addresses Tab -->
                <div id="addresses" class="tab-pane" style="display: none;">
                    <h2 class="section-title">My Addresses</h2>
                    <div class="empty-state">
                        <i class="fas fa-map-marker-alt"></i>
                        <h4>No addresses saved</h4>
                        <p>You haven't saved any addresses yet.</p>
                        <button class="btn btn-primary" onclick="addAddress()">
                            <i class="fas fa-plus"></i> Add New Address
                        </button>
                    </div>
                </div>

                <!-- Profile Tab -->
                <div id="profile" class="tab-pane" style="display: none;">
                    <h2 class="section-title">Profile Settings</h2>
                    <form method="POST">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">First Name *</label>
                                    <input type="text" 
                                           name="first_name" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($user['first_name']) ?>" 
                                           required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Last Name *</label>
                                    <input type="text" 
                                           name="last_name" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($user['last_name']) ?>" 
                                           required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($user['email']) ?>" 
                                           disabled>
                                    <small class="text-muted d-block mt-1">Email cannot be changed</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Mobile Number</label>
                                    <input type="tel" 
                                           name="mobile" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($user['mobile'] ?? '') ?>"
                                           pattern="[0-9]{10}">
                                    <small class="text-muted d-block mt-1">Enter 10-digit mobile number</small>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Security Tab -->
                <div id="security" class="tab-pane" style="display: none;">
                    <h2 class="section-title">Change Password</h2>
                    <form method="POST">
                        <input type="hidden" name="change_password" value="1">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Current Password *</label>
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
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">New Password *</label>
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
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Confirm New Password *</label>
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
                                    <div id="passwordMatch" class="form-text mt-1"></div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Wishlist Tab -->
                <div id="wishlist" class="tab-pane" style="display: none;">
                    <h2 class="section-title">My Wishlist</h2>
                    <div class="empty-state">
                        <i class="fas fa-heart"></i>
                        <h4>Your wishlist is empty</h4>
                        <p>You haven't added any products to your wishlist yet.</p>
                        <a href="<?= $site ?>category/sale" class="btn btn-primary">
                            <i class="fas fa-store"></i> Browse Products
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include_once "includes/footer.php" ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile Menu Toggle
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mobileSidebar = document.getElementById('mobileSidebar');

        if (mobileMenuToggle && mobileSidebar) {
            mobileMenuToggle.addEventListener('click', function() {
                mobileSidebar.classList.toggle('show');
                this.classList.toggle('active');
            });

            // Close mobile menu when clicking outside
            document.addEventListener('click', function(event) {
                if (!mobileSidebar.contains(event.target) && !mobileMenuToggle.contains(event.target)) {
                    mobileSidebar.classList.remove('show');
                    mobileMenuToggle.classList.remove('active');
                }
            });
        }

        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;

        // Check for saved theme preference
        const savedTheme = localStorage.getItem('theme') || 'light';
        if (savedTheme === 'dark') {
            body.classList.add('dark-mode');
            if (themeToggle) themeToggle.checked = true;
        }

        if (themeToggle) {
            themeToggle.addEventListener('change', function() {
                if (this.checked) {
                    body.classList.add('dark-mode');
                    localStorage.setItem('theme', 'dark');
                } else {
                    body.classList.remove('dark-mode');
                    localStorage.setItem('theme', 'light');
                }
            });
        }

        // Tab Switching
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.getAttribute('href').startsWith('#')) {
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

                    // Close mobile menu on mobile
                    if (window.innerWidth < 992) {
                        mobileSidebar.classList.remove('show');
                        mobileMenuToggle.classList.remove('active');
                    }
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
        if (hash && document.getElementById(hash)) {
            document.querySelectorAll('.nav-link').forEach(n => n.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');

            const navLink = document.querySelector(`.nav-link[href="#${hash}"]`);
            if (navLink) {
                navLink.classList.add('active');
                document.getElementById(hash).style.display = 'block';
            }
        }

        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = event.currentTarget.querySelector('i');

            if (field.type === 'password') {
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

            if (!matchElement) return;

            if (confirmPass.length > 0) {
                if (newPass === confirmPass) {
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

        // Cancel order function
        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order?')) {
                fetch('<?= $site ?>ajax/cancel-order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'order_id=' + orderId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Order cancelled successfully');
                        location.reload();
                    } else {
                        alert('Failed to cancel order: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while cancelling the order');
                });
            }
        }

        // Add address function
        function addAddress() {
            window.location.href = '<?= $site ?>add-address/';
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.remove();
                }, 500);
            });
        }, 5000);

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                mobileSidebar.classList.remove('show');
                mobileMenuToggle.classList.remove('active');
            }
        });
    </script>
    <?php include_once "includes/footer-link.php" ?>
</body>

</html>