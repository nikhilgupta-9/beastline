<?php
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';
require_once __DIR__ . '/models/setting.php';

// Initialize Settings
$setting = new Setting($conn);

// Check if ID parameter exists
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view-banners.php");
    exit();
}

$id = (int)$_GET['id'];
$errors = [];
$success = '';

// Fetch banner data
$stmt = $conn->prepare("SELECT * FROM banners WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$banner = $result->fetch_assoc();
$stmt->close();

if (!$banner) {
    $_SESSION['error'] = "Banner not found";
    header("Location: view-banners.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    // Collect form data
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $alt_text = trim($_POST['alt_text'] ?? '');
    $link_url = trim($_POST['link_url'] ?? '');
    $target_blank = isset($_POST['target_blank']) ? 1 : 0;
    $display_order = (int)($_POST['display_order'] ?? 0);
    $status = isset($_POST['status']) ? 1 : 0;
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : NULL;
    
    // Validate
    if (empty($title)) {
        $errors[] = "Banner title is required";
    }
    
    if (strlen($title) > 255) {
        $errors[] = "Title must be less than 255 characters";
    }
    
    // Handle new image upload if provided
    $new_image_path = $banner['banner_path'];
    $old_image_path = $banner['banner_path'];
    
    if (!empty($_FILES['new_banner']['name'])) {
        $target_dir = "uploads/banners/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        // Generate unique filename
        $file_ext = strtolower(pathinfo($_FILES["new_banner"]["name"], PATHINFO_EXTENSION));
        $unique_id = uniqid('banner_');
        $new_image_path = $target_dir . $unique_id . '.' . $file_ext;
        
        // Validate new image
        $check = @getimagesize($_FILES["new_banner"]["tmp_name"]);
        if ($check === false) {
            $errors[] = "Invalid image file";
        }
        
        // Check file size (5MB limit)
        if ($_FILES["new_banner"]["size"] > 5000000) {
            $errors[] = "Image size exceeds 5MB limit";
        }
        
        // Allow certain file formats
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($file_ext, $allowed_types)) {
            $errors[] = "Allowed formats: JPG, JPEG, PNG, GIF, WEBP";
        }
        
        // Check dimensions
        if ($check && ($check[0] > 4000 || $check[1] > 4000)) {
            $errors[] = "Image dimensions too large (max 4000x4000)";
        }
    }
    
    // Update if no errors
    if (empty($errors)) {
        // Upload new image if provided
        if (!empty($_FILES['new_banner']['name'])) {
            if (move_uploaded_file($_FILES["new_banner"]["tmp_name"], $new_image_path)) {
                // Delete old image if it exists and is different
                if ($old_image_path != $new_image_path && file_exists($old_image_path)) {
                    unlink($old_image_path);
                }
            } else {
                $errors[] = "Error uploading new image";
                $new_image_path = $old_image_path; // Revert to old image
            }
        }
        
        if (empty($errors)) {
            $updated_by = $_SESSION['admin_id'] ?? 0;
            
            $stmt = $conn->prepare("UPDATE banners SET 
                banner_path = ?, 
                title = ?, 
                description = ?, 
                alt_text = ?, 
                link_url = ?, 
                target_blank = ?, 
                display_order = ?, 
                status = ?, 
                expiry_date = ?,
                uploaded_at = NOW()
                WHERE id = ?");
            
            $stmt->bind_param("sssssiiisi", 
                $new_image_path, 
                $title,
                $description,
                $alt_text,
                $link_url,
                $target_blank,
                $display_order,
                $status,
                $expiry_date,
                $id
            );
            
            if ($stmt->execute()) {
                $success = "Banner updated successfully!";
                
                // Update banner data for form
                $banner = array_merge($banner, [
                    'banner_path' => $new_image_path,
                    'title' => $title,
                    'description' => $description,
                    'alt_text' => $alt_text,
                    'link_url' => $link_url,
                    'target_blank' => $target_blank,
                    'display_order' => $display_order,
                    'status' => $status,
                    'expiry_date' => $expiry_date
                ]);
            } else {
                $errors[] = "Database error: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Handle banner restore (if you want to keep deleted banners)
if (isset($_POST['restore_original'])) {
    // This would restore from backup if you have that feature
    $success = "Original banner restored";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Edit Banner | Admin <?php echo htmlspecialchars($setting->get('site_name')); ?></title>
    <link rel="icon" href="<?php echo htmlspecialchars($setting->get('favicon', 'assets/img/logo.png')); ?>" type="image/png">

    <?php include "links.php"; ?>
    
    <style>
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9fa;
            margin-bottom: 1rem;
        }
        
        .upload-area:hover {
            border-color: #4361ee;
            background: rgba(67, 97, 238, 0.05);
        }
        
        .upload-area.dragover {
            border-color: #4361ee;
            background: rgba(67, 97, 238, 0.1);
        }
        
        .image-preview {
            max-width: 100%;
            max-height: 200px;
            border-radius: 6px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin: 15px auto;
            display: block;
        }
        
        .preview-info {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 10px 15px;
            margin-top: 10px;
            font-size: 0.9rem;
        }
        
        .form-section {
            border-left: 4px solid #4361ee;
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .required-star {
            color: #dc3545;
        }
        
        .current-image {
            position: relative;
            text-align: center;
        }
        
        .image-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        
        .dimension-badge {
            display: inline-block;
            padding: 2px 8px;
            background: #e9ecef;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-right: 5px;
        }
        
        .original-info {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
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
                                        <h3 class="m-0"><i class="fas fa-edit mr-2"></i> Edit Banner</h3>
                                        <p class="mb-0 text-muted">Editing Banner #<?php echo $banner['id']; ?> - <?php echo htmlspecialchars($banner['title']); ?></p>
                                    </div>
                                    <div class="action-btn">
                                        <a href="view-banners.php" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left mr-1"></i> Back to List
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="white_card_body">
                                <!-- Messages -->
                                <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <h6><i class="fas fa-exclamation-circle mr-2"></i> Please fix the following:</h6>
                                    <ul class="mb-0 pl-3">
                                        <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($success)): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($success) ?>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Original Banner Info -->
                                <div class="original-info">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-info-circle fa-lg text-warning mr-3"></i>
                                        <div>
                                            <h6 class="mb-1">Editing Existing Banner</h6>
                                            <p class="mb-0 small">
                                                <strong>Original Upload:</strong> <?php echo date('F j, Y, g:i a', strtotime($banner['uploaded_at'])); ?> |
                                                <strong>Last Updated:</strong> <?php echo date('F j, Y, g:i a', strtotime($banner['updated_at'] ?? $banner['uploaded_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $id); ?>" method="post" enctype="multipart/form-data" id="editBannerForm">
                                    <div class="row">
                                        <!-- Left Column: Image Section -->
                                        <div class="col-lg-6">
                                            <div class="form-section mb-4">
                                                <h5 class="mb-3"><i class="fas fa-image mr-2"></i> Banner Image</h5>
                                                
                                                <!-- Current Image -->
                                                <div class="current-image mb-4">
                                                    <h6>Current Banner:</h6>
                                                    <img src="<?php echo htmlspecialchars($banner['banner_path']); ?>" 
                                                         alt="Current Banner" 
                                                         class="image-preview" id="currentImage">
                                                    <span class="badge badge-primary image-badge">
                                                        Current
                                                    </span>
                                                    <div class="preview-info">
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <strong>Path:</strong> <?php echo htmlspecialchars(basename($banner['banner_path'])); ?>
                                                            </div>
                                                            <div class="col-6">
                                                                <strong>Uploaded:</strong> <?php echo date('M d, Y', strtotime($banner['uploaded_at'])); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- New Image Upload -->
                                                <h6 class="mb-3">Update Image (Optional):</h6>
                                                <div class="upload-area" id="uploadArea" onclick="document.getElementById('new_banner').click()">
                                                    <div class="mb-3">
                                                        <i class="fas fa-cloud-upload-alt fa-2x text-primary"></i>
                                                    </div>
                                                    <h5 class="mb-2">Click to upload new image</h5>
                                                    <p class="text-muted mb-0">or drag and drop</p>
                                                    <p class="text-muted small mt-2">PNG, JPG, GIF, WEBP up to 5MB</p>
                                                </div>
                                                
                                                <input type="file" name="new_banner" id="new_banner" accept="image/*" class="d-none">
                                                
                                                <div id="newPreviewContainer" class="text-center" style="display: none;">
                                                    <h6 class="mb-2">New Preview:</h6>
                                                    <img id="newImagePreview" class="image-preview" alt="New Preview">
                                                    <div id="newImageInfo" class="preview-info">
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <strong>Size:</strong> <span id="newFileSize">0 KB</span>
                                                            </div>
                                                            <div class="col-6">
                                                                <strong>Dimensions:</strong> <span id="newFileDimensions">0x0</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="mt-3">
                                                    <small class="text-muted">
                                                        <i class="fas fa-info-circle mr-1"></i>
                                                        Leave empty to keep current image. New image will replace the current one.
                                                    </small>
                                                </div>
                                            </div>
                                            
                                            <div class="form-section">
                                                <h5 class="mb-3"><i class="fas fa-link mr-2"></i> Banner Link Settings</h5>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Redirect URL</label>
                                                    <input type="url" class="form-control" name="link_url" 
                                                           value="<?php echo htmlspecialchars($banner['link_url'] ?? ''); ?>"
                                                           placeholder="https://example.com/page">
                                                    <small class="form-text text-muted">Where users go when clicking the banner</small>
                                                </div>
                                                
                                                <div class="form-group mb-0">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input" 
                                                               name="target_blank" id="target_blank" value="1"
                                                               <?php echo $banner['target_blank'] ? 'checked' : ''; ?>>
                                                        <label class="custom-control-label" for="target_blank">
                                                            Open link in new tab
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Right Column: Banner Details -->
                                        <div class="col-lg-6">
                                            <div class="form-section">
                                                <h5 class="mb-3"><i class="fas fa-info-circle mr-2"></i> Banner Details</h5>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Title <span class="required-star">*</span></label>
                                                    <input type="text" class="form-control" name="title" 
                                                           value="<?php echo htmlspecialchars($banner['title']); ?>" 
                                                           required maxlength="255">
                                                    <small class="form-text text-muted">Enter a descriptive title for this banner</small>
                                                    <div class="character-count" id="titleCount"><?php echo strlen($banner['title']); ?>/255 characters</div>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Description</label>
                                                    <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($banner['description'] ?? ''); ?></textarea>
                                                    <small class="form-text text-muted">Optional description for internal reference</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Alt Text (SEO)</label>
                                                    <input type="text" class="form-control" name="alt_text" 
                                                           value="<?php echo htmlspecialchars($banner['alt_text'] ?? ''); ?>"
                                                           placeholder="Describe the banner image for SEO" maxlength="255">
                                                    <small class="form-text text-muted">Important for accessibility and SEO</small>
                                                    <div class="character-count" id="altTextCount"><?php echo strlen($banner['alt_text'] ?? ''); ?>/255 characters</div>
                                                </div>
                                            </div>
                                            
                                            <div class="form-section">
                                                <h5 class="mb-3"><i class="fas fa-cog mr-2"></i> Display Settings</h5>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="form-label">Display Order</label>
                                                            <input type="number" class="form-control" name="display_order" 
                                                                   value="<?php echo htmlspecialchars($banner['display_order']); ?>"
                                                                   min="0" max="999">
                                                            <small class="form-text text-muted">Lower numbers display first</small>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="form-label">Expiry Date (Optional)</label>
                                                            <input type="date" class="form-control" name="expiry_date" 
                                                                   value="<?php echo htmlspecialchars($banner['expiry_date'] ?? ''); ?>">
                                                            <small class="form-text text-muted">Banner will auto-expire on this date</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group mb-0">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <label class="form-label mb-0">Banner Status</label>
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" 
                                                                   name="status" id="status" value="1"
                                                                   <?php echo $banner['status'] ? 'checked' : ''; ?>>
                                                            <label class="custom-control-label" for="status">
                                                                <span id="statusText"><?php echo $banner['status'] ? 'Active' : 'Inactive'; ?></span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <small class="form-text text-muted">Inactive banners won't be displayed on the website</small>
                                                </div>
                                            </div>
                                            
                                            <!-- Banner Stats -->
                                            <div class="card bg-light mt-3">
                                                <div class="card-body p-3">
                                                    <h6 class="mb-2"><i class="fas fa-chart-bar mr-2"></i> Banner Information</h6>
                                                    <div class="row small">
                                                        <div class="col-6">
                                                            <strong>Banner ID:</strong> #<?php echo $banner['id']; ?>
                                                        </div>
                                                        <div class="col-6">
                                                            <strong>Uploaded By:</strong> 
                                                            <?php 
                                                            if (!empty($banner['created_by'])) {
                                                                echo "Admin #" . $banner['created_by'];
                                                            } else {
                                                                echo "Unknown";
                                                            }
                                                            ?>
                                                        </div>
                                                        <div class="col-12 mt-1">
                                                            <strong>Current Status:</strong> 
                                                            <span class="badge badge-<?php echo $banner['status'] ? 'success' : 'danger'; ?>">
                                                                <?php echo $banner['status'] ? 'Active' : 'Inactive'; ?>
                                                            </span>
                                                            <?php if ($banner['expiry_date'] && strtotime($banner['expiry_date']) < time()): ?>
                                                                <span class="badge badge-warning ml-1">Expired</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="mt-4 pt-3 border-top">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <a href="view-banners.php" class="btn btn-secondary mr-2">
                                                    <i class="fas fa-times mr-1"></i> Cancel
                                                </a>
                                                <button type="button" class="btn btn-outline-danger" onclick="resetForm()">
                                                    <i class="fas fa-redo mr-1"></i> Reset Changes
                                                </button>
                                            </div>
                                            <div>
                                                <button type="submit" name="restore_original" class="btn btn-warning mr-2" onclick="return confirm('Restore original banner data?')">
                                                    <i class="fas fa-history mr-1"></i> Restore Original
                                                </button>
                                                <button type="submit" name="update" class="btn btn-primary">
                                                    <i class="fas fa-save mr-2"></i> Update Banner
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include "includes/footer.php"; ?>
    </section>

    <script>
        // File upload and preview functionality
        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('new_banner');
            const preview = document.getElementById('newImagePreview');
            const previewContainer = document.getElementById('newPreviewContainer');
            const imageInfo = document.getElementById('newImageInfo');
            const fileSize = document.getElementById('newFileSize');
            const fileDimensions = document.getElementById('newFileDimensions');
            const statusSwitch = document.getElementById('status');
            const statusText = document.getElementById('statusText');
            const titleInput = document.querySelector('input[name="title"]');
            const altInput = document.querySelector('input[name="alt_text"]');
            const titleCounter = document.getElementById('titleCount');
            const altCounter = document.getElementById('altTextCount');
            const originalTitle = "<?php echo addslashes($banner['title']); ?>";
            const originalAlt = "<?php echo addslashes($banner['alt_text'] ?? ''); ?>";
            const originalDescription = "<?php echo addslashes($banner['description'] ?? ''); ?>";
            const originalLink = "<?php echo addslashes($banner['link_url'] ?? ''); ?>";
            const originalTarget = <?php echo $banner['target_blank'] ? 'true' : 'false'; ?>;
            const originalOrder = <?php echo $banner['display_order']; ?>;
            const originalExpiry = "<?php echo $banner['expiry_date'] ?? ''; ?>";
            const originalStatus = <?php echo $banner['status'] ? 'true' : 'false'; ?>;
            
            // Click upload area
            uploadArea.addEventListener('click', function() {
                fileInput.click();
            });
            
            // File input change
            fileInput.addEventListener('change', function(e) {
                if (fileInput.files && fileInput.files[0]) {
                    const file = fileInput.files[0];
                    const reader = new FileReader();
                    
                    reader.onload = function(event) {
                        preview.src = event.target.result;
                        previewContainer.style.display = 'block';
                        
                        // Show file info
                        const sizeKB = (file.size / 1024).toFixed(2);
                        fileSize.textContent = sizeKB + ' KB';
                        
                        // Get image dimensions
                        const img = new Image();
                        img.onload = function() {
                            fileDimensions.textContent = img.width + 'x' + img.height;
                            imageInfo.style.display = 'block';
                            
                            // Show warning for non-standard sizes
                            if (img.width < 800 || img.height < 300) {
                                alert('Warning: Banner dimensions (' + img.width + 'x' + img.height + ') might be too small for optimal display.');
                            }
                        };
                        img.src = event.target.result;
                        
                        // Update upload area text
                        uploadArea.innerHTML = `
                            <div class="mb-2">
                                <i class="fas fa-check-circle fa-2x text-success"></i>
                            </div>
                            <h6 class="mb-1">${file.name}</h6>
                            <p class="text-muted small mb-0">Click to change image</p>
                        `;
                    };
                    
                    reader.readAsDataURL(file);
                }
            });
            
            // Drag and drop
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                uploadArea.addEventListener(eventName, () => {
                    uploadArea.classList.add('dragover');
                });
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, () => {
                    uploadArea.classList.remove('dragover');
                });
            });
            
            uploadArea.addEventListener('drop', function(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                
                if (files.length) {
                    fileInput.files = files;
                    const event = new Event('change');
                    fileInput.dispatchEvent(event);
                }
            });
            
            // Status switch text update
            statusSwitch.addEventListener('change', function() {
                statusText.textContent = this.checked ? 'Active' : 'Inactive';
            });
            
            // Character counters
            titleInput.addEventListener('input', function() {
                const count = this.value.length;
                titleCounter.textContent = count + '/255 characters';
                titleCounter.style.color = count > 255 ? '#dc3545' : '#6c757d';
            });
            
            altInput.addEventListener('input', function() {
                const count = this.value.length;
                altCounter.textContent = count + '/255 characters';
                altCounter.style.color = count > 255 ? '#dc3545' : '#6c757d';
            });
            
            // Form validation
            const form = document.getElementById('editBannerForm');
            form.addEventListener('submit', function(e) {
                if (!e.submitter || e.submitter.name !== 'update') return true;
                
                const titleInput = document.querySelector('input[name="title"]');
                if (!titleInput.value.trim()) {
                    e.preventDefault();
                    alert('Please enter a banner title');
                    titleInput.focus();
                    return false;
                }
                
                if (fileInput.files && fileInput.files[0]) {
                    const file = fileInput.files[0];
                    
                    // Check file size
                    if (file.size > 5000000) { // 5MB
                        e.preventDefault();
                        alert('File size exceeds 5MB limit');
                        return false;
                    }
                    
                    // Check file type
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    if (!allowedTypes.includes(file.type)) {
                        e.preventDefault();
                        alert('Invalid file type. Allowed: JPG, PNG, GIF, WEBP');
                        return false;
                    }
                }
                
                return true;
            });
            
            // Reset form to original values
            window.resetForm = function() {
                if (confirm('Reset all changes and restore original values?')) {
                    // Restore form values
                    document.querySelector('input[name="title"]').value = originalTitle;
                    document.querySelector('textarea[name="description"]').value = originalDescription;
                    document.querySelector('input[name="alt_text"]').value = originalAlt;
                    document.querySelector('input[name="link_url"]').value = originalLink;
                    document.querySelector('input[name="display_order"]').value = originalOrder;
                    document.querySelector('input[name="expiry_date"]').value = originalExpiry;
                    
                    // Restore checkboxes
                    document.getElementById('target_blank').checked = originalTarget;
                    document.getElementById('status').checked = originalStatus;
                    statusText.textContent = originalStatus ? 'Active' : 'Inactive';
                    
                    // Clear file input
                    fileInput.value = '';
                    previewContainer.style.display = 'none';
                    
                    // Reset upload area
                    uploadArea.innerHTML = `
                        <div class="mb-3">
                            <i class="fas fa-cloud-upload-alt fa-2x text-primary"></i>
                        </div>
                        <h5 class="mb-2">Click to upload new image</h5>
                        <p class="text-muted mb-0">or drag and drop</p>
                        <p class="text-muted small mt-2">PNG, JPG, GIF, WEBP up to 5MB</p>
                    `;
                    
                    // Update character counters
                    titleCounter.textContent = originalTitle.length + '/255 characters';
                    altCounter.textContent = originalAlt.length + '/255 characters';
                    
                    alert('Form reset to original values');
                }
            };
            
            // Preview current image on click
            document.getElementById('currentImage').addEventListener('click', function() {
                window.open(this.src, '_blank');
            });
            
            // Show current image dimensions
            const currentImg = new Image();
            currentImg.onload = function() {
                document.querySelector('.preview-info .row').innerHTML += `
                    <div class="col-12 mt-1">
                        <strong>Dimensions:</strong> ${this.width}x${this.height}px
                    </div>
                `;
            };
            currentImg.src = "<?php echo $banner['banner_path']; ?>";
        });
    </script>
</body>
</html>