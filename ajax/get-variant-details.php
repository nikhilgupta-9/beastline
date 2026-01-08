<?php
session_start();
include_once "../config/connect.php";

if(isset($_GET['product_id']) && isset($_GET['color']) && isset($_GET['size'])) {
    $product_id = intval($_GET['product_id']);
    $color = mysqli_real_escape_string($conn, $_GET['color']);
    $size = mysqli_real_escape_string($conn, $_GET['size']);
    
    $sql = "SELECT * FROM product_variants 
            WHERE product_id = ? 
            AND color = ? 
            AND size = ? 
            AND status = 1 
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $product_id, $color, $size);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'variant_id' => $row['id'],
            'price' => $row['price'] ? floatval($row['price']) : null,
            'compare_at_price' => $row['compare_at_price'] ? floatval($row['compare_at_price']) : null,
            'quantity' => intval($row['quantity']),
            'sku' => $row['sku']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Variant not available'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid parameters'
    ]);
}
?>