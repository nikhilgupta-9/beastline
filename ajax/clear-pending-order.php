<?php
// clear-pending-order.php
session_start();
header('Content-Type: application/json');

try {
    // Clear pending order from session
    if (isset($_SESSION['pending_order'])) {
        unset($_SESSION['pending_order']);
    }
    
    echo json_encode(['success' => true, 'message' => 'Pending order cleared']);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>