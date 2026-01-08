<?php
session_start();
require_once __DIR__ . '/util/visitor_tracker.php';
include_once "config/connect.php";
include_once "util/function.php";

$contact = contact_us();

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: " . $site . "my-account/");
    exit();
}

// Handle login form submission
$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? 1 : 0;
    
    if(empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Check if user exists
        $sql = "SELECT * FROM users WHERE email = ? AND status = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if(password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_type'] = $user['user_type'];

                if (!empty($_COOKIE['visitor_id'])) {
                    $tracker = new VisitorTracker($conn);
                    $tracker->linkVisitorToUser($user['id']);
                }
                
                // Set remember me cookie if selected
                if($remember == 1) {
                    $token = bin2hex(random_bytes(32));
                    $expiry = time() + (86400 * 30); // 30 days
                    setcookie('remember_token', $token, $expiry, '/');
                    
                    // Store token in database
                    $update_sql = "UPDATE users SET remember_token = ?, token_expiry = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("si", $token, $user['id']);
                    $update_stmt->execute();
                }
                
                // Record login activity
                $ip = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                $activity_sql = "INSERT INTO user_activities (user_id, activity_type, ip_address, user_agent) VALUES (?, 'login', ?, ?)";
                $activity_stmt = $conn->prepare($activity_sql);
                $activity_stmt->bind_param("iss", $user['id'], $ip, $user_agent);
                $activity_stmt->execute();
                
                // Merge guest cart if exists (if you have cart functionality)
                if(isset($_SESSION['guest_cart'])) {
                    // Load cart manager class if exists
                    if(file_exists("util/cart_manager.php")) {
                        include_once "util/cart_manager.php";
                        $cartManager = new CartManager($conn);
                        $cartManager->migrateGuestCartToUser($user['id']);
                    }
                }
                
                // Check for redirect URL
                $redirect_url = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : $site . "my-account/";
                unset($_SESSION['redirect_url']);
                
                // Redirect based on user type
                if($user['user_type'] == 'admin') {
                    header("Location: " . $site . "admin/");
                } else {
                    header("Location: " . $redirect_url);
                }
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}

// Remember me auto-login
if(!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $sql = "SELECT * FROM users WHERE remember_token = ? AND token_expiry > NOW() AND status = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_type'] = $user['user_type'];
        
        header("Location: " . $site . "my-account/");
        exit();
    }
}

