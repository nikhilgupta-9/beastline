<?php
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';
require_once __DIR__ . '/models/setting.php';

// Initialize Settings
$setting = new Setting($conn);

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    switch ($_GET['action']) {
        case 'delete':
            $delete_stmt = $conn->prepare("DELETE FROM visitor_tracking WHERE id = ?");
            $delete_stmt->bind_param("i", $id);
            if ($delete_stmt->execute()) {
                $_SESSION['success'] = "Visitor record deleted successfully";
            } else {
                $_SESSION['error'] = "Error deleting visitor record";
            }
            $delete_stmt->close();
            break;
    }

    header("Location: visitor-tracking.php");
    exit();
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_action'])) {
    $selected_ids = $_POST['selected_ids'] ?? [];

    if (!empty($selected_ids)) {
        $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));

        switch ($_POST['bulk_action']) {
            case 'delete':
                $stmt = $conn->prepare("DELETE FROM visitor_tracking WHERE id IN ($placeholders)");
                break;
            default:
                $stmt = null;
        }

        if ($stmt) {
            $types = str_repeat('i', count($selected_ids));
            $stmt->bind_param($types, ...$selected_ids);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Selected visitor records deleted successfully";
            } else {
                $_SESSION['error'] = "Error performing bulk action";
            }
            $stmt->close();
        }
    } else {
        $_SESSION['error'] = "No records selected";
    }

    header("Location: visitor-tracking.php");
    exit();
}

// Handle export to CSV
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="visitor_data_' . date('Y-m-d') . '.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add headers
    fputcsv($output, [
        'ID', 'Visitor ID', 'IP Address', 'User ID', 'Session ID', 
        'Country', 'City', 'Device Type', 'Browser', 'OS',
        'Landing Page', 'Referrer', 'First Visit', 'Last Visit', 
        'Visit Count', 'Is Bot'
    ]);
    
    // Fetch all data for export
    $export_query = "SELECT * FROM visitor_tracking ORDER BY last_visit DESC";
    $export_result = mysqli_query($conn, $export_query);
    
    while ($row = mysqli_fetch_assoc($export_result)) {
        fputcsv($output, [
            $row['id'],
            $row['visitor_id'],
            $row['ip_address'],
            $row['user_id'],
            $row['session_id'],
            $row['country'],
            $row['city'],
            $row['device_type'],
            $row['browser'],
            $row['os'],
            $row['landing_page'],
            $row['referrer'],
            $row['first_visit'],
            $row['last_visit'],
            $row['visit_count'],
            $row['is_bot'] ? 'Yes' : 'No'
        ]);
    }
    
    fclose($output);
    exit();
}

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$device = isset($_GET['device']) ? $_GET['device'] : '';
$country_filter = isset($_GET['country']) ? $_GET['country'] : '';

// Build WHERE clause based on filters
$where_conditions = [];

switch ($filter) {
    case 'today':
        $where_conditions[] = "DATE(last_visit) = CURDATE()";
        break;
    case 'yesterday':
        $where_conditions[] = "DATE(last_visit) = CURDATE() - INTERVAL 1 DAY";
        break;
    case 'week':
        $where_conditions[] = "last_visit >= CURDATE() - INTERVAL 7 DAY";
        break;
    case 'month':
        $where_conditions[] = "MONTH(last_visit) = MONTH(CURDATE()) AND YEAR(last_visit) = YEAR(CURDATE())";
        break;
    case 'custom':
        if (!empty($date_from)) {
            $where_conditions[] = "DATE(last_visit) >= '" . mysqli_real_escape_string($conn, $date_from) . "'";
        }
        if (!empty($date_to)) {
            $where_conditions[] = "DATE(last_visit) <= '" . mysqli_real_escape_string($conn, $date_to) . "'";
        }
        break;
}

// Apply search filter
if (!empty($search)) {
    $search_escaped = mysqli_real_escape_string($conn, $search);
    $where_conditions[] = "(visitor_id LIKE '%$search_escaped%' 
                          OR ip_address LIKE '%$search_escaped%' 
                          OR country LIKE '%$search_escaped%' 
                          OR city LIKE '%$search_escaped%' 
                          OR browser LIKE '%$search_escaped%' 
                          OR landing_page LIKE '%$search_escaped%')";
}

