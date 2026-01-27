<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require '../../vendor/autoload.php'; // PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../db-conn.php'; // Database connection

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Check if email exists in admin_user table
    $stmt = $conn->prepare("SELECT * FROM admin_user WHERE email = ? AND status = 'active'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();


    if ($user) {
        // Generate 6-digit OTP
        $otp = rand(100000, 999999);

        // Store OTP in session for verification
        $_SESSION['reset_email'] = $email;
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_expiry'] = time() + 300; // 5 minutes

        // Send OTP email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.hostinger.com'; // your SMTP host
            $mail->SMTPAuth = true;
            $mail->Username = 'no-reply@zebulli.com'; // your email
            $mail->Password = 'e[3K0HD~qOe2'; // your app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('no-reply@zebulli.com', 'Zebulli Wellness Admin');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Your Admin Panel OTP Code';
            $mail->Body = "<p>Dear Admin,</p>
                          <p>Your OTP for password reset is: <b>$otp</b></p>
                          <p>This OTP is valid for 5 minutes.</p>
                          <p>Best Regards,<br>Web2tech Solutions Team</p>";

            $mail->send();
            header("Location: verify-otp.php");
            exit;
        } catch (Exception $e) {
            $message = "Error sending OTP email: {$mail->ErrorInfo}";
        }
    } else {
        $message = "No active admin account found with that email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password - Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      background: linear-gradient(135deg, #d8c3a5, #eae7dc);
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      font-family: "Poppins", sans-serif;
    }
    .card {
      background: #fffaf3;
      padding: 35px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
      width: 380px;
    }
    .btn-brown {
      background-color: #b07b52;
      color: #fff;
      border: none;
    }
    .btn-brown:hover {
      background-color: #9c6942;
    }
  </style>
</head>
<body>
  <div class="card">
    <h4 class="text-center mb-4">Forgot Password</h4>

    <?php if ($message): ?>
      <div class="alert alert-danger text-center"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="mb-3">
        <label class="form-label">Enter your registered email</label>
        <input type="email" class="form-control" name="email" placeholder="Enter your email..." required>
      </div>
      <button type="submit" class="btn btn-brown w-100">Send OTP</button>
    </form>

    <div class="text-center mt-3">
      <a href="login.php" class="text-decoration-none" style="color:#b07b52;">Back to Login</a>
    </div>
  </div>
</body>
</html>
