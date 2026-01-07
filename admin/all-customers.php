<?php
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';
require_once __DIR__ . '/models/Setting.php';

// Initialize
$setting = new Setting($conn);

// Get stats
$stats = [];
$stats['total_users'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM `users`"))['total'];
$stats['total_visitors'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT session_id) as total FROM `visitor_tracking`"))['total'];
$stats['active_today'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) as total FROM `visitor_tracking` WHERE DATE(created_at) = CURDATE() AND user_id IS NOT NULL"))['total'];
$stats['total_orders'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM `orders`"))['total'];

// Handle filters
$user_type = $_GET['user_type'] ?? 'all'; // all, users, visitors
$search = $_GET['search'] ?? '';
$date_range = $_GET['date_range'] ?? 'all'; // today, week, month, all

// Build query based on filters
$where_conditions = [];
$params = [];
$types = "";

// User type filter
if ($user_type === 'users') {
    $where_conditions[] = "u.id IS NOT NULL";
} elseif ($user_type === 'visitors') {
    $where_conditions[] = "u.id IS NULL";
}

// Search filter
if (!empty($search)) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR u.mobile LIKE ? OR va.ip_address LIKE ? OR va.page_url LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term, $search_term]);
    $types .= str_repeat('s', 5);
}

// Date range filter
$date_condition = "";
switch ($date_range) {
    case 'today':
        $date_condition = "AND DATE(va.created_at) = CURDATE()";
        break;
    case 'week':
        $date_condition = "AND va.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $date_condition = "AND va.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        break;
}

