<?php
session_start();
include_once "../config/connect.php";

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$address_id = $data['address_id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Verify address belongs to user
$check_sql = "SELECT id FROM user_addresses WHERE id = ? AND user_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $address_id, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if($check_result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Address not found']);
    exit();
}

$delete_sql = "DELETE FROM user_addresses WHERE id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("i", $address_id);

if($delete_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Address deleted']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete address']);
}
?>