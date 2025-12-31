<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';
require_once __DIR__ . '/models/Setting.php';

// Initialize Settings
$setting = new Setting($conn);

// Fetch parent categories for dropdown
$parent_categories = [];
$parent_query = "SELECT id, categories, parent_id FROM categories WHERE parent_id = 0 AND status = 1 ORDER BY display_order, categories";
$parent_result = mysqli_query($conn, $parent_query);
if ($parent_result) {
    while ($row = mysqli_fetch_assoc($parent_result)) {
        $parent_categories[] = $row;
    }
}

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add-categories'])) {
    // Validate and sanitize inputs
    $categories = trim($_POST['cate_name'] ?? '');
    $parent_id = (int)($_POST['parent_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_desc = trim($_POST['meta_desc'] ?? '');
    $meta_key = trim($_POST['meta_key'] ?? '');
    $status = (int)($_POST['status'] ?? 1);
    $display_order = (int)($_POST['display_order'] ?? 0);
    $featured = isset($_POST['featured']) ? 1 : 0;
    $show_in_nav = isset($_POST['show_in_nav']) ? 1 : 0;
    $show_in_home = isset($_POST['show_in_home']) ? 1 : 0;
    $icon_class = trim($_POST['icon_class'] ?? '');
    
    // Validation
    if (empty($categories)) {
        $errors[] = "Category name is required";
    }
    
    if (strlen($categories) > 255) {
        $errors[] = "Category name must be less than 255 characters";
    }
    
    // Check if category already exists
    $check_sql = "SELECT id FROM categories WHERE categories = ? AND parent_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("si", $categories, $parent_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        $errors[] = "Category with this name already exists under selected parent";
    }
    $check_stmt->close();
    
    // Handle image upload
    $image_name = '';
    if (!empty($_FILES['imageUpload']['name'])) {
        $target_dir = "uploads/category/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES["imageUpload"]["name"], PATHINFO_EXTENSION));
        $unique_filename = uniqid('category_') . '.' . $file_ext;
        $target_file = $target_dir . $unique_filename;
        
        // Validate image
        $check = @getimagesize($_FILES["imageUpload"]["tmp_name"]);
        if ($check === false) {
            $errors[] = "File is not a valid image";
        }
        
        // Check file size (5MB limit)
        if ($_FILES["imageUpload"]["size"] > 5000000) {
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
        
        if (empty($errors)) {
            // Resize image for optimization
            if ($check[0] > 800 || $check[1] > 800) {
                // Create resized version
                $resized_file = $target_dir . 'resized_' . $unique_filename;
                if (resizeImage($_FILES["imageUpload"]["tmp_name"], $resized_file, 800, 800)) {
    $target_file = $resized_file;
}

            }
            
            if (move_uploaded_file($_FILES["imageUpload"]["tmp_name"], $target_file)) {
                $image_name = pathinfo($target_file, PATHINFO_BASENAME);
            } else {
                $errors[] = "Error uploading image file";
            }
        }
    }
    
    // Generate slug URL
    $slug_url = generate_slug($categories);
    
    // Ensure slug is unique
    $slug_counter = 1;
    $original_slug = $slug_url;
    while (true) {
        $check_slug = "SELECT id FROM categories WHERE slug_url = ?";
        $stmt = $conn->prepare($check_slug);
        $stmt->bind_param("s", $slug_url);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows === 0) {
            $stmt->close();
            break;
        }
        $stmt->close();
        
        $slug_url = $original_slug . '-' . $slug_counter;
        $slug_counter++;
    }
    
    // Generate unique category ID
    $cate_id = 'CAT' . strtoupper(substr(md5(uniqid()), 0, 8));
    
    // Insert if no errors
    if (empty($errors)) {
        $insert_sql = "INSERT INTO categories 
            (cate_id, categories, parent_id, description, meta_title, meta_desc, meta_key, 
             image, icon_class, slug_url, status, display_order, featured, 
             show_in_nav, show_in_home, added_on) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("ssisssssssiiiii", 
            $cate_id, 
            $categories,
            $parent_id,
            $description,
            $meta_title,
            $meta_desc,
            $meta_key,
            $image_name,
            $icon_class,
            $slug_url,
            $status,
            $display_order,
            $featured,
            $show_in_nav,
            $show_in_home
        );
        
        if ($stmt->execute()) {
            $category_id = $stmt->insert_id;
            $success = "Category added successfully! Category ID: " . $cate_id;
            
            // Clear form
            $_POST = array();
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
        $stmt->close();
    }
}

// Function to generate slug
function generate_slug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    
    return empty($text) ? 'n-a' : $text;
}

