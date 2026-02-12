<?php
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';
require_once __DIR__ . '/models/PaymentSmtpSetting.php';
require_once __DIR__ . '/models/setting.php';

// Initialize Settings
$setting = new Setting($conn);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_id'], $_POST['update_status'])) {
    $update_order_id = mysqli_real_escape_string($conn, $_POST['update_order_id']);
    $update_status = mysqli_real_escape_string($conn, $_POST['update_status']);

    $update_sql = "UPDATE orders SET order_status = ? WHERE order_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ss", $update_status, $update_order_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Order #$update_order_id status updated to " . ucfirst($update_status);
    } else {
        $_SESSION['error'] = "Failed to update order status";
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_action'])) {
    $selected_ids = $_POST['selected_ids'] ?? [];

    if (!empty($selected_ids)) {
        $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));

        switch ($_POST['bulk_action']) {
            case 'confirmed':
                $stmt = $conn->prepare("UPDATE orders SET order_status = 'confirmed' WHERE order_id IN ($placeholders)");
                break;
            case 'processing':
                $stmt = $conn->prepare("UPDATE orders SET order_status = 'processing' WHERE order_id IN ($placeholders)");
                break;
            case 'shipped':
                $stmt = $conn->prepare("UPDATE orders SET order_status = 'shipped' WHERE order_id IN ($placeholders)");
                break;
            case 'delivered':
                $stmt = $conn->prepare("UPDATE orders SET order_status = 'delivered' WHERE order_id IN ($placeholders)");
                break;
            case 'cancelled':
                $stmt = $conn->prepare("UPDATE orders SET order_status = 'cancelled' WHERE order_id IN ($placeholders)");
                break;
            case 'refunded':
                $stmt = $conn->prepare("UPDATE orders SET order_status = 'refunded' WHERE order_id IN ($placeholders)");
                break;
            case 'delete':
                $stmt = $conn->prepare("DELETE FROM orders WHERE order_id IN ($placeholders)");
                break;
            default:
                $stmt = null;
        }

        if ($stmt) {
            $types = str_repeat('s', count($selected_ids));
            $stmt->bind_param($types, ...$selected_ids);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Bulk action completed successfully";
            } else {
                $_SESSION['error'] = "Error performing bulk action";
            }
            $stmt->close();
        }
    } else {
        $_SESSION['error'] = "No orders selected";
    }

    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Handle search and filters
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$payment_status_filter = isset($_GET['payment_status']) ? mysqli_real_escape_string($conn, $_GET['payment_status']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Get order stats
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(order_status = 'confirmed') as confirmed,
    SUM(order_status = 'processing') as processing,
    SUM(order_status = 'delivered') as delivered,
    SUM(order_status = 'shipped') as shipped,
    SUM(order_status = 'cancelled') as cancelled,
    SUM(order_status = 'refunded') as refunded,
    SUM(payment_status = 'paid' OR payment_status = 'delivered') as paid,
    SUM(payment_status = 'pending') as pending
    FROM orders WHERE 1=1";

if (!empty($search)) {
    $stats_query .= " AND (order_id LIKE '%$search%' OR order_number LIKE '%$search%')";
}

if (!empty($status_filter)) {
    $stats_query .= " AND order_status = '$status_filter'";
}

if (!empty($payment_status_filter)) {
    $stats_query .= " AND payment_status = '$payment_status_filter'";
}

if (!empty($date_from) && !empty($date_to)) {
    $stats_query .= " AND DATE(created_at) BETWEEN '$date_from' AND '$date_to'";
}

$stats_result = mysqli_query($conn, $stats_query);
$stats = $stats_result ? mysqli_fetch_assoc($stats_result) : [
    'total' => 0, 'confirmed' => 0, 'processing' => 0, 'delivered' => 0, 
    'shipped' => 0, 'cancelled' => 0, 'refunded' => 0, 'paid' => 0, 'pending' => 0
];

// Build the query with filters for pagination
// In your SQL query section, update the SELECT statement:
$sql = "SELECT o.*, 
        u.first_name, u.last_name, u.email, u.mobile,
        (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) as item_count,
        (SELECT GROUP_CONCAT(CONCAT(oi.product_name, ' (', oi.quantity, ')') SEPARATOR ', ') 
         FROM order_items oi WHERE oi.order_id = o.order_id LIMIT 3) as products_summary
        FROM `orders` o
        LEFT JOIN `users` u ON o.user_id = u.id
        WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (o.order_id LIKE '%$search%' OR o.order_number LIKE '%$search%' OR u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR u.mobile LIKE '%$search%' OR u.email LIKE '%$search%')";
}

