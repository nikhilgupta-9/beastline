<?php
header('Content-Type: application/json');
require_once '../config/connect.php';
require_once '../models/OrderService.php';

$response = ['success' => false, 'message' => '', 'data' => []];
$method = $_SERVER['REQUEST_METHOD'];

// Check authentication (add your auth logic)
if (!isset($_SERVER['HTTP_API_KEY']) || $_SERVER['HTTP_API_KEY'] !== 'YOUR_API_KEY_HERE') {
    $response['message'] = 'Unauthorized';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

$orderService = new OrderService($conn, $site);

switch ($method) {
    case 'GET':
        handleGetRequest($orderService);
        break;
    case 'POST':
        handlePostRequest($orderService);
        break;
    case 'PUT':
        handlePutRequest($orderService);
        break;
    default:
        $response['message'] = 'Method not allowed';
        http_response_code(405);
        echo json_encode($response);
}

function handleGetRequest($orderService) {
    global $response;
    
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_orders':
            $filters = [
                'search' => $_GET['search'] ?? '',
                'start_date' => $_GET['start_date'] ?? '',
                'end_date' => $_GET['end_date'] ?? '',
                'status' => $_GET['status'] ?? '',
                'payment_method' => $_GET['payment_method'] ?? '',
                'limit' => $_GET['limit'] ?? 50,
                'offset' => $_GET['offset'] ?? 0
            ];
            
            $orders = $orderService->getLogisticsOrders($filters);
            $response['success'] = true;
            $response['data'] = $orders;
            $response['total'] = count($orders);
            break;
            
        case 'get_order_details':
            $orderId = $_GET['order_id'] ?? 0;
            if ($orderId) {
                $order = $orderService->getOrderById($orderId);
                $response['success'] = true;
                $response['data'] = $order;
            } else {
                $response['message'] = 'Order ID required';
            }
            break;
            
        case 'get_tracking':
            $awbNumber = $_GET['awb'] ?? '';
            require_once '../api/LogisticsApi.php';
            $api = new LogisticsApi($conn);
            $tracking = $api->trackShipment($awbNumber);
            $response['success'] = true;
            $response['data'] = $tracking;
            break;
            
        default:
            $response['message'] = 'Invalid action';
    }
    
    echo json_encode($response);
}

function handlePostRequest($orderService) {
    global $response;
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_shipment':
            $orderId = $_POST['order_id'] ?? 0;
            if ($orderId) {
                $result = $orderService->syncToLogisticsDashboard($orderId);
                $response = $result;
            } else {
                $response['message'] = 'Order ID required';
            }
            break;
            
        case 'bulk_update':
            $orderIds = $_POST['order_ids'] ?? [];
            $status = $_POST['status'] ?? '';
            
            if (!empty($orderIds) && $status) {
                $successCount = 0;
                foreach ($orderIds as $orderId) {
                    $result = $orderService->updateOrderStatus($orderId, $status);
                    if ($result['success']) $successCount++;
                }
                $response['success'] = true;
                $response['message'] = "Updated $successCount orders";
            } else {
                $response['message'] = 'Order IDs and status required';
            }
            break;
            
        default:
            $response['message'] = 'Invalid action';
    }
    
    echo json_encode($response);
}

function handlePutRequest($orderService) {
    parse_str(file_get_contents("php://input"), $putData);
    global $response;
    
    $action = $putData['action'] ?? '';
    
    switch ($action) {
        case 'update_order':
            $orderId = $putData['order_id'] ?? 0;
            $status = $putData['status'] ?? '';
            $remarks = $putData['remarks'] ?? '';
            
            if ($orderId && $status) {
                $result = $orderService->updateOrderStatus($orderId, $status, $remarks);
                $response = $result;
            } else {
                $response['message'] = 'Order ID and status required';
            }
            break;
            
        case 'cancel_shipment':
            $awbNumber = $putData['awb'] ?? '';
            require_once '../api/LogisticsApi.php';
            $api = new LogisticsApi($conn);
            $result = $api->cancelShipment($awbNumber);
            $response = $result;
            break;
            
        default:
            $response['message'] = 'Invalid action';
    }
    
    echo json_encode($response);
}
?>