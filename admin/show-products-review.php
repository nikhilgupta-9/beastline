<?php
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';
require_once __DIR__ . '/models/setting.php';

// Initialize
$setting = new Setting($conn);

// Messages
$success = '';
$error = '';
$deleted = '';

// Handle delete action
if (isset($_GET['delete_id'])) {
    $review_id = (int) $_GET['delete_id'];
    $sql = "DELETE FROM product_reviews WHERE review_id = $review_id";
    if (mysqli_query($conn, $sql)) {
        $deleted = "Review deleted successfully.";
    } else {
        $error = "Error deleting review: " . mysqli_error($conn);
    }
}

// Handle status update
if (isset($_GET['toggle_status'])) {
    $review_id = (int) $_GET['toggle_status'];
    $sql = "UPDATE product_reviews SET status = CASE WHEN status = 1 THEN 0 ELSE 1 END WHERE review_id = $review_id";
    if (mysqli_query($conn, $sql)) {
        $success = "Review status updated successfully.";
    } else {
        $error = "Error updating review status: " . mysqli_error($conn);
    }
}

// Handle bulk actions
if (isset($_POST['bulk_action']) && isset($_POST['selected_reviews'])) {
    $selected_ids = $_POST['selected_reviews'];
    $ids_str = implode(',', array_map('intval', $selected_ids));
    
    switch ($_POST['bulk_action']) {
        case 'approve':
            $sql = "UPDATE product_reviews SET status = 1 WHERE review_id IN ($ids_str)";
            break;
        case 'pending':
            $sql = "UPDATE product_reviews SET status = 0 WHERE review_id IN ($ids_str)";
            break;
        case 'delete':
            $sql = "DELETE FROM product_reviews WHERE review_id IN ($ids_str)";
            break;
        default:
            $error = "Invalid bulk action.";
            break;
    }
    
    if (!empty($sql)) {
        if (mysqli_query($conn, $sql)) {
            $success = "Bulk action completed successfully.";
        } else {
            $error = "Error performing bulk action: " . mysqli_error($conn);
        }
    }
}

