<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$serverName = $_SERVER['SERVER_NAME'];

if ($serverName == 'localhost' || $serverName == '127.0.0.1') {

    // LOCAL
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $dbName = 'beast_line_db';

    define('BASE_URL', 'http://localhost/beastline1/');
    define('ADMIN_URL', 'http://localhost/beastline1/admin/');

} else {

    // PRODUCTION
    $host = 'localhost';
    $username = 'u950539402_beastLine_db';
    $password = 'I~H!=Sf9&';
    $dbName = 'u950539402_beastLine_db';

    define('BASE_URL', 'https://beastline.in/');
    define('ADMIN_URL', 'https://beastline.in/admin/');
}

$conn = new mysqli($host, $username, $password, $dbName);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");
?>