// Get cart count for header
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Login | Beastline</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Favicon -->
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
        .login-message {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle input {
            padding-right: 40px;
        }
        
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
        }
        
        .remember-me input {
            margin-top: 5px;
            margin-right: 3px;
            height: 15px;
        }
        
        .guest-option {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .social-login {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .social-login h4 {
            text-align: center;
            margin-bottom: 15px;
            font-size: 16px;
            color: #666;
        }
        
       
    </style>
</head>

<body>

    <!--header area start-->
    <?php include_once "includes/header.php" ?>
    <!--header area end-->

    <!--breadcrumbs area start-->
    <div class="breadcrumbs_area">
        <div class="container">   
            <div class="row">
                <div class="col-12">
                    <div class="breadcrumb_content">
                        <h3>Customer Login</h3>
                        <ul>
                            <li><a href="<?= $site ?>">home</a></li>
                            <li>Login</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>         
    </div>
    <!--breadcrumbs area end-->
    
    <!-- customer login start -->
    <div class="customer_login">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    
                    <?php if($error): ?>
                    <div class="login-message alert-danger">
                        <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($success): ?>
                    <div class="login-message alert-success">
                        <i class="fa fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="account_form">
                        <h2>Customer Login</h2>
                        <form action="" method="POST" id="loginForm">
                            <input type="hidden" name="login" value="1">
                            
                            <div class="form-group mb-2">
                                <label>Email address <span>*</span></label>
                                <input type="email" 
                                       name="email" 
                                       class="form-control" 
                                       placeholder="Enter your email address" 
                                       required
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group mb-2">
                                <label>Password <span>*</span></label>
                                <div class="password-toggle">
                                    <input type="password" 
                                           name="password" 
                                           id="loginPassword" 
                                           class="form-control" 
                                           placeholder="Enter your password" 
                                           required>
                                    <button type="button" class="toggle-password p-1" onclick="togglePassword('loginPassword')">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="form-options">
                                <div class="remember-me">
                                    <input type="checkbox" id="remember" name="remember" value="1">
                                    <label for="remember" class="mb-0">Remember me</label>
                                </div>
                                <div class="forgot-password">
                                    <a href="<?= $site ?>forgot-password/" class="fw-bold text-primary">Forgot your password?</a>
                                </div>
                            </div>
                            
                            <div class="login_submit">
                                <button type="submit" class="btn btn-primary">Sign in to your account</button>
                            </div>
                        </form>
                        
                        <!-- Social Login Option -->
                        <div class="social-login">
                            <h4>Or sign in with</h4>
                            <div class="social-buttons d-flex align-items-center justify-content-center">
                                <a href="#" class="social-btn google btn btn-outline-danger mx-3">
                                    <i class="fa fa-google text-danger"></i> Google
                                </a>
                                <a href="#" class="social-btn facebook btn btn-outline-primary">
                                    <i class="fa fa-facebook text-primary"></i> Facebook
                                </a>
                            </div>
                        </div>
                        
                        <!-- Guest Option -->
                        <div class="guest-option">
                            <p>Don't have an account? <a href="<?= $site ?>register/"><b class="text-primary">Create one here</b></a></p>
                            <!-- <p>Want to checkout as guest? <a href="<?= $site ?>checkout/">Continue without account</a></p> -->
                        </div>
                    </div>
                    
                    <!-- Benefits Section -->
                    <div class="account_form register" style="margin-top: 40px;">
                        <h3>Benefits of creating an account</h3>
                        <ul style="list-style: none; padding-left: 0;">
                            <li style="padding: 8px 0; border-bottom: 1px solid #eee;">
                                <i class="fa fa-check text-success"></i> Faster checkout with saved details
                            </li>
                            <li style="padding: 8px 0; border-bottom: 1px solid #eee;">
                                <i class="fa fa-check text-success"></i> Track your order history
                            </li>
                            <li style="padding: 8px 0; border-bottom: 1px solid #eee;">
                                <i class="fa fa-check text-success"></i> Save items to your wishlist
                            </li>
                            <li style="padding: 8px 0; border-bottom: 1px solid #eee;">
                                <i class="fa fa-check text-success"></i> Get exclusive offers & discounts
                            </li>
                            <li style="padding: 8px 0;">
                                <i class="fa fa-check text-success"></i> Manage multiple shipping addresses
                            </li>
                        </ul>
                        <div style="text-align: center; margin-top: 20px;">
                            <a href="<?= $site ?>register/" class="button">Create New Account</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>    
    </div>
    <!-- customer login end -->
    
   <!--footer area start-->
    <?php include_once "includes/footer.php"; ?>
    <?php include_once "includes/footer-link.php"; ?>

    <!-- JavaScript -->
    <!-- <script src="<?= $site ?>assets/js/vendor/jquery-3.5.1.min.js"></script> -->
    <script>
    // Toggle password visibility
    function togglePassword(fieldId) {
        var field = document.getElementById(fieldId);
        var button = event.currentTarget;
        var icon = button.querySelector('i');
        
        if (field.type === 'password') {
            field.type = 'text';
            icon.className = 'fa fa-eye-slash';
        } else {
            field.type = 'password';
            icon.className = 'fa fa-eye';
        }
    }
    
    // Form validation
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        var email = this.querySelector('input[name="email"]').value;
        var password = this.querySelector('input[name="password"]').value;
        
        if (!email || !password) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }
        
        // Email validation
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            alert('Please enter a valid email address.');
            return false;
        }
        
        return true;
    });
    
    // Auto-focus on email field
    document.addEventListener('DOMContentLoaded', function() {
        var emailField = document.querySelector('input[name="email"]');
        if (emailField) {
            emailField.focus();
        }
    });
    
    // Social login handlers
    document.querySelectorAll('.social-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var provider = this.classList.contains('google') ? 'Google' : 'Facebook';
            alert(provider + ' login integration would be implemented here.');
        });
    });
    </script>
</body>
</html>