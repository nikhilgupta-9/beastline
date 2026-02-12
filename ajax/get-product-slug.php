<?php
include_once "../config/connect.php";

if(isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    
    $sql = "SELECT slug_url FROM products WHERE pro_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($row = $result->fetch_assoc()) {
        echo json_encode(['slug' => $row['slug_url']]);
    } else {
        echo json_encode(['slug' => null]);
    }
}
?>