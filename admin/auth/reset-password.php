<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require '../db-conn.php'; // Database connection

$message = '';

if (!isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified']) {
    header("Location: forgot-password.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPass = $_POST['new_password'];
    $confirmPass = $_POST['confirm_password'];

    if ($newPass !== $confirmPass) {
        $message = "Passwords do not match!";
    } else {
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);

        // ✅ Use MySQLi prepared statement syntax
        $stmt = $conn->prepare("UPDATE admin_user SET password = ?, password_changed_at = NOW() WHERE email = ?");
        $stmt->bind_param("ss", $hashed, $_SESSION['reset_email']);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // Cleanup session
            unset($_SESSION['otp'], $_SESSION['otp_verified'], $_SESSION['reset_email']);
            $message = "Password successfully updated! You can now <a href='login.php'>login</a>.";
        } else {
            $message = "No record updated. Please try again.";
        }

        $stmt->close();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password - Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      background: linear-gradient(135deg,#d8c3a5,#eae7dc);
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      font-family: "Poppins", sans-serif;
    }

    .card {
      width: 380px;
      background: #fffaf3;
      border-radius: 12px;
      padding: 30px 25px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }

    .strength-meter {
      height: 6px;
      border-radius: 5px;
      background: #ddd;
      margin-top: 5px;
      transition: background-color 0.3s ease, width 0.3s ease;
    }

    .strength-label {
      font-size: 14px;
      margin-top: 4px;
      font-weight: 500;
    }

    .btn-brown {
      background-color: #b07b52;
      color: #fff;
      border: none;
      transition: background 0.3s ease;
    }

    .btn-brown:hover {
      background-color: #9c6942;
    }

    .disabled-btn {
      background-color: #b07b52;
      opacity: 0.6;
      cursor: not-allowed;
    }

    .match-text {
      font-size: 13px;
      margin-top: 5px;
    }
  </style>
</head>

<body>
  <div class="card">
    <h4 class="text-center mb-4">Reset Password</h4>

    <?php if (isset($message) && $message): ?>
      <div class="alert alert-info text-center"><?= $message ?></div>
    <?php endif; ?>

    <form method="post" id="resetForm">
      <div class="mb-3">
        <label class="form-label">New Password</label>
        <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Enter New Password..." required>
        
        <!-- Strength Meter -->
        <div class="strength-meter" id="strength-bar"></div>
        <div class="strength-label" id="strength-text"></div>
      </div>

      <div class="mb-3">
        <label class="form-label">Confirm Password</label>
        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm New Password..." required>
        <div class="match-text" id="match-text"></div>
      </div>

      <button type="submit" id="submitBtn" class="btn btn-brown w-100 disabled-btn" disabled>
        Update Password
      </button>
    </form>
  </div>

  <script>
    const newPass = document.getElementById("new_password");
    const confirmPass = document.getElementById("confirm_password");
    const strengthBar = document.getElementById("strength-bar");
    const strengthText = document.getElementById("strength-text");
    const matchText = document.getElementById("match-text");
    const submitBtn = document.getElementById("submitBtn");

    // Function to check password strength
    function checkStrength(password) {
      let strength = 0;
      if (password.length >= 8) strength++;
      if (password.match(/[A-Z]/)) strength++;
      if (password.match(/[0-9]/)) strength++;
      if (password.match(/[^A-Za-z0-9]/)) strength++;

      // Update strength bar
      switch (strength) {
        case 0:
          strengthBar.style.width = "0%";
          strengthBar.style.background = "#ddd";
          strengthText.textContent = "";
          break;
        case 1:
          strengthBar.style.width = "25%";
          strengthBar.style.background = "#dc3545";
          strengthText.textContent = "Weak";
          strengthText.style.color = "#dc3545";
          break;
        case 2:
          strengthBar.style.width = "50%";
          strengthBar.style.background = "#ffc107";
          strengthText.textContent = "Medium";
          strengthText.style.color = "#ffc107";
          break;
        case 3:
          strengthBar.style.width = "75%";
          strengthBar.style.background = "#17a2b8";
          strengthText.textContent = "Good";
          strengthText.style.color = "#17a2b8";
          break;
        case 4:
          strengthBar.style.width = "100%";
          strengthBar.style.background = "#28a745";
          strengthText.textContent = "Strong";
          strengthText.style.color = "#28a745";
          break;
      }

      return strength >= 3; // At least "Good" strength
    }

    // Function to check if passwords match
    function checkMatch() {
      if (confirmPass.value.length === 0) {
        matchText.textContent = "";
        return false;
      }
      if (newPass.value === confirmPass.value) {
        matchText.textContent = "✅ Passwords match";
        matchText.style.color = "#28a745";
        return true;
      } else {
        matchText.textContent = "❌ Passwords do not match";
        matchText.style.color = "#dc3545";
        return false;
      }
    }

    // Live validation
    function validateForm() {
      const strongEnough = checkStrength(newPass.value);
      const match = checkMatch();
      if (strongEnough && match) {
        submitBtn.disabled = false;
        submitBtn.classList.remove("disabled-btn");
      } else {
        submitBtn.disabled = true;
        submitBtn.classList.add("disabled-btn");
      }
    }

    newPass.addEventListener("input", validateForm);
    confirmPass.addEventListener("input", validateForm);
  </script>
</body>
</html>

