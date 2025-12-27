<?php include "auth_check.php"; ?>
<?php
include "db-conn.php";

// Handle delete action
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $sql = "DELETE FROM inquiries WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Inquiry deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting inquiry: " . $conn->error;
    }
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle status update
if (isset($_GET['mark_read'])) {
    $id = intval($_GET['mark_read']);
    $sql = "UPDATE inquiries SET status = 'read' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Inquiry marked as read!";
    }
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get statistics
$total_inquiries = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM inquiries"))['total'];
$new_inquiries = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM inquiries WHERE status = 'new'"))['total'];
$read_inquiries = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM inquiries WHERE status = 'read'"))['total'];
$today_inquiries = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM inquiries WHERE DATE(created_at) = CURDATE()"))['total'];

// Handle search
$search = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $search_condition = "WHERE (name LIKE '%$search%' OR email LIKE '%$search%' OR subject LIKE '%$search%' OR message LIKE '%$search%')";
} else {
    $search_condition = "";
}

// Get inquiries with search condition
$sql = "SELECT * FROM inquiries $search_condition ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
$total_results = mysqli_num_rows($result);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Customer Inquiries | Admin Panel</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">
    
    <?php include "links.php"; ?>
    
    <style>
        .inquiry-avatar {
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
        .badge-new { 
            background: #e3f2fd; 
            color: #1976d2; 
        }
        .badge-read { 
            background: #e8f5e9; 
            color: #388e3c; 
        }
        .badge-urgent { 
            background: #ffebee; 
            color: #c62828; 
        }
        .search-box {
            max-width: 300px;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
        }
        .message-preview {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .message-preview:hover {
            white-space: normal;
            overflow: visible;
            position: absolute;
            background: white;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            max-width: 300px;
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
                    
                        <div class="white_card card_height_100 mb_30">
                            <div class="card-header bg-white border-0 py-3">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <div class="mb-2 mb-md-0">
                                        <h2 class="mb-0 fw-bold">Customer Inquiries</h2>
                                        <p class="text-muted mb-0">Manage and respond to customer inquiries</p>
                                    </div>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <!-- Search Form -->
                                        <form method="GET" class="d-flex search-box">
                                            <div class="input-group">
                                                <input type="text" name="search" class="form-control" 
                                                       placeholder="Search inquiries..." 
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
                                        
                                    </div>
                                </div>
                            </div>
                            
                            <div class="white_card_body">
                                <!-- Success/Error Messages -->
                                <?php if (isset($_SESSION['success'])): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($_SESSION['error'])): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>
                                
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
                                                        <th scope="col">Contact Info</th>
                                                        <th scope="col">Subject</th>
                                                        <th scope="col">Message Preview</th>
                                                        <th scope="col">Status</th>
                                                        <th scope="col">Date & Time</th>
                                                        <th scope="col" class="text-center">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $no = 1;
                                                    if (mysqli_num_rows($result) > 0) {
                                                        while ($row = mysqli_fetch_assoc($result)) {
                                                            $initial = strtoupper(substr($row['name'], 0, 1));
                                                            $created_date = date('M j, Y', strtotime($row['created_at']));
                                                            $created_time = date('g:i A', strtotime($row['created_at']));
                                                            $status_class = $row['status'] == 'read' ? 'badge-read' : 'badge-new';
                                                            $status_text = $row['status'] == 'read' ? 'Read' : 'New';
                                                            ?>
                                                            <tr>
                                                                <td>
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" value="<?= $row['id'] ?>">
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="d-flex align-items-center">
                                                                        <div class="inquiry-avatar me-3">
                                                                            <?= $initial ?>
                                                                        </div>
                                                                        <div>
                                                                            <div class="fw-bold"><?= htmlspecialchars($row['name']) ?></div>
                                                                            <small class="text-muted">Inquiry #<?= $row['id'] ?></small>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="small">
                                                                        <div class="d-flex align-items-center mb-1">
                                                                            <i class="fas fa-envelope text-muted me-2" style="width: 16px;"></i>
                                                                            <?= htmlspecialchars($row['email']) ?>
                                                                        </div>
                                                                        <div class="d-flex align-items-center">
                                                                            <i class="fas fa-phone text-muted me-2" style="width: 16px;"></i>
                                                                            <?= htmlspecialchars($row['phone']) ?>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <span class="fw-semibold"><?= htmlspecialchars($row['subject']) ?></span>
                                                                </td>
                                                                <td>
                                                                    <div class="message-preview" title="<?= htmlspecialchars($row['message']) ?>">
                                                                        <?= htmlspecialchars($row['message']) ?>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <span class="status-badge <?= $status_class ?>">
                                                                        <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                                                                        <?= $status_text ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <div>
                                                                        <div class="fw-bold small"><?= $created_date ?></div>
                                                                        <small class="text-muted"><?= $created_time ?></small>
                                                                    </div>
                                                                </td>
                                                                <td class="text-center">
                                                                    <div class="btn-group" role="group">
                                                                        <a href="view_inquiry.php?id=<?= $row['id'] ?>" 
                                                                           class="btn btn-sm btn-outline-primary action-btn" 
                                                                           data-bs-toggle="tooltip" title="View Details">
                                                                            <i class="fas fa-eye"></i>
                                                                        </a>
                                                                        <a href="reply_inquiry.php?id=<?= $row['id'] ?>" 
                                                                           class="btn btn-sm btn-outline-info action-btn"
                                                                           data-bs-toggle="tooltip" title="Reply">
                                                                            <i class="fas fa-reply"></i>
                                                                        </a>
                                                                        <?php if ($row['status'] == 'new'): ?>
                                                                            <a href="?mark_read=<?= $row['id'] ?>" 
                                                                               class="btn btn-sm btn-outline-success action-btn"
                                                                               data-bs-toggle="tooltip" title="Mark as Read">
                                                                                <i class="fas fa-check"></i>
                                                                            </a>
                                                                        <?php endif; ?>
                                                                        <a href="?delete=<?= $row['id'] ?>" 
                                                                           class="btn btn-sm btn-outline-danger action-btn"
                                                                           data-bs-toggle="tooltip" title="Delete" 
                                                                           onclick="return confirm('Are you sure you want to delete this inquiry?');">
                                                                            <i class="fas fa-trash"></i>
                                                                        </a>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            <?php
                                                            $no++;
                                                        }
                                                    } else {
                                                        ?>
                                                        <tr>
                                                            <td colspan="8" class="text-center py-4">
                                                                <div class="text-muted">
                                                                    <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
                                                                    <h5>No inquiries found</h5>
                                                                    <p><?= empty($search) ? 'No customer inquiries yet.' : 'No inquiries match your search criteria.' ?></p>
                                                                    <?php if (!empty($search)): ?>
                                                                        <a href="?" class="btn btn-primary">View All Inquiries</a>
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
                                        
                                        <!-- Table Footer with Pagination -->
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <div class="text-muted">
                                                Showing <?= min($no - 1, $total_results) ?> of <?= $total_results ?> inquiries
                                            </div>
                                            <nav>
                                                <ul class="pagination pagination-sm mb-0">
                                                    <li class="page-item disabled">
                                                        <a class="page-link" href="#" tabindex="-1">Previous</a>
                                                    </li>
                                                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                                                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                                                    <li class="page-item">
                                                        <a class="page-link" href="#">Next</a>
                                                    </li>
                                                </ul>
                                            </nav>
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

        // Auto-search functionality
        document.querySelector('input[name="search"]')?.addEventListener('input', function(e) {
            if (e.target.value.length >= 3) {
                clearTimeout(window.searchTimeout);
                window.searchTimeout = setTimeout(() => {
                    e.target.form.submit();
                }, 500);
            }
        });

        // Message preview hover effect
        document.querySelectorAll('.message-preview').forEach(preview => {
            preview.addEventListener('mouseenter', function() {
                this.style.whiteSpace = 'normal';
                this.style.overflow = 'visible';
                this.style.position = 'absolute';
                this.style.background = 'white';
                this.style.padding = '10px';
                this.style.border = '1px solid #dee2e6';
                this.style.borderRadius = '5px';
                this.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
                this.style.zIndex = '1000';
                this.style.maxWidth = '300px';
            });
            
            preview.addEventListener('mouseleave', function() {
                this.style.whiteSpace = 'nowrap';
                this.style.overflow = 'hidden';
                this.style.position = 'static';
                this.style.background = '';
                this.style.padding = '';
                this.style.border = '';
                this.style.borderRadius = '';
                this.style.boxShadow = '';
                this.style.zIndex = '';
                this.style.maxWidth = '200px';
            });
        });
    </script>

</body>
</html>