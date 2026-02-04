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
    
    $orderId = intval($_POST['order_id'] ?? 0);
    $orderNumber = $_POST['order_number'] ?? '';
    
    if ($orderId <= 0 && empty($orderNumber)) {
        throw new Exception('Order ID or number is required');
    }
    
    // If order number provided, get order ID
    if (!empty($orderNumber)) {
        $sql = "SELECT order_id FROM orders WHERE order_number = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $orderNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $order = $result->fetch_assoc();
            $orderId = $order['order_id'];
        } else {
            throw new Exception('Order not found');
        }
    }
    
    // Get order progress
    $progress = $orderService->getOrderProgress($orderId);
    
    if (!$progress) {
        throw new Exception('Unable to fetch order progress');
    }
    
    // Get shipment timeline
    $timeline = $orderService->getShipmentTimeline($orderId);
    
    // Get order details
    $sql = "SELECT o.*, 
            DATE_ADD(o.created_at, INTERVAL 5 DAY) as expected_delivery_date
            FROM orders o 
            WHERE o.order_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $orderDetails = $stmt->get_result()->fetch_assoc();
    
    $response = [
        'success' => true,
        'progress' => $progress,
        'timeline' => $timeline,
        'order_details' => $orderDetails,
        'message' => 'Order progress fetched successfully'
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
?>