// Handle review submission/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    $review_id = isset($_POST['review_id']) ? (int) $_POST['review_id'] : 0;
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT);
    $rating = filter_input(INPUT_POST, 'rating', FILTER_SANITIZE_NUMBER_INT);
    $review_message = trim($_POST['review_message']);
    $reviewer_name = trim($_POST['reviewer_name']);
    $reviewer_email = filter_input(INPUT_POST, 'reviewer_email', FILTER_VALIDATE_EMAIL);
    $status = isset($_POST['status']) ? 1 : 0;
    
    // Validation
    if (empty($product_id)) {
        $error = "Please select a product.";
    } elseif ($rating < 1 || $rating > 5) {
        $error = "Rating must be between 1 and 5 stars.";
    } elseif (empty($review_message) || empty($reviewer_name)) {
        $error = "Please fill in all required fields.";
    } elseif (!$reviewer_email) {
        $error = "Please enter a valid email address.";
    }
    
    // Handle image upload
    $image_path = isset($_POST['current_image']) ? $_POST['current_image'] : '';
    if (empty($error) && isset($_FILES['reviewer_img']) && $_FILES['reviewer_img']['error'] === 0) {
        $upload_dir = "uploads/reviews/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = uniqid() . '_' . basename($_FILES["reviewer_img"]["name"]);
        $target_file = $upload_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Validate image
        $allowed_types = ["jpg", "png", "jpeg", "gif", "webp"];
        $check = getimagesize($_FILES["reviewer_img"]["tmp_name"]);
        
        if (!$check) {
            $error = "File is not a valid image.";
        } elseif ($_FILES["reviewer_img"]["size"] > 2097152) { // 2MB
            $error = "File size must be less than 2MB.";
        } elseif (!in_array($imageFileType, $allowed_types)) {
            $error = "Only JPG, JPEG, PNG, GIF & WEBP files are allowed.";
        } elseif (move_uploaded_file($_FILES["reviewer_img"]["tmp_name"], $target_file)) {
            // Delete old image if exists
            if (!empty($image_path) && file_exists($image_path)) {
                unlink($image_path);
            }
            $image_path = $target_file;
        } else {
            $error = "Sorry, there was an error uploading your file.";
        }
    }
    
    // Save to database
    if (empty($error)) {
        if ($review_id > 0) {
            // Update existing review
            $stmt = $conn->prepare("UPDATE product_reviews SET 
                product_id = ?, rating = ?, review_message = ?, reviewer_name = ?, 
                reviewer_email = ?, reviewver_img = ?, status = ?
                WHERE review_id = ?");
            $stmt->bind_param("iissssii", $product_id, $rating, $review_message, 
                $reviewer_name, $reviewer_email, $image_path, $status, $review_id);
        } else {
            // Insert new review
            $stmt = $conn->prepare("INSERT INTO product_reviews 
                (product_id, rating, review_message, reviewer_name, reviewer_email, reviewver_img, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("iissssi", $product_id, $rating, $review_message, 
                $reviewer_name, $reviewer_email, $image_path, $status);
        }
        
        if ($stmt->execute()) {
            $success = $review_id > 0 ? "Review updated successfully!" : "Review added successfully!";
            // Clear form for new entry
            if ($review_id == 0) {
                $_POST = array();
            }
        } else {
            $error = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch review for editing
$edit_review = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int) $_GET['edit_id'];
    $sql = "SELECT * FROM product_reviews WHERE review_id = $edit_id";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
        $edit_review = mysqli_fetch_assoc($result);
    }
}

// Get filter parameters
$filter_product = isset($_GET['product']) ? (int) $_GET['product'] : 0;
$filter_rating = isset($_GET['rating']) ? (int) $_GET['rating'] : 0;
$filter_status = isset($_GET['status']) ? ($_GET['status'] == 'approved' ? 1 : 0) : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build filter conditions
$where = [];
$params = [];

if ($filter_product > 0) {
    $where[] = "r.product_id = $filter_product";
}

if ($filter_rating > 0) {
    $where[] = "r.rating = $filter_rating";
}

if ($filter_status !== null) {
    $where[] = "r.status = $filter_status";
}

if (!empty($search)) {
    $search_term = mysqli_real_escape_string($conn, $search);
    $where[] = "(r.reviewer_name LIKE '%$search_term%' OR 
                 r.reviewer_email LIKE '%$search_term%' OR 
                 r.review_message LIKE '%$search_term%' OR
                 p.pro_name LIKE '%$search_term%')";
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Pagination
$per_page = 10;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Count total reviews
$count_sql = "SELECT COUNT(*) as total 
              FROM product_reviews r
              LEFT JOIN products p ON r.product_id = p.pro_id 
              $where_clause";
$count_result = mysqli_query($conn, $count_sql);
$total_reviews = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_reviews / $per_page);

// Fetch reviews with filters and pagination
$reviews_sql = "SELECT r.*, p.pro_name, p.pro_img 
                FROM product_reviews r
                LEFT JOIN products p ON r.product_id = p.pro_id 
                $where_clause 
                ORDER BY r.created_at DESC 
                LIMIT $offset, $per_page";
$reviews_result = mysqli_query($conn, $reviews_sql);

// Fetch products for dropdown
$products_sql = "SELECT pro_id, pro_name FROM products ORDER BY pro_name";
$products_result = mysqli_query($conn, $products_sql);

// Fetch statistics
$stats_sql = "SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as avg_rating,
                SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as pending
              FROM product_reviews";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);
?>
<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Product Reviews Management | Admin Panel</title>
    <link rel="icon" href="<?php echo htmlspecialchars($setting->get('favicon', 'assets/img/logo.png')); ?>" type="image/png">
    <?php include "links.php"; ?>
    
    <!-- Include DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
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
                                        <h2 class="m-0">Product Reviews Management</h2>
                                        <p class="text-muted mb-0">Manage and moderate customer reviews</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="white_card_body">
                                <!-- Statistics Cards -->
                                <div class="row mb-4">
                                    <div class="col-xl-3 col-md-6">
                                        <div class="card bg-primary text-white mb-4">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h4 class="mb-0"><?= number_format($stats['total_reviews']) ?></h4>
                                                        <p class="mb-0">Total Reviews</p>
                                                    </div>
                                                    <i class="fas fa-comments fa-2x opacity-50"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6">
                                        <div class="card bg-success text-white mb-4">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h4 class="mb-0"><?= number_format($stats['approved']) ?></h4>
                                                        <p class="mb-0">Approved</p>
                                                    </div>
                                                    <i class="fas fa-check-circle fa-2x opacity-50"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6">
                                        <div class="card bg-warning text-white mb-4">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h4 class="mb-0"><?= number_format($stats['pending']) ?></h4>
                                                        <p class="mb-0">Pending</p>
                                                    </div>
                                                    <i class="fas fa-clock fa-2x opacity-50"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-md-6">
                                        <div class="card bg-info text-white mb-4">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h4 class="mb-0"><?= number_format($stats['avg_rating'], 1) ?>/5</h4>
                                                        <p class="mb-0">Avg. Rating</p>
                                                    </div>
                                                    <i class="fas fa-star fa-2x opacity-50"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Messages -->
                                <?php if (!empty($success)): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <?= $success ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($error)): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <?= $error ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($deleted)): ?>
                                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                                        <?= $deleted ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Filters -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Filters</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="GET" class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label">Product</label>
                                                <select class="form-control" name="product">
                                                    <option value="0">All Products</option>
                                                    <?php while($product = mysqli_fetch_assoc($products_result)): ?>
                                                        <option value="<?= $product['pro_id'] ?>" <?= $filter_product == $product['pro_id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($product['pro_name']) ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            
                                            <div class="col-md-2">
                                                <label class="form-label">Rating</label>
                                                <select class="form-control" name="rating">
                                                    <option value="0">All Ratings</option>
                                                    <option value="5" <?= $filter_rating == 5 ? 'selected' : '' ?>>5 Stars</option>
                                                    <option value="4" <?= $filter_rating == 4 ? 'selected' : '' ?>>4 Stars</option>
                                                    <option value="3" <?= $filter_rating == 3 ? 'selected' : '' ?>>3 Stars</option>
                                                    <option value="2" <?= $filter_rating == 2 ? 'selected' : '' ?>>2 Stars</option>
                                                    <option value="1" <?= $filter_rating == 1 ? 'selected' : '' ?>>1 Star</option>
                                                </select>
                                            </div>
                                            
                                            <div class="col-md-2">
                                                <label class="form-label">Status</label>
                                                <select class="form-control" name="status">
                                                    <option value="">All Status</option>
                                                    <option value="approved" <?= $filter_status === 1 ? 'selected' : '' ?>>Approved</option>
                                                    <option value="pending" <?= $filter_status === 0 ? 'selected' : '' ?>>Pending</option>
                                                </select>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <label class="form-label">Search</label>
                                                <input type="text" class="form-control" name="search" 
                                                       placeholder="Name, email, or review" value="<?= htmlspecialchars($search) ?>">
                                            </div>
                                            
                                            <div class="col-md-2 d-flex align-items-end">
                                                <div class="d-flex gap-2 w-100">
                                                    <button type="submit" class="btn btn-primary flex-fill">
                                                        <i class="fas fa-filter me-2"></i>Filter
                                                    </button>
                                                    <a href="?" class="btn btn-outline-secondary">
                                                        <i class="fas fa-redo"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                
                                <!-- Reviews Table -->
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">Customer Reviews</h5>
                                        <div class="d-flex gap-2">
                                            <!-- Bulk Actions -->
                                            <form method="POST" class="d-flex gap-2" onsubmit="return confirm('Are you sure?')">
                                                <select class="form-control form-control-sm" name="bulk_action" style="width: 120px;">
                                                    <option value="">Bulk Action</option>
                                                    <option value="approve">Approve Selected</option>
                                                    <option value="pending">Mark as Pending</option>
                                                    <option value="delete">Delete Selected</option>
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-check me-1"></i>Apply
                                                </button>
                                            </form>
                                            <a href="#addReviewModal" class="btn btn-sm btn-primary" data-bs-toggle="modal">
                                                <i class="fas fa-plus me-2"></i>Add Review
                                            </a>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover" id="reviewsTable">
                                                <thead>
                                                    <tr>
                                                        <th width="30">
                                                            <input type="checkbox" id="selectAll" class="form-check-input">
                                                        </th>
                                                        <th>Reviewer</th>
                                                        <th>Product</th>
                                                        <th>Rating</th>
                                                        <th>Review</th>
                                                        <th>Date</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (mysqli_num_rows($reviews_result) > 0): ?>
                                                        <?php while ($review = mysqli_fetch_assoc($reviews_result)): ?>
                                                            <tr>
                                                                <td>
                                                                    <input type="checkbox" name="selected_reviews[]" 
                                                                           value="<?= $review['review_id'] ?>" class="form-check-input review-checkbox">
                                                                </td>
                                                                <td>
                                                                    <div class="d-flex align-items-center">
                                                                        <?php if (!empty($review['reviewver_img'])): ?>
                                                                            <img src="<?= $review['reviewver_img'] ?>" 
                                                                                 alt="<?= htmlspecialchars($review['reviewer_name']) ?>" 
                                                                                 class="rounded-circle me-2" width="32" height="32">
                                                                        <?php endif; ?>
                                                                        <div>
                                                                            <div class="fw-bold"><?= htmlspecialchars($review['reviewer_name']) ?></div>
                                                                            <small class="text-muted"><?= htmlspecialchars($review['reviewer_email']) ?></small>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="d-flex align-items-center">
                                                                        <?php if (!empty($review['pro_img'])): ?>
                                                                            <img src="assets/img/uploads/<?= $review['pro_img'] ?>" 
                                                                                 alt="<?= htmlspecialchars($review['pro_name']) ?>" 
                                                                                 class="rounded me-2" width="40" height="40">
                                                                        <?php endif; ?>
                                                                        <div class="text-truncate" style="max-width: 150px;">
                                                                            <?= htmlspecialchars($review['pro_name']) ?>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="rating-stars text-warning">
                                                                        <?= str_repeat('★', $review['rating']) ?>
                                                                        <?= str_repeat('☆', 5 - $review['rating']) ?>
                                                                    </div>
                                                                </td>
                                                                <td class="text-truncate" style="max-width: 200px;">
                                                                    <?= htmlspecialchars($review['review_message']) ?>
                                                                </td>
                                                                <td>
                                                                    <?= date('M j, Y', strtotime($review['created_at'])) ?>
                                                                    <br>
                                                                    <small class="text-muted"><?= date('h:i A', strtotime($review['created_at'])) ?></small>
                                                                </td>
                                                                <td>
                                                                    <span class="badge <?= $review['status'] == 1 ? 'bg-success' : 'bg-warning' ?>">
                                                                        <?= $review['status'] == 1 ? 'Approved' : 'Pending' ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <div class="btn-group btn-group-sm">
                                                                        <a href="?toggle_status=<?= $review['review_id'] ?>" 
                                                                           class="btn btn-outline-<?= $review['status'] == 1 ? 'warning' : 'success' ?>"
                                                                           title="<?= $review['status'] == 1 ? 'Mark as Pending' : 'Approve' ?>">
                                                                            <i class="fas fa-<?= $review['status'] == 1 ? 'clock' : 'check' ?>"></i>
                                                                        </a>
                                                                        <a href="?edit_id=<?= $review['review_id'] ?>#addReviewModal" 
                                                                           class="btn btn-outline-primary" data-bs-toggle="modal"
                                                                           title="Edit">
                                                                            <i class="fas fa-edit"></i>
                                                                        </a>
                                                                        <a href="?delete_id=<?= $review['review_id'] ?>" 
                                                                           class="btn btn-outline-danger"
                                                                           onclick="return confirm('Are you sure you want to delete this review?')"
                                                                           title="Delete">
                                                                            <i class="fas fa-trash"></i>
                                                                        </a>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        <?php endwhile; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="8" class="text-center py-4">
                                                                <div class="text-muted">
                                                                    <i class="fas fa-comments fa-3x mb-3"></i>
                                                                    <h5>No reviews found</h5>
                                                                    <p>Try adjusting your filters or add a new review</p>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <!-- Pagination -->
                                        <?php if ($total_pages > 1): ?>
                                            <nav aria-label="Page navigation" class="mt-4">
                                                <ul class="pagination justify-content-center">
                                                    <?php if ($page > 1): ?>
                                                        <li class="page-item">
                                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                                                <i class="fas fa-chevron-left"></i>
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                    
                                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                                                <?= $i ?>
                                                            </a>
                                                        </li>
                                                    <?php endfor; ?>
                                                    
                                                    <?php if ($page < $total_pages): ?>
                                                        <li class="page-item">
                                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                                                <i class="fas fa-chevron-right"></i>
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </nav>
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

    <!-- Add/Edit Review Modal -->
    <div class="modal fade" id="addReviewModal" tabindex="-1" aria-labelledby="addReviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addReviewModalLabel">
                            <?= isset($edit_review) ? 'Edit Review' : 'Add New Review' ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="review_id" value="<?= isset($edit_review) ? $edit_review['review_id'] : '' ?>">
                        <input type="hidden" name="current_image" value="<?= isset($edit_review) ? $edit_review['reviewver_img'] : '' ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Product *</label>
                                <select class="form-control" name="product_id" required>
                                    <option value="">Select Product</option>
                                    <?php 
                                    mysqli_data_seek($products_result, 0);
                                    while ($product = mysqli_fetch_assoc($products_result)): 
                                    ?>
                                        <option value="<?= $product['pro_id'] ?>" 
                                            <?= (isset($edit_review) && $edit_review['product_id'] == $product['pro_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($product['pro_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rating *</label>
                                <div class="star-rating">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>"
                                               <?= (isset($edit_review) && $edit_review['rating'] == $i) || (!isset($edit_review) && $i == 5) ? 'checked' : '' ?>>
                                        <label for="star<?= $i ?>" title="<?= $i ?> stars">
                                            <i class="fas fa-star"></i>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Reviewer Name *</label>
                                <input type="text" class="form-control" name="reviewer_name" required
                                       value="<?= isset($edit_review) ? htmlspecialchars($edit_review['reviewer_name']) : '' ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Reviewer Email *</label>
                                <input type="email" class="form-control" name="reviewer_email" required
                                       value="<?= isset($edit_review) ? htmlspecialchars($edit_review['reviewer_email']) : '' ?>">
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label class="form-label">Review Message *</label>
                                <textarea class="form-control" name="review_message" rows="4" required><?= isset($edit_review) ? htmlspecialchars($edit_review['review_message']) : '' ?></textarea>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Reviewer Image</label>
                                <?php if (isset($edit_review) && !empty($edit_review['reviewver_img'])): ?>
                                    <div class="mb-2">
                                        <img src="<?= $edit_review['reviewver_img'] ?>" alt="Current Image" 
                                             class="img-thumbnail" style="max-width: 150px;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" name="reviewer_img" accept="image/*">
                                <small class="text-muted">Optional. Max 2MB (JPG, PNG, GIF, WEBP)</small>
                            </div>
                            
                            <div class="col-md-6 mb-3 d-flex align-items-center">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="status" value="1" 
                                           <?= isset($edit_review) && $edit_review['status'] == 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label">Approved</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="submit_review" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            <?= isset($edit_review) ? 'Update Review' : 'Add Review' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#reviewsTable').DataTable({
                paging: false, // Disable DataTables pagination (we have custom pagination)
                searching: false, // Disable search box (we have custom filter)
                info: false, // Disable "Showing X of Y entries"
                ordering: true, // Enable sorting
                responsive: true
            });
            
            // Select all checkboxes
            $('#selectAll').click(function() {
                $('.review-checkbox').prop('checked', this.checked);
            });
            
            // Star rating functionality
            $('.star-rating input').change(function() {
                const rating = $(this).val();
                $('.star-rating label').css('color', '#ddd');
                $(this).nextAll('label').addBack().find('label').css('color', '#ffc107');
            });
            
            // Open modal with edit data
            <?php if (isset($edit_review)): ?>
                $(document).ready(function() {
                    var modal = new bootstrap.Modal(document.getElementById('addReviewModal'));
                    modal.show();
                });
            <?php endif; ?>
            
            // Auto-close alerts after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
            
            // Confirm before bulk delete
            $('form[onsubmit]').submit(function(e) {
                const action = $(this).find('select[name="bulk_action"]').val();
                if (action === 'delete') {
                    const count = $(this).find('input[name="selected_reviews[]"]:checked').length;
                    if (count === 0) {
                        alert('Please select at least one review to delete.');
                        e.preventDefault();
                        return false;
                    }
                    return confirm('Are you sure you want to delete ' + count + ' review(s)?');
                }
            });
        });
        
        // Function to confirm before deleting single review
        function confirmDelete() {
            return confirm('Are you sure you want to delete this review?');
        }
    </script>
</body>
</html>