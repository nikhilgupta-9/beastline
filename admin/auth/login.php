<?php
session_start();
 
include_once '../config/db-conn.php'; // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Prepare SQL statement
    $stmt = $conn->prepare("SELECT id, username, password FROM admin_user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    // Use bind_result() instead of get_result()
    $stmt->bind_result($id, $user, $hashed_password);
    
    if ($stmt->fetch()) { // Fetch result
        if (password_verify($password, $hashed_password)) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $id;
            $_SESSION['admin_user'] = $user;

            header("Location: ".ADMIN_URL."index.php");
            exit();
        } else {
            $error = "Invalid username or password";
        }
    } else {
        $error = "Invalid username or password";
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login - eCommerce</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Bootstrap CSS -->
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

    .login-card {
      background: #fffaf3;
      border-radius: 16px;
      padding: 40px 35px;
      width: 380px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .login-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }

    .login-card h3 {
      text-align: center;
      margin-bottom: 25px;
      color: #4b3832;
      font-weight: 600;
      letter-spacing: 0.5px;
    }

    .form-label {
      color: #4b3832;
      font-weight: 500;
    }

    .form-control {
      border-radius: 10px;
      border: 1px solid #c0a080;
      padding: 10px 14px;
      font-size: 15px;
      transition: all 0.2s ease;
    }

    .form-control:focus {
      border-color: #b07b52;
      box-shadow: 0 0 0 0.2rem rgba(176, 123, 82, 0.25);
    }

    .btn-login {
      background-color: #b07b52;
      border: none;
      color: #fff;
      font-weight: 500;
      padding: 10px;
      border-radius: 10px;
      transition: background 0.3s ease;
    }

    .btn-login:hover {
      background-color: #a06642;
    }

    .forgot-link {
      display: block;
      text-align: right;
      margin-top: 10px;
      font-size: 14px;
      color: #8b6b4f;
      text-decoration: none;
      transition: color 0.3s ease;
    }

    .forgot-link:hover {
      color: #b07b52;
      text-decoration: underline;
    }

    .password-wrapper {
      position: relative;
    }

    .toggle-password {
      position: absolute;
      top: 70%;
      right: 12px;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: #8b6b4f;
      font-size: 14px;
      cursor: pointer;
      padding: 0;
      transition: color 0.3s ease;
    }

    .toggle-password:hover {
      color: #b07b52;
    }

    .footer-text {
      text-align: center;
      margin-top: 25px;
      font-size: 14px;
      color: #7d6a55;
    }

    @media (max-width: 480px) {
      .login-card {
        width: 90%;
        padding: 30px 25px;
      }
    }
  </style>
</head>

<body>
  <div class="login-card">
    <h3>Admin Login</h3>

    <form method="post" action="">
      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username" required>
      </div>

      <div class="mb-3 password-wrapper">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
        <button type="button" class="toggle-password" onclick="togglePassword()">Show</button>
      </div>

      <?php if (isset($error)): ?>
        <div class="alert alert-danger p-2 text-center">
          <?= htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <button type="submit" class="btn btn-login w-100">Login</button>

      <!-- Forgot Password Link -->
      <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
    </form>

    <div class="footer-text">
      &copy; <?= date("Y"); ?> eCommerce Admin Panel
    </div>
  </div>

  <script>
    function togglePassword() {
      const passwordInput = document.getElementById("password");
      const toggleBtn = document.querySelector(".toggle-password");

      if (passwordInput.type === "password") {
        passwordInput.type = "text";
        toggleBtn.textContent = "Hide";
      } else {
        passwordInput.type = "password";
        toggleBtn.textContent = "Show";
      }
    }
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
