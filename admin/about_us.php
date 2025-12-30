<?php
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';
require_once __DIR__ . '/models/Setting.php';

// Initialize
$setting = new Setting($conn);

// Initialize variables
$title = '';
$desc = '';
$image = '';
$meta_title = '';
$meta_description = '';
$meta_keywords = '';
$cta_button_text = '';
$cta_button_link = '';
$video_url = '';
$show_stats = '0';
$stats = [
    'years' => '',
    'customers' => '',
    'products' => '',
    'countries' => ''
];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $meta_title = trim($_POST['meta_title'] ?? '');
        $meta_description = trim($_POST['meta_description'] ?? '');
        $meta_keywords = trim($_POST['meta_keywords'] ?? '');
        $cta_button_text = trim($_POST['cta_button_text'] ?? '');
        $cta_button_link = trim($_POST['cta_button_link'] ?? '');
        $video_url = trim($_POST['video_url'] ?? '');
        $show_stats = isset($_POST['show_stats']) ? '1' : '0';
        $remove_image = isset($_POST['remove_image']) && $_POST['remove_image'] == '1';
        
        // Collect statistics
        $stats['years'] = intval($_POST['years'] ?? 0);
        $stats['customers'] = intval($_POST['customers'] ?? 0);
        $stats['products'] = intval($_POST['products'] ?? 0);
        $stats['countries'] = intval($_POST['countries'] ?? 0);
        $stats_json = json_encode($stats);
        
        // Validate required fields
        if (empty($title) || empty($content)) {
            throw new Exception("Title and content are required");
        }

        // Handle file upload
        $image_path = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/about_us/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
            $file_type = mime_content_type($_FILES['image']['tmp_name']);
            
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception("Only JPG, PNG, GIF, WEBP and SVG images are allowed");
            }
            
            // Check file size (max 5MB)
            $max_size = 5 * 1024 * 1024;
            if ($_FILES['image']['size'] > $max_size) {
                throw new Exception("Image size should not exceed 5MB");
            }
            
            // Generate unique filename
            $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $file_name = 'about-us-' . uniqid() . '.' . $file_ext;
            $target_path = $upload_dir . $file_name;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                throw new Exception("Failed to upload image");
            }
            
            $image_path = $target_path;
        }

        // Check if an entry already exists
        $sql = "SELECT * FROM about_us LIMIT 1";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {
            // Update existing entry
            $row = mysqli_fetch_assoc($result);
            $current_image = $row['image_url'];
            
            // Handle image removal
            if ($remove_image && !empty($current_image) && file_exists($current_image)) {
                unlink($current_image);
                $image_path = '';
            } elseif (!empty($image_path)) {
                // Delete old image if new one uploaded
                if (!empty($current_image) && file_exists($current_image)) {
                    unlink($current_image);
                }
            }
            
            // Update with or without image
            if (!empty($image_path) || $remove_image) {
                $stmt = $conn->prepare("UPDATE about_us SET title = ?, content = ?, image_url = ?, meta_title = ?, meta_description = ?, meta_keywords = ?, cta_button_text = ?, cta_button_link = ?, video_url = ?, show_stats = ?, stats_data = ?, updated_at = NOW() WHERE id = ?");
                $image_for_db = $remove_image ? '' : $image_path;
                $stmt->bind_param('sssssssssssi', $title, $content, $image_for_db, $meta_title, $meta_description, $meta_keywords, $cta_button_text, $cta_button_link, $video_url, $show_stats, $stats_json, $row['id']);
            } else {
                $stmt = $conn->prepare("UPDATE about_us SET title = ?, content = ?, meta_title = ?, meta_description = ?, meta_keywords = ?, cta_button_text = ?, cta_button_link = ?, video_url = ?, show_stats = ?, stats_data = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param('ssssssssssi', $title, $content, $meta_title, $meta_description, $meta_keywords, $cta_button_text, $cta_button_link, $video_url, $show_stats, $stats_json, $row['id']);
            }
        } else {
            // Insert new entry
            $stmt = $conn->prepare("INSERT INTO about_us (title, content, image_url, meta_title, meta_description, meta_keywords, cta_button_text, cta_button_link, video_url, show_stats, stats_data, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->bind_param('sssssssssss', $title, $content, $image_path, $meta_title, $meta_description, $meta_keywords, $cta_button_text, $cta_button_link, $video_url, $show_stats, $stats_json);
        }
        
        if ($stmt->execute()) {
            $success_message = "About Us content saved successfully!";
        } else {
            throw new Exception("Database error: " . $conn->error);
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Fetch the latest 'About Us' data
$sql = "SELECT * FROM about_us ORDER BY id DESC LIMIT 1";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $title = $row['title'] ?? '';
    $desc = $row['content'] ?? '';
    $image = $row['image_url'] ?? '';
    $meta_title = $row['meta_title'] ?? '';
    $meta_description = $row['meta_description'] ?? '';
    $meta_keywords = $row['meta_keywords'] ?? '';
    $cta_button_text = $row['cta_button_text'] ?? '';
    $cta_button_link = $row['cta_button_link'] ?? '';
    $video_url = $row['video_url'] ?? '';
    $show_stats = $row['show_stats'] ?? '0';
    
    if (!empty($row['stats_data'])) {
        $stats = json_decode($row['stats_data'], true);
    }
}

// First, we need to update the database schema to add new columns
// Run this SQL once in your database:
/*
ALTER TABLE about_us 
ADD COLUMN meta_title VARCHAR(255) DEFAULT '',
ADD COLUMN meta_description TEXT,
ADD COLUMN meta_keywords TEXT,
ADD COLUMN cta_button_text VARCHAR(100) DEFAULT 'Shop Now',
ADD COLUMN cta_button_link VARCHAR(255) DEFAULT '/shop',
ADD COLUMN video_url VARCHAR(500) DEFAULT '',
ADD COLUMN show_stats TINYINT(1) DEFAULT 0,
ADD COLUMN stats_data TEXT,
MODIFY COLUMN content LONGTEXT;
*/
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>About Us Management | Admin Panel</title>
    <link rel="icon" href="<?php echo htmlspecialchars($setting->get('favicon', 'assets/img/logo.png')); ?>" type="image/png">
    <?php include "links.php"; ?>
    <style>
        .about-form {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
            padding: 25px;
            margin-bottom: 30px;
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .form-control, .form-select, .form-control-file {
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            border: 1px solid #ced4da;
            font-size: 14px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #7367f0;
            box-shadow: 0 0 0 0.2rem rgba(115,103,240,.25);
        }
        .image-preview {
            max-width: 100%;
            max-height: 250px;
            margin-top: 15px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            padding: 5px;
            background: #f8f9fa;
        }
        .file-upload {
            position: relative;
            overflow: hidden;
        }
        .file-upload-input {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        .file-upload-label {
            display: inline-block;
            padding: 8px 16px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 14px;
        }
        .file-upload-label:hover {
            background-color: #e9ecef;
        }
        .btn-primary {
            background-color: #7367f0;
            border-color: #7367f0;
            padding: 8px 20px;
            border-radius: 0.375rem;
            font-size: 14px;
        }
        .btn-primary:hover {
            background-color: #5d50e6;
            border-color: #5d50e6;
        }
        .nav-tabs {
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 20px;
        }
        .nav-tabs .nav-link {
            color: #6c757d;
            border: 1px solid transparent;
            border-top-left-radius: 0.375rem;
            border-top-right-radius: 0.375rem;
            padding: 0.5rem 1rem;
            font-size: 14px;
        }
        .nav-tabs .nav-link.active {
            color: #7367f0;
            background-color: #fff;
            border-color: #dee2e6 #dee2e6 #fff;
        }
        .tab-pane {
            padding: 20px 0;
        }
        .stats-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .stats-box h6 {
            font-size: 14px;
            margin-bottom: 10px;
            color: #495057;
        }
        .video-preview {
            width: 100%;
            height: 200px;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
            border: 2px dashed #dee2e6;
        }
        .form-check-input:checked {
            background-color: #7367f0;
            border-color: #7367f0;
        }
        .card {
            border: 1px solid rgba(0,0,0,.125);
            border-radius: 0.5rem;
        }
        .card-header {
            background-color: rgba(0,0,0,.03);
            border-bottom: 1px solid rgba(0,0,0,.125);
            padding: 0.75rem 1.25rem;
        }
        .alert {
            border-radius: 0.5rem;
            font-size: 14px;
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
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <div class="page-header mb-4">
                            <div class="d-flex align-items-center justify-content-between">
                                <h2 class="mb-0">About Us Content</h2>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-12">
                        <div class="about-form">
                            <?php if (!empty($success_message)): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <?= htmlspecialchars($success_message) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($error_message)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <?= htmlspecialchars($error_message) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <form method="post" enctype="multipart/form-data" id="aboutForm">
                                <ul class="nav nav-tabs" id="aboutTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="content-tab" data-bs-toggle="tab" data-bs-target="#content" type="button" role="tab">Content</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="seo-tab" data-bs-toggle="tab" data-bs-target="#seo" type="button" role="tab">SEO & Meta</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="media-tab" data-bs-toggle="tab" data-bs-target="#media" type="button" role="tab">Media</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="cta-tab" data-bs-toggle="tab" data-bs-target="#cta" type="button" role="tab">Call to Action</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats" type="button" role="tab">Statistics</button>
                                    </li>
                                </ul>
                                
                                <div class="tab-content" id="aboutTabContent">
                                    <!-- Content Tab -->
                                    <div class="tab-pane fade show active" id="content" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="title" required value="<?= htmlspecialchars($title) ?>" placeholder="Enter about us title">
                                            </div>
                                            
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Content <span class="text-danger">*</span></label>
                                                <textarea class="form-control summernote" name="content" rows="10" required><?= htmlspecialchars($desc) ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- SEO Tab -->
                                    <div class="tab-pane fade" id="seo" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Meta Title</label>
                                                <input type="text" class="form-control" name="meta_title" value="<?= htmlspecialchars($meta_title) ?>" placeholder="Enter meta title for SEO">
                                                <small class="text-muted">Recommended: 50-60 characters</small>
                                            </div>
                                            
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Meta Description</label>
                                                <textarea class="form-control" name="meta_description" rows="3" placeholder="Enter meta description for SEO"><?= htmlspecialchars($meta_description) ?></textarea>
                                                <small class="text-muted">Recommended: 150-160 characters</small>
                                            </div>
                                            
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Meta Keywords</label>
                                                <input type="text" class="form-control" name="meta_keywords" value="<?= htmlspecialchars($meta_keywords) ?>" placeholder="Enter keywords separated by commas">
                                                <small class="text-muted">Example: about us, ecommerce, company story</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Media Tab -->
                                    <div class="tab-pane fade" id="media" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Featured Image</label>
                                                <div class="file-upload mb-3">
                                                    <label class="file-upload-label">
                                                        <i class="fas fa-cloud-upload-alt me-2"></i>Choose Image
                                                        <input type="file" name="image" class="file-upload-input" accept="image/*">
                                                    </label>
                                                    <small class="d-block text-muted mt-1">Recommended: 1200x800px (Max 5MB)</small>
                                                </div>
                                                
                                                <?php if (!empty($image)): ?>
                                                    <div class="current-image mb-3">
                                                        <p class="mb-2">Current Image:</p>
                                                        <img src="<?= $image ?>" alt="Current About Us Image" class="image-preview">
                                                        <div class="form-check mt-2">
                                                            <input class="form-check-input" type="checkbox" name="remove_image" id="removeImage" value="1">
                                                            <label class="form-check-label" for="removeImage">
                                                                Remove current image
                                                            </label>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Video URL (YouTube/Vimeo)</label>
                                                <input type="url" class="form-control" name="video_url" value="<?= htmlspecialchars($video_url) ?>" placeholder="https://youtube.com/embed/...">
                                                <small class="text-muted">Enter embed URL for YouTube/Vimeo video</small>
                                                
                                                <?php if (!empty($video_url)): ?>
                                                    <div class="video-preview mt-2">
                                                        <i class="fas fa-play-circle fa-3x text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- CTA Tab -->
                                    <div class="tab-pane fade" id="cta" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Button Text</label>
                                                <input type="text" class="form-control" name="cta_button_text" value="<?= htmlspecialchars($cta_button_text) ?>" placeholder="Shop Now">
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Button Link</label>
                                                <input type="text" class="form-control" name="cta_button_link" value="<?= htmlspecialchars($cta_button_link) ?>" placeholder="/shop">
                                            </div>
                                            
                                            <div class="col-12">
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    This button will appear at the bottom of your About Us page to encourage visitors to take action.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Statistics Tab -->
                                    <div class="tab-pane fade" id="stats" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="show_stats" id="showStats" value="1" <?= $show_stats == '1' ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="showStats">
                                                        Show statistics section
                                                    </label>
                                                </div>
                                                <small class="text-muted">Display statistics on your About Us page</small>
                                            </div>
                                            
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Years in Business</label>
                                                <input type="number" class="form-control" name="years" value="<?= $stats['years'] ?>" min="0" placeholder="0">
                                            </div>
                                            
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Happy Customers</label>
                                                <input type="number" class="form-control" name="customers" value="<?= $stats['customers'] ?>" min="0" placeholder="0">
                                            </div>
                                            
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Products Sold</label>
                                                <input type="number" class="form-control" name="products" value="<?= $stats['products'] ?>" min="0" placeholder="0">
                                            </div>
                                            
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Countries Served</label>
                                                <input type="number" class="form-control" name="countries" value="<?= $stats['countries'] ?>" min="0" placeholder="0">
                                            </div>
                                            
                                            <div class="col-12">
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    These statistics will be displayed in a counter format on your About Us page.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary me-2">
                                            <i class="fas fa-save me-2"></i> Save Changes
                                        </button>
                                        <button type="reset" class="btn btn-outline-secondary me-2">
                                            <i class="fas fa-redo me-2"></i> Reset
                                        </button>
                                        <a href="dashboard.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-2"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include "includes/footer.php"; ?>
    </section>
    
    <script>
        // Initialize Summernote editor
        $(document).ready(function() {
            $('.summernote').summernote({
                height: 300,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ]
            });
            
            // Image preview
            $('input[name="image"]').change(function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        let preview = $('.image-preview');
                        if (preview.length === 0) {
                            preview = $('<img class="image-preview">');
                            $('.file-upload').after(preview);
                        }
                        preview.attr('src', e.target.result);
                    }
                    reader.readAsDataURL(file);
                }
            });
            
            // Form validation
            $('#aboutForm').submit(function(e) {
                const title = $('input[name="title"]').val().trim();
                const content = $('.summernote').summernote('code').trim();
                
                if (!title) {
                    e.preventDefault();
                    alert('Please enter a title');
                    $('input[name="title"]').focus();
                    return false;
                }
                
                if (!content) {
                    e.preventDefault();
                    alert('Please enter content');
                    return false;
                }
            });
            
            // Tab navigation with save warning
            let formChanged = false;
            
            $('.form-control, .summernote').on('input change', function() {
                formChanged = true;
            });
            
            $('a[data-bs-toggle="tab"]').on('click', function(e) {
                if (formChanged) {
                    const confirmChange = confirm('You have unsaved changes. Do you want to continue?');
                    if (!confirmChange) {
                        e.preventDefault();
                        return false;
                    }
                }
            });
        });
    </script>
</body>
</html>