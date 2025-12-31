<?php
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';
require_once __DIR__ . '/models/setting.php';

// Initialize
$setting = new Setting($conn);

// Get current date ranges
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$this_month_start = date('Y-m-01');
$last_month_start = date('Y-m-01', strtotime('-1 month'));
$last_month_end = date('Y-m-t', strtotime('-1 month'));
$last_7_days = date('Y-m-d', strtotime('-7 days'));
$last_30_days = date('Y-m-d', strtotime('-30 days'));

// Get dashboard statistics
$total_revenue = 0;
$total_orders = 0;
$total_customers = 0;
$total_products = 0;
$pending_orders = 0;
$today_revenue = 0;
$today_orders = 0;
$month_revenue = 0;

// Total Revenue (from completed orders)
$sql_revenue = "SELECT SUM(order_total) as total FROM orders_new WHERE status = 'completed'";
$res_revenue = mysqli_query($conn, $sql_revenue);
if ($res_revenue && $row = mysqli_fetch_assoc($res_revenue)) {
  $total_revenue = $row['total'] ? $row['total'] : 0;
}

// Today's Revenue
$sql_today_rev = "SELECT SUM(order_total) as total FROM orders_new 
                  WHERE DATE(created_at) = '$today' AND status = 'completed'";
$res_today_rev = mysqli_query($conn, $sql_today_rev);
if ($res_today_rev && $row = mysqli_fetch_assoc($res_today_rev)) {
  $today_revenue = $row['total'] ? $row['total'] : 0;
}

// This Month Revenue
$sql_month_rev = "SELECT SUM(order_total) as total FROM orders_new 
                  WHERE DATE(created_at) >= '$this_month_start' AND status = 'completed'";
$res_month_rev = mysqli_query($conn, $sql_month_rev);
if ($res_month_rev && $row = mysqli_fetch_assoc($res_month_rev)) {
  $month_revenue = $row['total'] ? $row['total'] : 0;
}

// Last Month Revenue for growth calculation
$sql_last_month_rev = "SELECT SUM(order_total) as total FROM orders_new 
                       WHERE DATE(created_at) >= '$last_month_start' 
                       AND DATE(created_at) <= '$last_month_end' 
                       AND status = 'completed'";
$res_last_month_rev = mysqli_query($conn, $sql_last_month_rev);
$last_month_revenue = 0;
if ($res_last_month_rev && $row = mysqli_fetch_assoc($res_last_month_rev)) {
  $last_month_revenue = $row['total'] ? $row['total'] : 0;
}

// Calculate revenue growth
$revenue_growth = 0;
if ($last_month_revenue > 0) {
  $revenue_growth = round((($month_revenue - $last_month_revenue) / $last_month_revenue) * 100, 1);
}

// Total Orders
$sql_orders = "SELECT COUNT(*) as count FROM orders_new";
$res_orders = mysqli_query($conn, $sql_orders);
if ($res_orders && $row = mysqli_fetch_assoc($res_orders)) {
  $total_orders = $row['count'];
}

// Today's Orders
$sql_today_orders = "SELECT COUNT(*) as count FROM orders_new WHERE DATE(created_at) = '$today'";
$res_today_orders = mysqli_query($conn, $sql_today_orders);
if ($res_today_orders && $row = mysqli_fetch_assoc($res_today_orders)) {
  $today_orders = $row['count'];
}

// Pending Orders
$sql_pending = "SELECT COUNT(*) as count FROM orders_new WHERE status = 'pending'";
$res_pending = mysqli_query($conn, $sql_pending);
if ($res_pending && $row = mysqli_fetch_assoc($res_pending)) {
  $pending_orders = $row['count'];
}

// Last Month Orders for growth calculation
$sql_last_month_orders = "SELECT COUNT(*) as count FROM orders_new 
                          WHERE DATE(created_at) >= '$last_month_start' 
                          AND DATE(created_at) <= '$last_month_end'";
$res_last_month_orders = mysqli_query($conn, $sql_last_month_orders);
$last_month_orders = 0;
if ($res_last_month_orders && $row = mysqli_fetch_assoc($res_last_month_orders)) {
  $last_month_orders = $row['count'];
}

// Calculate order growth
$order_growth = 0;
if ($last_month_orders > 0) {
  $order_growth = round((($total_orders - $last_month_orders) / $last_month_orders) * 100, 1);
}

