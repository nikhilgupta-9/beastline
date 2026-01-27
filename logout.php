<?php
session_start();
include_once "config/connect.php";

/* Record logout activity (optional but recommended) */
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $activity_sql = "INSERT INTO user_activities (user_id, activity_type, ip_address, user_agent)
                     VALUES (?, 'logout', ?, ?)";
    $stmt = $conn->prepare($activity_sql);
    $stmt->bind_param("iss", $user_id, $ip, $user_agent);
    $stmt->execute();
}

/* Remove remember-me token from database */
if (isset($_SESSION['user_id'])) {
    $clear_sql = "UPDATE users SET remember_token = NULL, token_expiry = NULL WHERE id = ?";
    $stmt = $conn->prepare($clear_sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
}

/* Delete remember cookie */
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

/* Clear session data */
$_SESSION = [];
session_unset();
session_destroy();

/* Redirect to login page with success message */
header("Location: ".$site."user-login?success=" . urlencode("You have been logged out successfully."));
exit();
