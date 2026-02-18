<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* -------------------------
   Auto Detect Base URL
------------------------- */
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];

// If project is inside a folder (e.g. localhost/beast-line/)
$projectFolder = trim(dirname($_SERVER['SCRIPT_NAME']), '/');

$site = $protocol . $host . '/' . ($projectFolder ? $projectFolder . '/' : '');

// Make `$site` global
global $site;

/* -------------------------
   Database Configuration
------------------------- */
if ($host === 'localhost') {
    // Local DB
    $dbHost = 'localhost';
    $username = 'root';
    $password = '';
    $dbName = 'beast_line_db';
} else {
    // Live DB
    $dbHost = 'localhost';
    $username = 'u950539402_beastLine_db';
    $password = 'I~H!=Sf9&';
    $dbName = 'u950539402_beastLine_db';
}

// Create Database Connection
$conn = new mysqli($dbHost, $username, $password, $dbName);

// Check Connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// UTF-8 Encoding
$conn->set_charset("utf8");
?>