// Total Customers
$sql_customers = "SELECT COUNT(*) as count FROM users";
$res_customers = mysqli_query($conn, $sql_customers);
if ($res_customers && $row = mysqli_fetch_assoc($res_customers)) {
  $total_customers = $row['count'];
}

// Last Month Customers
$sql_last_month_customers = "SELECT COUNT(*) as count FROM users 
                             WHERE DATE(created_at) >= '$last_month_start' 
                             AND DATE(created_at) <= '$last_month_end'";
$res_last_month_customers = mysqli_query($conn, $sql_last_month_customers);
$last_month_customers = 0;
if ($res_last_month_customers && $row = mysqli_fetch_assoc($res_last_month_customers)) {
  $last_month_customers = $row['count'];
}

// Calculate customer growth
$customer_growth = 0;
if ($last_month_customers > 0) {
  $customer_growth = round((($total_customers - $last_month_customers) / $last_month_customers) * 100, 1);
}

// Total Products
$sql_products = "SELECT COUNT(*) as count FROM products WHERE status = 1";
$res_products = mysqli_query($conn, $sql_products);
if ($res_products && $row = mysqli_fetch_assoc($res_products)) {
  $total_products = $row['count'];
}

// Last Month Products
$sql_last_month_products = "SELECT COUNT(*) as count FROM products 
                            WHERE DATE(added_on) >= '$last_month_start' 
                            AND DATE(added_on) <= '$last_month_end' 
                            AND status = 1";
$res_last_month_products = mysqli_query($conn, $sql_last_month_products);
$last_month_products = 0;
if ($res_last_month_products && $row = mysqli_fetch_assoc($res_last_month_products)) {
  $last_month_products = $row['count'];
}

// Calculate product growth
$product_growth = 0;
if ($last_month_products > 0) {
  $product_growth = round((($total_products - $last_month_products) / $last_month_products) * 100, 1);
}

// Get sales data for chart (last 30 days)
$sales_data = [];
$sales_chart_labels = [];
$sales_chart_data = [];
$orders_chart_data = [];

for ($i = 29; $i >= 0; $i--) {
  $date = date('Y-m-d', strtotime("-$i days"));
  $formatted_date = date('M j', strtotime($date));

  // Get sales for this date
  $sql_sales = "SELECT SUM(order_total) as sales, COUNT(*) as orders 
                  FROM orders_new 
                  WHERE DATE(created_at) = '$date' AND status = 'completed'";
  $res_sales = mysqli_query($conn, $sql_sales);
  $sales = 0;
  $orders = 0;
  if ($res_sales && $row = mysqli_fetch_assoc($res_sales)) {
    $sales = $row['sales'] ? $row['sales'] : 0;
    $orders = $row['orders'] ? $row['orders'] : 0;
  }

  $sales_data[] = [
    'date' => $formatted_date,
    'sales' => $sales,
    'orders' => $orders
  ];

  $sales_chart_labels[] = $formatted_date;
  $sales_chart_data[] = $sales;
  $orders_chart_data[] = $orders;
}

// Get top products by orders
$sql_top_products = "SELECT p.pro_id, p.pro_name, p.selling_price, p.pro_img, 
                     COUNT(o.id) as order_count,
                     SUM(o.order_total) as revenue
                     FROM products p
                     LEFT JOIN orders_new o ON o.products LIKE CONCAT('%', p.pro_name, '%')
                     WHERE p.status = 1
                     GROUP BY p.pro_id
                     ORDER BY order_count DESC, revenue DESC
                     LIMIT 5";
$res_top_products = mysqli_query($conn, $sql_top_products);
$top_products = [];
if ($res_top_products) {
  while ($row = mysqli_fetch_assoc($res_top_products)) {
    $top_products[] = $row;
  }
}

// Get recent orders
$sql_recent_orders = "SELECT o.*, 
                      SUBSTRING_INDEX(SUBSTRING_INDEX(o.products, '(', 1), '(', -1) as product_name
                      FROM orders_new o
                      ORDER BY o.created_at DESC 
                      LIMIT 5";
$res_recent_orders = mysqli_query($conn, $sql_recent_orders);
$recent_orders = [];
if ($res_recent_orders) {
  while ($row = mysqli_fetch_assoc($res_recent_orders)) {
    $recent_orders[] = $row;
  }
}

