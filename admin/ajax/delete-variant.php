<?php
session_start();
require_once __DIR__ . '/../config/db-conn.php';
require_once __DIR__ . '/../auth/admin-auth.php';

header('Content-Type: application/json');

// Check if it's an AJAX request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get variant ID
$variant_id = isset($_POST['variant_id']) ? intval($_POST['variant_id']) : 0;

if ($variant_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid variant ID']);
    exit;
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // First, get the variant image path if exists
    $image_query = "SELECT image FROM product_variants WHERE id = $variant_id";
    $image_result = mysqli_query($conn, $image_query);
    
    if ($image_result && mysqli_num_rows($image_result) > 0) {
        $variant = mysqli_fetch_assoc($image_result);
        
        // Delete the variant from database
        $delete_sql = "DELETE FROM product_variants WHERE id = $variant_id";
        
        if (mysqli_query($conn, $delete_sql)) {
            // Delete the image file if exists
            if (!empty($variant['image'])) {
                $image_path = __DIR__ . '/../assets/img/uploads/variants/' . $variant['image'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            mysqli_commit($conn);
            echo json_encode(['success' => true, 'message' => 'Variant deleted successfully']);
        } else {
            throw new Exception('Error deleting variant: ' . mysqli_error($conn));
        }
    } else {
        throw new Exception('Variant not found');
    }
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>