<?php
session_start();
require_once __DIR__ . '/../config/connect.php';
require_once __DIR__ . '/../models/OrderService.php';

header('Content-Type: application/json');

$orderService = new OrderService($conn, $site);

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $order_id = intval($_POST['order_id'] ?? 0);
    
    if ($order_id <= 0) {
        throw new Exception('Invalid order ID');
    }
    
    // Update order status to confirmed
    $sql = "UPDATE orders SET 
            order_status = 'confirmed',
            updated_at = NOW()
            WHERE order_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    
    if ($stmt->execute()) {
        // Clear sessions
        $orderService->clearSessions();
        
        $response = [
            'success' => true,
            'order_id' => $order_id,
            'message' => 'COD order confirmed successfully!'
        ];
    } else {
        throw new Exception('Failed to update COD order status');
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
?>