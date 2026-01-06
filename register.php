<?php
session_start();
include_once "config/connect.php";
include_once "util/function.php";

$contact = contact_us();

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: " . $site . "my-account/");
    exit();
}

// Handle registration form submission
$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $first_name = mysqli_real_escape_string($conn, trim($_POST['first_name']));
    $last_name = mysqli_real_escape_string($conn, trim($_POST['last_name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $mobile = mysqli_real_escape_string($conn, trim($_POST['mobile'] ?? ''));
    $agree_terms = isset($_POST['agree_terms']) ? 1 : 0;
    $subscribe = isset($_POST['subscribe']) ? 1 : 0;
    
    // Validation
    $errors = [];
    
    // Name validation
    if(empty($first_name) || strlen($first_name) < 2) {
        $errors[] = "First name must be at least 2 characters.";
    }
    
    if(empty($last_name) || strlen($last_name) < 2) {
        $errors[] = "Last name must be at least 2 characters.";
    }
    
    // Email validation
    if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    } else {
        // Check if email already exists
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if($check_result->num_rows > 0) {
            $errors[] = "This email is already registered. Please use a different email or <a href='" . $site . "login/'>login here</a>.";
        }
    }
    
    // Password validation
    if(empty($password) || strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    
    if($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    // Mobile validation (if provided)
    if(!empty($mobile) && !preg_match('/^[0-9]{10}$/', $mobile)) {
        $errors[] = "Please enter a valid 10-digit mobile number.";
    }
    
    if(!$agree_terms) {
        $errors[] = "You must agree to the Terms & Conditions to register.";
    }
    
    // If no errors, proceed with registration
    if(empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Generate verification token
        $verification_token = bin2hex(random_bytes(32));
        
        // Insert user into database
        $insert_sql = "INSERT INTO users (
            first_name, 
            last_name, 
            email, 
            password, 
            mobile,
            newsletter_subscribed,
            verification_token,
            status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())";
        
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param(
            "sssssis",
            $first_name,
            $last_name,
            $email,
            $hashed_password,
            $mobile,
            $subscribe,
            $verification_token
        );
        
        if($insert_stmt->execute()) {
            $user_id = $insert_stmt->insert_id;
            
            // Set session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $first_name . ' ' . $last_name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_type'] = 'customer';
            
            // Record registration activity
            $ip = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $activity_sql = "INSERT INTO user_activities (user_id, activity_type, ip_address, user_agent) VALUES (?, 'registration', ?, ?)";
            $activity_stmt = $conn->prepare($activity_sql);
            $activity_stmt->bind_param("iss", $user_id, $ip, $user_agent);
            $activity_stmt->execute();
            
            // Send welcome email (you'll need to implement email sending)
            // sendWelcomeEmail($email, $first_name, $verification_token);
            
            // Merge guest cart if exists
            if(isset($_SESSION['guest_cart'])) {
                if(file_exists("util/cart_manager.php")) {
                    include_once "util/cart_manager.php";
                    $cartManager = new CartManager($conn);
                    $cartManager->migrateGuestCartToUser($user_id);
                }
            }
            
            $success = "Registration successful! Redirecting to your account dashboard...";
            
            // Redirect after 3 seconds
            header("refresh:3;url=" . $site . "my-account/");
        } else {
            $error = "Registration failed. Please try again later.";
        }
    } else {
        $error = implode("<br>", $errors);
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
    <title>Register | Beastline</title>
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
        .register-message {
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
        
        .password-strength {
            margin-top: 5px;
            font-size: 12px;
        }
        
        .strength-weak { color: #dc3545; }
        .strength-fair { color: #ffc107; }
        .strength-good { color: #28a745; }
        .strength-strong { color: #28a745; }
        
        .terms-checkbox {
            margin: 20px 0;
        }
        
        .terms-checkbox label {
            display: flex;
            align-items: flex-start;
        }
        
        .terms-checkbox input {
            margin-right: 10px;
            margin-top: 3px;
        }
        
        .benefits-list {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 30px 0;
        }
        
        .benefits-list h4 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .benefits-list ul {
            padding-left: 20px;
            margin-bottom: 0;
        }
        
        .benefits-list li {
            margin-bottom: 10px;
            color: #555;
        }
        
        .name-fields {
            display: flex;
            gap: 15px;
        }
        
        .name-fields .form-group {
            flex: 1;
        }
        
        @media (max-width: 768px) {
            .name-fields {
                flex-direction: column;
                gap: 0;
            }
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
                        <h3>Create Account</h3>
                        <ul>
                            <li><a href="<?= $site ?>">home</a></li>
                            <li>Register</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>         
    </div>
    <!--breadcrumbs area end-->
    
    <!-- registration start -->
    <div class="customer_login">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10">
                    
                    <?php if($error): ?>
                    <div class="register-message alert-danger">
                        <i class="fa fa-exclamation-circle"></i> <?= $error ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($success): ?>
                    <div class="register-message alert-success">
                        <i class="fa fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="account_form register">
                        <h2>Create Your Account</h2>
                        <form action="" method="POST" id="registerForm">
                            <input type="hidden" name="register" value="1">
                            
                            <div class="name-fields">
                                <div class="form-group">
                                    <label>First Name <span>*</span></label>
                                    <input type="text" 
                                           name="first_name" 
                                           class="form-control" 
                                           placeholder="Enter your first name" 
                                           required
                                           value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Last Name <span>*</span></label>
                                    <input type="text" 
                                           name="last_name" 
                                           class="form-control" 
                                           placeholder="Enter your last name" 
                                           required
                                           value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Email Address <span>*</span></label>
                                <input type="email" 
                                       name="email" 
                                       class="form-control" 
                                       placeholder="Enter your email address" 
                                       required
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                                <small class="text-muted">We'll send your order confirmation here</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Mobile Number (Optional)</label>
                                <input type="tel" 
                                       name="mobile" 
                                       class="form-control" 
                                       placeholder="Enter 10-digit mobile number"
                                       value="<?= htmlspecialchars($_POST['mobile'] ?? '') ?>">
                                <small class="text-muted">For delivery updates and OTP verification</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Create Password <span>*</span></label>
                                <div class="password-toggle">
                                    <input type="password" 
                                           name="password" 
                                           id="registerPassword" 
                                           class="form-control" 
                                           placeholder="Enter at least 6 characters" 
                                           required
                                           minlength="6">
                                    <button type="button" class="toggle-password" onclick="togglePassword('registerPassword')">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                                <div id="passwordStrength" class="password-strength"></div>
                            </div>
                            
                            <div class="form-group">
                                <label>Confirm Password <span>*</span></label>
                                <div class="password-toggle">
                                    <input type="password" 
                                           name="confirm_password" 
                                           id="confirmPassword" 
                                           class="form-control" 
                                           placeholder="Re-enter your password" 
                                           required>
                                    <button type="button" class="toggle-password" onclick="togglePassword('confirmPassword')">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                                <div id="passwordMatch" class="password-strength"></div>
                            </div>
                            
                            <!-- Benefits Section -->
                            <div class="benefits-list">
                                <h4><i class="fa fa-star text-warning"></i> Benefits of Creating an Account:</h4>
                                <ul>
                                    <li><strong>Faster Checkout:</strong> Save your details for quick purchases</li>
                                    <li><strong>Order Tracking:</strong> Monitor all your orders in one place</li>
                                    <li><strong>Wishlist:</strong> Save products you love for later</li>
                                    <li><strong>Exclusive Offers:</strong> Get member-only discounts and early access to sales</li>
                                    <li><strong>Order History:</strong> View and re-order your past purchases</li>
                                    <li><strong>Multiple Addresses:</strong> Save home, work, and gift addresses</li>
                                </ul>
                            </div>
                            
                            <div class="terms-checkbox">
                                <label>
                                    <input type="checkbox" 
                                           name="agree_terms" 
                                           value="1" 
                                           required>
                                    I agree to the <a href="<?= $site ?>terms/" target="_blank">Terms & Conditions</a> 
                                    and <a href="<?= $site ?>privacy/" target="_blank">Privacy Policy</a> <span>*</span>
                                </label>
                            </div>
                            
                            <div class="terms-checkbox">
                                <label>
                                    <input type="checkbox" 
                                           name="subscribe" 
                                           value="1" 
                                           checked>
                                    Yes, I want to receive newsletters, exclusive offers, and updates from Beastline
                                </label>
                            </div>
                            
                            <div class="login_submit">
                                <button type="submit" class="btn btn-primary btn-lg btn-block">
                                    <i class="fa fa-user-plus"></i> Create My Account
                                </button>
                            </div>
                        </form>
                        
                        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                            <p>Already have an account? <a href="<?= $site ?>login/">Sign in here</a></p>
                            <p>Want to checkout as guest? <a href="<?= $site ?>checkout/">Continue without creating account</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>    
    </div>
    <!-- registration end -->
    
   <!--footer area start-->
    <?php include_once "includes/footer.php"; ?>
    <?php include_once "includes/footer-link.php"; ?>

    <!-- JavaScript -->
    <script src="<?= $site ?>assets/js/vendor/jquery-3.5.1.min.js"></script>
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
    
    // Check password strength
    document.getElementById('registerPassword').addEventListener('input', function() {
        var password = this.value;
        var strength = 0;
        var text = '';
        var className = '';
        
        if (password.length >= 6) strength++;
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
        if (password.match(/\d/)) strength++;
        if (password.match(/[^a-zA-Z\d]/)) strength++;
        
        switch(strength) {
            case 0: text = ''; break;
            case 1: text = 'Very weak'; className = 'strength-weak'; break;
            case 2: text = 'Weak'; className = 'strength-weak'; break;
            case 3: text = 'Good'; className = 'strength-good'; break;
            case 4: text = 'Strong'; className = 'strength-strong'; break;
        }
        
        var strengthElement = document.getElementById('passwordStrength');
        if (password.length > 0) {
            strengthElement.textContent = 'Password strength: ' + text;
            strengthElement.className = 'password-strength ' + className;
        } else {
            strengthElement.textContent = '';
        }
    });
    
    // Check password match
    document.getElementById('confirmPassword').addEventListener('input', function() {
        var password = document.getElementById('registerPassword').value;
        var confirmPassword = this.value;
        var matchElement = document.getElementById('passwordMatch');
        
        if (confirmPassword.length > 0) {
            if (password === confirmPassword) {
                matchElement.textContent = '✓ Passwords match';
                matchElement.className = 'password-strength strength-strong';
            } else {
                matchElement.textContent = '✗ Passwords do not match';
                matchElement.className = 'password-strength strength-weak';
            }
        } else {
            matchElement.textContent = '';
        }
    });
    
    // Mobile number validation
    document.querySelector('input[name="mobile"]').addEventListener('blur', function() {
        var mobile = this.value;
        if (mobile && !/^\d{10}$/.test(mobile)) {
            alert('Please enter a valid 10-digit mobile number.');
            this.focus();
        }
    });
    
    // Form validation
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        var firstName = this.querySelector('input[name="first_name"]').value;
        var lastName = this.querySelector('input[name="last_name"]').value;
        var email = this.querySelector('input[name="email"]').value;
        var password = this.querySelector('input[name="password"]').value;
        var confirmPassword = this.querySelector('input[name="confirm_password"]').value;
        var mobile = this.querySelector('input[name="mobile"]').value;
        var agreeTerms = this.querySelector('input[name="agree_terms"]').checked;
        
        // Basic validation
        if (!firstName || !lastName || !email || !password || !confirmPassword) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }
        
        // Name length validation
        if (firstName.length < 2 || lastName.length < 2) {
            e.preventDefault();
            alert('First and last name must be at least 2 characters.');
            return false;
        }
        
        // Email validation
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            alert('Please enter a valid email address.');
            return false;
        }
        
        // Password length
        if (password.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters.');
            return false;
        }
        
        // Password match
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match. Please try again.');
            return false;
        }
        
        // Mobile validation
        if (mobile && !/^\d{10}$/.test(mobile)) {
            e.preventDefault();
            alert('Please enter a valid 10-digit mobile number.');
            return false;
        }
        
        // Terms agreement
        if (!agreeTerms) {
            e.preventDefault();
            alert('You must agree to the Terms & Conditions to register.');
            return false;
        }
        
        return true;
    });
    
    // Auto-focus on first name field
    document.addEventListener('DOMContentLoaded', function() {
        var firstNameField = document.querySelector('input[name="first_name"]');
        if (firstNameField) {
            firstNameField.focus();
        }
    });
    </script>
</body>
</html>