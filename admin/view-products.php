<?php
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';
require_once __DIR__ . '/models/Setting.php';

// Initialize
$setting = new Setting($conn);

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? intval($_GET['category']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$stock_status = isset($_GET['stock_status']) ? $_GET['stock_status'] : '';

// Pagination
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Build query with filters
$sql = "SELECT p.*, 
               c.categories as category_name,
               b.brand_name as brand_name
        FROM products p
        LEFT JOIN categories c ON p.pro_cate = c.cate_id
        LEFT JOIN pro_brands b ON p.brand_name = b.id
        WHERE 1=1";

$countSql = "SELECT COUNT(*) as total 
             FROM products p
             LEFT JOIN categories c ON p.pro_cate = c.cate_id
             LEFT JOIN pro_brands b ON p.brand_name = b.id
             WHERE 1=1";

if (!empty($search)) {
    $searchTerm = mysqli_real_escape_string($conn, $search);
    $sql .= " AND (p.pro_name LIKE '%$searchTerm%' 
                  OR p.pro_id LIKE '%$searchTerm%'
                  OR p.sku LIKE '%$searchTerm%'
                  OR b.brand_name LIKE '%$searchTerm%')";
    $countSql .= " AND (p.pro_name LIKE '%$searchTerm%' 
                        OR p.pro_id LIKE '%$searchTerm%'
                        OR p.sku LIKE '%$searchTerm%'
                        OR b.brand_name LIKE '%$searchTerm%')";
}

if (!empty($category)) {
    $sql .= " AND p.pro_cate = $category";
    $countSql .= " AND p.pro_cate = $category";
}

if ($status !== '') {
    $sql .= " AND p.status = '$status'";
    $countSql .= " AND p.status = '$status'";
}

// Get categories for filter dropdown
$categories_sql = "SELECT DISTINCT p.pro_cate, c.categories 
                   FROM products p 
                   LEFT JOIN categories c ON p.pro_cate = c.cate_id 
                   WHERE p.pro_cate != '' 
                   ORDER BY c.categories ASC";
$categories_result = mysqli_query($conn, $categories_sql);

// Get brands for filter dropdown
$brands_sql = "SELECT DISTINCT p.brand_name, b.brand_name as brand_display
               FROM products p 
               LEFT JOIN pro_brands b ON p.brand_name = b.id 
               WHERE p.brand_name != '' 
               ORDER BY b.brand_name ASC";
$brands_result = mysqli_query($conn, $brands_sql);

// Get statistics for dashboard
$stats_sql = "SELECT 
               COUNT(*) as total_products,
               SUM(CASE WHEN status = '1' THEN 1 ELSE 0 END) as active_products,
               SUM(CASE WHEN status = '0' THEN 1 ELSE 0 END) as inactive_products,
               SUM(CASE WHEN stock = 'in_stock' THEN 1 ELSE 0 END) as in_stock,
               SUM(CASE WHEN stock = 'low_stock' THEN 1 ELSE 0 END) as low_stock,
               SUM(CASE WHEN stock = 'out_of_stock' THEN 1 ELSE 0 END) as out_of_stock
               FROM products";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

// Complete query with sorting and pagination
$sql .= " ORDER BY p.id DESC LIMIT $offset, $perPage";

$result = mysqli_query($conn, $sql);
$countResult = mysqli_query($conn, $countSql);
$totalRows = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalRows / $perPage);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Product Management | Admin Panel</title>
    <link rel="icon" href="<?php echo htmlspecialchars($setting->get('favicon', 'assets/img/logo.png')); ?>" type="image/png">
    <?php include "links.php"; ?>
    
    <style>
        .white_card {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card h3 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            opacity: 0.9;
            font-size: 14px;
            margin: 0;
        }
        
        .stat-card.secondary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .stat-card.success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .stat-card.warning {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
        }
        
        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #d1f7c4;
            color: #0c831f;
        }
        
        .status-inactive {
            background-color: #ffeaea;
            color: #ff5252;
        }
        
        .stock-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .stock-in-stock {
            background-color: #d1f7c4;
            color: #0c831f;
        }
        
        .stock-low {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .stock-out {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            background: white;
            color: #6c757d;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .action-btn.view:hover {
            background-color: #17a2b8;
            color: white;
            border-color: #17a2b8;
        }
        
        .action-btn.edit:hover {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .action-btn.delete:hover {
            background-color: #dc3545;
            color: white;
            border-color: #dc3545;
        }
        
        .filter-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .price-highlight {
            font-weight: 600;
            color: #28a745;
        }
        
        .discount-badge {
            background: #ff6b6b;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            margin-left: 5px;
        }
        
        .pagination .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
        }
        
        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            font-weight: 600;
            color: #495057;
        }
        
        .badge-new {
            background: #7367f0;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
        }
        
        .badge-trending {
            background: #28c76f;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
        }
        
        .badge-deal {
            background: #ff9f43;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
        }
    </style>
