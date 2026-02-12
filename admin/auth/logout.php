<?php

session_start();

// Unset all session variables
// $_SESSION = array();
session_unset();
// Destroy the session
session_destroy();

// Redirect to login page
header("Location: ".ADMIN_URL."auth/login.php");
exit();
?>