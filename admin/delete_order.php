<?php
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';

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

// Check if order exists before deleting
$stmt_check = $conn->prepare("SELECT order_id FROM orders WHERE order_id = ?");
$stmt_check->bind_param("s", $order_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    $_SESSION['error'] = "Order not found.";
    header("Location: orders.php");
    exit;
}

// Start transaction to ensure both orders and order_items are deleted
$conn->begin_transaction();

try {
    // First delete from order_items table
    $stmt_items = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
    $stmt_items->bind_param("s", $order_id);
    $stmt_items->execute();
    
    // Then delete from orders table
    $stmt_order = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
    $stmt_order->bind_param("s", $order_id);
    $stmt_order->execute();
    
    // Commit the transaction
    $conn->commit();
    
    $_SESSION['success'] = "Order deleted successfully.";
} catch (Exception $e) {
    // Rollback the transaction on error
    $conn->rollback();
    $_SESSION['error'] = "Failed to delete order: " . $e->getMessage();
}

// Redirect back to orders page
header("Location: orders.php");
exit;
?>