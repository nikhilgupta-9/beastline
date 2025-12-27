<?php
include "db-conn.php";

// Get total customers count
$total_customers_query = "SELECT COUNT(*) as total FROM `users`";
$total_customers_result = mysqli_query($conn, $total_customers_query);
$total_customers = mysqli_fetch_assoc($total_customers_result)['total'];

// Handle search functionality
$search = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $search_condition = "WHERE name LIKE '%$search%' OR email LIKE '%$search%' OR mobile LIKE '%$search%'";
} else {
    $search_condition = "";
}

// Get customers with search condition
$sql = "SELECT * FROM `users` $search_condition ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
$total_results = mysqli_num_rows($result);
?>
<!DOCTYPE html>
<html lang="zxx">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Customer Management | Admin Panel</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">
    <?php include "links.php"; ?>
    
    <style>
        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-active { background: #d4edda; color: #155724; }
        .badge-inactive { background: #f8d7da; color: #721c24; }
        .search-box {
            max-width: 300px;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
        }
        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            margin: 2px;
            transition: all 0.3s ease;
        }
        .action-btn:hover {
            transform: translateY(-1px);
        }
        .table-hover tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.05);
        }
    </style>
</head>

<body class="crm_body_bg">
    <?php include "header.php"; ?>
    
    <section class="main_content dashboard_part large_header_bg">
        <div class="container-fluid g-0">
            <div class="row">
                <div class="col-lg-12 p-0">
                    <?php include "top_nav.php"; ?>
                </div>
            </div>
        </div>

        <div class="main_content_iner">
            <div class="container-fluid p-0">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <!-- Statistics Cards -->
                        

                        <div class="white_card card_height_100 mb_30">
                            <div class="card-header bg-white border-0 py-3">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <div class="mb-2 mb-md-0">
                                        <h2 class="mb-0 fw-bold">Customer Management</h2>
                                        <p class="text-muted mb-0">Manage and view all registered customers</p>
                                    </div>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <!-- Search Form -->
                                        <form method="GET" class="d-flex search-box">
                                            <div class="input-group">
                                                <input type="text" name="search" class="form-control" 
                                                       placeholder="Search customers..." 
                                                       value="<?= htmlspecialchars($search) ?>">
                                                <button class="btn btn-primary" type="submit">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                                <?php if (!empty($search)): ?>
                                                    <a href="?" class="btn btn-outline-secondary">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                        
                                        <!-- Export Button -->
                                        <!-- <button class="btn btn-success">
                                            <i class="fas fa-download me-2"></i>Export
                                        </button> -->
                                    </div>
                                </div>
                            </div>
                            
                            <div class="white_card_body">
                                <?php if (!empty($search)): ?>
                                    <div class="alert alert-info d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-info-circle me-2"></i>
                                            Showing <?= $total_results ?> results for "<?= htmlspecialchars($search) ?>"
                                        </div>
                                        <a href="?" class="btn btn-sm btn-outline-info">Clear Search</a>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="QA_section">
                                    <div class="QA_table mb_30">
                                        <!-- Responsive Table Container -->
                                        <div class="table-responsive">
                                            <table class="table table-hover lms_table_active">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th scope="col" width="60">#</th>
                                                        <th scope="col">Customer</th>
                                                        <th scope="col">Customer ID</th>
                                                        <th scope="col">Mobile</th>
                                                        <th scope="col">Email</th>
                                                        <th scope="col">Status</th>
                                                        <th scope="col">Join Date</th>
                                                        <!-- <th scope="col" class="text-center">Actions</th> -->
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $no = 1;
                                                    if (mysqli_num_rows($result) > 0) {
                                                        while ($row = mysqli_fetch_assoc($result)) {
                                                            $initial = strtoupper(substr($row['name'], 0, 1));
                                                            $join_date = date('M j, Y', strtotime($row['created_at']));
                                                            $join_time = date('g:i A', strtotime($row['created_at']));
                                                            ?>
                                                            <tr>
                                                                <td>
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" value="<?= $row['id'] ?>">
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="d-flex align-items-center">
                                                                        <div class="customer-avatar me-3">
                                                                            <?= $initial ?>
                                                                        </div>
                                                                        <div>
                                                                            <div class="fw-bold"><?= htmlspecialchars($row['name']) ?></div>
                                                                            <small class="text-muted">Registered User</small>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <span class="badge bg-light text-dark">#<?= $row['id'] ?></span>
                                                                </td>
                                                                <td>
                                                                    <div class="d-flex align-items-center">
                                                                        <i class="fas fa-phone text-muted me-2"></i>
                                                                        <?= $row['mobile'] ?>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="d-flex align-items-center">
                                                                        <i class="fas fa-envelope text-muted me-2"></i>
                                                                        <?= htmlspecialchars($row['email']) ?>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <span class="status-badge badge-active">
                                                                        <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                                                                        Active
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <div>
                                                                        <div class="fw-bold"><?= $join_date ?></div>
                                                                        <small class="text-muted"><?= $join_time ?></small>
                                                                    </div>
                                                                </td>
                                                                <!-- <td class="text-center">
                                                                    <div class="btn-group" role="group">
                                                                        <button type="button" class="btn btn-sm btn-outline-primary action-btn" 
                                                                                data-bs-toggle="tooltip" title="View Details">
                                                                            <i class="fas fa-eye"></i>
                                                                        </button>
                                                                        <button type="button" class="btn btn-sm btn-outline-info action-btn"
                                                                                data-bs-toggle="tooltip" title="Send Email">
                                                                            <i class="fas fa-envelope"></i>
                                                                        </button>
                                                                        <button type="button" class="btn btn-sm btn-outline-warning action-btn"
                                                                                data-bs-toggle="tooltip" title="Edit Customer">
                                                                            <i class="fas fa-edit"></i>
                                                                        </button>
                                                                    </div>
                                                                </td> -->
                                                            </tr>
                                                            <?php
                                                            $no++;
                                                        }
                                                    } else {
                                                        ?>
                                                        <tr>
                                                            <td colspan="8" class="text-center py-4">
                                                                <div class="text-muted">
                                                                    <i class="fas fa-users fa-3x mb-3 opacity-25"></i>
                                                                    <h5>No customers found</h5>
                                                                    <p><?= empty($search) ? 'No customers registered yet.' : 'No customers match your search criteria.' ?></p>
                                                                    <?php if (!empty($search)): ?>
                                                                        <a href="?" class="btn btn-primary">View All Customers</a>
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

        <?php include "footer.php"; ?>
    </section>

    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Select all checkbox functionality
        document.querySelector('thead .form-check-input')?.addEventListener('change', function(e) {
            const checkboxes = document.querySelectorAll('tbody .form-check-input');
            checkboxes.forEach(checkbox => {
                checkbox.checked = e.target.checked;
            });
        });

        // Search functionality enhancement
        document.querySelector('input[name="search"]')?.addEventListener('input', function(e) {
            if (e.target.value.length >= 3) {
                // Auto-submit form after 500ms delay
                clearTimeout(window.searchTimeout);
                window.searchTimeout = setTimeout(() => {
                    e.target.form.submit();
                }, 500);
            }
        });
    </script>

</body>
</html>