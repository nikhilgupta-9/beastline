<?php
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';
require_once __DIR__ . '/models/setting.php';
require_once __DIR__ . '/models/Policy.php';

// Initialize
$setting = new Setting($conn);
$policy = new Policy($conn);

// Handle actions
if (isset($_GET['action'])) {
    $id = (int)$_GET['id'] ?? 0;
    
    switch ($_GET['action']) {
        case 'toggle_status':
            if ($policy->toggleStatus($id)) {
                $_SESSION['success'] = "Policy status updated";
            } else {
                $_SESSION['error'] = "Error updating status";
            }
            break;
            
        case 'delete':
            if ($policy->deletePolicy($id)) {
                $_SESSION['success'] = "Policy deleted successfully";
            } else {
                $_SESSION['error'] = "Error deleting policy";
            }
            break;
    }
    
    header("Location: view-policies.php");
    exit();
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_action'])) {
    $selected_ids = $_POST['selected_ids'] ?? [];
    
    if (!empty($selected_ids)) {
        $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
        
        switch ($_POST['bulk_action']) {
            case 'activate':
                $stmt = $conn->prepare("UPDATE policies SET status = 1 WHERE id IN ($placeholders)");
                break;
            case 'deactivate':
                $stmt = $conn->prepare("UPDATE policies SET status = 0 WHERE id IN ($placeholders)");
                break;
            case 'delete':
                $stmt = $conn->prepare("DELETE FROM policies WHERE id IN ($placeholders)");
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
        $_SESSION['error'] = "No policies selected";
    }
    
    header("Location: view-policies.php");
    exit();
}

// Get all policies
$policies = $policy->getAllPolicies();
$stats = $policy->getStats();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Manage Policies | Admin <?php echo htmlspecialchars($setting->get('site_name')); ?></title>
    <link rel="icon" href="<?php echo htmlspecialchars($setting->get('favicon', 'assets/img/logo.png')); ?>" type="image/png">

    <?php include "links.php"; ?>
    
    <!-- Summernote for rich text editor -->
    <link rel="stylesheet" href="assets/vendors/text_editor/summernote-bs4.css">
    
    <style>
        .badge{
            color: black;
            padding: 5px 5px;
            background-color: burlywood;
            border-radius: 4px;
        }
        .policy-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 30px;
            transition: transform 0.3s;
        }
        
        .policy-card:hover {
            transform: translateY(-2px);
        }
        
        .policy-icon {
            font-size: 2.5rem;
            color: #4361ee;
            margin-bottom: 15px;
        }
        
        .status-badge {
            cursor: pointer;
            transition: opacity 0.3s;
        }
        
        .status-badge:hover {
            opacity: 0.8;
        }
        
        .table-actions .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .stats-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .stats-card i {
            font-size: 2.5rem;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        
        .stats-card h4 {
            margin: 0;
            font-weight: 600;
            font-size: 2rem;
        }
        
        .stats-card p {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .bulk-actions {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .content-preview {
            max-height: 100px;
            overflow: hidden;
            position: relative;
        }
        
        .content-preview::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 20px;
            background: linear-gradient(to bottom, transparent, white);
        }
        
        .policy-slug {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #6c757d;
        }
        
        .search-box {
            max-width: 300px;
        }
        
        .policy-type-badge {
            background: #e7f3ff;
            color: #4361ee;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
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
                                        <h2 class="m-0"><i class="fas fa-file-contract mr-2"></i> Manage Policies</h2>
                                        <p class="mb-0 text-muted">Manage website policies and legal documents</p>
                                    </div>
                                    <div class="action-btn">
                                        <a href="add-policy.php" class="btn btn-primary">
                                            <i class="fas fa-plus mr-1"></i> Add New Policy
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
                                
                                <!-- Stats Overview -->
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <div class="stats-card">
                                            <i class="fas fa-file-contract"></i>
                                            <h4><?php echo $stats['total']; ?></h4>
                                            <p>Total Policies</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="stats-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                                            <i class="fas fa-check-circle"></i>
                                            <h4><?php echo $stats['active']; ?></h4>
                                            <p>Active Policies</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="stats-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                                            <i class="fas fa-ban"></i>
                                            <h4><?php echo $stats['inactive']; ?></h4>
                                            <p>Inactive Policies</p>
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
                                                <input type="text" class="form-control" placeholder="Search policies..." id="searchInput">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary" type="button">
                                                        <i class="fas fa-search"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                                
                                <!-- Policies Table -->
                                <?php if (empty($policies)): ?>
                                    <div class="empty-state">
                                        <i class="fas fa-file-contract"></i>
                                        <h4 class="mt-3">No Policies Found</h4>
                                        <p class="text-muted mb-4">You haven't added any policies yet. Add your first policy to get started.</p>
                                        <a href="add-policy.php" class="btn btn-primary">
                                            <i class="fas fa-plus mr-1"></i> Add First Policy
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th width="3%">
                                                        <input type="checkbox" id="selectAll">
                                                    </th>
                                                    <th width="5%">#</th>
                                                    <th width="25%">Policy Details</th>
                                                    <th width="15%">SEO Information</th>
                                                    <th width="10%">Display</th>
                                                    <th width="10%">Status</th>
                                                    <th width="15%">Dates</th>
                                                    <th width="17%" class="text-center">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="policyTable">
                                                <?php foreach ($policies as $index => $policy_item): 
                                                    $policy_type = strtolower(pathinfo($policy_item['slug'], PATHINFO_FILENAME));
                                                    $icon_map = [
                                                        'privacy' => 'fas fa-user-shield',
                                                        'shipping' => 'fas fa-shipping-fast',
                                                        'return' => 'fas fa-exchange-alt',
                                                        'refund' => 'fas fa-money-bill-wave',
                                                        'terms' => 'fas fa-balance-scale',
                                                        'cookie' => 'fas fa-cookie-bite',
                                                        'default' => 'fas fa-file-contract'
                                                    ];
                                                    
                                                    $icon = $icon_map['default'];
                                                    foreach ($icon_map as $key => $value) {
                                                        if (strpos($policy_type, $key) !== false) {
                                                            $icon = $value;
                                                            break;
                                                        }
                                                    }
                                                ?>
                                                <tr class="policy-row" data-policy-id="<?php echo $policy_item['id']; ?>">
                                                    <td>
                                                        <input type="checkbox" name="selected_ids[]" value="<?php echo $policy_item['id']; ?>" class="policy-checkbox">
                                                    </td>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="mr-3">
                                                                <i class="<?php echo $icon; ?> fa-2x text-primary"></i>
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-1"><?php echo htmlspecialchars($policy_item['title']); ?></h6>
                                                                <div class="small text-muted">
                                                                    <div class="policy-slug d-inline-block mb-1">
                                                                        <?php echo htmlspecialchars($policy_item['slug']); ?>
                                                                    </div>
                                                                    <div class="policy-id">
                                                                        <strong>ID:</strong> <?php echo htmlspecialchars($policy_item['policy_id']); ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="content-preview mt-2 small text-muted">
                                                            <?php 
                                                            $content = strip_tags($policy_item['content']);
                                                            echo htmlspecialchars(substr($content, 0, 150)) . (strlen($content) > 150 ? '...' : '');
                                                            ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="small">
                                                            <?php if (!empty($policy_item['meta_title'])): ?>
                                                            <div class="mb-1">
                                                                <strong>Title:</strong> 
                                                                <span class="text-truncate d-inline-block" style="max-width: 150px;" title="<?php echo htmlspecialchars($policy_item['meta_title']); ?>">
                                                                    <?php echo htmlspecialchars(substr($policy_item['meta_title'], 0, 30)); ?>...
                                                                </span>
                                                            </div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($policy_item['meta_description'])): ?>
                                                            <div class="mb-1">
                                                                <strong>Desc:</strong> 
                                                                <span class="text-truncate d-inline-block" style="max-width: 150px;" title="<?php echo htmlspecialchars($policy_item['meta_description']); ?>">
                                                                    <?php echo htmlspecialchars(substr($policy_item['meta_description'], 0, 40)); ?>...
                                                                </span>
                                                            </div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($policy_item['meta_keywords'])): ?>
                                                            <div>
                                                                <strong>Keywords:</strong> 
                                                                <span class="text-truncate d-inline-block" style="max-width: 150px;">
                                                                    <?php echo htmlspecialchars(substr($policy_item['meta_keywords'], 0, 30)); ?>...
                                                                </span>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-info p-2">
                                                            <i class="fas fa-sort-numeric-down"></i> <?php echo $policy_item['display_order']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="?action=toggle_status&id=<?php echo $policy_item['id']; ?>" 
                                                           class="badge badge-<?php echo $policy_item['status'] ? 'success' : 'danger'; ?> status-badge p-2"
                                                           onclick="return confirm('Change status to <?php echo $policy_item['status'] ? 'inactive' : 'active'; ?>?')">
                                                            <?php echo $policy_item['status'] ? 'Active' : 'Inactive'; ?>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <div class="small">
                                                            <div><strong>Created:</strong></div>
                                                            <div><?php echo date('d M Y', strtotime($policy_item['created_at'])); ?></div>
                                                            <div class="mt-1"><strong>Updated:</strong></div>
                                                            <div><?php echo date('d M Y', strtotime($policy_item['last_updated'])); ?></div>
                                                        </div>
                                                    </td>
                                                    <td class="table-actions text-center">
                                                        <div class="btn-group" role="group">
                                                            <a href="edit-policy.php?id=<?php echo $policy_item['id']; ?>" 
                                                               class="btn btn-outline-primary btn-sm" 
                                                               title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="?action=toggle_status&id=<?php echo $policy_item['id']; ?>" 
                                                               class="btn btn-outline-<?php echo $policy_item['status'] ? 'warning' : 'success'; ?> btn-sm"
                                                               title="<?php echo $policy_item['status'] ? 'Deactivate' : 'Activate'; ?>"
                                                               onclick="return confirm('<?php echo $policy_item['status'] ? 'Deactivate' : 'Activate'; ?> this policy?')">
                                                                <i class="fas fa-power-off"></i>
                                                            </a>
                                                            <a href="?action=delete&id=<?php echo $policy_item['id']; ?>" 
                                                               class="btn btn-outline-danger btn-sm"
                                                               title="Delete"
                                                               onclick="return confirm('Are you sure you want to delete this policy?\nThis action cannot be undone.')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                            <a href="../policy/<?php echo $policy_item['slug']; ?>" 
                                                               target="_blank" 
                                                               class="btn btn-outline-info btn-sm"
                                                               title="Preview">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </div>
                                                        <div class="mt-2">
                                                            <small class="text-muted">
                                                                <i class="fas fa-id-badge"></i> ID: <?php echo $policy_item['id']; ?>
                                                            </small>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Pagination (Optional) -->
                                    <div class="row mt-4">
                                        <div class="col-md-6">
                                            <small class="text-muted">
                                                Showing <?php echo count($policies); ?> policies
                                            </small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Policy Types Info -->
                                <div class="row mt-5">
                                    <div class="col-md-12">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6><i class="fas fa-info-circle mr-2"></i> Common Policy Types</h6>
                                                <div class="row mt-3">
                                                    <div class="col-md-4 mb-3">
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-user-shield text-primary mr-3 fa-lg"></i>
                                                            <div>
                                                                <h6 class="mb-1">Privacy Policy</h6>
                                                                <p class="text-muted small mb-0">Explains how you collect and use personal data</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-shipping-fast text-success mr-3 fa-lg"></i>
                                                            <div>
                                                                <h6 class="mb-1">Shipping Policy</h6>
                                                                <p class="text-muted small mb-0">Details about delivery methods, times, and costs</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-exchange-alt text-warning mr-3 fa-lg"></i>
                                                            <div>
                                                                <h6 class="mb-1">Return Policy</h6>
                                                                <p class="text-muted small mb-0">Guidelines for returns and exchanges</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-money-bill-wave text-danger mr-3 fa-lg"></i>
                                                            <div>
                                                                <h6 class="mb-1">Refund Policy</h6>
                                                                <p class="text-muted small mb-0">Explains refund procedures and timelines</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-balance-scale text-info mr-3 fa-lg"></i>
                                                            <div>
                                                                <h6 class="mb-1">Terms & Conditions</h6>
                                                                <p class="text-muted small mb-0">Legal agreement for website use</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-cookie-bite text-secondary mr-3 fa-lg"></i>
                                                            <div>
                                                                <h6 class="mb-1">Cookie Policy</h6>
                                                                <p class="text-muted small mb-0">Information about cookie usage</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
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
        $(function () {
            $('[title]').tooltip();
        });
        
        // Select All checkboxes
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.policy-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.policy-row');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Confirm bulk action
        function confirmBulkAction() {
            const selected = document.querySelectorAll('.policy-checkbox:checked');
            const action = document.querySelector('select[name="bulk_action"]').value;
            
            if (selected.length === 0) {
                alert('Please select at least one policy.');
                return false;
            }
            
            if (!action) {
                alert('Please select a bulk action.');
                return false;
            }
            
            let message = '';
            switch (action) {
                case 'activate':
                    message = 'Activate ' + selected.length + ' selected policies?';
                    break;
                case 'deactivate':
                    message = 'Deactivate ' + selected.length + ' selected policies?';
                    break;
                case 'delete':
                    message = 'Delete ' + selected.length + ' selected policies? This action cannot be undone.';
                    break;
            }
            
            return confirm(message);
        }
        
        // Update checkboxes when any checkbox changes
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('policy-checkbox')) {
                const allChecked = Array.from(document.querySelectorAll('.policy-checkbox')).every(cb => cb.checked);
                const someChecked = Array.from(document.querySelectorAll('.policy-checkbox')).some(cb => cb.checked);
                
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
        
        // Quick preview toggle
        document.querySelectorAll('.content-preview').forEach(preview => {
            preview.addEventListener('click', function() {
                if (this.style.maxHeight && this.style.maxHeight !== '100px') {
                    this.style.maxHeight = '100px';
                    this.querySelector('::after').style.display = 'block';
                } else {
                    this.style.maxHeight = 'none';
                    this.querySelector('::after').style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>