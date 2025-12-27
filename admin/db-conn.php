<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Database Configuration
$local = false; // Set to false for live server

if ($local) {
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $dbName = 'beast_line_db';
    $site = "http://localhost/beast-line/";
} else {
    $host = 'localhost';
    $username = 'u799879276_1zebulli_db';
    $password = '6Aq0F[o*';
    $dbName = 'u799879276_1zebulli_db';
    $site = 'https://zebulli.com/';
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
