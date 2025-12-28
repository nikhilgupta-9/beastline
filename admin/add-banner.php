<?php
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';
require_once __DIR__ . '/models/Setting.php';

// Initialize Settings
$setting = new Setting($conn);

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
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
    if (empty($_FILES['banner']['name'])) {
        $errors[] = "Banner image is required";
    }
    
    if (empty($title)) {
        $errors[] = "Banner title is required";
    }
    
    if (strlen($title) > 255) {
        $errors[] = "Title must be less than 255 characters";
    }
    
    // File validation
    if (empty($errors)) {
        $target_dir = "uploads/banners/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        // Generate unique filename
        $file_ext = strtolower(pathinfo($_FILES["banner"]["name"], PATHINFO_EXTENSION));
        $unique_id = uniqid('banner_');
        $target_file = $target_dir . $unique_id . '.' . $file_ext;
        
        // Validate image
        $check = @getimagesize($_FILES["banner"]["tmp_name"]);
        if ($check === false) {
            $errors[] = "Invalid image file";
        }
        
        // Check file size (5MB limit)
        if ($_FILES["banner"]["size"] > 5000000) {
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
    
    // Process if no errors
    if (empty($errors)) {
        if (move_uploaded_file($_FILES["banner"]["tmp_name"], $target_file)) {
            $created_by = $_SESSION['admin_id'] ?? 0;
            
            $stmt = $conn->prepare("INSERT INTO banners 
                (banner_path, title, description, alt_text, link_url, target_blank, 
                 display_order, status, expiry_date, created_by, uploaded_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            $stmt->bind_param("sssssiiisi", 
                $target_file, 
                $title,
                $description,
                $alt_text,
                $link_url,
                $target_blank,
                $display_order,
                $status,
                $expiry_date,
                $created_by
            );
            
            if ($stmt->execute()) {
                $banner_id = $stmt->insert_id;
                $success = "Banner added successfully!";
                
                // Clear form
                $_POST = array();
            } else {
                unlink($target_file);
                $errors[] = "Database error: " . $conn->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Error uploading file";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Add Banner | Admin Panel</title>
    <link rel="icon" href="<?php echo htmlspecialchars($setting->get('favicon', 'assets/img/logo.png')); ?>" type="image/png">

    <?php include "links.php"; ?>
    
    <style>
        /* Minimal custom CSS */
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 2.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9fa;
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
            max-height: 250px;
            border-radius: 6px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: none;
            margin: 15px auto;
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
        
        .dimension-badge {
            display: inline-block;
            padding: 2px 8px;
            background: #e9ecef;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-right: 5px;
        }
        
        .switch-sm {
            width: 45px;
            height: 24px;
        }
        
        .switch-sm .slider:before {
            height: 18px;
            width: 18px;
        }
        
        .switch-sm input:checked + .slider:before {
            transform: translateX(21px);
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
                                        <h3 class="m-0"><i class="fas fa-image mr-2"></i> Add New Banner</h3>
                                    </div>
                                    <div class="action-btn">
                                        <a href="view-banners.php" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-list mr-1"></i> View All Banners
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

                                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data" id="bannerForm">
                                    <div class="row">
                                        <!-- Left Column: Image Upload -->
                                        <div class="col-lg-6">
                                            <div class="form-section mb-4">
                                                <h5 class="mb-3"><i class="fas fa-upload mr-2"></i> Banner Image <span class="required-star">*</span></h5>
                                                
                                                <div class="upload-area" id="uploadArea" onclick="document.getElementById('banner').click()">
                                                    <div class="mb-3">
                                                        <i class="fas fa-cloud-upload-alt fa-3x text-primary"></i>
                                                    </div>
                                                    <h5 class="mb-2">Click to upload banner</h5>
                                                    <p class="text-muted mb-0">or drag and drop</p>
                                                    <p class="text-muted small mt-2">PNG, JPG, GIF, WEBP up to 5MB</p>
                                                </div>
                                                
                                                <input type="file" name="banner" id="banner" accept="image/*" class="d-none" required>
                                                
                                                <div id="previewContainer" class="text-center">
                                                    <img id="imagePreview" class="image-preview" alt="Preview">
                                                    <div id="imageInfo" class="preview-info d-none">
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <strong>Size:</strong> <span id="fileSize">0 KB</span>
                                                            </div>
                                                            <div class="col-6">
                                                                <strong>Dimensions:</strong> <span id="fileDimensions">0x0</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="mt-3">
                                                    <small class="text-muted">
                                                        <i class="fas fa-info-circle mr-1"></i>
                                                        Recommended: 1920x500px for banners, 800x600px for side banners
                                                    </small>
                                                </div>
                                            </div>
                                            
                                            <div class="form-section">
                                                <h5 class="mb-3"><i class="fas fa-link mr-2"></i> Banner Link Settings</h5>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Redirect URL</label>
                                                    <input type="url" class="form-control" name="link_url" 
                                                           value="<?php echo htmlspecialchars($_POST['link_url'] ?? ''); ?>"
                                                           placeholder="https://example.com/page">
                                                </div>
                                                
                                                <div class="form-group mb-0">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input" 
                                                               name="target_blank" id="target_blank" value="1"
                                                               <?php echo isset($_POST['target_blank']) ? 'checked' : ''; ?>>
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
                                                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                                                           required maxlength="255">
                                                    <small class="form-text text-muted">Enter a descriptive title for this banner</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Description</label>
                                                    <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                                    <small class="form-text text-muted">Optional description for internal reference</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Alt Text (SEO)</label>
                                                    <input type="text" class="form-control" name="alt_text" 
                                                           value="<?php echo htmlspecialchars($_POST['alt_text'] ?? ''); ?>"
                                                           placeholder="Describe the banner image for SEO">
                                                </div>
                                            </div>
                                            
                                            <div class="form-section">
                                                <h5 class="mb-3"><i class="fas fa-cog mr-2"></i> Display Settings</h5>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="form-label">Display Order</label>
                                                            <input type="number" class="form-control" name="display_order" 
                                                                   value="<?php echo htmlspecialchars($_POST['display_order'] ?? 0); ?>"
                                                                   min="0" max="999">
                                                            <small class="form-text text-muted">Lower numbers display first</small>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="form-label">Expiry Date (Optional)</label>
                                                            <input type="date" class="form-control" name="expiry_date" 
                                                                   value="<?php echo htmlspecialchars($_POST['expiry_date'] ?? ''); ?>">
                                                            <small class="form-text text-muted">Banner will auto-expire</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group mb-0">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <label class="form-label mb-0">Banner Status</label>
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" 
                                                                   name="status" id="status" value="1"
                                                                   <?php echo !isset($_POST['submit']) || isset($_POST['status']) ? 'checked' : ''; ?>>
                                                            <label class="custom-control-label" for="status">
                                                                <span id="statusText">Active</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <small class="form-text text-muted">Inactive banners won't be displayed on the website</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Submit Button -->
                                    <div class="mt-4 pt-3 border-top">
                                        <div class="d-flex justify-content-between">
                                            <a href="view-banners.php" class="btn btn-secondary">
                                                <i class="fas fa-arrow-left mr-2"></i> Back to List
                                            </a>
                                            <div>
                                                <button type="reset" class="btn btn-outline-secondary mr-2">
                                                    <i class="fas fa-redo mr-1"></i> Reset
                                                </button>
                                                <button type="submit" name="submit" class="btn btn-primary">
                                                    <i class="fas fa-save mr-2"></i> Save Banner
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
            const fileInput = document.getElementById('banner');
            const preview = document.getElementById('imagePreview');
            const previewContainer = document.getElementById('previewContainer');
            const imageInfo = document.getElementById('imageInfo');
            const fileSize = document.getElementById('fileSize');
            const fileDimensions = document.getElementById('fileDimensions');
            const statusSwitch = document.getElementById('status');
            const statusText = document.getElementById('statusText');
            
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
                        preview.style.display = 'block';
                        previewContainer.style.display = 'block';
                        
                        // Show file info
                        const sizeKB = (file.size / 1024).toFixed(2);
                        fileSize.textContent = sizeKB + ' KB';
                        
                        // Get image dimensions
                        const img = new Image();
                        img.onload = function() {
                            fileDimensions.textContent = img.width + 'x' + img.height;
                            imageInfo.classList.remove('d-none');
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
            
            // Form validation
            const form = document.getElementById('bannerForm');
            form.addEventListener('submit', function(e) {
                if (!fileInput.files || !fileInput.files[0]) {
                    e.preventDefault();
                    alert('Please select a banner image');
                    return false;
                }
                
                const titleInput = document.querySelector('input[name="title"]');
                if (!titleInput.value.trim()) {
                    e.preventDefault();
                    alert('Please enter a banner title');
                    titleInput.focus();
                    return false;
                }
                
                // Check file size
                const file = fileInput.files[0];
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
                
                return true;
            });
            
            // Real-time character count
            const titleInput = document.querySelector('input[name="title"]');
            const titleCounter = document.createElement('small');
            titleCounter.className = 'form-text text-muted text-right';
            titleCounter.textContent = '0/255 characters';
            titleInput.parentNode.appendChild(titleCounter);
            
            titleInput.addEventListener('input', function() {
                titleCounter.textContent = this.value.length + '/255 characters';
                if (this.value.length > 255) {
                    titleCounter.style.color = '#dc3545';
                } else {
                    titleCounter.style.color = '#6c757d';
                }
            });
            
            // Alt text counter
            const altInput = document.querySelector('input[name="alt_text"]');
            const altCounter = document.createElement('small');
            altCounter.className = 'form-text text-muted text-right';
            altCounter.textContent = '0/255 characters';
            altInput.parentNode.appendChild(altCounter);
            
            altInput.addEventListener('input', function() {
                altCounter.textContent = this.value.length + '/255 characters';
                if (this.value.length > 255) {
                    altCounter.style.color = '#dc3545';
                } else {
                    altCounter.style.color = '#6c757d';
                }
            });
        });
    </script>
</body>
</html>