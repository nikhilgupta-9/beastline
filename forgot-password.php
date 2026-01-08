<?php
session_start();
require_once __DIR__ . '/util/visitor_tracker.php';
include_once "config/connect.php";
include_once "util/function.php";
include_once "util/mail-services.php";

$emailService = new EmailService($conn, $site);

$contact = contact_us();

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: " . $site . "my-account/");
    exit();
}

// Initialize variables
$error = '';
$success = '';
$step = isset($_GET['step']) ? $_GET['step'] : 'email';
$email = '';

// Handle Email Submission (Step 1)
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_otp'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    if(empty($email)) {
        $error = "Please enter your email address.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if user exists with this email
        $sql = "SELECT id, first_name, email, status FROM users WHERE email = ? AND status = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Generate OTP (6 digits)
            $otp = rand(100000, 999999);
            
            // Set OTP expiry (10 minutes from now)
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Store OTP in session and database
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_otp'] = $otp;
            $_SESSION['reset_otp_expiry'] = $otp_expiry;
            $_SESSION['reset_user_id'] = $user['id'];
            
            // Store OTP in database for verification
            $update_sql = "UPDATE users SET reset_otp = ?, otp_expiry = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssi", $otp, $otp_expiry, $user['id']);
            $update_stmt->execute();
            
            // Record OTP activity
            $ip = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $activity_sql = "INSERT INTO user_activities (user_id, activity_type, ip_address, user_agent) VALUES (?, 'password_reset_request', ?, ?)";
            $activity_stmt = $conn->prepare($activity_sql);
            $activity_stmt->bind_param("iss", $user['id'], $ip, $user_agent);
            $activity_stmt->execute();
            
            // Send OTP via Email
            if(sendOTPEmail($email, $user['first_name'], $otp)) {
                // Redirect to OTP verification step
                header("Location: ?step=otp&email=" . urlencode($email));
                exit();
            } else {
                $error = "Failed to send OTP email. Please try again.";
            }
        } else {
            // For security, don't reveal if email exists or not
            $success = "If your email exists in our system, you will receive an OTP shortly.";
            
            // Still redirect to prevent email enumeration
            $_SESSION['reset_email'] = $email; // Store in session but don't validate
            header("Location: ?step=otp&email=" . urlencode($email));
            exit();
        }
    }
}

// Handle OTP Verification (Step 2)
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_otp'])) {
    $otp = $_POST['otp'];
    $email = isset($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : '';
    
    if(empty($otp) || strlen($otp) != 6) {
        $error = "Please enter a valid 6-digit OTP.";
    } else {
        // Verify OTP from database
        $sql = "SELECT id, reset_otp, otp_expiry FROM users WHERE email = ? AND reset_otp = ? AND otp_expiry > NOW() AND status = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $otp);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Store verification in session
            $_SESSION['reset_verified'] = true;
            $_SESSION['reset_user_id'] = $user['id'];
            $_SESSION['reset_email'] = $email;
            
            // Clear OTP from database after successful verification
            $clear_sql = "UPDATE users SET reset_otp = NULL, otp_expiry = NULL WHERE id = ?";
            $clear_stmt = $conn->prepare($clear_sql);
            $clear_stmt->bind_param("i", $user['id']);
            $clear_stmt->execute();
            
            // Redirect to password reset step
            header("Location: ?step=reset");
            exit();
        } else {
            $error = "Invalid OTP or OTP has expired. Please try again.";
            
            // Check if OTP expired
            $check_sql = "SELECT id FROM users WHERE email = ? AND otp_expiry < NOW()";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if($check_result->num_rows == 1) {
                $error = "OTP has expired. Please request a new one.";
            }
        }
    }
}

