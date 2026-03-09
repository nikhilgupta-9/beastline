<?php
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';
require_once __DIR__ . '/models/setting.php';

// Initialize Settings
$setting = new Setting($conn);

// Handle actions
if (isset($_GET['action'])) {
    $id = (int)$_GET['id'] ?? 0;

    switch ($_GET['action']) {
        case 'toggle_status':
            $stmt = $conn->prepare("UPDATE categories SET status = 1 - status WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Status updated successfully";
            } else {
                $_SESSION['error'] = "Error updating status";
            }
            $stmt->close();
            break;

        case 'delete':
            // Get category image
            $stmt = $conn->prepare("SELECT image FROM categories WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->bind_result($image);
            $stmt->fetch();
            $stmt->close();

            // Delete from database
            $delete_stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $delete_stmt->bind_param("i", $id);

            if ($delete_stmt->execute()) {
                // Delete image file if exists
                if (!empty($image) && file_exists("uploads/category/" . $image)) {
                    unlink("uploads/category/" . $image);
                }
                $_SESSION['success'] = "Category deleted successfully";
            } else {
                $_SESSION['error'] = "Error deleting category";
            }
            $delete_stmt->close();
            break;

        case 'make_featured':
            $stmt = $conn->prepare("UPDATE categories SET featured = 1 - featured WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Featured status updated";
            }
            $stmt->close();
            break;
    }

    header("Location: view-categories.php");
    exit();
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_action'])) {
    $selected_ids = $_POST['selected_ids'] ?? [];

    if (!empty($selected_ids)) {
        $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));

        switch ($_POST['bulk_action']) {
            case 'activate':
                $stmt = $conn->prepare("UPDATE categories SET status = 1 WHERE id IN ($placeholders)");
                break;
            case 'deactivate':
                $stmt = $conn->prepare("UPDATE categories SET status = 0 WHERE id IN ($placeholders)");
                break;
            case 'delete':
                $stmt = $conn->prepare("DELETE FROM categories WHERE id IN ($placeholders)");
                break;
            case 'featured':
                $stmt = $conn->prepare("UPDATE categories SET featured = 1 WHERE id IN ($placeholders)");
                break;
            case 'unfeatured':
                $stmt = $conn->prepare("UPDATE categories SET featured = 0 WHERE id IN ($placeholders)");
                break;
            default:
                $stmt = null;
        }

        if ($stmt) {
            $types = str_repeat('i', count($selected_ids));
            $stmt->bind_param($types, ...$selected_ids);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Bulk action completed successfully";
            } else {
                $_SESSION['error'] = "Error performing bulk action";
            }
            $stmt->close();
        }
    } else {
        $_SESSION['error'] = "No categories selected";
    }

    header("Location: view-categories.php");
    exit();
}

// Get category stats
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(status = 1) as active,
    SUM(status = 0) as inactive,
    SUM(featured = 1) as featured
    FROM categories";
$stats_result = mysqli_query($conn, $stats_query);
$stats = $stats_result ? mysqli_fetch_assoc($stats_result) : ['total' => 0, 'active' => 0, 'inactive' => 0, 'featured' => 0];
?>

