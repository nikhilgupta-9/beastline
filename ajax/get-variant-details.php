<?php
session_start();
include_once "../config/connect.php";

if (isset($_POST['product_id']) && isset($_POST['color']) && isset($_POST['size'])) {

    $product_id = intval($_POST['product_id']);
    $color = mysqli_real_escape_string($conn, $_POST['color']);
    $size = mysqli_real_escape_string($conn, $_POST['size']);


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

    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'variant' => [
                'id' => $row['id'],
                'price' => $row['price'] ? floatval($row['price']) : 0,
                'stock' => intval($row['quantity']),
                'sku' => $row['sku']
            ]
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