if (!empty($status_filter)) {
    $sql .= " AND o.order_status = '$status_filter'";
}

if (!empty($payment_status_filter)) {
    $sql .= " AND o.payment_status = '$payment_status_filter'";
}

if (!empty($date_from) && !empty($date_to)) {
    $sql .= " AND DATE(o.created_at) BETWEEN '$date_from' AND '$date_to'";
}

// Get total count for pagination
$count_query = "
    SELECT COUNT(*) AS total
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE 1
";
$count_result = mysqli_query($conn, $count_query);
$total_rows = $count_result ? mysqli_fetch_assoc($count_result)['total'] : 0;

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$total_pages = ceil($total_rows / $limit);

// Add pagination to main query
$sql .= " ORDER BY o.created_at DESC LIMIT $limit OFFSET $offset";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Order Management | Admin <?php echo htmlspecialchars($setting->get('site_name')); ?></title>
    <link rel="icon" href="<?php echo htmlspecialchars($setting->get('favicon', 'assets/img/logo.png')); ?>" type="image/png">

    <?php include "links.php"; ?>

    <style>
        .order-image {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
        }

        .table-actions .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .stats-card {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .stats-card i {
            font-size: 1.8rem;
            margin-bottom: 8px;
        }

        .stats-card h4 {
            margin: 0;
            font-weight: 600;
            font-size: 1.25rem;
        }

        .stats-card p {
            margin: 0;
            font-size: 0.875rem;
            color: #6c757d;
        }

        .search-box {
            max-width: 300px;
        }

        .bulk-actions {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .status-badge {
            cursor: pointer;
            transition: opacity 0.3s;
        }

        .status-badge:hover {
            opacity: 0.8;
        }

        .order-checkbox {
            cursor: pointer;
        }

        #selectAll {
            cursor: pointer;
        }

        .badge-confirmed {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        .badge-processing {
            background-color: rgba(23, 162, 184, 0.2);
            color: #17a2b8;
        }

        .badge-shipped {
            background-color: rgba(0, 123, 255, 0.2);
            color: #007bff;
        }

        .badge-delivered {
            background-color: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }

        .badge-cancelled {
            background-color: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }

        .badge-refunded {
            background-color: rgba(108, 117, 125, 0.2);
            color: #6c757d;
        }

        .badge-paid {
            background-color: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }

        .badge-pending {
            background-color: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }

        .filter-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .customer-info {
            font-size: 0.9rem;
        }

        .product-summary {
            max-width: 200px;
            font-size: 0.85rem;
        }

        .amount-cell {
            font-weight: 600;
        }

        .address-cell {
            max-width: 200px;
            font-size: 0.85rem;
        }
    </style>
</head>

<body class="crm_body_bg">

    <?php include "includes/header.php"; ?>

    <section class="main_content dashboard_part large_header_bg">
        <div class="container-fluid g-0">
            <div class="row">
                <div class="col-lg-12 p-0">
                    <?php include "includes/top_nav.php"; ?>
                </div>
            </div>
        </div>

        <div class="main_content_iner ">
            <div class="container-fluid p-0 sm_padding_15px">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <div class="white_card card_height_100 mb_30">
                            <div class="white_card_header">
                                <div class="box_header m-0">
                                    <div class="main-title">
                                        <h2 class="m-0">Order Management</h2>
                                    </div>
                                    <!--<div class="action-btn">-->
                                    <!--    <a href="add-order.php" class="btn_1">Add New Order</a>-->
                                    <!--</div>-->
                                </div>
                            </div>

                            <div class="white_card_body">
                                <!-- Messages -->
                                <?php if (isset($_SESSION['success'])): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="fas fa-check-circle mr-2"></i> <?php echo $_SESSION['success']; ?>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <?php unset($_SESSION['success']); ?>
                                <?php endif; ?>

                                <?php if (isset($_SESSION['error'])): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $_SESSION['error']; ?>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <?php unset($_SESSION['error']); ?>
                                <?php endif; ?>

                                <!-- Stats Overview -->
                                <div class="row mb-4">
                                    <div class="col-md-3 col-sm-6">
                                        <div class="stats-card bg-light border">
                                            <i class="fas fa-shopping-cart text-primary"></i>
                                            <h4><?php echo $stats['total']; ?></h4>
                                            <p>Total Orders</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="stats-card bg-light border">
                                            <i class="fas fa-clock text-warning"></i>
                                            <h4><?php echo $stats['confirmed']; ?></h4>
                                            <p>Confirmed</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="stats-card bg-light border">
                                            <i class="fas fa-cogs text-info"></i>
                                            <h4><?php echo $stats['processing']; ?></h4>
                                            <p>Processing</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="stats-card bg-light border">
                                            <i class="fas fa-check-circle text-success"></i>
                                            <h4><?php echo $stats['delivered']; ?></h4>
                                            <p>Delivered</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="stats-card bg-light border">
                                            <i class="fas fa-truck text-primary"></i>
                                            <h4><?php echo $stats['shipped']; ?></h4>
                                            <p>Shipped</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="stats-card bg-light border">
                                            <i class="fas fa-times-circle text-danger"></i>
                                            <h4><?php echo $stats['cancelled']; ?></h4>
                                            <p>Cancelled</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="stats-card bg-light border">
                                            <i class="fas fa-exchange-alt text-secondary"></i>
                                            <h4><?php echo $stats['refunded']; ?></h4>
                                            <p>Refunded</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="stats-card bg-light border">
                                            <i class="fas fa-credit-card text-success"></i>
                                            <h4><?php echo $stats['paid']; ?></h4>
                                            <p>Paid</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Filter Section -->
                                <div class="filter-section">
                                    <form method="GET" action="">
                                        <div class="row">
                                            <div class="col-md-3 mb-2">
                                                <input type="text" class="form-control" name="search" 
                                                       placeholder="Search orders..." value="<?php echo htmlspecialchars($search); ?>">
                                            </div>
                                            <div class="col-md-2 mb-2">
                                                <select class="form-control" name="status">
                                                    <option value="">All Statuses</option>
                                                    <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                    <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                    <option value="shipped" <?php echo $status_filter == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                    <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                    <option value="refunded" <?php echo $status_filter == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2 mb-2">
                                                <select class="form-control" name="payment_status">
                                                    <option value="">Payment Status</option>
                                                    <option value="paid" <?php echo $payment_status_filter == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                    <option value="pending" <?php echo $payment_status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2 mb-2">
                                                <input type="date" class="form-control" name="date_from" 
                                                       value="<?php echo htmlspecialchars($date_from); ?>" placeholder="From Date">
                                            </div>
                                            <div class="col-md-2 mb-2">
                                                <input type="date" class="form-control" name="date_to" 
                                                       value="<?php echo htmlspecialchars($date_to); ?>" placeholder="To Date">
                                            </div>
                                            <div class="col-md-1 mb-2">
                                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- Bulk Actions -->
                                <form method="POST" id="bulkForm" class="bulk-actions">
                                    <div class="d-flex align-items-center">
                                        <div class="mr-3">
                                            <select class="form-control form-control-sm" name="bulk_action" style="min-width: 150px;">
                                                <option value="">Bulk Actions</option>
                                                <option value="confirmed">Mark as Confirmed</option>
                                                <option value="processing">Mark as Processing</option>
                                                <option value="shipped">Mark as Shipped</option>
                                                <option value="delivered">Mark as Delivered</option>
                                                <option value="cancelled">Mark as Cancelled</option>
                                                <option value="refunded">Mark as Refunded</option>
                                                <option value="delete" class="text-danger">Delete Selected</option>
                                            </select>
                                        </div>
                                        <div class="mr-3">
                                            <button type="submit" class="btn btn-sm btn-primary" onclick="return confirmBulkAction()">
                                                <i class="fas fa-play mr-1"></i> Apply
                                            </button>
                                        </div>
                                        <div class="ml-auto">
                                            <div class="input-group input-group-sm search-box">
                                                <input type="text" class="form-control" placeholder="Quick search..." id="searchInput">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary" type="button">
                                                        <i class="fas fa-search"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <!-- Orders Table -->
                                <div class="QA_section">
                                    <div class="QA_table mb_30">
                                        <table class="table lms_table_active">
                                            <thead>
                                                <tr>
                                                    <th scope="col" width="3%">
                                                        <input type="checkbox" id="selectAll">
                                                    </th>
                                                    <th scope="col">#</th>
                                                    <th scope="col">Order ID</th>
                                                    <th scope="col">Order #</th>
                                                    <th scope="col">Customer</th>
                                                    <th scope="col">Products</th>
                                                    <th scope="col">Amount</th>
                                                    <th scope="col">Order Status</th>
                                                    <th scope="col">Payment Status</th>
                                                    <th scope="col">Payment Method</th>
                                                    <th scope="col">Date</th>
                                                    <th scope="col">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $sno = $offset + 1;

                                                if (mysqli_num_rows($result) > 0) {
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        // Order Status badge
                                                        $orderStatusClass = 'badge-confirmed';
                                                        $orderStatusText = ucfirst($row['order_status']);
                                                        switch (strtolower($row['order_status'])) {
                                                            case 'confirmed':
                                                                $orderStatusClass = 'badge-confirmed';
                                                                break;
                                                            case 'processing':
                                                                $orderStatusClass = 'badge-processing';
                                                                break;
                                                            case 'shipped':
                                                                $orderStatusClass = 'badge-shipped';
                                                                break;
                                                            case 'delivered':
                                                                $orderStatusClass = 'badge-delivered';
                                                                break;
                                                            case 'cancelled':
                                                                $orderStatusClass = 'badge-cancelled';
                                                                break;
                                                            case 'refunded':
                                                                $orderStatusClass = 'badge-refunded';
                                                                break;
                                                        }

                                                        // Payment Status badge
                                                        $paymentStatusClass = 'badge-pending';
                                                        if (strtolower($row['payment_status']) === 'paid' || strtolower($row['payment_status']) === 'delivered') {
                                                            $paymentStatusClass = 'badge-paid';
                                                            $paymentStatusText = 'Paid';
                                                        } else {
                                                            $paymentStatusText = 'Pending';
                                                        }

                                                        // Format date
                                                        $orderDate = date('d M Y h:i A', strtotime($row['created_at']));

                                                        // Customer name
                                                        $customerName = !empty($row['first_name']) ? 
                                                            htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) : 
                                                            'Guest Customer';
                                                ?>
                                                        <tr>
                                                            <td class="text-center">
                                                                <input type="checkbox" name="selected_ids[]" value="<?php echo $row['order_id']; ?>" class="order-checkbox">
                                                            </td>
                                                            <td class="text-center"><?php echo $sno++; ?></td>
                                                            <td class="fw-semibold">
                                                                <a href="order_details.php?id=<?php echo $row['order_id']; ?>" class="text-primary">
                                                                    #<?php echo $row['order_id']; ?>
                                                                </a>
                                                                
                                                            </td>
                                                            <td><?php echo htmlspecialchars($row['order_number']); ?> <br> <?php echo htmlspecialchars($row['razorpay_order_id'] ?? 'NA'); ?></td>
                                                            <td class="customer-info">
                                                                <div><strong><?php echo $customerName; ?></strong></div>
                                                                <?php if (!empty($row['email'])): ?>
                                                                    <div class="text-muted small"><?php echo htmlspecialchars($row['email']); ?></div>
                                                                <?php endif; ?>
                                                                <?php if (!empty($row['mobile'])): ?>
                                                                    <div class="text-muted small"><?php echo htmlspecialchars($row['mobile']); ?></div>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="product-summary">
                                                                <span class="badge bg-secondary"><?php echo $row['item_count']; ?> items</span>
                                                                <?php if (!empty($row['products_summary'])): ?>
                                                                    <div class="mt-1 small text-muted">
                                                                        <?php 
                                                                        $products = explode(', ', $row['products_summary']);
                                                                        foreach (array_slice($products, 0, 2) as $product) {
                                                                            echo htmlspecialchars($product) . '<br>';
                                                                        }
                                                                        if ($row['item_count'] > 2) {
                                                                            echo '... +' . ($row['item_count'] - 2) . ' more';
                                                                        }
                                                                        ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="amount-cell">
                                                                <strong>Rs. <?php echo number_format($row['final_amount'], 2); ?></strong>
                                                                <?php if ($row['discount_amount'] > 0): ?>
                                                                    <div class="text-success small">-Rs. <?php echo number_format($row['discount_amount'], 2); ?></div>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <span class="badge <?php echo $orderStatusClass; ?> status-badge">
                                                                    <?php echo $orderStatusText; ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="badge <?php echo $paymentStatusClass; ?>">
                                                                    <?php echo $paymentStatusText; ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo ucfirst(htmlspecialchars($row['payment_method'])); ?></td>
                                                            <td class="text-center"><?php echo $orderDate; ?></td>
                                                            <td class="text-center">
                                                                <div class="d-flex justify-content-center gap-2">
                                                                    <a href="order_details.php?id=<?php echo $row['order_id']; ?>"
                                                                        class="btn btn-sm btn-outline-primary rounded-circle p-2"
                                                                        data-bs-toggle="tooltip" title="View Details">
                                                                        <i class="fas fa-eye fs-6"></i>
                                                                    </a>
                                                                    <form method="POST" action="" class="d-inline">
                                                                        <input type="hidden" name="update_order_id" value="<?php echo $row['order_id']; ?>">
                                                                        <select name="update_status" 
                                                                                class="form-select form-select-sm" 
                                                                                onchange="this.form.submit()"
                                                                                data-bs-toggle="tooltip" title="Change Status">
                                                                            <option value="confirmed" <?php echo $row['order_status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                                            <option value="processing" <?php echo $row['order_status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                                            <option value="shipped" <?php echo $row['order_status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                                            <option value="delivered" <?php echo $row['order_status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                                            <option value="cancelled" <?php echo $row['order_status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                                            <option value="refunded" <?php echo $row['order_status'] == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                                                        </select>
                                                                    </form>
                                                                    <a href="delete_order.php?id=<?php echo $row['order_id']; ?>"
                                                                        onclick='return confirm("Are you sure you want to delete this order?")'
                                                                        class="btn btn-sm btn-outline-danger rounded-circle p-2"
                                                                        data-bs-toggle="tooltip" title="Delete">
                                                                        <i class="fas fa-trash fs-6"></i>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php
                                                    }
                                                } else {
                                                    ?>
                                                    <tr>
                                                        <td colspan="12" class="text-center py-4">
                                                            <div class="empty-state">
                                                                <i class="fas fa-shopping-cart text-muted"></i>
                                                                <h4 class="mt-3">No Orders Found</h4>
                                                                <p class="text-muted mb-4">No orders match your search criteria.</p>
                                                                <a href="?status=" class="btn btn-primary">
                                                                    <i class="fas fa-redo mr-1"></i> Reset Filters
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>

                                        <!-- Pagination -->
                                        <?php if ($total_pages > 1): ?>
                                            <div class="row mt-4">
                                                <div class="col-md-6">
                                                    <div class="d-flex align-items-center">
                                                        <div class="mr-3">
                                                            <select class="form-control form-control-sm" style="width: 80px;" onchange="window.location.href='?limit='+this.value">
                                                                <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                                                                <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                                                                <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                                                                <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <small class="text-muted">
                                                                Showing <?php echo min($limit, $total_rows - $offset); ?> of <?php echo $total_rows; ?> orders
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <nav aria-label="Page navigation" class="float-right">
                                                        <ul class="pagination pagination-sm mb-0">
                                                            <?php if ($page > 1): ?>
                                                                <li class="page-item">
                                                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                                                        <span aria-hidden="true">&laquo;</span>
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>

                                                            <?php
                                                            $start = max(1, $page - 2);
                                                            $end = min($total_pages, $page + 2);

                                                            for ($i = $start; $i <= $end; $i++):
                                                            ?>
                                                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                                                </li>
                                                            <?php endfor; ?>

                                                            <?php if ($page < $total_pages): ?>
                                                                <li class="page-item">
                                                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                                                        <span aria-hidden="true">&raquo;</span>
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    </nav>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include "includes/footer.php"; ?>
    </section>

    <script>
        // Initialize tooltips
        $(function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });

        // Select All checkboxes
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.order-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Confirm bulk action
        function confirmBulkAction() {
            const selected = document.querySelectorAll('.order-checkbox:checked');
            const action = document.querySelector('select[name="bulk_action"]').value;

            if (selected.length === 0) {
                alert('Please select at least one order.');
                return false;
            }

            if (!action) {
                alert('Please select a bulk action.');
                return false;
            }

            let message = '';
            switch (action) {
                case 'confirmed':
                    message = 'Mark ' + selected.length + ' selected orders as confirmed?';
                    break;
                case 'processing':
                    message = 'Mark ' + selected.length + ' selected orders as processing?';
                    break;
                case 'shipped':
                    message = 'Mark ' + selected.length + ' selected orders as shipped?';
                    break;
                case 'delivered':
                    message = 'Mark ' + selected.length + ' selected orders as delivered?';
                    break;
                case 'cancelled':
                    message = 'Mark ' + selected.length + ' selected orders as cancelled?';
                    break;
                case 'refunded':
                    message = 'Mark ' + selected.length + ' selected orders as refunded?';
                    break;
                case 'delete':
                    message = 'Delete ' + selected.length + ' selected orders? This action cannot be undone.';
                    break;
            }

            return confirm(message);
        }

        // Update checkboxes when any checkbox changes
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('order-checkbox')) {
                const allChecked = Array.from(document.querySelectorAll('.order-checkbox')).every(cb => cb.checked);
                const someChecked = Array.from(document.querySelectorAll('.order-checkbox')).some(cb => cb.checked);

                const selectAll = document.getElementById('selectAll');
                if (allChecked) {
                    selectAll.checked = true;
                    selectAll.indeterminate = false;
                } else if (someChecked) {
                    selectAll.checked = false;
                    selectAll.indeterminate = true;
                } else {
                    selectAll.checked = false;
                    selectAll.indeterminate = false;
                }
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>

</html>