<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: https://zebulli.com/admin/auth/login.php");
    exit();
}
?>