<?php
session_start();
include_once "config/connect.php";
require_once __DIR__ . '/models/OrderService.php';

// Get Razorpay response
$razorpay_payment_id = $_POST['razorpay_payment_id'] ?? '';
$razorpay_order_id = $_POST['razorpay_order_id'] ?? '';
$razorpay_signature = $_POST['razorpay_signature'] ?? '';

// Redirect to order confirmation
if (isset($_SESSION['pending_order']['order_id'])) {
    header("Location: " . $site . "order-confirmation/" . $_SESSION['pending_order']['order_id']);
} else {
    header("Location: " . $site);
}
exit();
?>