// Get order status breakdown
$sql_status_stats = "SELECT 
                     SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                     SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                     SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                     SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                     FROM orders_new";
$res_status_stats = mysqli_query($conn, $sql_status_stats);
$status_stats = mysqli_fetch_assoc($res_status_stats);

// Get payment method stats
$sql_payment_stats = "SELECT 
                      payment_method,
                      COUNT(*) as count,
                      SUM(order_total) as total
                      FROM orders_new 
                      WHERE payment_method IS NOT NULL
                      GROUP BY payment_method";
$res_payment_stats = mysqli_query($conn, $sql_payment_stats);
$payment_stats = [];
if ($res_payment_stats) {
  while ($row = mysqli_fetch_assoc($res_payment_stats)) {
    $payment_stats[] = $row;
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>Admin Dashboard | <?php echo htmlspecialchars($setting->get('site_name', 'Beastline')); ?></title>
  <link rel="icon" href="<?php echo htmlspecialchars($setting->get('favicon', 'assets/img/logo.png')); ?>" type="image/png">
<?php include "links.php"; ?>
  <!-- Bootstrap & Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <!-- ApexCharts -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.45.0/dist/apexcharts.css">
  <!-- Date Range Picker -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
  <!-- Animate CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

  <style>
    :root {
      --primary-color: #4e73df;
      --secondary-color: #1cc88a;
      --warning-color: #f6c23e;
      --info-color: #36b9cc;
      --danger-color: #e74a3b;
      --dark-color: #5a5c69;
      --light-color: #f8f9fc;
    }

    body {
      background-color: #f5f7fb;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .dashboard-wrapper {
      min-height: 100vh;
    }

    /* Stat Cards */
    .stat-card {
      border-radius: 15px;
      border: none;
      transition: all 0.3s ease;
      overflow: hidden;
      position: relative;
      box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
    }

    .stat-card .card-icon {
      position: absolute;
      right: 20px;
      top: 20px;
      font-size: 2.5rem;
      opacity: 0.2;
      z-index: 1;
    }

    .stat-card .card-body {
      position: relative;
      z-index: 2;
    }

    .stat-card .growth-indicator {
      font-size: 0.75rem;
      padding: 3px 10px;
      border-radius: 20px;
      display: inline-block;
    }

    .growth-up {
      background: rgba(28, 200, 138, 0.1);
      color: #1cc88a;
    }

    .growth-down {
      background: rgba(231, 74, 59, 0.1);
      color: #e74a3b;
    }

    /* Chart Cards */
    .chart-card {
      background: white;
      border-radius: 15px;
      padding: 20px;
      box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
      height: 100%;
    }

    .chart-card .card-header {
      background: transparent;
      border-bottom: 1px solid #e3e6f0;
      padding: 15px 0;
      margin-bottom: 15px;
    }

    /* Recent Orders Table */
    .recent-orders-table {
      background: white;
      border-radius: 15px;
      box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }

    .recent-orders-table .table {
      margin-bottom: 0;
    }

    .recent-orders-table .table th {
      border-top: none;
      font-weight: 600;
      color: #5a5c69;
      padding-top: 20px;
      padding-bottom: 20px;
    }

    .recent-orders-table .table td {
      padding-top: 15px;
      padding-bottom: 15px;
      vertical-align: middle;
    }

    .recent-orders-table .table tr:last-child td {
      border-bottom: none;
    }

    /* Status Badges */
    .status-badge {
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 500;
      text-transform: capitalize;
    }

    .status-pending {
      background-color: #fff2cc;
      color: #b38f00;
    }

    .status-completed {
      background-color: #d1f7c4;
      color: #0f7b0f;
    }

    .status-processing {
      background-color: #cce5ff;
      color: #004085;
    }

    .status-cancelled {
      background-color: #f8d7da;
      color: #842029;
    }

    /* Top Products */
    .top-product-item {
      display: flex;
      align-items: center;
      padding: 12px;
      border-radius: 10px;
      transition: all 0.3s ease;
      margin-bottom: 10px;
      border: 1px solid #e3e6f0;
    }

    .top-product-item:hover {
      background-color: #f8f9fc;
      transform: translateX(5px);
      border-color: var(--primary-color);
    }

    .top-product-item img {
      width: 50px;
      height: 50px;
      border-radius: 10px;
      object-fit: cover;
      margin-right: 15px;
    }

    .top-product-item .product-info {
      flex-grow: 1;
    }

    .top-product-item .product-stats {
      text-align: right;
      min-width: 100px;
    }

    /* Quick Actions */
    .quick-action-card {
      background: white;
      border-radius: 12px;
      padding: 25px;
      text-align: center;
      transition: all 0.3s ease;
      border: 2px solid transparent;
      height: 100%;
      box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .quick-action-card:hover {
      transform: translateY(-5px);
      border-color: var(--primary-color);
      box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .quick-action-card .action-icon {
      width: 60px;
      height: 60px;
      border-radius: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 15px;
      font-size: 24px;
      color: white;
    }

    /* Dashboard Header */
    .dashboard-header {
      background: white;
      border-radius: 15px;
      padding: 20px;
      margin-bottom: 30px;
      box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }

    /* Loading Overlay */
    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.9);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 9999;
      display: none;
    }

    .loader {
      width: 40px;
      height: 40px;
      border: 4px solid #f3f3f3;
      border-top: 4px solid var(--primary-color);
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% {
        transform: rotate(0deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }

    /* Custom Tooltip */
    .custom-tooltip {
      background: rgba(0, 0, 0, 0.8);
      color: white;
      padding: 8px 12px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 500;
    }

    /* Refresh Button */
    .refresh-btn {
      cursor: pointer;
      transition: transform 0.3s ease;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--primary-color);
      color: white;
    }

    .refresh-btn:hover {
      transform: rotate(180deg);
      background: #3a56c4;
    }

    /* Date Range Picker */
    .date-range-container {
      min-width: 250px;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
      .stat-card .card-icon {
        font-size: 2rem;
      }

      .dashboard-header {
        padding: 15px;
      }

      .date-range-container {
        min-width: 200px;
      }
    }

    /* Animation Classes */
    .animate-fade-in {
      animation: fadeIn 0.5s ease-in;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
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
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
      <div class="text-center">
        <div class="loader mb-3"></div>
        <p class="text-muted">Loading dashboard data...</p>
      </div>
    </div>

    <div class="container-fluid py-4">

      <!-- Dashboard Header -->
      <div class="dashboard-header animate-fade-in">
        <div class="row align-items-center">
          <div class="col-md-6">
            <h1 class="h3 mb-2 text-gray-800">
              <i class="fas fa-tachometer-alt me-2"></i>Dashboard Overview
            </h1>
            <p class="text-muted mb-0">
              Welcome back, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>!
              Here's what's happening with your store today.
            </p>
          </div>
          <div class="col-md-6">
            <div class="d-flex justify-content-end gap-3 align-items-center">
              <div class="date-range-container">
                <div class="input-group">
                  <span class="input-group-text bg-white border-end-0">
                    <i class="fas fa-calendar-alt text-primary"></i>
                  </span>
                  <input type="text" class="form-control border-start-0"
                    id="dateRangePicker" placeholder="Select date range">
                </div>
              </div>
              <button class="btn btn-outline-primary refresh-btn"
                onclick="refreshDashboard()"
                id="refreshBtn"
                title="Refresh Dashboard">
                <i class="fas fa-sync-alt"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Key Metrics -->
      <div class="row g-4 mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6">
          <div class="card stat-card border-left-primary shadow h-100">
            <div class="card-body">
              <i class="fas fa-dollar-sign card-icon text-primary"></i>
              <div class="text-uppercase text-primary fw-bold small mb-1">
                Total Revenue
              </div>
              <div class="h3 mb-2 fw-bold text-gray-800">
                ₹<?= number_format($total_revenue, 2) ?>
              </div>
              <div class="d-flex align-items-center">
                <span class="growth-indicator growth-up me-2">
                  <i class="fas fa-arrow-up me-1"></i>
                  <?= $revenue_growth > 0 ? '+' : '' ?><?= $revenue_growth ?>%
                </span>
                <span class="text-muted small">
                  <i class="fas fa-calendar-day me-1"></i>
                  ₹<?= number_format($month_revenue, 2) ?> this month
                </span>
              </div>
              <div class="mt-2 text-muted small">
                <i class="fas fa-sun me-1"></i>
                Today: ₹<?= number_format($today_revenue, 2) ?>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6">
          <div class="card stat-card border-left-success shadow h-100">
            <div class="card-body">
              <i class="fas fa-shopping-cart card-icon text-success"></i>
              <div class="text-uppercase text-success fw-bold small mb-1">
                Total Orders
              </div>
              <div class="h3 mb-2 fw-bold text-gray-800">
                <?= number_format($total_orders) ?>
              </div>
              <div class="d-flex align-items-center">
                <span class="growth-indicator <?= $order_growth >= 0 ? 'growth-up' : 'growth-down' ?> me-2">
                  <i class="fas fa-arrow-<?= $order_growth >= 0 ? 'up' : 'down' ?> me-1"></i>
                  <?= $order_growth > 0 ? '+' : '' ?><?= $order_growth ?>%
                </span>
                <span class="text-muted small">
                  <i class="fas fa-clock me-1"></i>
                  <?= $pending_orders ?> pending
                </span>
              </div>
              <div class="mt-2 text-muted small">
                <i class="fas fa-sun me-1"></i>
                Today: <?= $today_orders ?> orders
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6">
          <div class="card stat-card border-left-info shadow h-100">
            <div class="card-body">
              <i class="fas fa-users card-icon text-info"></i>
              <div class="text-uppercase text-info fw-bold small mb-1">
                Total Customers
              </div>
              <div class="h3 mb-2 fw-bold text-gray-800">
                <?= number_format($total_customers) ?>
              </div>
              <div class="d-flex align-items-center">
                <span class="growth-indicator <?= $customer_growth >= 0 ? 'growth-up' : 'growth-down' ?> me-2">
                  <i class="fas fa-arrow-<?= $customer_growth >= 0 ? 'up' : 'down' ?> me-1"></i>
                  <?= $customer_growth > 0 ? '+' : '' ?><?= $customer_growth ?>%
                </span>
                <span class="text-muted small">
                  <i class="fas fa-user-plus me-1"></i>
                  New this month
                </span>
              </div>
              <div class="mt-2">
                <a href="customers.php" class="text-decoration-none small">
                  <i class="fas fa-eye me-1"></i>View all customers
                </a>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6">
          <div class="card stat-card border-left-warning shadow h-100">
            <div class="card-body">
              <i class="fas fa-box card-icon text-warning"></i>
              <div class="text-uppercase text-warning fw-bold small mb-1">
                Total Products
              </div>
              <div class="h3 mb-2 fw-bold text-gray-800">
                <?= number_format($total_products) ?>
              </div>
              <div class="d-flex align-items-center">
                <span class="growth-indicator <?= $product_growth >= 0 ? 'growth-up' : 'growth-down' ?> me-2">
                  <i class="fas fa-arrow-<?= $product_growth >= 0 ? 'up' : 'down' ?> me-1"></i>
                  <?= $product_growth > 0 ? '+' : '' ?><?= $product_growth ?>%
                </span>
                <span class="text-muted small">
                  <i class="fas fa-star me-1"></i>
                  Active products
                </span>
              </div>
              <div class="mt-2">
                <a href="show-products.php" class="text-decoration-none small">
                  <i class="fas fa-boxes me-1"></i>Manage products
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts and Stats Row -->
      <div class="row g-4 mb-4">
        <!-- Sales Chart -->
        <div class="col-lg-8">
          <div class="chart-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h5 class="fw-bold mb-0">
                <i class="fas fa-chart-line me-2"></i>Sales Overview (Last 30 Days)
              </h5>
              <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm btn-outline-primary active"
                  onclick="updateChartPeriod(7)">7D</button>
                <button type="button" class="btn btn-sm btn-outline-primary"
                  onclick="updateChartPeriod(30)">30D</button>
                <button type="button" class="btn btn-sm btn-outline-primary"
                  onclick="updateChartPeriod(90)">90D</button>
              </div>
            </div>
            <div id="salesChart" style="min-height: 300px;"></div>
          </div>
        </div>

        <!-- Order Status Breakdown -->
        <div class="col-lg-4">
          <div class="chart-card">
            <h5 class="fw-bold mb-4">
              <i class="fas fa-chart-pie me-2"></i>Order Status
            </h5>
            <div id="orderStatusChart" style="min-height: 300px;"></div>
            <div class="mt-4">
              <div class="row text-center">
                <div class="col-6 mb-3">
                  <div class="p-3 bg-light rounded">
                    <div class="h4 mb-1 text-success">
                      <?= $status_stats['completed'] ?? 0 ?>
                    </div>
                    <div class="small text-muted">Completed</div>
                  </div>
                </div>
                <div class="col-6 mb-3">
                  <div class="p-3 bg-light rounded">
                    <div class="h4 mb-1 text-warning">
                      <?= $status_stats['pending'] ?? 0 ?>
                    </div>
                    <div class="small text-muted">Pending</div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="p-3 bg-light rounded">
                    <div class="h4 mb-1 text-primary">
                      <?= $status_stats['processing'] ?? 0 ?>
                    </div>
                    <div class="small text-muted">Processing</div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="p-3 bg-light rounded">
                    <div class="h4 mb-1 text-danger">
                      <?= $status_stats['cancelled'] ?? 0 ?>
                    </div>
                    <div class="small text-muted">Cancelled</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Orders and Quick Actions -->
      <div class="row g-4 mb-4">
        <!-- Recent Orders -->
        <div class="col-lg-8">
          <div class="recent-orders-table p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h5 class="fw-bold mb-0">
                <i class="fas fa-history me-2"></i>Recent Orders
              </h5>
              <a href="orders.php" class="btn btn-sm btn-primary">
                <i class="fas fa-eye me-1"></i>View All Orders
              </a>
            </div>
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($recent_orders)): ?>
                    <?php foreach ($recent_orders as $order): ?>
                      <tr>
                        <td class="fw-bold">
                          #<?= htmlspecialchars($order['order_id']) ?>
                        </td>
                        <td>
                          <div class="fw-bold">
                            <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?>
                          </div>
                          <small class="text-muted">
                            <?= htmlspecialchars($order['email']) ?>
                          </small>
                        </td>
                        <td>
                          <div class="text-truncate" style="max-width: 150px;">
                            <?= htmlspecialchars($order['product_name'] ?? $order['products']) ?>
                          </div>
                        </td>
                        <td class="fw-bold">
                          ₹<?= number_format($order['order_total'], 2) ?>
                        </td>
                        <td>
                          <span class="status-badge status-<?= $order['status'] ?>">
                            <?= ucfirst($order['status']) ?>
                          </span>
                        </td>
                        <td>
                          <?= date('M d, Y', strtotime($order['created_at'])) ?>
                          <br>
                          <small class="text-muted">
                            <?= date('h:i A', strtotime($order['created_at'])) ?>
                          </small>
                        </td>
                        <td>
                          <a href="order_details.php?id=<?= $order['id'] ?>"
                            class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye"></i>
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="7" class="text-center py-4">
                        <div class="text-muted">
                          <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                          <h5>No orders yet</h5>
                          <p>Start selling to see orders here</p>
                        </div>
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Quick Actions & Top Products -->
        <div class="col-lg-4">
          <!-- Quick Actions -->
          <div class="card shadow mb-4">
            <div class="card-header bg-white py-3">
              <h5 class="fw-bold mb-0">
                <i class="fas fa-bolt me-2"></i>Quick Actions
              </h5>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-6">
                  <a href="add-products.php" class="quick-action-card text-decoration-none">
                    <div class="action-icon bg-primary">
                      <i class="fas fa-plus"></i>
                    </div>
                    <h6 class="mb-2">Add Product</h6>
                    <p class="text-muted small mb-0">Add new product</p>
                  </a>
                </div>
                <div class="col-6">
                  <a href="orders.php" class="quick-action-card text-decoration-none">
                    <div class="action-icon bg-success">
                      <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h6 class="mb-2">Manage Orders</h6>
                    <p class="text-muted small mb-0">View all orders</p>
                  </a>
                </div>
                <div class="col-6">
                  <a href="show-products.php" class="quick-action-card text-decoration-none">
                    <div class="action-icon bg-warning">
                      <i class="fas fa-boxes"></i>
                    </div>
                    <h6 class="mb-2">Products</h6>
                    <p class="text-muted small mb-0">Manage products</p>
                  </a>
                </div>
                <div class="col-6">
                  <a href="reports.php" class="quick-action-card text-decoration-none">
                    <div class="action-icon bg-info">
                      <i class="fas fa-chart-bar"></i>
                    </div>
                    <h6 class="mb-2">Reports</h6>
                    <p class="text-muted small mb-0">View reports</p>
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Top Products -->
          <div class="card shadow">
            <div class="card-header bg-white py-3">
              <div class="d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">
                  <i class="fas fa-fire me-2"></i>Top Products
                </h5>
                <a href="show-products.php" class="btn btn-sm btn-outline-primary">
                  View All
                </a>
              </div>
            </div>
            <div class="card-body">
              <?php if (!empty($top_products)): ?>
                <?php foreach ($top_products as $product): ?>
                  <div class="top-product-item">
                    <img src="assets/img/uploads/<?= htmlspecialchars($product['pro_img'] ?? 'default-product.jpg') ?>"
                      alt="<?= htmlspecialchars($product['pro_name']) ?>"
                      onerror="this.src='assets/img/default-product.jpg'">
                    <div class="product-info">
                      <h6 class="mb-1 fw-bold">
                        <?= htmlspecialchars($product['pro_name']) ?>
                      </h6>
                      <div class="text-muted small">
                        ₹<?= number_format($product['selling_price'], 2) ?>
                      </div>
                    </div>
                    <div class="product-stats">
                      <div class="text-primary fw-bold">
                        <?= $product['order_count'] ?? 0 ?> sold
                      </div>
                      <div class="text-success small">
                        ₹<?= number_format($product['revenue'] ?? 0, 2) ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="text-center py-4">
                  <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                  <p class="text-muted">No product data available</p>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment Methods Stats -->
      <?php if (!empty($payment_stats)): ?>
        <div class="row mb-4">
          <div class="col-12">
            <div class="card shadow">
              <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0">
                  <i class="fas fa-credit-card me-2"></i>Payment Methods
                </h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <?php foreach ($payment_stats as $payment): ?>
                    <div class="col-md-3 col-6 mb-3">
                      <div class="p-3 border rounded text-center">
                        <div class="h4 mb-1 text-primary">
                          <?= $payment['count'] ?>
                        </div>
                        <div class="small text-muted mb-1">
                          <?= ucfirst($payment['payment_method']) ?>
                        </div>
                        <div class="text-success fw-bold">
                          ₹<?= number_format($payment['total'], 2) ?>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

    </div>
    <?php include "includes/footer.php"; ?>
  </section>
  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.0"></script>
  <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

  <script>
    // Initialize date range picker
    $(function() {
      $('#dateRangePicker').daterangepicker({
        opens: 'left',
        startDate: moment().subtract(29, 'days'),
        endDate: moment(),
        ranges: {
          'Today': [moment(), moment()],
          'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
          'Last 7 Days': [moment().subtract(6, 'days'), moment()],
          'Last 30 Days': [moment().subtract(29, 'days'), moment()],
          'This Month': [moment().startOf('month'), moment().endOf('month')],
          'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        locale: {
          format: 'YYYY-MM-DD'
        }
      }, function(start, end, label) {
        filterDashboardByDate(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
      });
    });

    // Sales Chart
    var salesChart = new ApexCharts(document.querySelector("#salesChart"), {
      series: [{
        name: "Revenue",
        data: <?= json_encode($sales_chart_data) ?>
      }, {
        name: "Orders",
        data: <?= json_encode($orders_chart_data) ?>
      }],
      chart: {
        height: 300,
        type: 'area',
        toolbar: {
          show: true,
          tools: {
            download: true,
            selection: false,
            zoom: false,
            zoomin: false,
            zoomout: false,
            pan: false,
            reset: false
          }
        }
      },
      colors: ['#4e73df', '#1cc88a'],
      dataLabels: {
        enabled: false
      },
      stroke: {
        curve: 'smooth',
        width: 2
      },
      xaxis: {
        categories: <?= json_encode($sales_chart_labels) ?>,
        labels: {
          style: {
            fontSize: '12px'
          }
        }
      },
      yaxis: [{
        title: {
          text: 'Revenue (₹)'
        },
        labels: {
          formatter: function(value) {
            return '₹' + value.toLocaleString('en-IN');
          }
        }
      }, {
        opposite: true,
        title: {
          text: 'Orders'
        }
      }],
      tooltip: {
        y: {
          formatter: function(value, {
            seriesIndex
          }) {
            if (seriesIndex === 0) {
              return '₹' + value.toLocaleString('en-IN');
            }
            return value;
          }
        }
      },
      legend: {
        position: 'top'
      },
      grid: {
        borderColor: '#f1f1f1'
      }
    });
    salesChart.render();

    // Order Status Chart
    var orderStatusChart = new ApexCharts(document.querySelector("#orderStatusChart"), {
      series: [
        <?= $status_stats['completed'] ?? 0 ?>,
        <?= $status_stats['pending'] ?? 0 ?>,
        <?= $status_stats['processing'] ?? 0 ?>,
        <?= $status_stats['cancelled'] ?? 0 ?>
      ],
      chart: {
        height: 200,
        type: 'donut'
      },
      labels: ['Completed', 'Pending', 'Processing', 'Cancelled'],
      colors: ['#1cc88a', '#f6c23e', '#4e73df', '#e74a3b'],
      legend: {
        position: 'bottom',
        horizontalAlign: 'center'
      },
      plotOptions: {
        pie: {
          donut: {
            size: '70%',
            labels: {
              show: true,
              total: {
                show: true,
                label: 'Total Orders',
                formatter: function(w) {
                  return <?= $total_orders ?>;
                }
              }
            }
          }
        }
      },
      dataLabels: {
        enabled: false
      }
    });
    orderStatusChart.render();

    // Refresh dashboard function
    function refreshDashboard() {
      const btn = document.getElementById('refreshBtn');
      const overlay = document.getElementById('loadingOverlay');

      // Show loading
      btn.innerHTML = '<div class="loader-sm"></div>';
      btn.disabled = true;
      overlay.style.display = 'flex';

      // Add small loader style
      const style = document.createElement('style');
      style.innerHTML = '.loader-sm { width: 16px; height: 16px; border: 2px solid #fff; border-top: 2px solid transparent; border-radius: 50%; animation: spin 1s linear infinite; }';
      document.head.appendChild(style);

      // Simulate refresh
      setTimeout(() => {
        location.reload();
      }, 1000);
    }

    // Filter dashboard by date range
    function filterDashboardByDate(startDate, endDate) {
      showLoading();

      // In a real application, you would make an AJAX call here
      console.log('Filtering from', startDate, 'to', endDate);

      // Simulate API call
      setTimeout(() => {
        hideLoading();
        // Show notification
        showNotification('success', 'Dashboard filtered for selected date range');
      }, 1500);
    }

    // Update chart period
    function updateChartPeriod(days) {
      showLoading();

      // Update active button
      document.querySelectorAll('.btn-group .btn').forEach(btn => {
        btn.classList.remove('active');
      });
      event.target.classList.add('active');

      // In a real application, fetch new data for the selected period
      setTimeout(() => {
        hideLoading();
        showNotification('info', `Chart updated for last ${days} days`);
      }, 1000);
    }

    // Show loading
    function showLoading() {
      document.getElementById('loadingOverlay').style.display = 'flex';
    }

    // Hide loading
    function hideLoading() {
      document.getElementById('loadingOverlay').style.display = 'none';
    }

    // Show notification
    function showNotification(type, message) {
      // Create notification element
      const notification = document.createElement('div');
      notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
      notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
      notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

      document.body.appendChild(notification);

      // Auto remove after 5 seconds
      setTimeout(() => {
        notification.remove();
      }, 5000);
    }

    // Check for new orders (simulated)
    function checkNewOrders() {
      // In a real application, this would be an AJAX call
      const newOrdersCount = Math.floor(Math.random() * 3); // Simulated

      if (newOrdersCount > 0) {
        // Update notification badge
        const badge = document.querySelector('.notification-badge');
        if (badge) {
          badge.textContent = newOrdersCount;
          badge.style.display = 'inline-block';
          badge.classList.add('animate__animated', 'animate__pulse');

          // Show toast
          showNotification('info', `You have ${newOrdersCount} new order(s)!`);
        }
      }
    }

    // Initialize animations on page load
    document.addEventListener('DOMContentLoaded', function() {
      // Add animation to stat cards
      const cards = document.querySelectorAll('.stat-card');
      cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('animate-fade-in');
      });

      // Check for new orders every 60 seconds
      setInterval(checkNewOrders, 60000);

      // Initial check
      checkNewOrders();
    });

    // Handle window resize for charts
    window.addEventListener('resize', function() {
      setTimeout(() => {
        salesChart.updateOptions({
          chart: {
            height: 300
          }
        });
        orderStatusChart.updateOptions({
          chart: {
            height: 200
          }
        });
      }, 300);
    });
  </script>

</body>

</html>