// Handle Password Reset (Step 3)
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Check if verification is valid
    if(!isset($_SESSION['reset_verified']) || !$_SESSION['reset_verified']) {
        $error = "Session expired. Please start the process again.";
        header("Location: ?step=email");
        exit();
    }
    
    if(empty($password) || empty($confirm_password)) {
        $error = "Please fill in both password fields.";
    } elseif(strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif(!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = "Password must contain at least one uppercase letter, one lowercase letter, and one number.";
    } else {
        $user_id = $_SESSION['reset_user_id'];
        $email = $_SESSION['reset_email'];
        
        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update password in database
        $update_sql = "UPDATE users SET password = ?, reset_otp = NULL, otp_expiry = NULL WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $hashed_password, $user_id);
        
        if($update_stmt->execute()) {
            // Record password reset activity
            $ip = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $activity_sql = "INSERT INTO user_activities (user_id, activity_type, ip_address, user_agent) VALUES (?, 'password_reset_success', ?, ?)";
            $activity_stmt = $conn->prepare($activity_sql);
            $activity_stmt->bind_param("iss", $user_id, $ip, $user_agent);
            $activity_stmt->execute();
            
            // Send confirmation email
            sendPasswordResetConfirmation($email);
            
            // Clear reset session
            unset($_SESSION['reset_verified']);
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_otp']);
            unset($_SESSION['reset_otp_expiry']);
            
            $success = "Password reset successfully! You can now login with your new password.";
            header("Location: " . $site . "login/?success=" . urlencode($success));
            exit();
        } else {
            $error = "Failed to reset password. Please try again.";
        }
    }
}

// Handle Resend OTP
if(isset($_GET['resend']) && isset($_SESSION['reset_email'])) {
    $email = $_SESSION['reset_email'];
    
    // Generate new OTP
    $otp = rand(100000, 999999);
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Update OTP in database
    $update_sql = "UPDATE users SET reset_otp = ?, otp_expiry = ? WHERE email = ? AND status = 1";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("sss", $otp, $otp_expiry, $email);
    
    if($update_stmt->execute()) {
        // Update session
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['reset_otp_expiry'] = $otp_expiry;
        
        // Send email
        sendOTPEmail($email, '', $otp);
        
        $success = "New OTP has been sent to your email.";
    } else {
        $error = "Failed to resend OTP. Please try again.";
    }
    
    header("Location: ?step=otp&email=" . urlencode($email) . ($success ? "&success=" . urlencode($success) : ""));
    exit();
}

// Function to send OTP email

$success = $emailService->sendRegistrationOTP(
    $email,
    $name,
    $otp
);

