<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';
require_once __DIR__ . '/models/setting.php';

// Initialize Settings
$setting = new Setting($conn);

// Fetch parent categories for dropdown
$parent_categories = [];
$parent_query = "SELECT id, categories, parent_id FROM categories WHERE parent_id = 0 AND status = 1 AND id != ? ORDER BY display_order, categories";
$parent_stmt = $conn->prepare($parent_query);

// Get category ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view-categories.php");
    exit();
}

$cat_id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch category details from the database
$sql = "SELECT * FROM categories WHERE cate_id = ? OR id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $cat_id, $cat_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Category not found";
    header("Location: view-categories.php");
    exit();
}

$category = $result->fetch_assoc();
$stmt->close();

// Bind parent_id for parent query
$parent_stmt->bind_param("i", $category['id']);
$parent_stmt->execute();
$parent_result = $parent_stmt->get_result();
while ($row = $parent_result->fetch_assoc()) {
    $parent_categories[] = $row;
}
$parent_stmt->close();

// Process form submission for updating the category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_category'])) {
    // Retrieve updated values from POST
    $categories = trim($_POST['categories'] ?? '');
    $parent_id = (int)($_POST['parent_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_desc = trim($_POST['meta_desc'] ?? '');
    $meta_key = trim($_POST['meta_key'] ?? '');
    $slug_url = trim($_POST['slug_url'] ?? '');
    $status = (int)($_POST['status'] ?? 1);
    $display_order = (int)($_POST['display_order'] ?? 0);
    $featured = isset($_POST['featured']) ? 1 : 0;
    $show_in_nav = isset($_POST['show_in_nav']) ? 1 : 0;
    $show_in_home = isset($_POST['show_in_home']) ? 1 : 0;
    $icon_class = trim($_POST['icon_class'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($categories)) {
        $errors[] = "Category name is required";
    }
    
    if (strlen($categories) > 255) {
        $errors[] = "Category name must be less than 255 characters";
    }
    
    // Check if category already exists (excluding current category)
    $check_sql = "SELECT id FROM categories WHERE categories = ? AND parent_id = ? AND id != ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("sii", $categories, $parent_id, $category['id']);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        $errors[] = "Category with this name already exists under selected parent";
    }
    $check_stmt->close();
    
    // Ensure slug is unique
    $slug_counter = 1;
    $original_slug = $slug_url;
    while (true) {
        $check_slug = "SELECT id FROM categories WHERE slug_url = ? AND id != ?";
        $stmt = $conn->prepare($check_slug);
        $stmt->bind_param("si", $slug_url, $category['id']);
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
    
    // Handle image upload
    $image_name = $category['image']; // Keep existing image by default
    
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
        
        if (empty($errors)) {
            if (move_uploaded_file($_FILES["imageUpload"]["tmp_name"], $target_file)) {
                // Delete old image if exists and is different
                if (!empty($category['image']) && $category['image'] != $unique_filename && file_exists($target_dir . $category['image'])) {
                    unlink($target_dir . $category['image']);
                }
                $image_name = $unique_filename;
            } else {
                $errors[] = "Error uploading image file";
            }
        }
    }
    
    // Update if no errors
    if (empty($errors)) {
        $update_sql = "UPDATE categories SET 
            categories = ?,
            parent_id = ?,
            description = ?,
            meta_title = ?,
            meta_desc = ?,
            meta_key = ?,
            slug_url = ?,
            status = ?,
            display_order = ?,
            featured = ?,
            show_in_nav = ?,
            show_in_home = ?,
            image = ?,
            icon_class = ?,
            updated_on = NOW()
            WHERE id = ?";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sisssssiiiiissi", 
            $categories,
            $parent_id,
            $description,
            $meta_title,
            $meta_desc,
            $meta_key,
            $slug_url,
            $status,
            $display_order,
            $featured,
            $show_in_nav,
            $show_in_home,
            $image_name,
            $icon_class,
            $category['id']
        );
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Category updated successfully!";
            header("Location: view-categories.php");
            exit();
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Edit Category | Admin <?php echo htmlspecialchars($setting->get('site_name')); ?></title>
    <link rel="icon" href="<?php echo htmlspecialchars($setting->get('favicon', 'assets/img/logo.png')); ?>" type="image/png">

    <?php include "links.php"; ?>
    
    <style>
        .badge{
            color: black;
            padding: 5px 5px;
            background-color: burlywood;
            border-radius: 4px;
        }
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
            display: block;
        }
        
        .current-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e9ecef;
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
        
        .info-card {
            background: #f8f9fa;
            border-left: 4px solid #6c757d;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
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
                                        <h3 class="m-0"><i class="fas fa-edit mr-2"></i> Edit Category</h3>
                                        <p class="mb-0 text-muted">Editing: <?php echo htmlspecialchars($category['categories']); ?> (ID: <?php echo $category['cate_id']; ?>)</p>
                                    </div>
                                    <div class="action-btn">
                                        <a href="view-categories.php" class="btn btn-secondary">
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
                                
                                <!-- Category Info -->
                                <div class="info-card">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>Category ID:</strong> <?php echo $category['cate_id']; ?>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Created:</strong> <?php echo date('d M Y, h:i A', strtotime($category['added_on'])); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Last Updated:</strong> 
                                            <?php echo $category['updated_on'] ? date('d M Y, h:i A', strtotime($category['updated_on'])) : 'Never'; ?>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Current Status:</strong> 
                                            <span class="badge badge-<?php echo $category['status'] ? 'success' : 'danger'; ?>">
                                                <?php echo $category['status'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $cat_id); ?>" method="post" enctype="multipart/form-data" id="editCategoryForm">
                                    <div class="row">
                                        <!-- Left Column: Basic Info -->
                                        <div class="col-lg-6">
                                            <div class="form-section mb-4">
                                                <h5 class="mb-3"><i class="fas fa-info-circle mr-2"></i> Basic Information</h5>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Category Name <span class="required-star">*</span></label>
                                                    <input type="text" class="form-control" name="categories" 
                                                           value="<?php echo htmlspecialchars($category['categories']); ?>" 
                                                           required maxlength="255" placeholder="e.g., Electronics, Clothing">
                                                    <div class="character-count" id="nameCount"><?php echo strlen($category['categories']); ?>/255 characters</div>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Parent Category</label>
                                                    <select class="form-control" name="parent_id">
                                                        <option value="0">-- Main Category (No Parent) --</option>
                                                        <?php foreach ($parent_categories as $parent): ?>
                                                        <option value="<?php echo $parent['id']; ?>"
                                                            <?php echo $category['parent_id'] == $parent['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($parent['categories']); ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <small class="form-text text-muted">Select parent category if this is a sub-category</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Description</label>
                                                    <textarea class="form-control" name="description" rows="3"
                                                        placeholder="Describe this category..."><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                                                    <small class="form-text text-muted">Optional description for internal reference</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Display Order</label>
                                                    <input type="number" class="form-control" name="display_order" 
                                                           value="<?php echo htmlspecialchars($category['display_order']); ?>"
                                                           min="0" max="999">
                                                    <small class="form-text text-muted">Lower numbers appear first in listings</small>
                                                </div>
                                            </div>
                                            
                                            <div class="form-section">
                                                <h5 class="mb-3"><i class="fas fa-image mr-2"></i> Category Image & Icon</h5>
                                                
                                                <!-- Current Image -->
                                                <?php if (!empty($category['image'])): ?>
                                                <div class="form-group mb-3">
                                                    <label class="form-label">Current Image</label>
                                                    <div class="d-flex align-items-center">
                                                        <img src="uploads/category/<?php echo htmlspecialchars($category['image']); ?>" 
                                                             alt="Current Category Image"
                                                             class="current-image mr-3">
                                                        <div>
                                                            <a href="uploads/category/<?php echo htmlspecialchars($category['image']); ?>" 
                                                               target="_blank" class="btn btn-sm btn-outline-info">
                                                                <i class="fas fa-eye"></i> View Full Image
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <!-- New Image Upload -->
                                                <div class="form-group mb-4">
                                                    <label class="form-label">Update Image (Optional)</label>
                                                    <div class="upload-area" onclick="document.getElementById('imageUpload').click()">
                                                        <div class="mb-2">
                                                            <i class="fas fa-cloud-upload-alt fa-2x text-primary"></i>
                                                        </div>
                                                        <h6 class="mb-1">Click to upload new image</h6>
                                                        <p class="text-muted small mb-0">or drag and drop</p>
                                                        <p class="text-muted small mt-2">PNG, JPG, GIF, WEBP up to 5MB</p>
                                                    </div>
                                                    <input type="file" name="imageUpload" id="imageUpload" 
                                                           accept="image/*" class="d-none" onchange="previewImage(this)">
                                                    <img id="imagePreview" class="image-preview" alt="Preview" style="display: none;">
                                                    <small class="form-text text-muted">Leave empty to keep current image</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Icon Class (Optional)</label>
                                                    <input type="text" class="form-control" name="icon_class" 
                                                           value="<?php echo htmlspecialchars($category['icon_class'] ?? ''); ?>"
                                                           placeholder="e.g., fas fa-mobile-alt, fas fa-tshirt">
                                                    <div id="iconPreview" class="icon-preview">
                                                        <?php if (!empty($category['icon_class'])): ?>
                                                        <i class="<?php echo htmlspecialchars($category['icon_class']); ?>"></i>
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
                                                           value="<?php echo htmlspecialchars($category['meta_title'] ?? ''); ?>"
                                                           maxlength="255" placeholder="Title for search engines">
                                                    <div class="character-count" id="metaTitleCount"><?php echo strlen($category['meta_title'] ?? ''); ?>/255 characters</div>
                                                    <small class="form-text text-muted">Optimal: 50-60 characters</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Meta Description</label>
                                                    <textarea class="form-control" name="meta_desc" rows="2"
                                                        maxlength="320" placeholder="Description for search engines"><?php echo htmlspecialchars($category['meta_desc'] ?? ''); ?></textarea>
                                                    <div class="character-count" id="metaDescCount"><?php echo strlen($category['meta_desc'] ?? ''); ?>/320 characters</div>
                                                    <small class="form-text text-muted">Optimal: 150-160 characters</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Meta Keywords</label>
                                                    <input type="text" class="form-control" name="meta_key" 
                                                           value="<?php echo htmlspecialchars($category['meta_key'] ?? ''); ?>"
                                                           placeholder="keywords, separated by commas" maxlength="255">
                                                    <div class="character-count" id="metaKeyCount"><?php echo strlen($category['meta_key'] ?? ''); ?>/255 characters</div>
                                                    <small class="form-text text-muted">Separate with commas (optional)</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Slug URL <span class="required-star">*</span></label>
                                                    <input type="text" class="form-control" name="slug_url" 
                                                           value="<?php echo htmlspecialchars($category['slug_url']); ?>" 
                                                           required placeholder="category-url">
                                                    <small class="form-text text-muted">Used in URLs. Must be unique.</small>
                                                </div>
                                            </div>
                                            
                                            <div class="form-section mb-4">
                                                <h5 class="mb-3"><i class="fas fa-cog mr-2"></i> Display Settings</h5>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Status <span class="required-star">*</span></label>
                                                    <select class="form-control" name="status" required>
                                                        <option value="1" <?php echo $category['status'] ? 'selected' : ''; ?>>Active</option>
                                                        <option value="0" <?php echo !$category['status'] ? 'selected' : ''; ?>>Inactive</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="checkbox-group mt-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="featured" 
                                                               id="featured" value="1"
                                                               <?php echo $category['featured'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="featured">
                                                            Featured Category
                                                        </label>
                                                        <small class="form-text text-muted d-block">Show in featured sections</small>
                                                    </div>
                                                    
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="show_in_nav" 
                                                               id="show_in_nav" value="1"
                                                               <?php echo $category['show_in_nav'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="show_in_nav">
                                                            Show in Navigation
                                                        </label>
                                                        <small class="form-text text-muted d-block">Display in website menu</small>
                                                    </div>
                                                    
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="show_in_home" 
                                                               id="show_in_home" value="1"
                                                               <?php echo $category['show_in_home'] ? 'checked' : ''; ?>>
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
                                                    <h6><i class="fas fa-eye mr-2"></i> Preview</h6>
                                                    <div class="small">
                                                        <div><strong>Category:</strong> <span id="previewName"><?php echo htmlspecialchars($category['categories']); ?></span></div>
                                                        <div><strong>Slug:</strong> <span id="previewSlug"><?php echo htmlspecialchars($category['slug_url']); ?></span></div>
                                                        <div><strong>Status:</strong> 
                                                            <span id="previewStatus" class="badge badge-<?php echo $category['status'] ? 'success' : 'danger'; ?>">
                                                                <?php echo $category['status'] ? 'Active' : 'Inactive'; ?>
                                                            </span>
                                                        </div>
                                                        <?php if ($category['featured']): ?>
                                                        <div><strong>Featured:</strong> <span class="badge badge-warning">Yes</span></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="mt-4 pt-3 border-top">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <a href="view-categories.php" class="btn btn-secondary mr-2">
                                                    <i class="fas fa-times mr-1"></i> Cancel
                                                </a>
                                                <button type="button" class="btn btn-outline-danger" onclick="resetForm()">
                                                    <i class="fas fa-redo mr-1"></i> Reset Changes
                                                </button>
                                            </div>
                                            <div>
                                                <button type="submit" name="update_category" class="btn btn-primary">
                                                    <i class="fas fa-save mr-2"></i> Update Category
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
                'categories': 'nameCount',
                'meta_title': 'metaTitleCount',
                'meta_desc': 'metaDescCount',
                'meta_key': 'metaKeyCount'
            };
            
            // Initialize counters
            Object.keys(inputs).forEach(inputId => {
                const input = document.querySelector(`[name="${inputId}"]`);
                const counter = document.getElementById(inputs[inputId]);
                
                if (input && counter) {
                    // Initial count
                    counter.textContent = input.value.length + '/' + (input.maxLength || 255);
                    
                    input.addEventListener('input', function() {
                        const max = this.maxLength || 255;
                        counter.textContent = this.value.length + '/' + max;
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
            
            // Auto-generate slug from category name
            const nameInput = document.querySelector('input[name="categories"]');
            const slugInput = document.querySelector('input[name="slug_url"]');
            
            nameInput.addEventListener('input', function() {
                const slug = this.value.toLowerCase()
                    .replace(/[^\w\s]/gi, '')
                    .replace(/\s+/g, '-')
                    .replace(/--+/g, '-')
                    .trim('-');
                
                // Only auto-update if slug is empty or matches old category name
                if (!slugInput.value || slugInput.value === '<?php echo $category['slug_url']; ?>') {
                    slugInput.value = slug;
                    updatePreview();
                }
            });
            
            // Update status preview
            const statusSelect = document.querySelector('select[name="status"]');
            statusSelect.addEventListener('change', updatePreview);
            
            // Update preview function
            function updatePreview() {
                // Update name preview
                const previewName = document.getElementById('previewName');
                previewName.textContent = nameInput.value || '<?php echo addslashes($category['categories']); ?>';
                
                // Update slug preview
                const previewSlug = document.getElementById('previewSlug');
                previewSlug.textContent = slugInput.value || '<?php echo addslashes($category['slug_url']); ?>';
                
                // Update status preview
                const previewStatus = document.getElementById('previewStatus');
                if (statusSelect.value == '0') {
                    previewStatus.className = 'badge badge-danger';
                    previewStatus.textContent = 'Inactive';
                } else {
                    previewStatus.className = 'badge badge-success';
                    previewStatus.textContent = 'Active';
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
            const form = document.getElementById('editCategoryForm');
            form.addEventListener('submit', function(e) {
                const categoryName = document.querySelector('input[name="categories"]').value.trim();
                
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
                
                const slug = document.querySelector('input[name="slug_url"]').value.trim();
                if (!slug) {
                    e.preventDefault();
                    alert('Please enter a slug URL.');
                    return false;
                }
                
                return true;
            });
            
            // Reset form to original values
            window.resetForm = function() {
                if (confirm('Reset all changes and restore original values?')) {
                    // Get original values from PHP
                    const originalValues = {
                        categories: '<?php echo addslashes($category['categories']); ?>',
                        description: '<?php echo addslashes($category['description'] ?? ''); ?>',
                        meta_title: '<?php echo addslashes($category['meta_title'] ?? ''); ?>',
                        meta_desc: '<?php echo addslashes($category['meta_desc'] ?? ''); ?>',
                        meta_key: '<?php echo addslashes($category['meta_key'] ?? ''); ?>',
                        slug_url: '<?php echo addslashes($category['slug_url']); ?>',
                        parent_id: '<?php echo $category['parent_id']; ?>',
                        display_order: '<?php echo $category['display_order']; ?>',
                        icon_class: '<?php echo addslashes($category['icon_class'] ?? ''); ?>',
                        status: '<?php echo $category['status']; ?>',
                        featured: '<?php echo $category['featured']; ?>',
                        show_in_nav: '<?php echo $category['show_in_nav']; ?>',
                        show_in_home: '<?php echo $category['show_in_home']; ?>'
                    };
                    
                    // Restore form values
                    document.querySelector('input[name="categories"]').value = originalValues.categories;
                    document.querySelector('textarea[name="description"]').value = originalValues.description;
                    document.querySelector('input[name="meta_title"]').value = originalValues.meta_title;
                    document.querySelector('textarea[name="meta_desc"]').value = originalValues.meta_desc;
                    document.querySelector('input[name="meta_key"]').value = originalValues.meta_key;
                    document.querySelector('input[name="slug_url"]').value = originalValues.slug_url;
                    document.querySelector('select[name="parent_id"]').value = originalValues.parent_id;
                    document.querySelector('input[name="display_order"]').value = originalValues.display_order;
                    document.querySelector('input[name="icon_class"]').value = originalValues.icon_class;
                    document.querySelector('select[name="status"]').value = originalValues.status;
                    
                    // Restore checkboxes
                    document.getElementById('featured').checked = originalValues.featured == '1';
                    document.getElementById('show_in_nav').checked = originalValues.show_in_nav == '1';
                    document.getElementById('show_in_home').checked = originalValues.show_in_home == '1';
                    
                    // Clear file input
                    fileInput.value = '';
                    document.getElementById('imagePreview').style.display = 'none';
                    
                    // Update icon preview
                    iconPreview.innerHTML = originalValues.icon_class ? `<i class="${originalValues.icon_class}"></i>` : '';
                    
                    // Update character counters
                    document.querySelectorAll('.character-count').forEach((counter, index) => {
                        const inputId = Object.keys(inputs)[index];
                        const input = document.querySelector(`[name="${inputId}"]`);
                        if (input) {
                            const max = input.maxLength || 255;
                            counter.textContent = input.value.length + '/' + max;
                        }
                    });
                    
                    updatePreview();
                    alert('Form reset to original values');
                }
            };
        });
    </script>
</body>
</html>