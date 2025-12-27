<?php
include_once('../db-conn.php');
// admin/auth/logout.php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: ".$site."/admin/auth/login.php");
exit();
?>