<?php
session_start();
include('auth_check.php');  // Ensure only logged-in users can delete
include('db-conn.php'); // Your $conn connection

// Check if order_id is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Order ID is required.";
    header("Location: orders.php");
    exit;
}

$order_id = $_GET['id'];

// Validate alphanumeric order ID
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $order_id)) {
    $_SESSION['error'] = "Invalid Order ID.";
    header("Location: orders.php");
    exit;
}

// Optional: Check if order exists before deleting
$stmt_check = $conn->prepare("SELECT id FROM orders_new WHERE order_id = ?");
$stmt_check->bind_param("s", $order_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    $_SESSION['error'] = "Order not found.";
    header("Location: orders.php");
    exit;
}

// Delete the order
$stmt = $conn->prepare("DELETE FROM orders_new WHERE order_id = ?");
$stmt->bind_param("s", $order_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Order deleted successfully.";
} else {
    $_SESSION['error'] = "Failed to delete order. Please try again.";
}

// Redirect back to orders page
header("Location: orders.php");
exit;
?>
