<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require '../db-conn.php'; // Database connection

$message = '';

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot-password.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp']);

    if (time() > $_SESSION['otp_expiry']) {
        $message = "OTP expired. Please request a new one.";
    } elseif ($otp == $_SESSION['otp']) {
        $_SESSION['otp_verified'] = true;
        header("Location: reset-password.php");
        exit;
    } else {
        $message = "Invalid OTP. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Verify OTP - Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background: linear-gradient(135deg,#d8c3a5,#eae7dc); height:100vh; display:flex; justify-content:center; align-items:center;">
  <div class="card p-4" style="width:380px; background:#fffaf3; border-radius:12px;">
    <h4 class="text-center mb-4">Verify OTP</h4>
    <?php if ($message): ?>
      <div class="alert alert-danger text-center"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Enter OTP</label>
        <input type="text" class="form-control" name="otp" maxlength="6" required>
      </div>
      <button type="submit" class="btn btn-brown w-100" style="background-color:#b07b52; color:#fff;">Verify</button>
    </form>
  </div>
</body>
</html>
