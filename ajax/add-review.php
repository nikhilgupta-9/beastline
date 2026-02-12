<?php
session_start();
header('Content-Type: application/json');
require_once "../config/connect.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$product_id = intval($_POST['product_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 0);
$review_message = trim($_POST['comment'] ?? '');

if ($product_id <= 0 || $rating < 1 || $rating > 5 || empty($review_message)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

/* Verify product exists */
$check = $conn->prepare("SELECT pro_id FROM products WHERE pro_id=? AND status=1");
$check->bind_param("i", $product_id);
$check->execute();
if ($check->get_result()->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit;
}

/* Fetch user info */
$user_stmt = $conn->prepare("SELECT name,email,profile_image FROM users WHERE id=?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

$reviewer_name  = $user['name'] ?? 'Customer';
$reviewer_email = $user['email'] ?? '';
$reviewer_img   = $user['profile_image'] ?? null;

/* Prevent duplicate review */
$dup = $conn->prepare("SELECT review_id FROM product_reviews WHERE product_id=? AND user_id=?");
$dup->bind_param("ii", $product_id, $user_id);
$dup->execute();
if ($dup->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You already reviewed this product']);
    exit;
}

/* Insert review */
$stmt = $conn->prepare("
INSERT INTO product_reviews
(user_id, product_id, rating, review_message, reviewer_name, reviewer_email, reviewver_img, status, created_at)
VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
");

$stmt->bind_param(
    "iiissss",
    $user_id,
    $product_id,
    $rating,
    $review_message,
    $reviewer_name,
    $reviewer_email,
    $reviewer_img
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
