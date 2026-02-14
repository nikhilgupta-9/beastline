<?php
session_start();
include_once "../config/connect.php";

if (isset($_POST['variant_id'])) {

    $variant_id = intval($_POST['variant_id']);

    $sql = "SELECT * FROM product_variants 
            WHERE id = ? AND status = 1 
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $variant_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'variant' => [
                'id' => $row['id'],
                'price' => floatval($row['price']),
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
