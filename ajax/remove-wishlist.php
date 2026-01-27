<?php
session_start();
include_once "../config/connect.php";

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$product_id = $data['product_id'] ?? 0;
$user_id = $_SESSION['user_id'];

$sql = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $product_id);

if($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Removed from wishlist']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove from wishlist']);
}
?>