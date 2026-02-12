<?php
include_once(__DIR__ . "/../config/db-conn.php");

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];

    if (!empty($delete_id) && is_numeric($delete_id)) {

        // Start transaction
        $conn->begin_transaction();

        try {
            // 1. Delete product variants
            $variant_sql = "DELETE FROM `product_variants` WHERE `product_id` = ?";
            $variant_stmt = $conn->prepare($variant_sql);
            $variant_stmt->bind_param('i', $delete_id);
            $variant_stmt->execute();
            $variant_stmt->close();

            // 2. Delete product
            $product_sql = "DELETE FROM `products` WHERE `pro_id` = ?";
            $product_stmt = $conn->prepare($product_sql);
            $product_stmt->bind_param('i', $delete_id);
            $product_stmt->execute();
            $product_stmt->close();

            // Commit changes
            $conn->commit();

            header('Location: ' . ADMIN_URL . 'view-products.php');
            exit();

        } catch (Exception $e) {
            // Rollback if error occurs
            $conn->rollback();
            echo "Failed to delete product and variants.";
        }

    } else {
        echo "Invalid product ID.";
    }
}

$conn->close();
?>
