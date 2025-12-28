<?php
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';
require_once __DIR__ . '/models/Setting.php';

// Initialize Settings
$setting = new Setting($conn);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $successCount = 0;
    
    // Process all POST data
    foreach($_POST as $key => $value) {
        if($key != 'submit' && $key != 'logo' && $key != 'favicon') {
            if($setting->set($key, $value)) {
                $successCount++;
            }
        }
    }
    
    // Handle logo upload
    if(isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $logo_dir = "uploads/logo/";
        
        // Create directory if not exists
        if(!is_dir($logo_dir)){
            mkdir($logo_dir, 0755, true);
        }
        
        // Generate unique filename
        $file_extension = strtolower(pathinfo($_FILES["logo"]["name"], PATHINFO_EXTENSION));
        $new_filename = 'logo_' . uniqid() . '.' . $file_extension;
        $target_file = $logo_dir . $new_filename;
        
        $uploadOk = 1;
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        // Check if image file is a actual image
        $check = getimagesize($_FILES["logo"]["tmp_name"]);
        if ($check !== false) {
            // Check MIME type
            $mime = mime_content_type($_FILES["logo"]["tmp_name"]);
            if (in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                // Check file size (500KB)
                if ($_FILES["logo"]["size"] <= 500000) {
                    // Try to upload file
                    if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
                        // Store relative path in settings
                        $relative_path = 'uploads/logo/' . $new_filename;
                        if($setting->set('site_logo', $relative_path)) {
                            $successCount++;
                        }
                    }
                }
            }
        }
    }
    
    // Handle favicon upload
    if(isset($_FILES['favicon']) && $_FILES['favicon']['error'] == 0) {
        $favicon_dir = "uploads/favicon/";
        
        // Create directory if not exists
        if(!is_dir($favicon_dir)){
            mkdir($favicon_dir, 0755, true);
        }
        
        // Generate unique filename
        $file_extension = strtolower(pathinfo($_FILES["favicon"]["name"], PATHINFO_EXTENSION));
        $new_filename = 'favicon_' . uniqid() . '.' . $file_extension;
        $target_file = $favicon_dir . $new_filename;
        
        $uploadOk = 1;
        $allowed_types = ['ico', 'png', 'jpg'];
        
        // Check file size (100KB)
        if ($_FILES["favicon"]["size"] <= 100000) {
            // Check MIME type for favicon
            $mime = mime_content_type($_FILES["favicon"]["tmp_name"]);
            if (in_array($mime, ['image/x-icon', 'image/vnd.microsoft.icon', 'image/png', 'image/jpeg'])) {
                // Try to upload file
                if (move_uploaded_file($_FILES["favicon"]["tmp_name"], $target_file)) {
                    // Store relative path in settings
                    $relative_path = 'uploads/favicon/' . $new_filename;
                    if($setting->set('favicon', $relative_path)) {
                        $successCount++;
                    }
                }
            }
        }
    }
    
    // Set success/error message
    if($successCount > 0) {
        $success = "Settings updated successfully!";
    } else if(isset($_POST['submit'])) {
        $message = "No changes were made.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Website Settings | Admin Panel |  <?php echo htmlspecialchars($setting->get('site_name')); ?></title>
    <link rel="icon" href="<?php echo htmlspecialchars($setting->get('favicon')); ?>" type="image/png">

    <?php include "links.php"; ?>
    
    <!-- Color Picker CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-colorpicker/3.4.0/css/bootstrap-colorpicker.min.css">
    
    <style>
        .color-picker-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .color-preview {
            width: 40px;
            height: 40px;
            border-radius: 5px;
            border: 2px solid #dee2e6;
            cursor: pointer;
            display: inline-block;
        }
        
        .tab-content {
            padding: 20px 0;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            padding: 12px 20px;
        }
        
        .nav-tabs .nav-link.active {
            color: #3498db;
            border-bottom: 3px solid #3498db;
            background: transparent;
        }
        
        .settings-preview {
            border-left: 4px solid #3498db;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .preview-btn {
            padding: 8px 16px;
            border-radius: 5px;
            border: none;
            margin: 5px;
            color: white;
        }
        
        .file-upload-area {
            border: 2px dashed #ddd;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
            background: #f8f9fa;
            cursor: pointer;
            margin-bottom: 15px;
        }
        
        .file-upload-area:hover {
            background: #e9ecef;
        }
        
        .image-preview-container {
            text-align: center;
            margin-top: 15px;
        }
        
        .image-preview {
            max-width: 200px;
            max-height: 100px;
            border: 1px solid #ddd;
            padding: 5px;
            border-radius: 5px;
        }
        
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
        }
        
        input:checked + .slider {
            background-color: #2196F3;
        }
        
        input:focus + .slider {
            box-shadow: 0 0 1px #2196F3;
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .slider.round {
            border-radius: 34px;
        }
        
        .slider.round:before {
            border-radius: 50%;
        }
        
        .form-check-input:checked {
            background-color: #3498db;
            border-color: #3498db;
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
                                        <h2 class="m-0"><i class="fas fa-cog"></i> Website Settings</h2>
                                    </div>
                                    <div class="action-btn">
                                        <button type="button" class="btn btn-primary btn-sm" onclick="saveAllSettings()">
                                            <i class="fas fa-save"></i> Save All Settings
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if(isset($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(isset($message)): ?>
                            <div class="alert alert-warning alert-dismissible fade show m-3" role="alert">
                                <i class="fas fa-info-circle"></i> <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php endif; ?>

                            <div class="white_card_body">
                                <!-- Tabs Navigation -->
                                <ul class="nav nav-tabs mb-4" id="settingsTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button">
                                            <i class="fas fa-globe"></i> General
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="appearance-tab" data-bs-toggle="tab" data-bs-target="#appearance" type="button">
                                            <i class="fas fa-paint-brush"></i> Appearance
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="admin-tab" data-bs-toggle="tab" data-bs-target="#admin" type="button">
                                            <i class="fas fa-user-shield"></i> Admin Panel
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact" type="button">
                                            <i class="fas fa-address-book"></i> Contact
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="social-tab" data-bs-toggle="tab" data-bs-target="#social" type="button">
                                            <i class="fas fa-share-alt"></i> Social Media
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button">
                                            <i class="fas fa-shield-alt"></i> Security
                                        </button>
                                    </li>
                                </ul>

                                <!-- Tab Content -->
                                <div class="tab-content" id="settingsTabContent">
                                    
                                    <!-- General Tab -->
                                    <div class="tab-pane fade show active" id="general" role="tabpanel">
                                        <form id="generalForm" method="POST" enctype="multipart/form-data">
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">Website Name *</label>
                                                        <input type="text" class="form-control" name="site_name" 
                                                               value="<?php echo htmlspecialchars($setting->get('site_name', 'My Website')); ?>" required>
                                                    </div>
                                                    
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">Website Logo</label>
                                                        <div class="file-upload-area" onclick="document.getElementById('logoInput').click()">
                                                            <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i>
                                                            <p>Click to upload logo</p>
                                                            <p class="text-muted small">PNG, JPG, GIF up to 500KB</p>
                                                        </div>
                                                        <input type="file" name="logo" id="logoInput" accept="image/*" class="d-none" onchange="previewImage(this, 'logoPreview')">
                                                        <div class="image-preview-container" id="logoPreview">
                                                            <?php if($setting->get('site_logo')): ?>
                                                            <img src="<?php echo htmlspecialchars($setting->get('site_logo')); ?>" class="image-preview" alt="Current Logo">
                                                            <p class="text-muted small mt-2">Current Logo</p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">Favicon</label>
                                                        <div class="file-upload-area" onclick="document.getElementById('faviconInput').click()">
                                                            <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i>
                                                            <p>Click to upload favicon</p>
                                                            <p class="text-muted small">ICO, PNG up to 100KB</p>
                                                        </div>
                                                        <input type="file" name="favicon" id="faviconInput" accept=".ico,image/x-icon,image/png" class="d-none" onchange="previewImage(this, 'faviconPreview')">
                                                        <div class="image-preview-container" id="faviconPreview">
                                                            <?php if($setting->get('favicon')): ?>
                                                            <img src="<?php echo htmlspecialchars($setting->get('favicon')); ?>" class="image-preview" style="max-width: 32px;" alt="Current Favicon">
                                                            <p class="text-muted small mt-2">Current Favicon</p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-lg-6">
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">Copyright Text</label>
                                                        <input type="text" class="form-control" name="copyright_text" 
                                                               value="<?php echo htmlspecialchars($setting->get('copyright_text', '© 2024 Your Company. All rights reserved.')); ?>">
                                                    </div>
                                                    
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">Meta Description (SEO)</label>
                                                        <textarea class="form-control" name="meta_description" rows="3"><?php echo htmlspecialchars($setting->get('meta_description')); ?></textarea>
                                                        <small class="text-muted">Recommended: 150-160 characters</small>
                                                    </div>
                                                    
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">Default Currency</label>
                                                        <select class="form-select" name="currency">
                                                            <option value="USD" <?php echo $setting->get('currency') == 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                                            <option value="INR" <?php echo $setting->get('currency') == 'INR' ? 'selected' : ''; ?>>INR (₹)</option>
                                                            <option value="EUR" <?php echo $setting->get('currency') == 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                                            <option value="GBP" <?php echo $setting->get('currency') == 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="settings-preview">
                                                        <h6><i class="fas fa-eye"></i> Preview</h6>
                                                        <p><strong>Website:</strong> <?php echo htmlspecialchars($setting->get('site_name', 'My Website')); ?></p>
                                                        <p><small>Copyright: <?php echo htmlspecialchars($setting->get('copyright_text', '© 2024 Your Company')); ?></small></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    
                                    <!-- Appearance Tab -->
                                    <div class="tab-pane fade" id="appearance" role="tabpanel">
                                        <form id="appearanceForm" method="POST">
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">Primary Color</label>
                                                        <div class="color-picker-container">
                                                            <input type="text" class="form-control color-picker-input" name="primary_color" 
                                                                   value="<?php echo htmlspecialchars($setting->get('primary_color', '#3498db')); ?>">
                                                            <div class="color-preview" 
                                                                 style="background-color: <?php echo $setting->get('primary_color', '#3498db'); ?>"
                                                                 onclick="document.querySelector('input[name=\"primary_color\"]').focus()"></div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">Secondary Color</label>
                                                        <div class="color-picker-container">
                                                            <input type="text" class="form-control color-picker-input" name="secondary_color" 
                                                                   value="<?php echo htmlspecialchars($setting->get('secondary_color', '#2ecc71')); ?>">
                                                            <div class="color-preview" 
                                                                 style="background-color: <?php echo $setting->get('secondary_color', '#2ecc71'); ?>"
                                                                 onclick="document.querySelector('input[name=\"secondary_color\"]').focus()"></div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">Font Family</label>
                                                        <select class="form-select" name="font_family" onchange="updatePreview()">
                                                            <option value="Inter" <?php echo $setting->get('font_family') == 'Inter' ? 'selected' : ''; ?>>Inter</option>
                                                            <option value="Roboto" <?php echo $setting->get('font_family') == 'Roboto' ? 'selected' : ''; ?>>Roboto</option>
                                                            <option value="Poppins" <?php echo $setting->get('font_family') == 'Poppins' ? 'selected' : ''; ?>>Poppins</option>
                                                            <option value="Open Sans" <?php echo $setting->get('font_family') == 'Open Sans' ? 'selected' : ''; ?>>Open Sans</option>
                                                            <option value="Arial" <?php echo $setting->get('font_family') == 'Arial' ? 'selected' : ''; ?>>Arial</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">Base Font Size</label>
                                                        <div class="d-flex align-items-center">
                                                            <input type="range" class="form-range me-3" name="font_size" min="12" max="20" 
                                                                   value="<?php echo $setting->get('font_size', '16'); ?>" 
                                                                   oninput="document.getElementById('fontSizeValue').textContent = this.value + 'px'; updatePreview()">
                                                            <span id="fontSizeValue" class="badge bg-primary"><?php echo $setting->get('font_size', '16'); ?>px</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-lg-6">
                                                    <div class="settings-preview">
                                                        <h5>Live Preview</h5>
                                                        <p id="previewText" style="font-size: <?php echo $setting->get('font_size', '16'); ?>px;">
                                                            This is how your website text will appear with the selected settings.
                                                        </p>
                                                        <div class="mt-3">
                                                            <button type="button" class="preview-btn" id="previewPrimaryBtn">Primary Button</button>
                                                            <button type="button" class="preview-btn" id="previewSecondaryBtn">Secondary Button</button>
                                                        </div>
                                                        <div class="mt-3 p-3 rounded" id="previewBg">
                                                            <small>This is a sample background with primary color overlay</small>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group mt-3">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" name="enable_dark_mode" value="1" 
                                                                   id="enableDarkMode" <?php echo $setting->get('enable_dark_mode') == '1' ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="enableDarkMode">
                                                                Enable Dark Mode Toggle for Users
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    
                                    <!-- Admin Panel Tab -->
                                    <div class="tab-pane fade" id="admin" role="tabpanel">
                                        <form id="adminForm" method="POST">
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">Admin Theme</label>
                                                        <div class="row">
                                                            <div class="col-md-4 mb-2">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="radio" name="admin_theme" value="light" 
                                                                           id="themeLight" <?php echo $setting->get('admin_theme') == 'light' ? 'checked' : ''; ?>>
                                                                    <label class="form-check-label" for="themeLight">
                                                                        <i class="fas fa-sun"></i> Light
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4 mb-2">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="radio" name="admin_theme" value="dark" 
                                                                           id="themeDark" <?php echo $setting->get('admin_theme') == 'dark' ? 'checked' : ''; ?>>
                                                                    <label class="form-check-label" for="themeDark">
                                                                        <i class="fas fa-moon"></i> Dark
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4 mb-2">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="radio" name="admin_theme" value="auto" 
                                                                           id="themeAuto" <?php echo $setting->get('admin_theme') == 'auto' ? 'checked' : ''; ?>>
                                                                    <label class="form-check-label" for="themeAuto">
                                                                        <i class="fas fa-adjust"></i> Auto
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">Sidebar Color</label>
                                                        <div class="color-picker-container">
                                                            <input type="text" class="form-control color-picker-input" name="sidebar_color" 
                                                                   value="<?php echo htmlspecialchars($setting->get('sidebar_color', '#2c3e50')); ?>">
                                                            <div class="color-preview" 
                                                                 style="background-color: <?php echo $setting->get('sidebar_color', '#2c3e50'); ?>"></div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">Header Color</label>
                                                        <div class="color-picker-container">
                                                            <input type="text" class="form-control color-picker-input" name="header_color" 
                                                                   value="<?php echo htmlspecialchars($setting->get('header_color', '#34495e')); ?>">
                                                            <div class="color-preview" 
                                                                 style="background-color: <?php echo $setting->get('header_color', '#34495e'); ?>"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-lg-6">
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">Admin Panel Font</label>
                                                        <select class="form-select" name="admin_font">
                                                            <option value="default" <?php echo $setting->get('admin_font') == 'default' ? 'selected' : ''; ?>>Default</option>
                                                            <option value="Inter" <?php echo $setting->get('admin_font') == 'Inter' ? 'selected' : ''; ?>>Inter</option>
                                                            <option value="Roboto" <?php echo $setting->get('admin_font') == 'Roboto' ? 'selected' : ''; ?>>Roboto</option>
                                                            <option value="Poppins" <?php echo $setting->get('admin_font') == 'Poppins' ? 'selected' : ''; ?>>Poppins</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">Table Style</label>
                                                        <select class="form-select" name="table_style">
                                                            <option value="striped" <?php echo $setting->get('table_style') == 'striped' ? 'selected' : ''; ?>>Striped</option>
                                                            <option value="bordered" <?php echo $setting->get('table_style') == 'bordered' ? 'selected' : ''; ?>>Bordered</option>
                                                            <option value="hover" <?php echo $setting->get('table_style') == 'hover' ? 'selected' : ''; ?>>Hover Rows</option>
                                                            <option value="compact" <?php echo $setting->get('table_style') == 'compact' ? 'selected' : ''; ?>>Compact</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="form-group mb-3">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" name="enable_sidebar_collapse" value="1" 
                                                                   id="sidebarCollapse" <?php echo $setting->get('enable_sidebar_collapse') == '1' ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="sidebarCollapse">
                                                                Enable Sidebar Collapse
                                                            </label>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="settings-preview">
                                                        <h6>Admin Preview</h6>
                                                        <p>Sidebar: <span class="badge" style="background-color: <?php echo $setting->get('sidebar_color', '#2c3e50'); ?>; color: white;">Color</span></p>
                                                        <p>Header: <span class="badge" style="background-color: <?php echo $setting->get('header_color', '#34495e'); ?>; color: white;">Color</span></p>
                                                        <p>Theme: <span class="badge <?php echo $setting->get('admin_theme') == 'dark' ? 'bg-dark' : 'bg-light text-dark'; ?>">
                                                            <?php echo ucfirst($setting->get('admin_theme', 'dark')); ?>
                                                        </span></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    
                                    <!-- Contact Tab -->
                                    <div class="tab-pane fade" id="contact" role="tabpanel">
                                        <form id="contactForm" method="POST">
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">Business Email *</label>
                                                        <input type="email" class="form-control" name="business_email" 
                                                               value="<?php echo htmlspecialchars($setting->get('business_email')); ?>" required>
                                                    </div>
                                                    
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">Support Phone</label>
                                                        <input type="tel" class="form-control" name="support_phone" 
                                                               value="<?php echo htmlspecialchars($setting->get('support_phone')); ?>">
                                                    </div>
                                                    
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">WhatsApp Number</label>
                                                        <input type="tel" class="form-control" name="whatsapp_number" 
                                                               value="<?php echo htmlspecialchars($setting->get('whatsapp_number')); ?>">
                                                        <small class="text-muted">Include country code (e.g., +91)</small>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-lg-6">
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">Business Address</label>
                                                        <textarea class="form-control" name="business_address" rows="4"><?php echo htmlspecialchars($setting->get('business_address')); ?></textarea>
                                                    </div>
                                                    
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">Google Maps Embed Code</label>
                                                        <textarea class="form-control" name="google_maps" rows="3" placeholder='<iframe src="https://www.google.com/maps/embed?..."'></textarea>
                                                        <small class="text-muted">Paste iframe code from Google Maps</small>
                                                    </div>
                                                    
                                                    <div class="settings-preview">
                                                        <h6>Contact Preview</h6>
                                                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($setting->get('business_email', 'Not set')); ?></p>
                                                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($setting->get('support_phone', 'Not set')); ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    
                                    <!-- Social Media Tab -->
                                    <div class="tab-pane fade" id="social" role="tabpanel">
                                        <form id="socialForm" method="POST">
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="form-group mb-3">
                                                        <label class="form-label"><i class="fab fa-facebook text-primary"></i> Facebook URL</label>
                                                        <input type="url" class="form-control" name="facebook_url" 
                                                               value="<?php echo htmlspecialchars($setting->get('facebook_url')); ?>"
                                                               placeholder="https://facebook.com/yourpage">
                                                    </div>
                                                    
                                                    <div class="form-group mb-3">
                                                        <label class="form-label"><i class="fab fa-instagram text-danger"></i> Instagram URL</label>
                                                        <input type="url" class="form-control" name="instagram_url" 
                                                               value="<?php echo htmlspecialchars($setting->get('instagram_url')); ?>"
                                                               placeholder="https://instagram.com/yourpage">
                                                    </div>
                                                    
                                                    <div class="form-group mb-3">
                                                        <label class="form-label"><i class="fab fa-twitter text-info"></i> Twitter (X) URL</label>
                                                        <input type="url" class="form-control" name="twitter_url" 
                                                               value="<?php echo htmlspecialchars($setting->get('twitter_url')); ?>"
                                                               placeholder="https://twitter.com/yourprofile">
                                                    </div>
                                                </div>
                                                
                                                <div class="col-lg-6">
                                                    <div class="form-group mb-3">
                                                        <label class="form-label"><i class="fab fa-linkedin text-primary"></i> LinkedIn URL</label>
                                                        <input type="url" class="form-control" name="linkedin_url" 
                                                               value="<?php echo htmlspecialchars($setting->get('linkedin_url')); ?>"
                                                               placeholder="https://linkedin.com/company/yourcompany">
                                                    </div>
                                                    
                                                    <div class="form-group mb-3">
                                                        <label class="form-label"><i class="fab fa-youtube text-danger"></i> YouTube URL</label>
                                                        <input type="url" class="form-control" name="youtube_url" 
                                                               value="<?php echo htmlspecialchars($setting->get('youtube_url')); ?>"
                                                               placeholder="https://youtube.com/yourchannel">
                                                    </div>
                                                    
                                                    <div class="form-group mb-3">
                                                        <label class="form-label"><i class="fab fa-pinterest text-danger"></i> Pinterest URL</label>
                                                        <input type="url" class="form-control" name="pinterest_url" 
                                                               value="<?php echo htmlspecialchars($setting->get('pinterest_url')); ?>"
                                                               placeholder="https://pinterest.com/yourprofile">
                                                    </div>
                                                    
                                                    <div class="settings-preview">
                                                        <h6>Social Media Icons Preview</h6>
                                                        <div class="social-icons mt-2">
                                                            <?php if($setting->get('facebook_url')): ?>
                                                            <a href="#" class="me-2"><i class="fab fa-facebook fa-2x text-primary"></i></a>
                                                            <?php endif; ?>
                                                            <?php if($setting->get('instagram_url')): ?>
                                                            <a href="#" class="me-2"><i class="fab fa-instagram fa-2x text-danger"></i></a>
                                                            <?php endif; ?>
                                                            <?php if($setting->get('twitter_url')): ?>
                                                            <a href="#" class="me-2"><i class="fab fa-twitter fa-2x text-info"></i></a>
                                                            <?php endif; ?>
                                                            <?php if($setting->get('linkedin_url')): ?>
                                                            <a href="#" class="me-2"><i class="fab fa-linkedin fa-2x text-primary"></i></a>
                                                            <?php endif; ?>
                                                            <?php if($setting->get('youtube_url')): ?>
                                                            <a href="#" class="me-2"><i class="fab fa-youtube fa-2x text-danger"></i></a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    
                                    <!-- Security Tab -->
                                    <div class="tab-pane fade" id="security" role="tabpanel">
                                        <form id="securityForm" method="POST">
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">Session Timeout (minutes)</label>
                                                        <input type="number" class="form-control" name="session_timeout" min="5" max="240" 
                                                               value="<?php echo htmlspecialchars($setting->get('session_timeout', '30')); ?>">
                                                        <small class="text-muted">User will be logged out after this time of inactivity</small>
                                                    </div>
                                                    
                                                    <div class="form-group mb-3">
                                                        <label class="form-label">Max Login Attempts</label>
                                                        <input type="number" class="form-control" name="max_login_attempts" min="1" max="10" 
                                                               value="<?php echo htmlspecialchars($setting->get('max_login_attempts', '5')); ?>">
                                                        <small class="text-muted">Number of failed login attempts before lockout</small>
                                                    </div>
                                                    
                                                    <div class="form-group mb-3">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" name="enable_2fa" value="1" 
                                                                   id="enable2FA" <?php echo $setting->get('enable_2fa') == '1' ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="enable2FA">
                                                                Enable Two-Factor Authentication
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-lg-6">
                                                    <div class="form-group mb-3">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" name="force_strong_passwords" value="1" 
                                                                   id="strongPasswords" <?php echo $setting->get('force_strong_passwords') == '1' ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="strongPasswords">
                                                                Require Strong Passwords (Min 8 chars, uppercase, lowercase, number)
                                                            </label>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group mb-3">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" name="enable_captcha" value="1" 
                                                                   id="enableCaptcha" <?php echo $setting->get('enable_captcha') == '1' ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="enableCaptcha">
                                                                Enable CAPTCHA on Login Page
                                                            </label>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group mb-3">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" name="log_login_attempts" value="1" 
                                                                   id="logLogins" <?php echo $setting->get('log_login_attempts') == '1' ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="logLogins">
                                                                Log All Login Attempts
                                                            </label>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="settings-preview">
                                                        <h6>Security Status</h6>
                                                        <p><span class="badge <?php echo $setting->get('enable_2fa') == '1' ? 'bg-success' : 'bg-warning'; ?>">
                                                            <?php echo $setting->get('enable_2fa') == '1' ? '2FA: Enabled' : '2FA: Disabled'; ?>
                                                        </span></p>
                                                        <p>Session Timeout: <span class="badge bg-info"><?php echo $setting->get('session_timeout', '30'); ?> minutes</span></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    
                                </div>
                                
                                <!-- Save Button -->
                                <div class="mt-4 text-center">
                                    <button type="button" class="btn btn-primary btn-lg" onclick="saveAllSettings()">
                                        <i class="fas fa-save"></i> Save All Settings
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-lg ms-2" onclick="resetToDefaults()">
                                        <i class="fas fa-undo"></i> Reset to Defaults
                                    </button>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include "includes/footer.php"; ?>
    </section>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Color Picker -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-colorpicker/3.4.0/js/bootstrap-colorpicker.min.js"></script>
    
    <script>
        // Initialize color pickers
        $(document).ready(function() {
            $('.color-picker-input').colorpicker({
                format: 'hex'
            });
            
            // Update preview when color changes
            $('.color-picker-input').on('colorpickerChange', function(event) {
                const color = event.color.toString();
                const inputName = $(this).attr('name');
                $(this).closest('.color-picker-container').find('.color-preview').css('background-color', color);
                
                // Update live preview for appearance tab
                if(inputName === 'primary_color') {
                    $('#previewPrimaryBtn').css('background-color', color);
                    $('#previewBg').css('background-color', color + '20');
                }
                if(inputName === 'secondary_color') {
                    $('#previewSecondaryBtn').css('background-color', color);
                }
            });
            
            // Initialize preview
            updatePreview();
        });
        
        // Image preview function
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'image-preview';
                    img.style.maxWidth = previewId === 'faviconPreview' ? '32px' : '200px';
                    
                    const p = document.createElement('p');
                    p.className = 'text-muted small mt-2';
                    p.textContent = 'New Upload';
                    
                    preview.appendChild(img);
                    preview.appendChild(p);
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Update live preview
        function updatePreview() {
            // Get current form values
            const primaryColor = document.querySelector('input[name="primary_color"]').value;
            const secondaryColor = document.querySelector('input[name="secondary_color"]').value;
            const fontFamily = document.querySelector('select[name="font_family"]').value;
            const fontSize = document.querySelector('input[name="font_size"]').value;
            
            // Update preview elements
            document.getElementById('previewText').style.fontFamily = fontFamily + ', sans-serif';
            document.getElementById('previewText').style.fontSize = fontSize + 'px';
            
            document.getElementById('previewPrimaryBtn').style.backgroundColor = primaryColor;
            document.getElementById('previewSecondaryBtn').style.backgroundColor = secondaryColor;
            
            document.getElementById('previewBg').style.backgroundColor = primaryColor + '20';
        }
        
        // Save all settings
        function saveAllSettings() {
            // Show loading state
            const saveBtn = document.querySelector('button[onclick="saveAllSettings()"]');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            saveBtn.disabled = true;
            
            // Collect data from all forms
            const forms = ['generalForm', 'appearanceForm', 'adminForm', 'contactForm', 'socialForm', 'securityForm'];
            const formData = new FormData();
            
            // Add data from general form (includes files)
            const generalForm = document.getElementById('generalForm');
            const generalFormData = new FormData(generalForm);
            for(let [key, value] of generalFormData.entries()) {
                if(value instanceof File) {
                    formData.append(key, value);
                } else {
                    formData.append(key, value);
                }
            }
            
            // Add data from other forms
            forms.slice(1).forEach(formId => {
                const form = document.getElementById(formId);
                const data = new FormData(form);
                for(let [key, value] of data.entries()) {
                    formData.append(key, value);
                }
            });
            
            // Submit via AJAX
            fetch('<?= ADMIN_URL ?>setting.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                // Create a temporary div to parse the response
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                
                // Check for success message
                const successAlert = tempDiv.querySelector('.alert-success');
                if(successAlert) {
                    // Show success message
                    showAlert('success', successAlert.textContent.trim());
                    
                    // Reload page after 1.5 seconds to show updated settings
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    // Show error message
                    showAlert('danger', 'There was an error saving settings.');
                    saveBtn.innerHTML = originalText;
                    saveBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Network error. Please try again.');
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
            });
        }
        
        // Reset to defaults
        function resetToDefaults() {
            if(confirm('Are you sure you want to reset all settings to default values? This cannot be undone.')) {
                fetch('<?= ADMIN_URL ?>reset-settings.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        showAlert('success', 'Settings reset to defaults.');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                });
            }
        }
        
        // Show alert message
        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show m-3`;
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Insert after the header
            const header = document.querySelector('.white_card_header');
            header.parentNode.insertBefore(alertDiv, header.nextSibling);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
    </script>

</body>
</html>