</head>

<body class="crm_body_bg">
    <?php include "includes/header.php"; ?>

    <section class="main_content dashboard_part">
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
                        <div class="white_card">
                            <div class="card-header">
                                <div class="d-flex align-items-center justify-content-between flex-wrap">
                                    <div>
                                        <h2 class="mb-0">Product Management</h2>
                                        <p class="text-muted mb-0">Manage your product inventory</p>
                                    </div>
                                    <div class="d-flex">
                                        <a href="add-products.php" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Add Product
                                        </a>
                                        <button type="button" class="btn btn-outline-secondary ms-2" id="exportBtn">
                                            <i class="fas fa-download me-2"></i>Export
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Statistics Cards -->
                            <div class="row mb-4">
                                <div class="col-xl-3 col-md-6">
                                    <div class="stat-card">
                                        <h3><?= number_format($stats['total_products']) ?></h3>
                                        <p>Total Products</p>
                                        <i class="fas fa-box fa-2x opacity-25 float-end"></i>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6">
                                    <div class="stat-card secondary">
                                        <h3><?= number_format($stats['active_products']) ?></h3>
                                        <p>Active Products</p>
                                        <i class="fas fa-check-circle fa-2x opacity-25 float-end"></i>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6">
                                    <div class="stat-card success">
                                        <h3><?= number_format($stats['in_stock']) ?></h3>
                                        <p>In Stock</p>
                                        <i class="fas fa-store fa-2x opacity-25 float-end"></i>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6">
                                    <div class="stat-card warning">
                                        <h3><?= number_format($stats['out_of_stock']) ?></h3>
                                        <p>Out of Stock</p>
                                        <i class="fas fa-exclamation-triangle fa-2x opacity-25 float-end"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Filter Section -->
                            <div class="filter-section">
                                <form method="GET" class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Search</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-search"></i>
                                            </span>
                                            <input type="text" class="form-control" name="search" 
                                                   placeholder="Product name, SKU, brand..." value="<?= htmlspecialchars($search) ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label class="form-label">Category</label>
                                        <select class="form-control" name="category">
                                            <option value="">All Categories</option>
                                            <?php while($cat = mysqli_fetch_assoc($categories_result)): ?>
                                                <option value="<?= $cat['pro_cate'] ?>" <?= $category == $cat['pro_cate'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($cat['categories']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label class="form-label">Status</label>
                                        <select class="form-control" name="status">
                                            <option value="">All Status</option>
                                            <option value="1" <?= $status === '1' ? 'selected' : '' ?>>Active</option>
                                            <option value="0" <?= $status === '0' ? 'selected' : '' ?>>Inactive</option>
                                            <option value="2" <?= $status === '2' ? 'selected' : '' ?>>Draft</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label class="form-label">Stock Status</label>
                                        <select class="form-control" name="stock_status">
                                            <option value="">All Stock</option>
                                            <option value="in_stock" <?= $stock_status === 'in_stock' ? 'selected' : '' ?>>In Stock</option>
                                            <option value="low_stock" <?= $stock_status === 'low_stock' ? 'selected' : '' ?>>Low Stock</option>
                                            <option value="out_of_stock" <?= $stock_status === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-3 d-flex align-items-end">
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
                            
                            <!-- Products Table -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th>Category</th>
                                            <th>Brand</th>
                                            <th>Price</th>
                                            <th>Stock</th>
                                            <th>Tags</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(mysqli_num_rows($result) > 0): ?>
                                            <?php $sno = $offset + 1; ?>
                                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                                                <?php 
                                                // Parse image array
                                                $images = !empty($row['pro_img']) ? explode(",", $row['pro_img']) : [];
                                                $first_image = !empty($images) ? $images[0] : 'no-image.jpg';
                                                
                                                // Calculate discount percentage
                                                $discount = 0;
                                                if($row['mrp'] > 0 && $row['selling_price'] > 0) {
                                                    $discount = round((($row['mrp'] - $row['selling_price']) / $row['mrp']) * 100);
                                                }
                                                ?>
                                                <tr>
                                                    <td><?= $sno++ ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="assets/img/uploads/<?= htmlspecialchars($first_image) ?>" 
                                                                 alt="<?= htmlspecialchars($row['pro_name']) ?>"
                                                                 class="product-img me-3">
                                                            <div>
                                                                <div class="fw-bold"><?= htmlspecialchars($row['pro_name']) ?></div>
                                                                <small class="text-muted">ID: <?= htmlspecialchars($row['pro_id']) ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <code class="bg-light p-1 rounded"><?= htmlspecialchars($row['sku'] ?? 'N/A') ?></code>
                                                    </td>
                                                    <td><?= htmlspecialchars($row['category_name'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($row['brand_name'] ?? 'N/A') ?></td>
                                                    <td>
                                                        <div>
                                                            <span class="price-highlight">₹<?= number_format($row['selling_price'], 2) ?></span>
                                                            <del class="text-muted small ms-2">₹<?= number_format($row['mrp'], 2) ?></del>
                                                            <?php if($discount > 0): ?>
                                                                <span class="discount-badge"><?= $discount ?>% OFF</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if($row['stock'] == 'in_stock'): ?>
                                                            <span class="stock-badge stock-in-stock">In Stock</span>
                                                        <?php elseif($row['stock'] == 'low_stock'): ?>
                                                            <span class="stock-badge stock-low">Low Stock</span>
                                                        <?php else: ?>
                                                            <span class="stock-badge stock-out">Out of Stock</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex gap-1">
                                                            <?php if($row['new_arrival'] == '1'): ?>
                                                                <span class="badge-new">New</span>
                                                            <?php endif; ?>
                                                            <?php if($row['trending'] == '1'): ?>
                                                                <span class="badge-trending">Trending</span>
                                                            <?php endif; ?>
                                                            <?php if($row['is_deal'] == '1'): ?>
                                                                <span class="badge-deal">Deal</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="status-badge <?= $row['status'] == '1' ? 'status-active' : 'status-inactive' ?>">
                                                            <?= $row['status'] == '1' ? 'Active' : ($row['status'] == '2' ? 'Draft' : 'Inactive') ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <a href="view-product-details.php?id=<?= $row['pro_id'] ?>" 
                                                               class="action-btn view" title="View">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="edit_products.php?edit_product_details=<?= $row['pro_id'] ?>" 
                                                               class="action-btn edit" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <!-- <a href="add-product-page-banner.php?id=<?= $row['pro_id'] ?>" 
                                                               class="action-btn" title="Add Banner" style="background:#17a2b8;color:white;border-color:#17a2b8;">
                                                                <i class="fas fa-image"></i>
                                                            </a>
                                                            <a href="multiple_img.php?id=<?= $row['pro_id'] ?>" 
                                                               class="action-btn" title="Multiple Images" style="background:#6c757d;color:white;border-color:#6c757d;">
                                                                <i class="fas fa-images"></i>
                                                            </a> -->
                                                            <a href="ajax/product_delete.php?delete=<?= $row['pro_id'] ?>" 
                                                               class="action-btn delete" 
                                                               onclick="return confirm('Are you sure you want to delete this product?')"
                                                               title="Delete">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="10" class="text-center py-5">
                                                    <div class="text-muted">
                                                        <i class="fas fa-box-open fa-3x mb-3"></i>
                                                        <h5>No products found</h5>
                                                        <p>Try adjusting your filters or add a new product</p>
                                                        <a href="add-products.php" class="btn btn-primary mt-2">
                                                            <i class="fas fa-plus me-2"></i>Add First Product
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if($totalPages > 1): ?>
                                <div class="d-flex justify-content-between align-items-center mt-4">
                                    <div class="text-muted">
                                        Showing <?= $offset + 1 ?> to <?= min($offset + $perPage, $totalRows) ?> of <?= number_format($totalRows) ?> entries
                                    </div>
                                    <nav>
                                        <ul class="pagination mb-0">
                                            <?php if($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&category=<?= $category ?>&status=<?= $status ?>&stock_status=<?= $stock_status ?>">
                                                        <i class="fas fa-chevron-left"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php for($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= $category ?>&status=<?= $status ?>&stock_status=<?= $stock_status ?>">
                                                        <?= $i ?>
                                                    </a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <?php if($page < $totalPages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&category=<?= $category ?>&status=<?= $status ?>&stock_status=<?= $stock_status ?>">
                                                        <i class="fas fa-chevron-right"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include "includes/footer.php"; ?>
    </section>

    <script>
        // Quick status toggle
        document.addEventListener('DOMContentLoaded', function() {
            // Export button functionality
            document.getElementById('exportBtn').addEventListener('click', function() {
                const params = new URLSearchParams(window.location.search);
                params.set('export', 'csv');
                window.location.href = 'product-export.php?' + params.toString();
            });
            
            // Quick status toggle (optional feature)
            document.querySelectorAll('.status-badge').forEach(badge => {
                badge.addEventListener('click', function(e) {
                    if(e.target.closest('a')) return; // Don't interfere with links
                    
                    const row = this.closest('tr');
                    const productId = row.querySelector('td:nth-child(2) small').textContent.replace('ID: ', '');
                    const currentStatus = this.classList.contains('status-active') ? '1' : '0';
                    const newStatus = currentStatus === '1' ? '0' : '1';
                    
                    if(confirm('Toggle product status?')) {
                        fetch('functions.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'action=toggle_product_status&id=' + productId + '&status=' + newStatus
                        })
                        .then(response => response.json())
                        .then(data => {
                            if(data.success) {
                                location.reload();
                            }
                        });
                    }
                });
            });
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>