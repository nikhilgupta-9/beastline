<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';
require_once __DIR__ . '/models/setting.php';

// Initialize Settings
$setting = new Setting($conn);

// Handle status toggle
if (isset($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];
    $stmt = $conn->prepare("UPDATE banners SET status = 1 - status WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: view-banners.php");
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Get banner path first
    $stmt = $conn->prepare("SELECT banner_path FROM banners WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($banner_path);
    $stmt->fetch();
    $stmt->close();
    
    // Delete from database
    $stmt = $conn->prepare("DELETE FROM banners WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        // Delete file
        if (file_exists($banner_path)) {
            unlink($banner_path);
        }
        $_SESSION['success'] = "Banner deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting banner";
    }
    $stmt->close();
    header("Location: view-banners.php");
    exit();
}

// Fetch banners
$query = "SELECT * FROM banners ORDER BY display_order ASC, uploaded_at DESC";
$result = mysqli_query($conn, $query);
$banners = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $banners[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Banners | Admin <?php echo htmlspecialchars($setting->get('site_name', 'Panel')); ?></title>
    <link rel="icon" href="<?php echo htmlspecialchars($setting->get('favicon', 'assets/img/logo.png')); ?>" type="image/png">
    <?php include "links.php"; ?>
    
    <style>
        .table-actions .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .banner-img {
            max-width: 150px;
            max-height: 60px;
            object-fit: contain;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* .status-badge {
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .status-badge:hover {
            opacity: 0.8;
        } */
            .badge{
                color: #212529;
            }
            li{
                    list-style: none;
    color: black;
            }
        
        .expired-badge {
            background-color: #ffc107;
            color: #212529;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
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
                                        <h3 class="m-0"><i class="fas fa-images mr-2"></i> Manage Banners</h3>
                                        <p class="mb-0 text-muted">Manage website banners and their display settings</p>
                                    </div>
                                    <div class="action-btn">
                                        <a href="add-banner.php" class="btn btn-primary">
                                            <i class="fas fa-plus mr-1"></i> Add New Banner
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
                                
                                <!-- Stats Summary -->
                                <div class="row mb-4">
                                    <div class="col-md-3">
                                        <div class="card card-body bg-light border">
                                            <div class="d-flex align-items-center">
                                                <div class="mr-3">
                                                    <i class="fas fa-layer-group fa-2x text-primary"></i>
                                                </div>
                                                <div>
                                                    <h4 class="mb-0"><?php echo count($banners); ?></h4>
                                                    <small class="text-muted">Total Banners</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card card-body bg-light border">
                                            <div class="d-flex align-items-center">
                                                <div class="mr-3">
                                                    <i class="fas fa-check-circle fa-2x text-success"></i>
                                                </div>
                                                <div>
                                                    <h4 class="mb-0">
                                                        <?php 
                                                        $active = array_filter($banners, function($b) { 
                                                            return $b['status'] == 1; 
                                                        });
                                                        echo count($active);
                                                        ?>
                                                    </h4>
                                                    <small class="text-muted">Active Banners</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card card-body bg-light border">
                                            <div class="d-flex align-items-center">
                                                <div class="mr-3">
                                                    <i class="fas fa-clock fa-2x text-warning"></i>
                                                </div>
                                                <div>
                                                    <h4 class="mb-0">
                                                        <?php 
                                                        $expired = array_filter($banners, function($b) { 
                                                            return $b['expiry_date'] && strtotime($b['expiry_date']) < time(); 
                                                        });
                                                        echo count($expired);
                                                        ?>
                                                    </h4>
                                                    <small class="text-muted">Expired Banners</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card card-body bg-light border">
                                            <div class="d-flex align-items-center">
                                                <div class="mr-3">
                                                    <i class="fas fa-ban fa-2x text-danger"></i>
                                                </div>
                                                <div>
                                                    <h4 class="mb-0">
                                                        <?php 
                                                        $inactive = array_filter($banners, function($b) { 
                                                            return $b['status'] == 0; 
                                                        });
                                                        echo count($inactive);
                                                        ?>
                                                    </h4>
                                                    <small class="text-muted">Inactive Banners</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Banners Table -->
                                <?php if (empty($banners)): ?>
                                    <div class="empty-state">
                                        <i class="fas fa-image text-muted"></i>
                                        <h4 class="mt-3">No Banners Found</h4>
                                        <p class="text-muted mb-4">You haven't added any banners yet. Add your first banner to get started.</p>
                                        <a href="add-banner.php" class="btn btn-primary">
                                            <i class="fas fa-plus mr-1"></i> Add First Banner
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th width="5%">#</th>
                                                    <th width="15%">Image</th>
                                                    <th width="20%">Title & Details</th>
                                                    <th width="10%">Order</th>
                                                    <th width="10%">Place</th>
                                                    <th width="15%">Dates</th>
                                                    <th width="10%">Status</th>
                                                    <th width="25%" class="text-center">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($banners as $index => $banner): 
                                                    $is_expired = $banner['expiry_date'] && strtotime($banner['expiry_date']) < time();
                                                    $badge_class = $is_expired ? 'badge-warning' : ($banner['status'] ? 'badge-success' : 'badge-danger');
                                                    $status_text = $is_expired ? 'Expired' : ($banner['status'] ? 'Active' : 'Inactive');
                                                ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td>
                                                        <div class="position-relative">
                                                            <img src="<?php echo htmlspecialchars($banner['banner_path']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($banner['alt_text'] ?? $banner['title'] ?? 'N/A'); ?>"
                                                                 class="banner-img">
                                                            <?php if ($is_expired): ?>
                                                                <span class="badge expired-badge position-absolute" style="top: 5px; right: 5px;">
                                                                    <i class="fas fa-clock"></i> Expired
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($banner['title'] ?? 'N/A'); ?></h6>
                                                        <?php if (!empty($banner['description'])): ?>
                                                            <p class="text-muted small mb-1"><?php echo nl2br(htmlspecialchars(substr($banner['description'], 0, 100))); ?>...</p>
                                                        <?php endif; ?>
                                                        <?php if (!empty($banner['link_url'])): ?>
                                                            <small>
                                                                <i class="fas fa-link text-info"></i>
                                                                <a href="<?php echo htmlspecialchars($banner['link_url']); ?>" 
                                                                   target="_blank" 
                                                                   class="text-primary">
                                                                    <?php echo htmlspecialchars(substr($banner['link_url'], 0, 30)); ?>...
                                                                </a>
                                                            </small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-info p-2">
                                                            <i class="fas fa-sort-numeric-down"></i> <?php echo $banner['display_order']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-info p-2">
                                                             <?php echo $banner['location']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="small">
                                                            <div><strong>Uploaded:</strong></div>
                                                            <div><?php echo date('d M Y', strtotime($banner['uploaded_at'])); ?></div>
                                                            <?php if ($banner['expiry_date']): ?>
                                                                <div class="mt-1"><strong>Expires:</strong></div>
                                                                <div class="<?php echo $is_expired ? 'text-danger' : 'text-success'; ?>">
                                                                    <?php echo date('d M Y', strtotime($banner['expiry_date'])); ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <a href="?toggle_status=<?php echo $banner['id']; ?>" 
                                                           class="badge <?php echo $badge_class; ?> status-badge p-2"
                                                           onclick="return confirm('Change status to <?php echo $banner['status'] ? 'inactive' : 'active'; ?>?')">
                                                            <?php echo $status_text; ?>
                                                        </a>
                                                    </td>
                                                    <td class="table-actions text-center">
                                                        <div class="btn-group" role="group">
                                                            <a href="edit-banner.php?id=<?php echo $banner['id']; ?>" 
                                                               class="btn btn-outline-primary btn-sm" 
                                                               title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="<?php echo htmlspecialchars($banner['banner_path']); ?>" 
                                                               target="_blank" 
                                                               class="btn btn-outline-info btn-sm"
                                                               title="Preview">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="?toggle_status=<?php echo $banner['id']; ?>" 
                                                               class="btn btn-outline-<?php echo $banner['status'] ? 'warning' : 'success'; ?> btn-sm"
                                                               title="<?php echo $banner['status'] ? 'Deactivate' : 'Activate'; ?>"
                                                               onclick="return confirm('<?php echo $banner['status'] ? 'Deactivate' : 'Activate'; ?> this banner?')">
                                                                <i class="fas fa-power-off"></i>
                                                            </a>
                                                            <a href="?delete=<?php echo $banner['id']; ?>" 
                                                               class="btn btn-outline-danger btn-sm"
                                                               title="Delete"
                                                               onclick="return confirm('Are you sure you want to delete this banner?\nThis action cannot be undone.')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </div>
                                                        <div class="mt-2">
                                                            <small class="text-muted">
                                                                <i class="fas fa-id-badge"></i> ID: <?php echo $banner['id']; ?>
                                                            </small>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Summary -->
                                    <div class="row mt-4">
                                        <div class="col-md-6">
                                            <div class="card bg-light">
                                                <div class="card-body p-3">
                                                    <h6 class="mb-2"><i class="fas fa-info-circle mr-2"></i> Banner Information</h6>
                                                    <ul class="list-unstyled mb-0 small">
                                                        <li><i class="fas fa-check text-success mr-1"></i> Active banners are displayed on the website</li>
                                                        <li><i class="fas fa-ban text-danger mr-1"></i> Inactive banners are hidden</li>
                                                        <li><i class="fas fa-clock text-warning mr-1"></i> Expired banners won't be displayed</li>
                                                        <li><i class="fas fa-sort-numeric-down text-info mr-1"></i> Lower order numbers appear first</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="card bg-light">
                                                <div class="card-body p-3">
                                                    <h6 class="mb-2"><i class="fas fa-lightbulb mr-2"></i> Quick Tips</h6>
                                                    <ul class="list-unstyled mb-0 small">
                                                        <li><i class="fas fa-mouse-pointer text-primary mr-1"></i> Click on status badge to toggle</li>
                                                        <li><i class="fas fa-external-link-alt text-info mr-1"></i> Preview opens image in new tab</li>
                                                        <li><i class="fas fa-trash text-danger mr-1"></i> Deletion removes image permanently</li>
                                                        <li><i class="fas fa-plus-circle text-success mr-1"></i> Add new banners for different pages</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include "includes/footer.php"; ?>
    </section>

    <script>
        // Confirmation for delete
        document.addEventListener('DOMContentLoaded', function() {
            // Add fade out to alerts
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });
            
            // Preview image on click
            document.querySelectorAll('.banner-img').forEach(img => {
                img.addEventListener('click', function() {
                    window.open(this.src, '_blank');
                });
                img.style.cursor = 'pointer';
            });
            
            // Tooltip initialization
            $('[title]').tooltip();
        });
    </script>
</body>
</html>