// Function to send password reset confirmation
function sendPasswordResetConfirmation($email) {
    global $site;
    
    $to = $email;
    $subject = "Password Reset Successful - Beastline";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Password Reset Successful</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
            .success-icon { text-align: center; font-size: 60px; color: #28a745; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Password Reset Successful</h2>
            </div>
            <div class='content'>
                <div class='success-icon'>✓</div>
                <h3>Your password has been reset successfully!</h3>
                <p>You can now login to your Beastline account using your new password.</p>
                
                <p>If you did not perform this password reset, please contact our support team immediately at <a href='mailto:support@beastline.com'>support@beastline.com</a>.</p>
                
                <p>For security reasons, we recommend:</p>
                <ul>
                    <li>Using a strong, unique password</li>
                    <li>Not sharing your password with anyone</li>
                    <li>Enabling two-factor authentication if available</li>
                </ul>
                
                <p style='text-align: center; margin-top: 30px;'>
                    <a href='" . $site . "login/' style='background: #c7a17a; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Login to Your Account</a>
                </p>
                
                <p>Best regards,<br>The Beastline Team</p>
            </div>
            <div class='footer'>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>&copy; " . date('Y') . " Beastline. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: Beastline <noreply@beastline.com>' . "\r\n";
    $headers .= 'Reply-To: support@beastline.com' . "\r\n";
    $headers .= 'X-Mailer: PHP/' . phpversion();
    
    return mail($to, $subject, $message, $headers);
}
?>
<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Forgot Password | Beastline</title>
    <meta name="description" content="Reset your Beastline account password securely with OTP verification.">
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
        .password-reset-container {
            max-width: 500px;
            margin: 0 auto;
        }
        
        .reset-step {
            display: none;
        }
        
        .reset-step.active {
            display: block;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .step {
            display: flex;
            align-items: center;
            flex-direction: column;
            position: relative;
            width: 100px;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ddd;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 10px;
            z-index: 2;
        }
        
        .step.active .step-number {
            background: #c7a17a;
            color: white;
        }
        
        .step.completed .step-number {
            background: #28a745;
            color: white;
        }
        
        .step-title {
            font-size: 14px;
            color: #666;
            text-align: center;
        }
        
        .step.active .step-title {
            color: #c7a17a;
            font-weight: bold;
        }
        
        .step.completed .step-title {
            color: #28a745;
        }
        
        .step-connector {
            position: absolute;
            top: 20px;
            left: 70px;
            width: 60px;
            height: 2px;
            background: #ddd;
            z-index: 1;
        }
        
        .step:last-child .step-connector {
            display: none;
        }
        
        .step.completed .step-connector {
            background: #28a745;
        }
        
        .otp-input {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }
        
        .otp-input input {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            border: 2px solid #ddd;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .otp-input input:focus {
            border-color: #c7a17a;
            box-shadow: 0 0 5px rgba(199, 161, 122, 0.3);
            outline: none;
        }
        
        .otp-input input.filled {
            border-color: #c7a17a;
            background: rgba(199, 161, 122, 0.1);
        }
        
        .resend-otp {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .resend-otp a {
            color: #c7a17a;
            text-decoration: none;
            font-weight: bold;
        }
        
        .resend-otp a:hover {
            text-decoration: underline;
        }
        
        .resend-otp.disabled {
            color: #999;
        }
        
        .password-strength {
            height: 5px;
            background: #ddd;
            border-radius: 3px;
            margin-top: 10px;
            overflow: hidden;
        }
        
        .password-strength-meter {
            height: 100%;
            width: 0;
            transition: width 0.3s ease, background 0.3s ease;
        }
        
        .strength-weak {
            background: #dc3545;
            width: 25%;
        }
        
        .strength-fair {
            background: #ffc107;
            width: 50%;
        }
        
        .strength-good {
            background: #28a745;
            width: 75%;
        }
        
        .strength-strong {
            background: #20c997;
            width: 100%;
        }
        
        .password-requirements {
            margin-top: 10px;
            font-size: 13px;
            color: #666;
        }
        
        .password-requirements ul {
            list-style: none;
            padding-left: 0;
        }
        
        .password-requirements li {
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }
        
        .password-requirements li i {
            margin-right: 8px;
            font-size: 12px;
        }
        
        .requirement-met {
            color: #28a745;
        }
        
        .requirement-not-met {
            color: #dc3545;
        }
        
        .back-to-login {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .countdown-timer {
            color: #c7a17a;
            font-weight: bold;
            font-size: 14px;
        }
        
        @media (max-width: 576px) {
            .step {
                width: 80px;
            }
            
            .step-connector {
                width: 40px;
                left: 60px;
            }
            
            .otp-input {
                gap: 5px;
            }
            
            .otp-input input {
                width: 40px;
                height: 50px;
                font-size: 20px;
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
                        <h3>Forgot Password</h3>
                        <ul>
                            <li><a href="<?= $site ?>">home</a></li>
                            <li><a href="<?= $site ?>user-login/">login</a></li>
                            <li>Forgot Password</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>         
    </div>
    <!--breadcrumbs area end-->
    
    <!-- password reset area start -->
    <div class="customer_login">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10">
                    <div class="password-reset-container">
                        
                        <!-- Step Indicator -->
                        <div class="step-indicator">
                            <div class="step <?= $step == 'email' ? 'active' : ($step == 'otp' || $step == 'reset' ? 'completed' : '') ?>">
                                <div class="step-number">1</div>
                                <div class="step-title">Enter Email</div>
                                <div class="step-connector"></div>
                            </div>
                            <div class="step <?= $step == 'otp' ? 'active' : ($step == 'reset' ? 'completed' : '') ?>">
                                <div class="step-number">2</div>
                                <div class="step-title">Verify OTP</div>
                                <div class="step-connector"></div>
                            </div>
                            <div class="step <?= $step == 'reset' ? 'active' : '' ?>">
                                <div class="step-number">3</div>
                                <div class="step-title">New Password</div>
                            </div>
                        </div>
                        
                        <!-- Messages -->
                        <?php if($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fa fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fa fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Step 1: Email Input -->
                        <div class="reset-step <?= $step == 'email' ? 'active' : '' ?>" id="step-email">
                            <div class="account_form">
                                <h2>Reset Your Password</h2>
                                <p>Enter your email address and we'll send you an OTP to verify your identity.</p>
                                
                                <form method="POST" action="">
                                    <div class="form-group mb-3">
                                        <label>Email address <span>*</span></label>
                                        <input type="email" 
                                               name="email" 
                                               class="form-control" 
                                               placeholder="Enter your registered email address" 
                                               required
                                               value="<?= htmlspecialchars($email) ?>">
                                    </div>
                                    
                                    <div class="login_submit">
                                        <button type="submit" name="send_otp" class="button w-100">
                                            <i class="fa fa-paper-plane me-2"></i> Send OTP
                                        </button>
                                    </div>
                                </form>
                                
                                <div class="back-to-login">
                                    <p>Remember your password? <a href="<?= $site ?>user-login/" class="fw-bold text-primary">Back to Login</a></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Step 2: OTP Verification -->
                        <div class="reset-step <?= $step == 'otp' ? 'active' : '' ?>" id="step-otp">
                            <div class="account_form">
                                <h2>Verify OTP</h2>
                                <p>We've sent a 6-digit OTP to <strong><?= htmlspecialchars($_GET['email'] ?? $email) ?></strong></p>
                                <p>Enter the OTP below to continue:</p>
                                
                                <form method="POST" action="">
                                    <input type="hidden" name="email" value="<?= htmlspecialchars($_GET['email'] ?? $email) ?>">
                                    
                                    <div class="otp-input">
                                        <input type="text" maxlength="1" pattern="\d" inputmode="numeric" autocomplete="off">
                                        <input type="text" maxlength="1" pattern="\d" inputmode="numeric" autocomplete="off">
                                        <input type="text" maxlength="1" pattern="\d" inputmode="numeric" autocomplete="off">
                                        <input type="text" maxlength="1" pattern="\d" inputmode="numeric" autocomplete="off">
                                        <input type="text" maxlength="1" pattern="\d" inputmode="numeric" autocomplete="off">
                                        <input type="text" maxlength="1" pattern="\d" inputmode="numeric" autocomplete="off">
                                    </div>
                                    <input type="hidden" name="otp" id="otp-field">
                                    
                                    <div class="resend-otp" id="resend-container">
                                        <p>Didn't receive OTP? <a href="?step=otp&email=<?= urlencode($_GET['email'] ?? $email) ?>&resend=1" id="resend-link">Resend OTP</a></p>
                                        <p class="countdown-timer" id="countdown">OTP expires in: <span id="timer">10:00</span></p>
                                    </div>
                                    
                                    <div class="login_submit mt-4">
                                        <button type="submit" name="verify_otp" class="btn btn-primary w-100" id="verify-btn" disabled>
                                            <i class="fa fa-check-circle me-2"></i> Verify OTP
                                        </button>
                                    </div>
                                </form>
                                
                                <div class="back-to-login">
                                    <p>Need to change email? <a href="?step=email">Go back</a></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Step 3: New Password -->
                        <div class="reset-step <?= $step == 'reset' ? 'active' : '' ?>" id="step-reset">
                            <div class="account_form">
                                <h2>Set New Password</h2>
                                <p>Create a strong new password for your account.</p>
                                
                                <form method="POST" action="">
                                    <div class="form-group mb-3">
                                        <label>New Password <span>*</span></label>
                                        <div class="password-toggle">
                                            <input type="password" 
                                                   name="password" 
                                                   id="newPassword" 
                                                   class="form-control" 
                                                   placeholder="Enter new password" 
                                                   required
                                                   minlength="8">
                                            <button type="button" class="toggle-password p-1" onclick="togglePassword('newPassword')">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="password-strength">
                                            <div class="password-strength-meter" id="strength-meter"></div>
                                        </div>
                                        <div class="password-requirements" id="password-requirements">
                                            <ul>
                                                <li id="req-length"><i class="fa fa-times requirement-not-met"></i> At least 8 characters</li>
                                                <li id="req-upper"><i class="fa fa-times requirement-not-met"></i> One uppercase letter</li>
                                                <li id="req-lower"><i class="fa fa-times requirement-not-met"></i> One lowercase letter</li>
                                                <li id="req-number"><i class="fa fa-times requirement-not-met"></i> One number</li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label>Confirm New Password <span>*</span></label>
                                        <div class="password-toggle">
                                            <input type="password" 
                                                   name="confirm_password" 
                                                   id="confirmPassword" 
                                                   class="form-control" 
                                                   placeholder="Confirm new password" 
                                                   required>
                                            <button type="button" class="toggle-password p-1" onclick="togglePassword('confirmPassword')">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="text-danger small mt-1" id="password-match"></div>
                                    </div>
                                    
                                    <div class="login_submit">
                                        <button type="submit" name="reset_password" class="btn btn-primary w-100" id="reset-btn" disabled>
                                            <i class="fa fa-key me-2"></i> Reset Password
                                        </button>
                                    </div>
                                </form>
                                
                                <div class="back-to-login">
                                    <p>Remember your password? <a href="<?= $site ?>user-login/" class="text-primary fw-bold">Back to Login</a></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>    
    </div>
    <!-- password reset area end -->
    
    <!--footer area start-->
    <?php include_once "includes/footer.php"; ?>
    <?php include_once "includes/footer-link.php"; ?>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // OTP Input Handling
            const otpInputs = document.querySelectorAll('.otp-input input');
            const otpField = document.getElementById('otp-field');
            const verifyBtn = document.getElementById('verify-btn');
            
            if (otpInputs.length > 0) {
                otpInputs.forEach((input, index) => {
                    // Handle input
                    input.addEventListener('input', function(e) {
                        // Only allow numbers
                        this.value = this.value.replace(/[^0-9]/g, '');
                        
                        // Move to next input if value entered
                        if (this.value.length === 1 && index < otpInputs.length - 1) {
                            otpInputs[index + 1].focus();
                        }
                        
                        // Update hidden field and check if all filled
                        updateOTPValue();
                    });
                    
                    // Handle paste
                    input.addEventListener('paste', function(e) {
                        e.preventDefault();
                        const pasteData = e.clipboardData.getData('text').replace(/[^0-9]/g, '');
                        
                        // Fill inputs with paste data
                        for (let i = 0; i < Math.min(pasteData.length, otpInputs.length); i++) {
                            otpInputs[i].value = pasteData[i];
                            otpInputs[i].classList.add('filled');
                        }
                        
                        // Focus last filled input
                        const lastFilledIndex = Math.min(pasteData.length, otpInputs.length) - 1;
                        if (lastFilledIndex < otpInputs.length - 1) {
                            otpInputs[lastFilledIndex + 1].focus();
                        }
                        
                        updateOTPValue();
                    });
                    
                    // Handle backspace
                    input.addEventListener('keydown', function(e) {
                        if (e.key === 'Backspace' && this.value === '' && index > 0) {
                            otpInputs[index - 1].focus();
                        }
                    });
                    
                    // Handle focus
                    input.addEventListener('focus', function() {
                        this.select();
                    });
                });
            }
            
            function updateOTPValue() {
                let otp = '';
                otpInputs.forEach(input => {
                    otp += input.value;
                    if (input.value) {
                        input.classList.add('filled');
                    } else {
                        input.classList.remove('filled');
                    }
                });
                
                otpField.value = otp;
                verifyBtn.disabled = otp.length !== 6;
            }
            
            // Countdown Timer
            let timerInterval;
            function startCountdown(duration) {
                let timer = duration, minutes, seconds;
                const timerElement = document.getElementById('timer');
                const resendLink = document.getElementById('resend-link');
                const resendContainer = document.getElementById('resend-container');
                
                timerInterval = setInterval(function() {
                    minutes = parseInt(timer / 60, 10);
                    seconds = parseInt(timer % 60, 10);
                    
                    minutes = minutes < 10 ? "0" + minutes : minutes;
                    seconds = seconds < 10 ? "0" + seconds : seconds;
                    
                    timerElement.textContent = minutes + ":" + seconds;
                    
                    if (--timer < 0) {
                        clearInterval(timerInterval);
                        timerElement.textContent = "00:00";
                        timerElement.style.color = "#dc3545";
                        resendContainer.classList.remove('disabled');
                        resendLink.style.pointerEvents = 'auto';
                        resendLink.style.opacity = '1';
                    } else if (timer < 60) {
                        // Last minute warning
                        timerElement.style.color = "#dc3545";
                    }
                }, 1000);
                
                // Disable resend for first 30 seconds
                setTimeout(() => {
                    resendContainer.classList.remove('disabled');
                    resendLink.style.pointerEvents = 'auto';
                    resendLink.style.opacity = '1';
                }, 30000);
            }
            
            // Start countdown on OTP step
            if (document.getElementById('step-otp').classList.contains('active')) {
                startCountdown(600); // 10 minutes
            }
            
            // Password Strength Checker
            const passwordField = document.getElementById('newPassword');
            const confirmField = document.getElementById('confirmPassword');
            const strengthMeter = document.getElementById('strength-meter');
            const resetBtn = document.getElementById('reset-btn');
            
            if (passwordField) {
                passwordField.addEventListener('input', function() {
                    const password = this.value;
                    checkPasswordStrength(password);
                    checkPasswordMatch();
                });
            }
            
            if (confirmField) {
                confirmField.addEventListener('input', checkPasswordMatch);
            }
            
            function checkPasswordStrength(password) {
                let strength = 0;
                let requirements = {
                    length: false,
                    upper: false,
                    lower: false,
                    number: false
                };
                
                // Check length
                if (password.length >= 8) {
                    strength += 25;
                    requirements.length = true;
                    document.getElementById('req-length').innerHTML = '<i class="fa fa-check requirement-met"></i> At least 8 characters';
                } else {
                    document.getElementById('req-length').innerHTML = '<i class="fa fa-times requirement-not-met"></i> At least 8 characters';
                }
                
                // Check uppercase
                if (/[A-Z]/.test(password)) {
                    strength += 25;
                    requirements.upper = true;
                    document.getElementById('req-upper').innerHTML = '<i class="fa fa-check requirement-met"></i> One uppercase letter';
                } else {
                    document.getElementById('req-upper').innerHTML = '<i class="fa fa-times requirement-not-met"></i> One uppercase letter';
                }
                
                // Check lowercase
                if (/[a-z]/.test(password)) {
                    strength += 25;
                    requirements.lower = true;
                    document.getElementById('req-lower').innerHTML = '<i class="fa fa-check requirement-met"></i> One lowercase letter';
                } else {
                    document.getElementById('req-lower').innerHTML = '<i class="fa fa-times requirement-not-met"></i> One lowercase letter';
                }
                
                // Check number
                if (/[0-9]/.test(password)) {
                    strength += 25;
                    requirements.number = true;
                    document.getElementById('req-number').innerHTML = '<i class="fa fa-check requirement-met"></i> One number';
                } else {
                    document.getElementById('req-number').innerHTML = '<i class="fa fa-times requirement-not-met"></i> One number';
                }
                
                // Update strength meter
                strengthMeter.className = 'password-strength-meter';
                
                if (strength === 0) {
                    strengthMeter.style.width = '0';
                } else if (strength <= 25) {
                    strengthMeter.classList.add('strength-weak');
                } else if (strength <= 50) {
                    strengthMeter.classList.add('strength-fair');
                } else if (strength <= 75) {
                    strengthMeter.classList.add('strength-good');
                } else {
                    strengthMeter.classList.add('strength-strong');
                }
                
                // Enable reset button if all requirements met
                if (requirements.length && requirements.upper && requirements.lower && requirements.number) {
                    resetBtn.disabled = false;
                } else {
                    resetBtn.disabled = true;
                }
            }
            
            function checkPasswordMatch() {
                const password = passwordField.value;
                const confirm = confirmField.value;
                const matchElement = document.getElementById('password-match');
                
                if (!confirm) {
                    matchElement.textContent = '';
                    return;
                }
                
                if (password === confirm) {
                    matchElement.textContent = '✓ Passwords match';
                    matchElement.className = 'text-success small mt-1';
                } else {
                    matchElement.textContent = '✗ Passwords do not match';
                    matchElement.className = 'text-danger small mt-1';
                    resetBtn.disabled = true;
                }
            }
            
            // Toggle password visibility
            window.togglePassword = function(fieldId) {
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
            };
            
            // Auto-focus first OTP input
            if (document.getElementById('step-otp').classList.contains('active')) {
                setTimeout(() => {
                    otpInputs[0].focus();
                }, 100);
            }
            
            // Auto-focus password field
            if (document.getElementById('step-reset').classList.contains('active')) {
                setTimeout(() => {
                    passwordField.focus();
                }, 100);
            }
            
            // Prevent form resubmission
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        });
    </script>
</body>
</html>