<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Manage Categories | Admin <?php echo htmlspecialchars($setting->get('site_name')); ?></title>
    <link rel="icon" href="<?php echo htmlspecialchars($setting->get('favicon', 'assets/img/logo.png')); ?>" type="image/png">

    <?php include "links.php"; ?>

    <style>
        .category-image {
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

        .category-checkbox {
            cursor: pointer;
        }

        .featured-badge {
            cursor: pointer;
        }

        #selectAll {
            cursor: pointer;
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
                                        <h3 class="m-0">Category Management</h3>
                                    </div>
                                    <div class="action-btn">
                                        <a href="add-categories.php" class="btn_1">Add New</a>
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

                                <!-- Stats Overview -->
                                <div class="row mb-4">
                                    <div class="col-md-3 col-sm-6">
                                        <div class="stats-card bg-light border">
                                            <i class="fas fa-layer-group text-primary"></i>
                                            <h4><?php echo $stats['total']; ?></h4>
                                            <p>Total Categories</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="stats-card bg-light border">
                                            <i class="fas fa-check-circle text-success"></i>
                                            <h4><?php echo $stats['active']; ?></h4>
                                            <p>Active</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="stats-card bg-light border">
                                            <i class="fas fa-ban text-danger"></i>
                                            <h4><?php echo $stats['inactive']; ?></h4>
                                            <p>Inactive</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="stats-card bg-light border">
                                            <i class="fas fa-star text-warning"></i>
                                            <h4><?php echo $stats['featured']; ?></h4>
                                            <p>Featured</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bulk Actions -->
                                <form method="POST" id="bulkForm" class="bulk-actions">
                                    <div class="d-flex align-items-center">
                                        <div class="mr-3">
                                            <select class="form-control form-control-sm" name="bulk_action" style="min-width: 150px;">
                                                <option value="">Bulk Actions</option>
                                                <option value="activate">Activate Selected</option>
                                                <option value="deactivate">Deactivate Selected</option>
                                                <option value="featured">Mark as Featured</option>
                                                <option value="unfeatured">Remove Featured</option>
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
                                                <input type="text" class="form-control" placeholder="Search categories..." id="searchInput">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary" type="button">
                                                        <i class="fas fa-search"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <!-- Categories Table -->
                                <div class="QA_section">
                                    <div class="QA_table mb_30">
                                        <table class="table lms_table_active">
                                            <thead>
                                                <tr>
                                                    <th scope="col" width="3%">
                                                        <input type="checkbox" id="selectAll">
                                                    </th>
                                                    <th scope="col">#</th>
                                                    <th scope="col">Category Id</th>
                                                    <th scope="col">Parent Id</th>
                                                    <th scope="col">Category Details</th>
                                                    <th scope="col">Slug URL</th>
                                                    <th scope="col">Status</th>
                                                    <th scope="col">Featured</th>
                                                    <th scope="col">Added On</th>
                                                    <th scope="col">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Get total categories count
                                                $count_query = "SELECT COUNT(*) as total FROM categories";
                                                $count_result = mysqli_query($conn, $count_query);
                                                $total_rows = $count_result ? mysqli_fetch_assoc($count_result)['total'] : 0;

                                                // Pagination
                                                $limit = 10;
                                                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                                                $offset = ($page - 1) * $limit;
                                                $total_pages = ceil($total_rows / $limit);

                                                // Fetch categories with pagination
                                                $sql = "SELECT * FROM `categories` ORDER BY id DESC LIMIT $offset, $limit";
                                                $check = mysqli_query($conn, $sql);
                                                $sno = $offset + 1;

                                                if (mysqli_num_rows($check) > 0) {
                                                    while ($result = mysqli_fetch_assoc($check)) {
                                                        // Format status with badge
                                                        $status = $result['status'] == '1'
                                                            ? '<a href="?action=toggle_status&id=' . $result['id'] . '" class="badge bg-success bg-opacity-10 text-success text-light status-badge" onclick="return confirm(\'Change status to inactive?\')">Active</a>'
                                                            : '<a href="?action=toggle_status&id=' . $result['id'] . '" class="badge bg-danger bg-opacity-10 text-danger text-light status-badge" onclick="return confirm(\'Change status to active?\')">Inactive</a>';

                                                        // Featured badge
                                                        $featured = $result['featured'] == '1'
                                                            ? '<a href="?action=make_featured&id=' . $result['id'] . '" class="badge bg-warning bg-opacity-10 text-warning featured-badge" onclick="return confirm(\'Remove from featured?\')"><i class="fas fa-star"></i> Featured</a>'
                                                            : '<a href="?action=make_featured&id=' . $result['id'] . '" class="badge bg-secondary bg-opacity-10 text-secondary featured-badge" onclick="return confirm(\'Mark as featured?\')"><i class="far fa-star"></i> Regular</a>';

                                                        // Format date
                                                        $added_on = date('d M Y', strtotime($result['added_on']));

                                                        // Image preview
                                                        $image_html = '';
                                                        if (!empty($result['image'])) {
                                                            $image_html = '<img src="uploads/category/' . htmlspecialchars($result['image']) . '" 
                                                                           alt="' . htmlspecialchars($result['categories']) . '"
                                                                           class="category-image mr-2">';
                                                        } elseif (!empty($result['icon_class'])) {
                                                            $image_html = '<i class="' . htmlspecialchars($result['icon_class']) . ' text-muted mr-2"></i>';
                                                        } else {
                                                            $image_html = '<i class="fas fa-folder text-muted mr-2"></i>';
                                                        }
                                                ?>
                                                        <tr>
                                                            <td class="text-center">
                                                                <input type="checkbox" name="selected_ids[]" value="<?php echo $result['id']; ?>" class="category-checkbox">
                                                            </td>
                                                            <td class="text-center"><?php echo $sno++; ?></td>
                                                            <td class="fw-semibold"><?php echo $result['id']; ?></td>
                                                            <td class="fw-semibold"><?php echo $result['parent_id']; ?></td>
                                                            <td class="text-capitalize">
                                                                <div class="d-flex align-items-center">
                                                                    <?php echo $image_html; ?>
                                                                    <div>
                                                                        <strong><?php echo $result['categories']; ?></strong>
                                                                        <?php if ($result['parent_id'] > 0): ?>
                                                                            <br><small class="text-muted">Subcategory</small>
                                                                        <?php endif; ?>
                                                                        <?php if (!empty($result['description'])): ?>
                                                                            <div class="text-muted small mt-1"><?php echo htmlspecialchars(substr($result['description'], 0, 50)); ?>...</div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td><span class="text-muted"><?php echo $result['slug_url']; ?></span></td>
                                                            <td class="text-center"><?php echo $status; ?></td>
                                                            <td class="text-center"><?php echo $featured; ?></td>
                                                            <td class="text-center"><?php echo $added_on; ?></td>
                                                            <td class="text-center">
                                                                <div class="d-flex justify-content-center gap-2">
                                                                    <a href="edit_category.php?id=<?php echo $result['id']; ?>"
                                                                        class="btn btn-sm btn-outline-primary rounded-circle p-2"
                                                                        data-bs-toggle="tooltip" title="Edit">
                                                                        <i class="fas fa-pen fs-6"></i>
                                                                    </a>
                                                                    <a href="?action=delete&id=<?php echo $result['id']; ?>"
                                                                        onclick='return confirm("Are you sure you want to delete this category?")'
                                                                        class="btn btn-sm btn-outline-danger rounded-circle p-2"
                                                                        data-bs-toggle="tooltip" title="Delete">
                                                                        <i class="fas fa-trash fs-6"></i>
                                                                    </a>
                                                                    <a href="add-categories.php?parent_id=<?php echo $result['id']; ?>"
                                                                        class="btn btn-sm btn-outline-info rounded-circle p-2"
                                                                        data-bs-toggle="tooltip" title="Add Subcategory">
                                                                        <i class="fas fa-plus fs-6"></i>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php
                                                    }
                                                } else {
                                                    ?>
                                                    <tr>
                                                        <td colspan="9" class="text-center py-4">
                                                            <div class="empty-state">
                                                                <i class="fas fa-layer-group text-muted"></i>
                                                                <h4 class="mt-3">No Categories Found</h4>
                                                                <p class="text-muted mb-4">You haven't added any categories yet.</p>
                                                                <a href="add-categories.php" class="btn btn-primary">
                                                                    <i class="fas fa-plus mr-1"></i> Add First Category
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
                                                                Showing <?php echo min($limit, $total_rows - $offset); ?> of <?php echo $total_rows; ?> categories
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
            const checkboxes = document.querySelectorAll('.category-checkbox');
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
            const selected = document.querySelectorAll('.category-checkbox:checked');
            const action = document.querySelector('select[name="bulk_action"]').value;

            if (selected.length === 0) {
                alert('Please select at least one category.');
                return false;
            }

            if (!action) {
                alert('Please select a bulk action.');
                return false;
            }

            let message = '';
            switch (action) {
                case 'activate':
                    message = 'Activate ' + selected.length + ' selected categories?';
                    break;
                case 'deactivate':
                    message = 'Deactivate ' + selected.length + ' selected categories?';
                    break;
                case 'featured':
                    message = 'Mark ' + selected.length + ' selected categories as featured?';
                    break;
                case 'unfeatured':
                    message = 'Remove ' + selected.length + ' selected categories from featured?';
                    break;
                case 'delete':
                    message = 'Delete ' + selected.length + ' selected categories? This action cannot be undone.';
                    break;
            }

            return confirm(message);
        }

        // Update checkboxes when any checkbox changes
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('category-checkbox')) {
                const allChecked = Array.from(document.querySelectorAll('.category-checkbox')).every(cb => cb.checked);
                const someChecked = Array.from(document.querySelectorAll('.category-checkbox')).some(cb => cb.checked);

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