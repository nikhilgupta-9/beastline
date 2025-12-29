<?php
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';
require_once __DIR__ . '/models/setting.php';
// require_once __DIR__ . '/models/Policy.php';

// Initialize
$setting = new Setting($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>Admin Panel | Dashboard</title>
  <link rel="icon" href="<?php echo htmlspecialchars($setting->get('favicon', 'assets/img/logo.png')); ?>" type="image/png">

  <!-- Bootstrap & Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- ApexCharts -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.css">

  <link rel="stylesheet" href="css/style.css" /> <!-- Custom Stylesheet -->
  <?php include "links.php"; ?>

  <style>
    /* Ensure full page height */
    html,
    body {
      height: 100%;
      display: flex;
      flex-direction: column;
    }

    /* Wrapper to push content down */
    .wrapper {
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .main_content {
      flex: 1;
    }

    /* Ensure footer sticks at bottom */
    footer {
      position: relative;
      bottom: 0;
      background: #f8f9fa;
      padding: 15px 0;
      text-align: center;
      width: 100%;
    }

    /* Custom Card Styles */
    .stat-card {
      transition: all 0.3s ease;
      border-radius: 10px;
      border-left: 4px solid;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .stat-card .card-icon {
      font-size: 2rem;
      opacity: 0.7;
    }

    .revenue-card {
      border-left-color: #4e73df;
    }

    .orders-card {
      border-left-color: #1cc88a;
    }

    .customers-card {
      border-left-color: #36b9cc;
    }

    .products-card {
      border-left-color: #f6c23e;
    }

    .chart-container {
      position: relative;
      height: 300px;
    }

    .activity-feed {
      max-height: 400px;
      overflow-y: auto;
    }

    .activity-item {
      border-left: 3px solid #4e73df;
      padding-left: 15px;
      margin-bottom: 15px;
    }

    .activity-time {
      font-size: 0.8rem;
      color: #6c757d;
    }

    .top-product-img {
      width: 40px;
      height: 40px;
      object-fit: cover;
      border-radius: 50%;
    }
  </style>
</head>
</head>

<body class="bg-light">

  <div class="wrapper">
    <?php
    include "includes/header.php";
    ?>

    <section class="main_content dashboard_part">
      <div class="container-fluid g-0">
        <div class="row">
          <div class="col-lg-12 p-0">
            <?php include "includes/top_nav.php"; ?>
          </div>
        </div>
      </div>

      <div class="container-fluid">
        <!-- Dashboard Header -->
        <div class="row mt-4">
          <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center bg-white p-3 shadow rounded">
              <h3 class="m-0 fw-bold text-primary"><i class="fas fa-chart-line me-2"></i> Dashboard Overview</h3>
              <div class="d-flex">
                <div class="input-group me-2" style="width: 250px;">
                  <span class="input-group-text bg-white"><i class="fas fa-calendar-alt"></i></span>
                  <input type="text" class="form-control" id="dateRangePicker" placeholder="Select date range">
                </div>
                <button class="btn btn-primary"><i class="fas fa-download me-2"></i>Export</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Key Metrics -->
        <div class="row mt-4">
          <?php
          // Get counts from database
          $total_revenue = 0;
          $total_orders = 0;
          $total_customers = 0;
          $total_products = 0;

          // Revenue (assuming we have orders data)
          $sql_orders = "SELECT SUM(order_total) as total FROM orders_new WHERE status = 'completed'";
          $res_orders = mysqli_query($conn, $sql_orders);
          if ($res_orders) {
            $row = mysqli_fetch_assoc($res_orders);
            $total_revenue = $row['total'] ? $row['total'] : 0;
          }

          // Total orders
          $sql_order_count = "SELECT COUNT(*) as count FROM orders_new";
          $res_order_count = mysqli_query($conn, $sql_order_count);
          if ($res_order_count) {
            $row = mysqli_fetch_assoc($res_order_count);
            $total_orders = $row['count'];
          }

          // Total customers
          $sql_cust = "SELECT COUNT(*) as count FROM users";
          $res_cust = mysqli_query($conn, $sql_cust);
          if ($res_cust) {
            $row = mysqli_fetch_assoc($res_cust);
            $total_customers = $row['count'];
          }

          // Total products
          $sql_pro = "SELECT COUNT(*) as count FROM products";
          $res_pro = mysqli_query($conn, $sql_pro);
          if ($res_pro) {
            $row = mysqli_fetch_assoc($res_pro);
            $total_products = $row['count'];
          }
          ?>



          <!-- Revenue Card -->
          <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card revenue-card shadow h-100 py-2">
              <div class="card-body">
                <div class="row no-gutters align-items-center">
                  <div class="col me-2">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                      Total Revenue</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">₹<?= number_format($total_revenue, 2) ?></div>
                    <div class="mt-2 mb-0 text-muted text-xs">
                      <span class="text-success me-2"><i class="fas fa-arrow-up me-1"></i> 12%</span>
                      <span>Since last month</span>
                    </div>
                  </div>
                  <div class="col-auto">
                    <i class="fas fa-dollar-sign card-icon text-primary"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Orders Card -->
          <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card orders-card shadow h-100 py-2">
              <div class="card-body">
                <div class="row no-gutters align-items-center">
                  <div class="col me-2">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                      Total Orders</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_orders ?></div>
                    <div class="mt-2 mb-0 text-muted text-xs">
                      <span class="text-success me-2"><i class="fas fa-arrow-up me-1"></i> 8%</span>
                      <span>Since last month</span>
                    </div>
                  </div>
                  <div class="col-auto">
                    <i class="fas fa-shopping-cart card-icon text-success"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Customers Card -->
          <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card customers-card shadow h-100 py-2">
              <div class="card-body">
                <div class="row no-gutters align-items-center">
                  <div class="col me-2">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                      Total Customers</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_customers ?></div>
                    <div class="mt-2 mb-0 text-muted text-xs">
                      <span class="text-danger me-2"><i class="fas fa-arrow-down me-1"></i> 2%</span>
                      <span>Since last month</span>
                    </div>
                  </div>
                  <div class="col-auto">
                    <i class="fas fa-users card-icon text-info"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Products Card -->
          <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card products-card shadow h-100 py-2">
              <div class="card-body">
                <div class="row no-gutters align-items-center">
                  <div class="col me-2">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                      Total Products</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_products ?></div>
                    <div class="mt-2 mb-0 text-muted text-xs">
                      <span class="text-success me-2"><i class="fas fa-arrow-up me-1"></i> 15%</span>
                      <span>Since last month</span>
                    </div>
                  </div>
                  <div class="col-auto">
                    <i class="fas fa-boxes card-icon text-warning"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
          <div class="col-12">
            <h4 class="mb-3 text-secondary"><i class="fas fa-bolt"></i> Quick Actions</h4>
          </div>

          <div class="col-lg-3 col-md-6 mb-4">
            <a href="add-products.php" class="card action-card shadow-sm border-0 text-center text-decoration-none">
              <div class="card-body">
                <div class="icon-circle bg-primary text-white mb-3">
                  <i class="fas fa-plus"></i>
                </div>
                <h5 class="card-title">Add Product</h5>
                <p class="text-muted small">Add new product to inventory</p>
              </div>
            </a>
          </div>

          <div class="col-lg-3 col-md-6 mb-4">
            <a href="add_contact.php" class="card action-card shadow-sm border-0 text-center text-decoration-none">
              <div class="card-body">
                <div class="icon-circle bg-success text-white mb-3">
                  <i class="fas fa-blog"></i>
                </div>
                <h5 class="card-title">Contact Details</h5>
                <p class="text-muted small">Publish Contact</p>
              </div>
            </a>
          </div>

          <div class="col-lg-3 col-md-6 mb-4">
            <a href="new-leads.php" class="card action-card shadow-sm border-0 text-center text-decoration-none">
              <div class="card-body">
                <div class="icon-circle bg-info text-white mb-3">
                  <i class="fas fa-envelope"></i>
                </div>
                <h5 class="card-title">View Inquiries</h5>
                <p class="text-muted small">Check customer inquiries</p>
              </div>
            </a>
          </div>

          <div class="col-lg-3 col-md-6 mb-4">
            <a href="show-products.php" class="card action-card shadow-sm border-0 text-center text-decoration-none">
              <div class="card-body">
                <div class="icon-circle bg-warning text-white mb-3">
                  <i class="fas fa-boxes"></i>
                </div>
                <h5 class="card-title">Manage Products</h5>
                <p class="text-muted small">View and edit products</p>
              </div>
            </a>
          </div>
        </div>
        <!-- Content Row -->
        <div class="row">
          <!-- Recent Orders -->
          <div class="col-lg-8 mb-4">
            <div class="card shadow mb-4">
              <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Recent Orders</h6>
                <a href="orders.php" class="btn btn-sm btn-primary">View All</a>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead class="table-light">
                      <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $sql_recent_orders = "SELECT * FROM orders_new ORDER BY created_at DESC LIMIT 5";
                      $res_recent_orders = mysqli_query($conn, $sql_recent_orders);
                      while ($order = mysqli_fetch_assoc($res_recent_orders)) {
                        $status_class = '';
                        if ($order['status'] == 'completed')
                          $status_class = 'success';
                        elseif ($order['status'] == 'pending')
                          $status_class = 'warning';
                        else
                          $status_class = 'danger';

                        echo "<tr>
                          <td>#{$order['order_id']}</td>
                          <td>{$order['first_name']} {$order['last_name']}</td>
                          <td>" . date('M d, Y', strtotime($order['created_at'])) . "</td>
                          <td>₹{$order['order_total']}</td>
                          <td><span class='badge bg-{$status_class}'>{$order['status']}</span></td>
                          <td><a href='order_details.php?id={$order['id']}' class='btn btn-sm btn-outline-primary'>View</a></td>
                        </tr>";
                      }
                      ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

        </div>


      </div>
    </section>

    <footer>
      <?php include "includes/footer.php"; ?>
    </footer>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<script>
  // Simulate new order notification (replace with real WebSocket/API implementation)
  document.addEventListener('DOMContentLoaded', function() {
    // Notification badge elements
    const orderBadge = document.getElementById('order-notification-badge');
    const unreadCount = document.getElementById('unread-count');
    const notificationContainer = document.getElementById('notification-container');
    const toast = new bootstrap.Toast(document.getElementById('newOrderToast'));

    // Demo: Simulate a new order every 30 seconds for testing
    setInterval(simulateNewOrder, 30000);

    // Initial load of notifications
    loadNotifications();

    function simulateNewOrder() {
      // Generate random order data
      const orderId = Math.floor(10000 + Math.random() * 90000);
      const amount = (Math.random() * 500 + 20).toFixed(2);
      const customers = ['John Smith', 'Emma Johnson', 'Michael Brown', 'Sarah Davis', 'Robert Wilson'];
      const customer = customers[Math.floor(Math.random() * customers.length)];

      // Update toast
      document.getElementById('toast-order-id').textContent = orderId;
      document.getElementById('toast-order-amount').textContent = amount;
      document.getElementById('toast-customer-name').textContent = customer;

      // Show toast with pulse animation
      toast.show();

      // Add to notifications
      addNotification({
        id: Date.now(),
        type: 'order',
        title: 'New Order Received',
        content: `Order #${orderId} from ${customer} for $${amount}`,
        time: 'Just now',
        unread: true
      });

      // Update badge with pulse animation
      updateBadge();
    }

    function loadNotifications() {
      // In a real app, you would fetch this from your API
      const notifications = [{
          id: 1,
          type: 'order',
          title: 'Order Shipped',
          content: 'Order #10244 has been shipped to customer',
          time: '2 hours ago',
          unread: false
        },
        {
          id: 2,
          type: 'system',
          title: 'System Update',
          content: 'New admin panel update available',
          time: '5 hours ago',
          unread: false
        }
      ];

      renderNotifications(notifications);
    }

    function addNotification(notification) {
      // Get current notifications
      const notifications = Array.from(notificationContainer.children)
        .filter(el => !el.classList.contains('empty-notifications'))
        .map(el => ({
          id: el.dataset.id,
          type: el.dataset.type,
          title: el.querySelector('h5').textContent,
          content: el.querySelector('p').textContent,
          time: el.querySelector('.notification-time').textContent,
          unread: el.classList.contains('unread')
        }));

      // Add new notification to beginning
      notifications.unshift(notification);

      // Re-render
      renderNotifications(notifications);
    }

    function renderNotifications(notifications) {
      if (notifications.length === 0) {
        notificationContainer.innerHTML = `
                <div class="empty-notifications text-center py-4">
                    <i class="fas fa-bell-slash fa-2x text-muted"></i>
                    <p class="mt-2">No new notifications</p>
                </div>
            `;
        return;
      }

      notificationContainer.innerHTML = notifications.map(notif => `
            <div class="single_notify ${notif.unread ? 'unread' : ''}" data-id="${notif.id}" data-type="${notif.type}">
                <div class="notify_thumb">
                    <a href="#">
                        <img src="assets/img/icon/${notif.type === 'order' ? 'shopping-cart' : 'bell'}.png" alt>
                    </a>
                </div>
                <div class="notify_content">
                    <a href="#">
                        <h5>${notif.title}</h5>
                    </a>
                    <p>${notif.content}</p>
                    <small class="text-muted notification-time">${notif.time}</small>
                </div>
            </div>
        `).join('');

      // Update badge count
      updateBadge();
    }

    function updateBadge() {
      const unreadNotifications = document.querySelectorAll('.single_notify.unread').length;
      orderBadge.textContent = unreadNotifications;
      unreadCount.textContent = `${unreadNotifications} new`;

      // Add pulse animation if there are new notifications
      if (unreadNotifications > 0) {
        orderBadge.classList.add('pulse');
        setTimeout(() => orderBadge.classList.remove('pulse'), 500);
      }
    }

    // Mark as read when clicked
    notificationContainer.addEventListener('click', function(e) {
      const notification = e.target.closest('.single_notify');
      if (notification && notification.classList.contains('unread')) {
        notification.classList.remove('unread');
        updateBadge();
      }
    });

    // Mark all as read
    document.querySelector('.mark-all-read')?.addEventListener('click', function(e) {
      e.preventDefault();
      document.querySelectorAll('.single_notify.unread').forEach(el => {
        el.classList.remove('unread');
      });
      updateBadge();
    });
  });
</script>