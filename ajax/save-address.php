<?php
session_start();
include_once "../config/connect.php";

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $address_type = $_POST['address_type'];
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
    $address_line1 = mysqli_real_escape_string($conn, $_POST['address_line1']);
    $address_line2 = mysqli_real_escape_string($conn, $_POST['address_line2'] ?? '');
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $state = mysqli_real_escape_string($conn, $_POST['state']);
    $country = mysqli_real_escape_string($conn, $_POST['country'] ?? 'India');
    $zip_code = mysqli_real_escape_string($conn, $_POST['zip_code']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    // If setting as default, remove default from other addresses
    if($is_default) {
        $update_sql = "UPDATE user_addresses SET is_default = 0 WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $user_id);
        $update_stmt->execute();
    }
    
    $sql = "INSERT INTO user_addresses (user_id, address_type, first_name, last_name, mobile, address_line1, address_line2, city, state, country, zip_code, is_default) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssssssssi", $user_id, $address_type, $first_name, $last_name, $mobile, $address_line1, $address_line2, $city, $state, $country, $zip_code, $is_default);
    
    if($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Address saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save address']);
    }
}
?>