// Apply device filter
if (!empty($device)) {
    switch ($device) {
        case 'desktop':
            $where_conditions[] = "is_desktop = 1 AND is_bot = 0";
            break;
        case 'mobile':
            $where_conditions[] = "is_mobile = 1 AND is_bot = 0";
            break;
        case 'tablet':
            $where_conditions[] = "is_tablet = 1 AND is_bot = 0";
            break;
        case 'bot':
            $where_conditions[] = "is_bot = 1";
            break;
    }
}

// Apply country filter
if (!empty($country_filter)) {
    $country_escaped = mysqli_real_escape_string($conn, $country_filter);
    $where_conditions[] = "country = '$country_escaped'";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get visitor stats (using optimized queries with indexes)
$stats_query = "SELECT 
    COUNT(*) as total_visits,
    COUNT(DISTINCT visitor_id) as unique_visitors,
    COUNT(DISTINCT ip_address) as unique_ips,
    COUNT(DISTINCT DATE(first_visit)) as active_days,
    SUM(CASE WHEN DATE(last_visit) = CURDATE() THEN 1 ELSE 0 END) as today_visits,
    SUM(CASE WHEN is_bot = 1 THEN 1 ELSE 0 END) as bot_visits,
    SUM(CASE WHEN is_mobile = 1 AND is_bot = 0 THEN 1 ELSE 0 END) as mobile_visits,
    SUM(CASE WHEN is_tablet = 1 AND is_bot = 0 THEN 1 ELSE 0 END) as tablet_visits,
    SUM(CASE WHEN is_desktop = 1 AND is_bot = 0 THEN 1 ELSE 0 END) as desktop_visits
    FROM visitor_tracking";
$stats_result = mysqli_query($conn, $stats_query);
$stats = $stats_result ? mysqli_fetch_assoc($stats_result) : [
    'total_visits' => 0,
    'unique_visitors' => 0,
    'unique_ips' => 0,
    'active_days' => 0,
    'today_visits' => 0,
    'bot_visits' => 0,
    'mobile_visits' => 0,
    'tablet_visits' => 0,
    'desktop_visits' => 0
];

// Get top countries (limited to 10 for performance)
$countries_query = "SELECT country, COUNT(*) as count, COUNT(DISTINCT visitor_id) as visitors 
                    FROM visitor_tracking 
                    WHERE country IS NOT NULL AND country != '' 
                    GROUP BY country 
                    ORDER BY count DESC 
                    LIMIT 10";
$countries_result = mysqli_query($conn, $countries_query);

// Get all distinct countries for filter dropdown
$all_countries_query = "SELECT DISTINCT country FROM visitor_tracking WHERE country IS NOT NULL AND country != '' ORDER BY country";
$all_countries_result = mysqli_query($conn, $all_countries_query);

// Get top browsers
$browsers_query = "SELECT browser, COUNT(*) as count 
                   FROM visitor_tracking 
                   WHERE browser IS NOT NULL AND browser != '' 
                   GROUP BY browser 
                   ORDER BY count DESC 
                   LIMIT 10";
$browsers_result = mysqli_query($conn, $browsers_query);

// Get top referrers
$referrers_query = "SELECT referrer, COUNT(*) as count 
                    FROM visitor_tracking 
                    WHERE referrer IS NOT NULL AND referrer != '' 
                    GROUP BY referrer 
                    ORDER BY count DESC 
                    LIMIT 10";
$referrers_result = mysqli_query($conn, $referrers_query);

// Pagination with improved handling for large datasets
$limit_options = [10, 25, 50, 100, 250, 500];
$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $limit_options) ? (int)$_GET['limit'] : 25;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total records count with filters
$count_query = "SELECT COUNT(*) as total FROM visitor_tracking $where_clause";
$count_result = mysqli_query($conn, $count_query);
$total_rows = $count_result ? mysqli_fetch_assoc($count_result)['total'] : 0;
$total_pages = ceil($total_rows / $limit);

// Ensure page is within valid range
if ($page < 1) $page = 1;
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;

// Fetch visitor records with pagination (optimized with indexes)
$sql = "SELECT * FROM visitor_tracking $where_clause ORDER BY last_visit DESC LIMIT $offset, $limit";
$check = mysqli_query($conn, $sql);

// Build query string for pagination links
$query_params = [];
if ($filter != 'all') $query_params[] = "filter=$filter";
if (!empty($search)) $query_params[] = "search=" . urlencode($search);
if (!empty($date_from)) $query_params[] = "date_from=$date_from";
if (!empty($date_to)) $query_params[] = "date_to=$date_to";
if (!empty($device)) $query_params[] = "device=$device";
if (!empty($country_filter)) $query_params[] = "country=" . urlencode($country_filter);
if ($limit != 25) $query_params[] = "limit=$limit";
$query_string = !empty($query_params) ? '&' . implode('&', $query_params) : '';
?>