function resizeImage($source, $destination, $max_width, $max_height) {

    if (!extension_loaded('gd')) {
        return false;
    }

    $info = getimagesize($source);
    if (!$info) return false;

    list($width, $height, $type) = $info;

    if ($width <= $max_width && $height <= $max_height) {
        return false;
    }

    $ratio = min($max_width / $width, $max_height / $height);
    $new_width  = (int) ($width * $ratio);
    $new_height = (int) ($height * $ratio);

    $new_image = imagecreatetruecolor($new_width, $new_height);

    switch ($type) {
        case IMAGETYPE_JPEG:
            $original_image = imagecreatefromjpeg($source);
            break;

        case IMAGETYPE_PNG:
            $original_image = imagecreatefrompng($source);
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            break;

        case IMAGETYPE_GIF:
            $original_image = imagecreatefromgif($source);
            break;

        case IMAGETYPE_WEBP:
            $original_image = imagecreatefromwebp($source);
            break;

        default:
            return false;
    }

    imagecopyresampled(
        $new_image, $original_image,
        0, 0, 0, 0,
        $new_width, $new_height,
        $width, $height
    );

    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($new_image, $destination, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($new_image, $destination, 8);
            break;
        case IMAGETYPE_GIF:
            imagegif($new_image, $destination);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($new_image, $destination, 85);
            break;
    }

    imagedestroy($original_image);
    imagedestroy($new_image);

    return true;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Add Category | Admin <?php echo htmlspecialchars($setting->get('site_name')); ?></title>
    <link rel="icon" href="<?php echo htmlspecialchars($setting->get('favicon', 'assets/img/logo.png')); ?>" type="image/png">

    <?php include "links.php"; ?>
    
    <style>
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9fa;
        }
        
        .upload-area:hover {
            border-color: #4361ee;
            background: rgba(67, 97, 238, 0.05);
        }
        
        .image-preview {
            max-width: 100%;
            max-height: 200px;
            border-radius: 6px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin: 15px auto;
            display: none;
        }
        
        .icon-preview {
            font-size: 2rem;
            margin: 10px 0;
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
        
        .character-count {
            font-size: 0.8rem;
            color: #6c757d;
            text-align: right;
            margin-top: 0.25rem;
        }
        
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .checkbox-group .form-check {
            margin-bottom: 0;
        }
        
        .slug-preview {
            background: #e9ecef;
            padding: 8px 12px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9rem;
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
                                        <h3 class="m-0"><i class="fas fa-layer-group mr-2"></i> Add New Category</h3>
                                        <p class="mb-0 text-muted">Create new product categories for your e-commerce store</p>
                                    </div>
                                    <div class="action-btn">
                                        <a href="view-categories.php" class="btn btn-secondary">
                                            <i class="fas fa-list mr-1"></i> View Categories
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

                                <form id="categoryForm" action="" method="post" enctype="multipart/form-data">
                                    <div class="row">
                                        <!-- Left Column: Basic Info -->
                                        <div class="col-lg-6">
                                            <div class="form-section mb-4">
                                                <h5 class="mb-3"><i class="fas fa-info-circle mr-2"></i> Basic Information</h5>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Category Name <span class="required-star">*</span></label>
                                                    <input type="text" class="form-control" name="cate_name" 
                                                           value="<?php echo htmlspecialchars($_POST['cate_name'] ?? ''); ?>" 
                                                           required maxlength="255" placeholder="e.g., Electronics, Clothing">
                                                    <div class="character-count" id="nameCount">0/255 characters</div>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Parent Category</label>
                                                    <select class="form-control" name="parent_id">
                                                        <option value="0">-- Main Category (No Parent) --</option>
                                                        <?php foreach ($parent_categories as $parent): ?>
                                                        <option value="<?php echo $parent['id']; ?>"
                                                            <?php echo (isset($_POST['parent_id']) && $_POST['parent_id'] == $parent['id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($parent['categories']); ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <small class="form-text text-muted">Select parent category if this is a sub-category</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Description</label>
                                                    <textarea class="form-control" name="description" rows="3"
                                                        placeholder="Describe this category..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                                    <small class="form-text text-muted">Optional description for internal reference</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Display Order</label>
                                                    <input type="number" class="form-control" name="display_order" 
                                                           value="<?php echo htmlspecialchars($_POST['display_order'] ?? 0); ?>"
                                                           min="0" max="999">
                                                    <small class="form-text text-muted">Lower numbers appear first in listings</small>
                                                </div>
                                            </div>
                                            
                                            <div class="form-section">
                                                <h5 class="mb-3"><i class="fas fa-image mr-2"></i> Category Image & Icon</h5>
                                                
                                                <div class="form-group mb-4">
                                                    <label class="form-label">Category Image</label>
                                                    <div class="upload-area" onclick="document.getElementById('imageUpload').click()">
                                                        <div class="mb-2">
                                                            <i class="fas fa-cloud-upload-alt fa-2x text-primary"></i>
                                                        </div>
                                                        <h6 class="mb-1">Click to upload image</h6>
                                                        <p class="text-muted small mb-0">or drag and drop</p>
                                                        <p class="text-muted small mt-2">PNG, JPG, GIF, WEBP up to 5MB</p>
                                                    </div>
                                                    <input type="file" name="imageUpload" id="imageUpload" 
                                                           accept="image/*" class="d-none" onchange="previewImage(this)">
                                                    <img id="imagePreview" class="image-preview" alt="Preview">
                                                    <small class="form-text text-muted">Recommended: 800x800px square image</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Icon Class (Optional)</label>
                                                    <input type="text" class="form-control" name="icon_class" 
                                                           value="<?php echo htmlspecialchars($_POST['icon_class'] ?? ''); ?>"
                                                           placeholder="e.g., fas fa-mobile-alt, fas fa-tshirt">
                                                    <div id="iconPreview" class="icon-preview">
                                                        <?php if (!empty($_POST['icon_class'])): ?>
                                                        <i class="<?php echo htmlspecialchars($_POST['icon_class']); ?>"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <small class="form-text text-muted">
                                                        Use Font Awesome icon classes. Preview: <i class="fas fa-mobile-alt"></i> 
                                                        <a href="https://fontawesome.com/icons" target="_blank">Browse icons</a>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Right Column: SEO & Settings -->
                                        <div class="col-lg-6">
                                            <div class="form-section mb-4">
                                                <h5 class="mb-3"><i class="fas fa-search mr-2"></i> SEO Settings</h5>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Meta Title</label>
                                                    <input type="text" class="form-control" name="meta_title" 
                                                           value="<?php echo htmlspecialchars($_POST['meta_title'] ?? ''); ?>"
                                                           maxlength="255" placeholder="Title for search engines">
                                                    <div class="character-count" id="metaTitleCount">0/255 characters</div>
                                                    <small class="form-text text-muted">Optimal: 50-60 characters</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Meta Description</label>
                                                    <textarea class="form-control" name="meta_desc" rows="2"
                                                        maxlength="320" placeholder="Description for search engines"><?php echo htmlspecialchars($_POST['meta_desc'] ?? ''); ?></textarea>
                                                    <div class="character-count" id="metaDescCount">0/320 characters</div>
                                                    <small class="form-text text-muted">Optimal: 150-160 characters</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Meta Keywords</label>
                                                    <input type="text" class="form-control" name="meta_key" 
                                                           value="<?php echo htmlspecialchars($_POST['meta_key'] ?? ''); ?>"
                                                           placeholder="keywords, separated by commas" maxlength="255">
                                                    <div class="character-count" id="metaKeyCount">0/255 characters</div>
                                                    <small class="form-text text-muted">Separate with commas (optional)</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Slug URL</label>
                                                    <div class="slug-preview" id="slugPreview">
                                                        <?php 
                                                        if (isset($_POST['cate_name']) && !empty($_POST['cate_name'])) {
                                                            echo generate_slug($_POST['cate_name']);
                                                        } else {
                                                            echo 'slug-will-appear-here';
                                                        }
                                                        ?>
                                                    </div>
                                                    <small class="form-text text-muted">Auto-generated from category name. Used in URLs.</small>
                                                </div>
                                            </div>
                                            
                                            <div class="form-section mb-4">
                                                <h5 class="mb-3"><i class="fas fa-cog mr-2"></i> Display Settings</h5>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Status <span class="required-star">*</span></label>
                                                    <select class="form-control" name="status" required>
                                                        <option value="1" <?php echo isset($_POST['status']) && $_POST['status'] == '1' ? 'selected' : 'selected'; ?>>Active</option>
                                                        <option value="0" <?php echo isset($_POST['status']) && $_POST['status'] == '0' ? 'selected' : ''; ?>>Inactive</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="checkbox-group mt-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="featured" 
                                                               id="featured" value="1"
                                                               <?php echo isset($_POST['featured']) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="featured">
                                                            Featured Category
                                                        </label>
                                                        <small class="form-text text-muted d-block">Show in featured sections</small>
                                                    </div>
                                                    
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="show_in_nav" 
                                                               id="show_in_nav" value="1"
                                                               <?php echo !isset($_POST['submit']) || isset($_POST['show_in_nav']) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="show_in_nav">
                                                            Show in Navigation
                                                        </label>
                                                        <small class="form-text text-muted d-block">Display in website menu</small>
                                                    </div>
                                                    
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="show_in_home" 
                                                               id="show_in_home" value="1"
                                                               <?php echo isset($_POST['show_in_home']) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="show_in_home">
                                                            Show on Homepage
                                                        </label>
                                                        <small class="form-text text-muted d-block">Display on homepage sections</small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Preview Section -->
                                            <div class="card bg-light">
                                                <div class="card-body">
                                                    <h6><i class="fas fa-eye mr-2"></i> Quick Preview</h6>
                                                    <div class="small">
                                                        <div><strong>Category:</strong> <span id="previewName"><?php echo htmlspecialchars($_POST['cate_name'] ?? 'Category Name'); ?></span></div>
                                                        <div><strong>Slug:</strong> <span id="previewSlug"><?php echo isset($_POST['cate_name']) ? generate_slug($_POST['cate_name']) : 'category-name'; ?></span></div>
                                                        <div><strong>Parent:</strong> <span id="previewParent">
                                                            <?php 
                                                            if (isset($_POST['parent_id']) && $_POST['parent_id'] > 0) {
                                                                foreach ($parent_categories as $parent) {
                                                                    if ($parent['id'] == $_POST['parent_id']) {
                                                                        echo htmlspecialchars($parent['categories']);
                                                                        break;
                                                                    }
                                                                }
                                                            } else {
                                                                echo 'Main Category';
                                                            }
                                                            ?>
                                                        </span></div>
                                                        <div class="mt-2">
                                                            <strong>Status:</strong> 
                                                            <span class="badge badge-<?php echo (isset($_POST['status']) && $_POST['status'] == 0) ? 'danger' : 'success'; ?>">
                                                                <?php echo (isset($_POST['status']) && $_POST['status'] == 0) ? 'Inactive' : 'Active'; ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="mt-4 pt-3 border-top">
                                        <div class="d-flex justify-content-between">
                                            <a href="view-categories.php" class="btn btn-secondary">
                                                <i class="fas fa-arrow-left mr-2"></i> Back to List
                                            </a>
                                            <div>
                                                <button type="reset" class="btn btn-outline-secondary mr-2">
                                                    <i class="fas fa-redo mr-1"></i> Reset
                                                </button>
                                                <button type="submit" name="add-categories" class="btn btn-primary">
                                                    <i class="fas fa-plus-circle mr-2"></i> Add Category
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
        // Image preview function
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const file = input.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    
                    // Show file info
                    const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                    const infoText = file.name + ' (' + sizeMB + ' MB)';
                    preview.alt = infoText;
                };
                
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
                preview.src = '';
            }
        }
        
        // Character counters
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = {
                'cate_name': 'nameCount',
                'meta_title': 'metaTitleCount',
                'meta_desc': 'metaDescCount',
                'meta_key': 'metaKeyCount'
            };
            
            // Initialize counters
            Object.keys(inputs).forEach(inputId => {
                const input = document.querySelector(`[name="${inputId}"]`);
                const counter = document.getElementById(inputs[inputId]);
                
                if (input && counter) {
                    counter.textContent = input.value.length + '/' + input.maxLength;
                    
                    input.addEventListener('input', function() {
                        counter.textContent = this.value.length + '/' + this.maxLength;
                        updatePreview();
                    });
                }
            });
            
            // Icon preview
            const iconInput = document.querySelector('input[name="icon_class"]');
            const iconPreview = document.getElementById('iconPreview');
            
            iconInput.addEventListener('input', function() {
                if (this.value) {
                    iconPreview.innerHTML = `<i class="${this.value}"></i>`;
                } else {
                    iconPreview.innerHTML = '';
                }
            });
            
            // Update slug preview
            const nameInput = document.querySelector('input[name="cate_name"]');
            const slugPreview = document.getElementById('slugPreview');
            
            nameInput.addEventListener('input', function() {
                const slug = this.value.toLowerCase()
                    .replace(/[^\w\s]/gi, '')
                    .replace(/\s+/g, '-')
                    .replace(/--+/g, '-')
                    .trim('-');
                
                slugPreview.textContent = slug || 'slug-will-appear-here';
                updatePreview();
            });
            
            // Update parent preview
            const parentSelect = document.querySelector('select[name="parent_id"]');
            parentSelect.addEventListener('change', updatePreview);
            
            // Update status preview
            const statusSelect = document.querySelector('select[name="status"]');
            statusSelect.addEventListener('change', updatePreview);
            
            // Update preview function
            function updatePreview() {
                // Update name preview
                const previewName = document.getElementById('previewName');
                previewName.textContent = nameInput.value || 'Category Name';
                
                // Update slug preview
                const previewSlug = document.getElementById('previewSlug');
                previewSlug.textContent = slugPreview.textContent;
                
                // Update parent preview
                const previewParent = document.getElementById('previewParent');
                const selectedOption = parentSelect.options[parentSelect.selectedIndex];
                previewParent.textContent = selectedOption.text;
                
                // Update status preview
                const statusBadge = document.querySelector('#previewSlug').parentNode.parentNode.querySelector('.badge');
                if (statusSelect.value == '0') {
                    statusBadge.className = 'badge badge-danger';
                    statusBadge.textContent = 'Inactive';
                } else {
                    statusBadge.className = 'badge badge-success';
                    statusBadge.textContent = 'Active';
                }
            }
            
            // Drag and drop for image upload
            const uploadArea = document.querySelector('.upload-area');
            const fileInput = document.getElementById('imageUpload');
            
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                uploadArea.addEventListener(eventName, () => {
                    uploadArea.style.borderColor = '#4361ee';
                    uploadArea.style.background = 'rgba(67, 97, 238, 0.1)';
                });
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, () => {
                    uploadArea.style.borderColor = '#dee2e6';
                    uploadArea.style.background = '#f8f9fa';
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
            
            // Form validation
            const form = document.getElementById('categoryForm');
            form.addEventListener('submit', function(e) {
                const categoryName = document.querySelector('input[name="cate_name"]').value.trim();
                
                if (!categoryName) {
                    e.preventDefault();
                    alert('Please enter a category name.');
                    return false;
                }
                
                if (categoryName.length > 255) {
                    e.preventDefault();
                    alert('Category name is too long (max 255 characters).');
                    return false;
                }
                
                return true;
            });
            
            // Auto-fill SEO fields
            nameInput.addEventListener('blur', function() {
                const name = this.value.trim();
                const metaTitle = document.querySelector('input[name="meta_title"]');
                const metaDesc = document.querySelector('textarea[name="meta_desc"]');
                const metaKey = document.querySelector('input[name="meta_key"]');
                
                // Auto-fill meta title if empty
                if (name && !metaTitle.value) {
                    metaTitle.value = name + ' - Best Collection | ' + '<?php echo htmlspecialchars($setting->get("site_name", "Our Store")); ?>';
                }
                
                // Auto-fill meta description if empty
                if (name && !metaDesc.value) {
                    metaDesc.value = 'Shop the best ' + name + ' collection online. Find high-quality ' + name.toLowerCase() + ' products at great prices. Fast shipping, easy returns.';
                }
                
                // Auto-fill meta keywords if empty
                if (name && !metaKey.value) {
                    const keywords = [
                        name.toLowerCase(),
                        'buy ' + name.toLowerCase(),
                        name.toLowerCase() + ' online',
                        'best ' + name.toLowerCase(),
                        name.toLowerCase() + ' shop',
                        'affordable ' + name.toLowerCase()
                    ];
                    metaKey.value = keywords.join(', ');
                }
            });
        });
    </script>
</body>
</html>