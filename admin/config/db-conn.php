<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
// init log error on in production 
ini_set('log_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Database Configuration
$local = true; // Set to false for live server

if ($local) {
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $dbName = 'beast_line_db';
    // $site = "http://localhost/beast-line/";
    define('BASE_URL', 'http://localhost/beast-line/') ;
    define('ADMIN_URL', 'http://localhost/beast-line/admin/') ;
} else {
    $host = 'localhost';
        $username = 'u950539402_beastLine_db';
    $password = 'I~H!=Sf9&';
    $dbName = 'u950539402_beastLine_db';
    // $site = 'https://zebulli.com/';
    define('BASE_URL', 'https://beastline.in/') ;
    define('ADMIN_URL', 'https://beastline.in/admin/') ;
}

// Create Database Connection
$conn = new mysqli($host, $username, $password, $dbName);

// Check Connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Optional: Set Character Encoding to UTF-8
$conn->set_charset("utf8");

?>