<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Visitor Tracking | Admin <?php echo htmlspecialchars($setting->get('site_name')); ?></title>
    <link rel="icon" href="<?php echo htmlspecialchars($setting->get('favicon', 'assets/img/logo.png')); ?>" type="image/png">

    <?php include "links.php"; ?>

    <style>
        .stats-card {
            text-align: center;
            padding: 20px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .stats-card i {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #4361ee;
        }

        .stats-card h3 {
            margin: 0;
            font-weight: 700;
            font-size: 2rem;
        }

        .stats-card p {
            margin: 5px 0 0;
            font-size: 0.9rem;
            color: #6c757d;
        }

        .device-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .device-desktop {
            background: #e3f2fd;
            color: #1976d2;
        }

        .device-mobile {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .device-tablet {
            background: #fff3e0;
            color: #f57c00;
        }

        .device-bot {
            background: #fce4e4;
            color: #c62828;
        }

        .visitor-id {
            font-family: monospace;
            font-size: 0.85rem;
            background: #f8f9fa;
            padding: 2px 5px;
            border-radius: 3px;
        }

        .ip-address {
            font-family: monospace;
            font-weight: 500;
        }

        .country-flag {
            width: 20px;
            height: 15px;
            margin-right: 5px;
            object-fit: cover;
        }

        .referrer-link {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }

        .empty-state i {
            font-size: 3rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }

        .bulk-actions {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .search-box {
            min-width: 300px;
        }

        .filter-tabs {
            margin-bottom: 20px;
        }

        .filter-tabs .btn {
            padding: 0.4rem 1rem;
            font-size: 0.9rem;
        }

        .mini-table {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            max-height: 300px;
            overflow-y: auto;
        }

        .mini-table h6 {
            margin-bottom: 15px;
            font-weight: 600;
            color: #495057;
            position: sticky;
            top: 0;
            background: white;
            padding-bottom: 10px;
            z-index: 1;
        }

        .progress-thin {
            height: 5px;
            margin-bottom: 10px;
        }

        .advanced-filters {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .pagination-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .pagination {
            margin-bottom: 0;
        }

        .page-link {
            padding: 0.3rem 0.75rem;
        }

        .table-responsive {
            min-height: 400px;
        }

        .loading-spinner {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
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
            <div class="container-fluid p-0 sm_padding_15px">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <div class="white_card card_height_100 mb_30">
                            <div class="white_card_header">
                                <div class="box_header m-0">
                                    <div class="main-title">
                                        <h3 class="m-0">Visitor Tracking Dashboard</h3>
                                        <p class="text-muted mt-1">Total Records: <?php echo number_format($total_rows); ?></p>
                                    </div>
                                    <div class="action-btn">
                                        <a href="?export=csv" class="btn_1 btn-sm" onclick="return confirm('Export all visitor data to CSV? This may take a while for large datasets.')">
                                            <i class="fas fa-download mr-1"></i> Export CSV
                                        </a>
                                    </div>
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

                                <!-- Stats Cards -->
                                <div class="row mb-4">
                                    <div class="col-md-3 col-sm-6">
                                        <div class="stats-card">
                                            <i class="fas fa-users"></i>
                                            <h3><?php echo number_format($stats['unique_visitors']); ?></h3>
                                            <p>Unique Visitors</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="stats-card">
                                            <i class="fas fa-eye"></i>
                                            <h3><?php echo number_format($stats['total_visits']); ?></h3>
                                            <p>Total Visits</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="stats-card">
                                            <i class="fas fa-calendar-day"></i>
                                            <h3><?php echo number_format($stats['today_visits']); ?></h3>
                                            <p>Today's Visits</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="stats-card">
                                            <i class="fas fa-clock"></i>
                                            <h3><?php echo number_format($stats['active_days']); ?></h3>
                                            <p>Active Days</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Advanced Filters -->
                                <div class="advanced-filters">
                                    <form method="GET" id="filterForm" class="row">
                                        <div class="col-md-2 mb-2">
                                            <label class="small text-muted">Date Range</label>
                                            <select name="filter" class="form-control form-control-sm" onchange="toggleCustomDate(this.value)">
                                                <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>All Time</option>
                                                <option value="today" <?php echo $filter == 'today' ? 'selected' : ''; ?>>Today</option>
                                                <option value="yesterday" <?php echo $filter == 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                                                <option value="week" <?php echo $filter == 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                                                <option value="month" <?php echo $filter == 'month' ? 'selected' : ''; ?>>This Month</option>
                                                <option value="custom" <?php echo $filter == 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 mb-2 custom-date" style="display: <?php echo $filter == 'custom' ? 'block' : 'none'; ?>">
                                            <label class="small text-muted">From</label>
                                            <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo htmlspecialchars($date_from); ?>">
                                        </div>
                                        <div class="col-md-2 mb-2 custom-date" style="display: <?php echo $filter == 'custom' ? 'block' : 'none'; ?>">
                                            <label class="small text-muted">To</label>
                                            <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo htmlspecialchars($date_to); ?>">
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <label class="small text-muted">Device</label>
                                            <select name="device" class="form-control form-control-sm">
                                                <option value="">All Devices</option>
                                                <option value="desktop" <?php echo $device == 'desktop' ? 'selected' : ''; ?>>Desktop</option>
                                                <option value="mobile" <?php echo $device == 'mobile' ? 'selected' : ''; ?>>Mobile</option>
                                                <option value="tablet" <?php echo $device == 'tablet' ? 'selected' : ''; ?>>Tablet</option>
                                                <option value="bot" <?php echo $device == 'bot' ? 'selected' : ''; ?>>Bots</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <label class="small text-muted">Country</label>
                                            <select name="country" class="form-control form-control-sm">
                                                <option value="">All Countries</option>
                                                <?php 
                                                if ($all_countries_result && mysqli_num_rows($all_countries_result) > 0) {
                                                    mysqli_data_seek($all_countries_result, 0);
                                                    while ($c = mysqli_fetch_assoc($all_countries_result)) {
                                                        $selected = ($country_filter == $c['country']) ? 'selected' : '';
                                                        echo '<option value="' . htmlspecialchars($c['country']) . '" ' . $selected . '>' . htmlspecialchars($c['country']) . '</option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2 mb-2 d-flex align-items-end">
                                            <button type="submit" class="btn btn-sm btn-primary mr-2">
                                                <i class="fas fa-filter mr-1"></i> Apply Filters
                                            </button>
                                            <a href="visitor-tracking.php" class="btn btn-sm btn-secondary">
                                                <i class="fas fa-redo mr-1"></i> Reset
                                            </a>
                                        </div>
                                    </form>
                                </div>

                                <!-- Filter Tabs (Quick filters) -->
                                <div class="filter-tabs">
                                    <div class="btn-group" role="group">
                                        <a href="?filter=all<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-sm btn-outline-primary <?php echo $filter == 'all' ? 'active' : ''; ?>">
                                            <i class="fas fa-globe mr-1"></i> All Time
                                        </a>
                                        <a href="?filter=today<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-sm btn-outline-primary <?php echo $filter == 'today' ? 'active' : ''; ?>">
                                            <i class="fas fa-sun mr-1"></i> Today
                                        </a>
                                        <a href="?filter=yesterday<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-sm btn-outline-primary <?php echo $filter == 'yesterday' ? 'active' : ''; ?>">
                                            <i class="fas fa-calendar-day mr-1"></i> Yesterday
                                        </a>
                                        <a href="?filter=week<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-sm btn-outline-primary <?php echo $filter == 'week' ? 'active' : ''; ?>">
                                            <i class="fas fa-calendar-week mr-1"></i> Last 7 Days
                                        </a>
                                        <a href="?filter=month<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-sm btn-outline-primary <?php echo $filter == 'month' ? 'active' : ''; ?>">
                                            <i class="fas fa-calendar-alt mr-1"></i> This Month
                                        </a>
                                    </div>
                                </div>

                                <!-- Device Stats Row -->
                                <div class="row mb-4">
                                    <div class="col-md-3">
                                        <div class="mini-table">
                                            <h6><i class="fas fa-globe-asia mr-2 text-primary"></i> Top Countries</h6>
                                            <?php if ($countries_result && mysqli_num_rows($countries_result) > 0): ?>
                                                <?php $max_count = 0; ?>
                                                <?php while ($country = mysqli_fetch_assoc($countries_result)): ?>
                                                    <?php if ($max_count == 0) $max_count = $country['count']; ?>
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span>
                                                            <i class="fas fa-map-marker-alt mr-1 text-muted"></i>
                                                            <?php echo htmlspecialchars($country['country'] ?: 'Unknown'); ?>
                                                        </span>
                                                        <span class="badge badge-primary"><?php echo number_format($country['count']); ?></span>
                                                    </div>
                                                    <div class="progress progress-thin">
                                                        <div class="progress-bar bg-primary" style="width: <?php echo ($country['count'] / $max_count) * 100; ?>%"></div>
                                                    </div>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <p class="text-muted small mb-0">No country data</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mini-table">
                                            <h6><i class="fas fa-compass mr-2 text-success"></i> Top Browsers</h6>
                                            <?php if ($browsers_result && mysqli_num_rows($browsers_result) > 0): ?>
                                                <?php $max_count = 0; ?>
                                                <?php while ($browser = mysqli_fetch_assoc($browsers_result)): ?>
                                                    <?php if ($max_count == 0) $max_count = $browser['count']; ?>
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span>
                                                            <?php
                                                            $browser_icon = 'fa-globe';
                                                            if (stripos($browser['browser'], 'chrome') !== false) $browser_icon = 'fa-chrome';
                                                            elseif (stripos($browser['browser'], 'firefox') !== false) $browser_icon = 'fa-firefox';
                                                            elseif (stripos($browser['browser'], 'safari') !== false) $browser_icon = 'fa-safari';
                                                            elseif (stripos($browser['browser'], 'edge') !== false) $browser_icon = 'fa-edge';
                                                            ?>
                                                            <i class="fab <?php echo $browser_icon; ?> mr-1"></i>
                                                            <?php echo htmlspecialchars($browser['browser'] ?: 'Unknown'); ?>
                                                        </span>
                                                        <span class="badge badge-success"><?php echo number_format($browser['count']); ?></span>
                                                    </div>
                                                    <div class="progress progress-thin">
                                                        <div class="progress-bar bg-success" style="width: <?php echo ($browser['count'] / $max_count) * 100; ?>%"></div>
                                                    </div>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <p class="text-muted small mb-0">No browser data</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mini-table">
                                            <h6><i class="fas fa-link mr-2 text-info"></i> Top Referrers</h6>
                                            <?php if ($referrers_result && mysqli_num_rows($referrers_result) > 0): ?>
                                                <?php $max_count = 0; ?>
                                                <?php while ($referrer = mysqli_fetch_assoc($referrers_result)): ?>
                                                    <?php if ($max_count == 0) $max_count = $referrer['count']; ?>
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span class="referrer-link" title="<?php echo htmlspecialchars($referrer['referrer']); ?>">
                                                            <i class="fas fa-external-link-alt mr-1"></i>
                                                            <?php
                                                            $domain = parse_url($referrer['referrer'], PHP_URL_HOST);
                                                            echo $domain ? htmlspecialchars($domain) : 'Direct';
                                                            ?>
                                                        </span>
                                                        <span class="badge badge-info"><?php echo number_format($referrer['count']); ?></span>
                                                    </div>
                                                    <div class="progress progress-thin">
                                                        <div class="progress-bar bg-info" style="width: <?php echo ($referrer['count'] / $max_count) * 100; ?>%"></div>
                                                    </div>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <p class="text-muted small mb-0">No referrer data</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mini-table">
                                            <h6><i class="fas fa-mobile-alt mr-2 text-warning"></i> Device Breakdown</h6>
                                            <?php
                                            $total_non_bot = $stats['desktop_visits'] + $stats['mobile_visits'] + $stats['tablet_visits'];
                                            $total_all = $stats['total_visits'];
                                            ?>
                                            <div class="mb-2">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span><i class="fas fa-desktop mr-1"></i> Desktop</span>
                                                    <span class="badge badge-secondary"><?php echo number_format($stats['desktop_visits']); ?></span>
                                                </div>
                                                <div class="progress progress-thin">
                                                    <div class="progress-bar bg-secondary" style="width: <?php echo $total_all > 0 ? ($stats['desktop_visits'] / $total_all) * 100 : 0; ?>%"></div>
                                                </div>
                                            </div>
                                            <div class="mb-2">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span><i class="fas fa-mobile-alt mr-1"></i> Mobile</span>
                                                    <span class="badge badge-success"><?php echo number_format($stats['mobile_visits']); ?></span>
                                                </div>
                                                <div class="progress progress-thin">
                                                    <div class="progress-bar bg-success" style="width: <?php echo $total_all > 0 ? ($stats['mobile_visits'] / $total_all) * 100 : 0; ?>%"></div>
                                                </div>
                                            </div>
                                            <div class="mb-2">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span><i class="fas fa-tablet-alt mr-1"></i> Tablet</span>
                                                    <span class="badge badge-warning"><?php echo number_format($stats['tablet_visits']); ?></span>
                                                </div>
                                                <div class="progress progress-thin">
                                                    <div class="progress-bar bg-warning" style="width: <?php echo $total_all > 0 ? ($stats['tablet_visits'] / $total_all) * 100 : 0; ?>%"></div>
                                                </div>
                                            </div>
                                            <div class="mb-0">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span><i class="fas fa-robot mr-1"></i> Bots</span>
                                                    <span class="badge badge-danger"><?php echo number_format($stats['bot_visits']); ?></span>
                                                </div>
                                                <div class="progress progress-thin">
                                                    <div class="progress-bar bg-danger" style="width: <?php echo $total_all > 0 ? ($stats['bot_visits'] / $total_all) * 100 : 0; ?>%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Search and Bulk Actions -->
                                <form method="GET" class="mb-3">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="input-group">
                                                <input type="text" class="form-control" name="search" placeholder="Search by Visitor ID, IP, Location, Browser..." value="<?php echo htmlspecialchars($search); ?>">
                                                <div class="input-group-append">
                                                    <button class="btn btn-primary" type="submit">
                                                        <i class="fas fa-search"></i> Search
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 text-right">
                                            <span class="text-muted">
                                                Showing <?php echo number_format(min($limit, $total_rows - $offset)); ?> of <?php echo number_format($total_rows); ?> records
                                            </span>
                                        </div>
                                    </div>
                                    <?php
                                    // Preserve other filter parameters
                                    if ($filter != 'all') echo '<input type="hidden" name="filter" value="' . htmlspecialchars($filter) . '">';
                                    if (!empty($device)) echo '<input type="hidden" name="device" value="' . htmlspecialchars($device) . '">';
                                    if (!empty($country_filter)) echo '<input type="hidden" name="country" value="' . htmlspecialchars($country_filter) . '">';
                                    if (!empty($date_from)) echo '<input type="hidden" name="date_from" value="' . htmlspecialchars($date_from) . '">';
                                    if (!empty($date_to)) echo '<input type="hidden" name="date_to" value="' . htmlspecialchars($date_to) . '">';
                                    ?>
                                </form>

                                <!-- Bulk Actions Form -->
                                <form method="POST" id="bulkForm" class="bulk-actions">
                                    <div class="d-flex align-items-center">
                                        <div class="mr-3">
                                            <select class="form-control form-control-sm" name="bulk_action" style="min-width: 150px;">
                                                <option value="">Bulk Actions</option>
                                                <option value="delete" class="text-danger">Delete Selected</option>
                                            </select>
                                        </div>
                                        <div class="mr-3">
                                            <button type="submit" class="btn btn-sm btn-primary" onclick="return confirmBulkAction()">
                                                <i class="fas fa-play mr-1"></i> Apply
                                            </button>
                                        </div>
                                        <div class="ml-auto">
                                            <select class="form-control form-control-sm" style="width: 100px;" onchange="window.location.href='?limit='+this.value+'<?php echo $query_string; ?>'">
                                                <?php foreach ($limit_options as $l): ?>
                                                    <option value="<?php echo $l; ?>" <?php echo $limit == $l ? 'selected' : ''; ?>><?php echo $l; ?> per page</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </form>

                                <!-- Visitor Table -->
                                <div class="QA_section">
                                    <div class="QA_table mb_30">
                                        <div class="table-responsive">
                                            <table class="table lms_table_active">
                                                <thead>
                                                    <tr>
                                                        <th scope="col" width="3%">
                                                            <input type="checkbox" id="selectAll">
                                                        </th>
                                                        <th scope="col">#</th>
                                                        <th scope="col">Visitor ID</th>
                                                        <th scope="col">IP Address</th>
                                                        <th scope="col">Location</th>
                                                        <th scope="col">Device/OS</th>
                                                        <th scope="col">Landing Page</th>
                                                        <th scope="col">Referrer</th>
                                                        <th scope="col">Visits</th>
                                                        <th scope="col">First Visit</th>
                                                        <th scope="col">Last Visit</th>
                                                        <th scope="col">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    if ($check && mysqli_num_rows($check) > 0) {
                                                        $sno = $offset + 1;
                                                        while ($row = mysqli_fetch_assoc($check)) {
                                                            // Device badge
                                                            $device_class = 'device-desktop';
                                                            $device_icon = 'fa-desktop';
                                                            if ($row['is_bot']) {
                                                                $device_class = 'device-bot';
                                                                $device_icon = 'fa-robot';
                                                            } elseif ($row['is_mobile']) {
                                                                $device_class = 'device-mobile';
                                                                $device_icon = 'fa-mobile-alt';
                                                            } elseif ($row['is_tablet']) {
                                                                $device_class = 'device-tablet';
                                                                $device_icon = 'fa-tablet-alt';
                                                            }

                                                            $device_badge = '<span class="device-badge ' . $device_class . '">';
                                                            $device_badge .= '<i class="fas ' . $device_icon . ' mr-1"></i>';

                                                            if ($row['os']) {
                                                                $os_short = explode(' ', $row['os'])[0];
                                                                $device_badge .= $os_short;
                                                            } else {
                                                                $device_badge .= $row['is_bot'] ? 'Bot' : 'Unknown';
                                                            }
                                                            $device_badge .= '</span>';

                                                            // Format dates
                                                            $first_visit = date('d M Y H:i', strtotime($row['first_visit']));
                                                            $last_visit = date('d M Y H:i', strtotime($row['last_visit']));

                                                            // Location
                                                            $location = [];
                                                            if (!empty($row['city'])) $location[] = $row['city'];
                                                            if (!empty($row['country'])) $location[] = $row['country'];
                                                            $location_str = !empty($location) ? implode(', ', $location) : 'Unknown';
                                                    ?>
                                                            <tr>
                                                                <td class="text-center">
                                                                    <input type="checkbox" name="selected_ids[]" value="<?php echo $row['id']; ?>" class="visitor-checkbox">
                                                                </td>
                                                                <td class="text-center"><?php echo $sno++; ?></td>
                                                                <td>
                                                                    <span class="visitor-id" title="<?php echo htmlspecialchars($row['visitor_id']); ?>">
                                                                        <?php echo htmlspecialchars(substr($row['visitor_id'], 0, 8)) . '...'; ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <span class="ip-address"><?php echo htmlspecialchars($row['ip_address'] ?: 'N/A'); ?></span>
                                                                </td>
                                                                <td>
                                                                    <i class="fas fa-map-marker-alt text-muted mr-1"></i>
                                                                    <?php echo htmlspecialchars($location_str); ?>
                                                                </td>
                                                                <td>
                                                                    <?php echo $device_badge; ?>
                                                                    <?php if ($row['browser']): ?>
                                                                        <div class="small text-muted mt-1" title="<?php echo htmlspecialchars($row['browser']); ?>">
                                                                            <i class="fas fa-globe mr-1"></i><?php echo htmlspecialchars(substr($row['browser'], 0, 15)) . (strlen($row['browser']) > 15 ? '...' : ''); ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <?php
                                                                    $page = basename($row['landing_page']);
                                                                    echo '<span title="' . htmlspecialchars($row['landing_page']) . '">';
                                                                    echo '<i class="fas fa-file mr-1"></i>' . htmlspecialchars(substr($page, 0, 20)) . (strlen($page) > 20 ? '...' : '');
                                                                    echo '</span>';
                                                                    ?>
                                                                </td>
                                                                <td>
                                                                    <?php if (!empty($row['referrer'])): ?>
                                                                        <?php
                                                                        $referrer_domain = parse_url($row['referrer'], PHP_URL_HOST);
                                                                        $referrer_display = $referrer_domain ?: 'Direct';
                                                                        ?>
                                                                        <span class="referrer-link" title="<?php echo htmlspecialchars($row['referrer']); ?>">
                                                                            <i class="fas fa-external-link-alt mr-1"></i>
                                                                            <?php echo htmlspecialchars($referrer_display); ?>
                                                                        </span>
                                                                    <?php else: ?>
                                                                        <span class="text-muted"><i class="fas fa-ban mr-1"></i>Direct</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td class="text-center">
                                                                    <span class="badge badge-info"><?php echo $row['visit_count']; ?></span>
                                                                </td>
                                                                <td><small><?php echo $first_visit; ?></small></td>
                                                                <td><small><?php echo $last_visit; ?></small></td>
                                                                <td class="text-center">
                                                                    <div class="d-flex justify-content-center gap-2">
                                                                        <a href="?action=delete&id=<?php echo $row['id']; ?><?php echo $query_string ? '&' . ltrim($query_string, '&') : ''; ?>"
                                                                            onclick='return confirm("Are you sure you want to delete this visitor record?")'
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
                                                                    <i class="fas fa-chart-line text-muted"></i>
                                                                    <h4 class="mt-3">No Visitor Data Found</h4>
                                                                    <p class="text-muted mb-4">Try adjusting your filters or search criteria.</p>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Enhanced Pagination -->
                                        <?php if ($total_pages > 1): ?>
                                            <div class="row mt-4">
                                                <div class="col-md-6">
                                                    <div class="pagination-info">
                                                        <span class="text-muted">
                                                            Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                                                            (<?php echo number_format($total_rows); ?> total records)
                                                        </span>
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <a href="?page=1<?php echo $query_string; ?>" class="btn btn-outline-secondary <?php echo $page <= 1 ? 'disabled' : ''; ?>" title="First Page">
                                                                <i class="fas fa-angle-double-left"></i>
                                                            </a>
                                                            <a href="?page=<?php echo $page - 1; ?><?php echo $query_string; ?>" class="btn btn-outline-secondary <?php echo $page <= 1 ? 'disabled' : ''; ?>" title="Previous Page">
                                                                <i class="fas fa-angle-left"></i>
                                                            </a>
                                                            <a href="?page=<?php echo $page + 1; ?><?php echo $query_string; ?>" class="btn btn-outline-secondary <?php echo $page >= $total_pages ? 'disabled' : ''; ?>" title="Next Page">
                                                                <i class="fas fa-angle-right"></i>
                                                            </a>
                                                            <a href="?page=<?php echo $total_pages; ?><?php echo $query_string; ?>" class="btn btn-outline-secondary <?php echo $page >= $total_pages ? 'disabled' : ''; ?>" title="Last Page">
                                                                <i class="fas fa-angle-double-right"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <nav aria-label="Page navigation" class="float-right">
                                                        <ul class="pagination pagination-sm mb-0">
                                                            <?php
                                                            // Calculate page range to display
                                                            $range = 5;
                                                            $start = max(1, $page - floor($range / 2));
                                                            $end = min($total_pages, $start + $range - 1);
                                                            $start = max(1, $end - $range + 1);

                                                            // First page
                                                            if ($start > 1) {
                                                                echo '<li class="page-item"><a class="page-link" href="?page=1' . $query_string . '">1</a></li>';
                                                                if ($start > 2) {
                                                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                                }
                                                            }

                                                            // Page numbers
                                                            for ($i = $start; $i <= $end; $i++) {
                                                                $active = ($i == $page) ? 'active' : '';
                                                                echo '<li class="page-item ' . $active . '">';
                                                                echo '<a class="page-link" href="?page=' . $i . $query_string . '">' . $i . '</a>';
                                                                echo '</li>';
                                                            }

                                                            // Last page
                                                            if ($end < $total_pages) {
                                                                if ($end < $total_pages - 1) {
                                                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                                }
                                                                echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . $query_string . '">' . $total_pages . '</a></li>';
                                                            }
                                                            ?>
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

    <!-- Loading Spinner -->
    <div class="loading-spinner" id="loadingSpinner">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>

    <script>
        // Initialize tooltips
        $(function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });

        // Toggle custom date fields
        function toggleCustomDate(value) {
            const customDates = document.querySelectorAll('.custom-date');
            if (value === 'custom') {
                customDates.forEach(el => el.style.display = 'block');
            } else {
                customDates.forEach(el => el.style.display = 'none');
            }
        }

        // Select All checkboxes
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.visitor-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Show loading spinner on form submissions
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                document.getElementById('loadingSpinner').style.display = 'block';
            });
        });

        // Confirm bulk action
        function confirmBulkAction() {
            const selected = document.querySelectorAll('.visitor-checkbox:checked');
            const action = document.querySelector('select[name="bulk_action"]').value;

            if (selected.length === 0) {
                alert('Please select at least one record.');
                return false;
            }

            if (!action) {
                alert('Please select a bulk action.');
                return false;
            }

            let message = '';
            switch (action) {
                case 'delete':
                    message = 'Delete ' + selected.length + ' selected visitor records? This action cannot be undone.';
                    break;
            }

            return confirm(message);
        }

        // Update checkboxes when any checkbox changes
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('visitor-checkbox')) {
                const allChecked = Array.from(document.querySelectorAll('.visitor-checkbox')).every(cb => cb.checked);
                const someChecked = Array.from(document.querySelectorAll('.visitor-checkbox')).some(cb => cb.checked);

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

        // Hide loading spinner on page load
        window.addEventListener('load', function() {
            document.getElementById('loadingSpinner').style.display = 'none';
        });
    </script>
</body>

</html>