// Get activities with user info
$sql = "SELECT 
            va.*,
            u.id as user_id,
            u.name,
            u.email,
            u.mobile,
            u.created_at as user_created,
            (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) as order_count,
            (SELECT COUNT(*) FROM visitor_tracking va2 WHERE va2.session_id = va.session_id) as activity_count,
            (SELECT MAX(created_at) FROM visitor_tracking va3 WHERE va3.session_id = va.session_id) as last_activity
        FROM visitor_tracking va
        LEFT JOIN users u ON va.user_id = u.id
        " . (!empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "") . "
        GROUP BY va.session_id
        ORDER BY va.created_at DESC
        LIMIT 100";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Customer Management | Admin Panel</title>
    <link rel="icon" href="<?php echo htmlspecialchars($setting->get('favicon', 'assets/img/logo.png')); ?>" type="image/png">
    <?php include "links.php"; ?>
    
    <style>
        :root {
            --primary-color: #4e73df;
            --primary-light: #5d7ce0;
            --success-color: #1cc88a;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --info-color: #36b9cc;
            --light-bg: #f8f9fc;
            --dark-text: #5a5c69;
            --muted-text: #858796;
        }

        body {
            background-color: #f5f7fb;
            color: var(--dark-text);
            font-family: 'Nunito', sans-serif;
        }

        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }

        .avatar-user {
            background: linear-gradient(135deg, var(--success-color) 0%, #17a673 100%);
        }

        .avatar-visitor {
            background: linear-gradient(135deg, var(--info-color) 0%, #258ea6 100%);
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-user {
            background-color: rgba(28, 200, 138, 0.2);
            color: var(--success-color);
        }

        .badge-visitor {
            background-color: rgba(54, 185, 204, 0.2);
            color: var(--info-color);
        }

        .badge-active {
            background-color: rgba(28, 200, 138, 0.2);
            color: var(--success-color);
        }

        .badge-inactive {
            background-color: rgba(108, 117, 125, 0.2);
            color: #6c757d;
        }

        .stats-card {
            background: white;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            padding: 1.5rem;
            height: 100%;
        }

        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 0.35rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .icon-primary {
            background: rgba(78, 115, 223, 0.1);
            color: var(--primary-color);
        }

        .icon-success {
            background: rgba(28, 200, 138, 0.1);
            color: var(--success-color);
        }

        .icon-info {
            background: rgba(54, 185, 204, 0.1);
            color: var(--info-color);
        }

        .icon-warning {
            background: rgba(246, 194, 62, 0.1);
            color: var(--warning-color);
        }

        .filter-section {
            background: #f8f9fc;
            padding: 1rem;
            border-radius: 0.35rem;
            margin-bottom: 1.5rem;
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--muted-text);
            margin-bottom: 0.25rem;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.5px;
            padding: 1rem;
            white-space: nowrap;
        }

        .table td {
            vertical-align: middle;
            padding: 0.75rem;
            border: 1px solid rgba(137, 139, 141, 0.15);
        }

        .activity-item {
            background: #f8f9fc;
            border-radius: 0.25rem;
            padding: 0.5rem;
            margin-bottom: 0.25rem;
            font-size: 0.85rem;
        }

        .page-url {
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
            vertical-align: middle;
        }

        .device-badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 0.25rem;
            background: #e9ecef;
            color: var(--dark-text);
        }

        .location-cell {
            max-width: 150px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .order-count {
            display: inline-block;
            width: 24px;
            height: 24px;
            line-height: 24px;
            text-align: center;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .last-seen {
            font-size: 0.85rem;
            color: var(--muted-text);
        }

        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .table-responsive table {
                min-width: 800px;
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

        <div class="main_content_iner">
            <div class="container-fluid p-0">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-xl-3 col-md-6">
                                <div class="stats-card">
                                    <div class="d-flex align-items-start">
                                        <div class="stats-icon icon-primary">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="ms-3">
                                            <h5 class="mb-1"><?= number_format($stats['total_users']) ?></h5>
                                            <p class="text-muted mb-0">Registered Users</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="stats-card">
                                    <div class="d-flex align-items-start">
                                        <div class="stats-icon icon-info">
                                            <i class="fas fa-globe"></i>
                                        </div>
                                        <div class="ms-3">
                                            <h5 class="mb-1"><?= number_format($stats['total_visitors']) ?></h5>
                                            <p class="text-muted mb-0">Unique Visitors</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="stats-card">
                                    <div class="d-flex align-items-start">
                                        <div class="stats-icon icon-success">
                                            <i class="fas fa-user-check"></i>
                                        </div>
                                        <div class="ms-3">
                                            <h5 class="mb-1"><?= number_format($stats['active_today']) ?></h5>
                                            <p class="text-muted mb-0">Active Today</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="stats-card">
                                    <div class="d-flex align-items-start">
                                        <div class="stats-icon icon-warning">
                                            <i class="fas fa-shopping-cart"></i>
                                        </div>
                                        <div class="ms-3">
                                            <h5 class="mb-1"><?= number_format($stats['total_orders']) ?></h5>
                                            <p class="text-muted mb-0">Total Orders</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="white_card card_height_100 mb_30">
                            <div class="card-header card-header-light">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <div class="mb-2 mb-md-0">
                                        <h3 class="mb-0 fw-bold">Customer Management</h3>
                                        <p class="text-muted mb-0">Manage users and visitors with their activities</p>
                                    </div>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <!-- Search Form -->
                                        <form method="GET" class="d-flex">
                                            <div class="input-group" style="max-width: 300px;">
                                                <input type="text" name="search" class="form-control" 
                                                       placeholder="Search by name, email, IP..." 
                                                       value="<?= htmlspecialchars($search) ?>">
                                                <button class="btn btn-primary" type="submit">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Filter Section -->
                            <div class="filter-section">
                                <form method="GET" action="">
                                    <div class="filter-row">
                                        <div class="filter-group">
                                            <label class="filter-label">User Type</label>
                                            <select name="user_type" class="form-select form-select-sm">
                                                <option value="all" <?= $user_type === 'all' ? 'selected' : '' ?>>All (Users & Visitors)</option>
                                                <option value="users" <?= $user_type === 'users' ? 'selected' : '' ?>>Registered Users Only</option>
                                                <option value="visitors" <?= $user_type === 'visitors' ? 'selected' : '' ?>>Visitors Only</option>
                                            </select>
                                        </div>
                                        
                                        <div class="filter-group">
                                            <label class="filter-label">Date Range</label>
                                            <select name="date_range" class="form-select form-select-sm">
                                                <option value="all" <?= $date_range === 'all' ? 'selected' : '' ?>>All Time</option>
                                                <option value="today" <?= $date_range === 'today' ? 'selected' : '' ?>>Today</option>
                                                <option value="week" <?= $date_range === 'week' ? 'selected' : '' ?>>Last 7 Days</option>
                                                <option value="month" <?= $date_range === 'month' ? 'selected' : '' ?>>Last 30 Days</option>
                                            </select>
                                        </div>
                                        
                                        <div class="filter-group">
                                            <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
                                            <a href="?" class="btn btn-outline-secondary btn-sm">Reset</a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            
                            <div class="white_card_body">
                                <?php if (!empty($search)): ?>
                                    <div class="alert alert-info d-flex justify-content-between align-items-center mb-4">
                                        <div>
                                            <i class="fas fa-info-circle me-2"></i>
                                            Showing results for "<?= htmlspecialchars($search) ?>"
                                        </div>
                                        <a href="?" class="btn btn-sm btn-outline-info">Clear Search</a>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="QA_section">
                                    <div class="QA_table mb_30">
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th scope="col" width="50"></th>
                                                        <th scope="col">Customer</th>
                                                        <th scope="col">Type</th>
                                                        <th scope="col">Contact Info</th>
                                                        <th scope="col">Activities</th>
                                                        <th scope="col">Device & Location</th>
                                                        <th scope="col">Orders</th>
                                                        <th scope="col">Last Seen</th>
                                                        <th scope="col">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    if (mysqli_num_rows($result) > 0) {
                                                        while ($row = mysqli_fetch_assoc($result)) {
                                                            $is_user = !empty($row['user_id']);
                                                            $avatar_class = $is_user ? 'avatar-user' : 'avatar-visitor';
                                                            $badge_class = $is_user ? 'badge-user' : 'badge-visitor';
                                                            $initial = $is_user ? strtoupper(substr($row['name'] ?? '?', 0, 1)) : 'V';
                                                            
                                                            // Get recent activities for this session
                                                            $activity_sql = "SELECT * FROM visitor_tracking 
                                                                           WHERE session_id = ? 
                                                                           ORDER BY created_at DESC 
                                                                           LIMIT 3";
                                                            $activity_stmt = $conn->prepare($activity_sql);
                                                            $activity_stmt->bind_param("s", $row['session_id']);
                                                            $activity_stmt->execute();
                                                            $activities_result = $activity_stmt->get_result();
                                                            $recent_activities = [];
                                                            while ($activity = $activities_result->fetch_assoc()) {
                                                                $recent_activities[] = $activity;
                                                            }
                                                            
                                                            // Format last seen time
                                                            $last_seen = !empty($row['last_activity']) ? 
                                                                time_ago($row['last_activity']) : 'Just now';
                                                    ?>
                                                    <tr>
                                                        <td>
                                                            <div class="customer-avatar <?= $avatar_class ?>">
                                                                <?= $initial ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div>
                                                                <div class="fw-bold">
                                                                    <?= $is_user ? htmlspecialchars($row['name']) : 'Visitor' ?>
                                                                </div>
                                                                <?php if ($is_user): ?>
                                                                    <small class="text-muted">ID: #<?= $row['user_id'] ?></small>
                                                                <?php else: ?>
                                                                    <small class="text-muted">Session: <?= substr($row['session_id'], 0, 8) ?>...</small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="status-badge <?= $badge_class ?>">
                                                                <?= $is_user ? 'User' : 'Visitor' ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php if ($is_user): ?>
                                                                <div class="small">
                                                                    <div><i class="fas fa-envelope text-muted me-1"></i> <?= htmlspecialchars($row['email']) ?></div>
                                                                    <?php if (!empty($row['mobile'])): ?>
                                                                        <div><i class="fas fa-phone text-muted me-1"></i> <?= htmlspecialchars($row['mobile']) ?></div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="small text-muted">
                                                                    <i class="fas fa-info-circle me-1"></i> Not registered
                                                                </div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="small">
                                                                <div class="mb-1">
                                                                    <span class="badge bg-secondary"><?= $row['activity_count'] ?> activities</span>
                                                                </div>
                                                                <?php foreach ($recent_activities as $activity): ?>
                                                                    <div class="activity-item">
                                                                        <div class="d-flex justify-content-between">
                                                                            <span class="page-url" title="<?= htmlspecialchars($activity['page_url']) ?>">
                                                                                <?= htmlspecialchars(basename($activity['page_url'])) ?>
                                                                            </span>
                                                                            <span class="text-muted">
                                                                                <?= date('H:i', strtotime($activity['created_at'])) ?>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </td>
                                                        <td class="location-cell">
                                                            <div class="small">
                                                                <div class="mb-1">
                                                                    <span class="device-badge">
                                                                        <i class="fas fa-<?= strpos(strtolower($row['user_agent']), 'mobile') !== false ? 'mobile-alt' : 'desktop' ?> me-1"></i>
                                                                        <?= strpos(strtolower($row['user_agent']), 'mobile') !== false ? 'Mobile' : 'Desktop' ?>
                                                                    </span>
                                                                </div>
                                                                <div>
                                                                    <i class="fas fa-map-marker-alt text-muted me-1"></i>
                                                                    <?= !empty($row['city']) ? htmlspecialchars($row['city']) . ', ' : '' ?>
                                                                    <?= !empty($row['country']) ? htmlspecialchars($row['country']) : 'Unknown' ?>
                                                                </div>
                                                                <div class="text-muted">
                                                                    IP: <?= htmlspecialchars($row['ip_address']) ?>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <?php if ($is_user && $row['order_count'] > 0): ?>
                                                                <span class="order-count" title="<?= $row['order_count'] ?> orders">
                                                                    <?= $row['order_count'] ?>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="last-seen">
                                                                <?= $last_seen ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm" role="group">
                                                                <?php if ($is_user): ?>
                                                                    <a href="user_details.php?id=<?= $row['user_id'] ?>" 
                                                                       class="btn btn-outline-primary" title="View Details">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                    <a href="mailto:<?= htmlspecialchars($row['email']) ?>" 
                                                                       class="btn btn-outline-info" title="Send Email">
                                                                        <i class="fas fa-envelope"></i>
                                                                    </a>
                                                                <?php else: ?>
                                                                    <button type="button" class="btn btn-outline-secondary" 
                                                                            onclick="viewVisitorDetails('<?= $row['session_id'] ?>')" 
                                                                            title="View Details">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                        }
                                                    } else {
                                                    ?>
                                                    <tr>
                                                        <td colspan="9" class="text-center py-4">
                                                            <div class="text-muted">
                                                                <i class="fas fa-users fa-3x mb-3 opacity-25"></i>
                                                                <h5>No records found</h5>
                                                                <p><?= empty($search) ? 'No customers or visitors found.' : 'No records match your search criteria.' ?></p>
                                                                <?php if (!empty($search)): ?>
                                                                    <a href="?" class="btn btn-primary">View All</a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php
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
                </div>
            </div>
        </div>

        <?php include "includes/footer.php"; ?>
    </section>

    <script>
        // Initialize tooltips
        $(document).ready(function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });

        function viewVisitorDetails(sessionId) {
            // Create a modal to show visitor details
            fetch('get_visitor_details.php?session_id=' + sessionId)
                .then(response => response.json())
                .then(data => {
                    // Show details in a modal
                    const modalHtml = `
                        <div class="modal fade" id="visitorModal" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Visitor Details</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Session Information</h6>
                                                <p><strong>Session ID:</strong> ${data.session_id}</p>
                                                <p><strong>IP Address:</strong> ${data.ip_address}</p>
                                                <p><strong>Device:</strong> ${data.device}</p>
                                                <p><strong>Browser:</strong> ${data.browser}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Location</h6>
                                                <p><strong>Country:</strong> ${data.country || 'Unknown'}</p>
                                                <p><strong>City:</strong> ${data.city || 'Unknown'}</p>
                                                <p><strong>Region:</strong> ${data.region || 'Unknown'}</p>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <h6>Recent Activities</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Time</th>
                                                            <th>Page</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        ${data.activities.map(activity => `
                                                            <tr>
                                                                <td>${activity.time}</td>
                                                                <td>${activity.page}</td>
                                                                <td>${activity.action}</td>
                                                            </tr>
                                                        `).join('')}
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    $('body').append(modalHtml);
                    const modal = new bootstrap.Modal(document.getElementById('visitorModal'));
                    modal.show();
                    
                    // Remove modal after hiding
                    $('#visitorModal').on('hidden.bs.modal', function() {
                        $(this).remove();
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load visitor details');
                });
        }
    </script>

</body>
</html>

<?php
// Helper function for time ago
function time_ago($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>