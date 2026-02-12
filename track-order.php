<?php
session_start();
include_once "config/connect.php";
include_once "util/function.php";
require_once __DIR__ . '/models/OrderService.php';

$orderService = new OrderService($conn, $site);
$contact = contact_us();

// Get parameters
$order_id = $_GET['order_id'] ?? 0;
$order_number = $_GET['order_number'] ?? '';
$tracking_id = $_GET['tracking_id'] ?? '';

// If user is logged in, get their orders
$userOrders = [];
if (isset($_SESSION['user_id'])) {
    $sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $userOrders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Order | Beastline</title>
       <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="<?= $site ?>assets/img/favicon/favicon.ico">

    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="<?= $site ?>assets/img/favicon/favicon.ico">

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <!-- CSS 
    ========================= -->
    <!--bootstrap min css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/bootstrap.min.css">
    <!--owl carousel min css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/owl.carousel.min.css">
    <!--slick min css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/slick.css">
    <!--magnific popup min css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/magnific-popup.css">
    <!--font awesome css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/font.awesome.css">
    <!--ionicons css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/ionicons.min.css">
    <!--7 stroke icons css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/pe-icon-7-stroke.css">
    <!--animate css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/animate.css">
    <!--jquery ui min css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/jquery-ui.min.css">
    <!--plugins css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/plugins.css">

    <!-- Main Style CSS -->
    <link rel="stylesheet" href="<?= $site ?>assets/css/style.css">

    
    <style>
        .tracking-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .tracking-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        /* Progress Bar Styles */
        .progress-track {
            position: relative;
            margin: 40px 0;
        }
        
        .progress-line {
            position: absolute;
            top: 30px;
            left: 0;
            right: 0;
            height: 4px;
            background: #e0e0e0;
            z-index: 1;
        }
        
        .progress-fill {
            position: absolute;
            top: 30px;
            left: 0;
            height: 4px;
            background: #28a745;
            z-index: 2;
            transition: width 0.5s ease;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            z-index: 3;
        }
        
        .progress-step {
            text-align: center;
            position: relative;
            flex: 1;
        }
        
        .step-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: white;
            border: 4px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 20px;
            color: #666;
            transition: all 0.3s;
        }
        
        .step-icon.completed {
            border-color: #28a745;
            background: #28a745;
            color: white;
        }
        
        .step-icon.active {
            border-color: #28a745;
            color: #28a745;
            transform: scale(1.1);
            box-shadow: 0 0 0 5px rgba(40, 167, 69, 0.2);
        }
        
        .step-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        
        .step-date {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .step-description {
            font-size: 12px;
            color: #888;
            line-height: 1.4;
        }
        
        /* Timeline Styles */
        .timeline {
            position: relative;
            margin: 30px 0;
            padding-left: 20px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #007bff;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
            padding-left: 25px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -5px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #007bff;
            border: 2px solid white;
            box-shadow: 0 0 0 2px #007bff;
        }
        
        .timeline-time {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .timeline-content {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 5px;
            border-left: 3px solid #007bff;
        }
        
        .timeline-status {
            font-weight: 600;
            color: #333;
        }
        
        .timeline-location {
            color: #666;
            font-size: 14px;
        }
        
        /* Order Cards */
        .order-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .order-card:hover {
            border-color: #007bff;
            box-shadow: 0 2px 8px rgba(0,123,255,0.1);
        }
        
        .order-status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .status-ordered { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #cce5ff; color: #004085; }
        .status-processing { background: #d1ecf1; color: #0c5460; }
        .status-shipped { background: #d4edda; color: #155724; }
        .status-out_for_delivery { background: #d1ecf1; color: #0c5460; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        @media (max-width: 768px) {
            .progress-steps {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .progress-step {
                width: 100%;
                display: flex;
                align-items: center;
                margin-bottom: 20px;
                text-align: left;
            }
            
            .step-icon {
                margin: 0 15px 0 0;
                flex-shrink: 0;
            }
            
            .progress-line {
                display: none;
            }
            
            .progress-fill {
                display: none;
            }
        }
    </style>
       <!--modernizr min js here-->
    <script src="<?= $site ?>assets/js/vendor/modernizr-3.7.1.min.js"></script>
</head>
<body>
    <?php include_once "includes/header.php" ?>
    
    <div class="breadcrumbs_area">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="breadcrumb_content">
                        <h3>Track Your Order</h3>
                        <ul>
                            <li><a href="<?= $site ?>">home</a></li>
                            <li>Track Order</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container my-5">
        <div class="tracking-container">
            
            <!-- Search Section -->
            <div class="tracking-card mb-4">
                <h4 class="mb-4"><i class="fa fa-search"></i> Track Your Order</h4>
                <form id="trackOrderForm" class="row g-3">
                    <div class="col-md-6">
                        <label>Order Number</label>
                        <input type="text" class="form-control" id="orderNumber" 
                               value="<?= htmlspecialchars($order_number) ?>" 
                               placeholder="e.g., ORD20240101123">
                    </div>
                    <div class="col-md-6">
                        <label>Tracking Number (Optional)</label>
                        <input type="text" class="form-control" id="trackingId" 
                               value="<?= htmlspecialchars($tracking_id) ?>" 
                               placeholder="Enter tracking number">
                    </div>
                    <div class="col-12 text-center mt-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fa fa-search"></i> Track Order
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- My Orders Section (Logged in users) -->
            <?php if (!empty($userOrders)): ?>
            <div class="tracking-card mb-4">
                <h4 class="mb-4"><i class="fa fa-list"></i> My Recent Orders</h4>
                <div id="myOrdersList">
                    <?php foreach ($userOrders as $order): ?>
                    <div class="order-card" data-order-id="<?= $order['order_id'] ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">
                                    Order #<?= htmlspecialchars($order['order_number']) ?>
                                    <span class="order-status-badge status-<?= $order['order_status'] ?>">
                                        <?= ucfirst(str_replace('_', ' ', $order['order_status'])) ?>
                                    </span>
                                </h6>
                                <p class="mb-1 text-muted">
                                    <small>Placed on: <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></small>
                                </p>
                                <p class="mb-0">
                                    <strong>₹<?= number_format($order['final_amount'], 2) ?></strong>
                                    <?php if ($order['tracking_number']): ?>
                                    <span class="ms-3">
                                        <i class="fa fa-truck"></i> 
                                        Tracking: <?= htmlspecialchars($order['tracking_number']) ?>
                                    </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <button class="btn btn-sm btn-outline-primary track-order-btn" 
                                    data-order-id="<?= $order['order_id'] ?>">
                                <i class="fa fa-eye"></i> Track
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Progress Bar Section -->
            <div class="tracking-card mb-4" id="progressSection" style="display: none;">
                <div id="orderProgressContainer">
                    <!-- Progress bar will be loaded here -->
                </div>
            </div>
            
            <!-- Timeline Section -->
            <div class="tracking-card mb-4" id="timelineSection" style="display: none;">
                <h4 class="mb-4"><i class="fa fa-history"></i> Order Timeline</h4>
                <div id="timelineContainer">
                    <!-- Timeline will be loaded here -->
                </div>
            </div>
            
            <!-- Order Details Section -->
            <div class="tracking-card" id="orderDetailsSection" style="display: none;">
                <h4 class="mb-4"><i class="fa fa-info-circle"></i> Order Details</h4>
                <div id="orderDetailsContainer">
                    <!-- Order details will be loaded here -->
                </div>
            </div>
            
        </div>
    </div>
    
    <?php include_once "includes/footer.php" ?>
    
    <script>
    $(document).ready(function() {
        // Form submission
        $('#trackOrderForm').submit(function(e) {
            e.preventDefault();
            const orderNumber = $('#orderNumber').val().trim();
            if (orderNumber) {
                fetchOrderProgress(null, orderNumber);
            } else {
                alert('Please enter order number');
            }
        });
        
        // Track button click for user orders
        $(document).on('click', '.track-order-btn', function() {
            const orderId = $(this).data('order-id');
            fetchOrderProgress(orderId);
        });
        
        <?php if ($order_id || $order_number): ?>
        // Auto-fetch if parameters exist
        setTimeout(() => {
            fetchOrderProgress(<?= $order_id ?: 'null' ?>, '<?= $order_number ?>');
        }, 500);
        <?php endif; ?>
    });
    
    function fetchOrderProgress(orderId = null, orderNumber = null) {
        $.ajax({
            url: '<?= $site ?>ajax/get-order-progress.php',
            method: 'POST',
            dataType: 'json',
            data: {
                order_id: orderId,
                order_number: orderNumber
            },
            beforeSend: function() {
                showLoading();
            },
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    displayProgressBar(response.progress);    
                    displayTimeline(response.timeline);
                    displayOrderDetails(response.order_details);
                    
                    // Show all sections
                    $('#progressSection').slideDown();
                    $('#timelineSection').slideDown();
                    $('#orderDetailsSection').slideDown();
                    
                    // Scroll to progress section
                    $('html, body').animate({
                        scrollTop: $('#progressSection').offset().top - 100
                    }, 500);
                } else {
                    alert(response.message || 'Order not found');
                }
            },
            error: function() {
                hideLoading();
                alert('Error fetching order details. Please try again.');
            }
        });
    }
    
    function displayProgressBar(progressData) {
        const progress = progressData.progress;
        const percentage = progressData.percentage;
        
        let stepsHtml = '';
        progress.forEach((step, index) => {
            const iconClass = step.is_completed ? 'completed' : (step.is_active ? 'active' : '');
            
            stepsHtml += `
                <div class="progress-step">
                    <div class="step-icon ${iconClass}">
                        <i class="fa ${step.icon}"></i>
                    </div>
                    <div class="step-title">${step.title}</div>
                    ${step.date ? `<div class="step-date">${formatDate(step.date)}</div>` : ''}
                    <div class="step-description">${step.description}</div>
                </div>
            `;
        });
        
        const progressHtml = `
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Order Status: 
                    <span class="text-capitalize">${progressData.current_status.replace('_', ' ')}</span>
                </h4>
                <div class="text-end">
                    <div class="h4 mb-0 text-primary">${percentage}%</div>
                    <small class="text-muted">Order Progress</small>
                </div>
            </div>
            
            <div class="progress-track">
                <div class="progress-line"></div>
                <div class="progress-fill" style="width: ${percentage}%"></div>
                <div class="progress-steps">
                    ${stepsHtml}
                </div>
            </div>
            
            ${progressData.tracking_info ? `
            <div class="alert alert-info mt-4">
                <div class="row">
                    <div class="col-md-4">
                        <strong><i class="fa fa-truck"></i> Courier:</strong><br>
                        ${progressData.tracking_info.courier_name || 'Not assigned'}
                    </div>
                    <div class="col-md-4">
                        <strong><i class="fa fa-barcode"></i> AWB Number:</strong><br>
                        ${progressData.tracking_info.awb_number || 'Not assigned'}
                    </div>
                    <div class="col-md-4">
                        <strong><i class="fa fa-hashtag"></i> Tracking ID:</strong><br>
                        ${progressData.tracking_info.tracking_id || 'Not assigned'}
                    </div>
                </div>
            </div>
            ` : ''}
        `;
        
        $('#orderProgressContainer').html(progressHtml);
    }
    
    function displayTimeline(timeline) {
        if (!timeline || timeline.length === 0) {
            $('#timelineContainer').html(`
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    No timeline updates available yet. Check back soon!
                </div>
            `);
            return;
        }
        
        let timelineHtml = '<div class="timeline">';
        
        timeline.forEach(item => {
            timelineHtml += `
                <div class="timeline-item">
                    <div class="timeline-time">
                        ${formatDateTime(item.date_time)}
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-status">
                            ${item.status}
                        </div>
                        ${item.location ? `<div class="timeline-location"><i class="fa fa-map-marker"></i> ${item.location}</div>` : ''}
                        ${item.remarks ? `<div class="timeline-remarks mt-2"><i class="fa fa-comment"></i> ${item.remarks}</div>` : ''}
                    </div>
                </div>
            `;
        });
        
        timelineHtml += '</div>';
        $('#timelineContainer').html(timelineHtml);
    }
    
    function displayOrderDetails(orderDetails) {
        if (!orderDetails) return;
        
        const address = JSON.parse(orderDetails.shipping_address || '{}');
        
        const detailsHtml = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Order Information</h6>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Order Number:</strong></td>
                            <td>${orderDetails.order_number}</td>
                        </tr>
                        <tr>
                            <td><strong>Order Date:</strong></td>
                            <td>${formatDateTime(orderDetails.created_at)}</td>
                        </tr>
                        <tr>
                            <td><strong>Payment Method:</strong></td>
                            <td class="text-uppercase">${orderDetails.payment_method}</td>
                        </tr>
                        <tr>
                            <td><strong>Payment Status:</strong></td>
                            <td>
                                <span class="badge ${orderDetails.payment_status === 'paid' ? 'bg-success' : 'bg-warning'}">
                                    ${orderDetails.payment_status}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Total Amount:</strong></td>
                            <td class="h6 text-primary">₹${parseFloat(orderDetails.final_amount).toFixed(2)}</td>
                        </tr>
                    </table>
                </div>
                
                <div class="col-md-6">
                    <h6>Delivery Information</h6>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Name:</strong></td>
                            <td>${address.name || 'N/A'}</td>
                        </tr>
                        <tr>
                            <td><strong>Phone:</strong></td>
                            <td>${address.phone || 'N/A'}</td>
                        </tr>
                        <tr>
                            <td><strong>Address:</strong></td>
                            <td>
                                ${address.address || ''}<br>
                                ${address.address2 || ''}<br>
                                ${address.city || ''}, ${address.state || ''}<br>
                                ${address.country || ''} - ${address.postcode || ''}
                            </td>
                        </tr>
                        ${orderDetails.expected_delivery_date ? `
                        <tr>
                            <td><strong>Expected Delivery:</strong></td>
                            <td>${formatDate(orderDetails.expected_delivery_date)}</td>
                        </tr>
                        ` : ''}
                    </table>
                </div>
            </div>
            
            <div class="mt-3">
                <a href="${orderDetails.tracking_number ? 'https://ithinklogistics.com/track?id=' + orderDetails.tracking_number : '#'}" 
                   target="_blank" class="btn btn-outline-primary ${!orderDetails.tracking_number ? 'disabled' : ''}">
                    <i class="fa fa-external-link"></i> Track on iThink Logistics
                </a>
                
                <a href="<?= $site ?>order-details/${orderDetails.order_id}" class="btn btn-outline-secondary ms-2">
                    <i class="fa fa-file-alt"></i> View Order Details
                </a>
            </div>
        `;
        
        $('#orderDetailsContainer').html(detailsHtml);
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-IN', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
    }
    
    function formatDateTime(dateTimeString) {
        const date = new Date(dateTimeString);
        return date.toLocaleString('en-IN', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    function showLoading() {
        $('#orderProgressContainer').html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3">Fetching order details...</p>
            </div>
        `);
    }
    
    function hideLoading() {
        // Already handled by displaying content
    }
    </script>
</body>
</html>