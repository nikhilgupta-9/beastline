<?php include('auth_check.php'); ?>
<?php
include "db-conn.php";
include "functions.php";

// Get category id from URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $cat_id = intval($_GET['id']);
    
    // Fetch category details from the database
    $sql = "SELECT * FROM categories WHERE cate_id = $cat_id";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $category = mysqli_fetch_assoc($result);
    } else {
        echo "Category not found.";
        exit;
    }
} else {
    echo "Invalid category id.";
    exit;
}

// Process form submission for updating the category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_category'])) {
    // Retrieve updated values from POST
    $cate_id = intval($_POST['cate_id']);
    $categories = mysqli_real_escape_string($conn, $_POST['categories']);
    $slug_url = mysqli_real_escape_string($conn, $_POST['slug_url']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $meta_title = mysqli_real_escape_string($conn, $_POST['meta_title']);
    $meta_desc = mysqli_real_escape_string($conn, $_POST['meta_desc']);
    $meta_key = mysqli_real_escape_string($conn, $_POST['meta_key']);
    
    // Handle image upload
    $image_name = $category['image']; // Keep existing image by default
    
    if (!empty($_FILES['imageUpload']['name'])) {
        $target_dir = "uploads/category/";
        $file_name = basename($_FILES['imageUpload']['name']);
        $target_file = $target_dir . uniqid() . "_" . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Check if image file is actual image
        $check = getimagesize($_FILES['imageUpload']['tmp_name']);
        if ($check !== false) {
            // Check file size (5MB limit)
            if ($_FILES['imageUpload']['size'] <= 5000000) {
                // Allow certain file formats
                if (in_array($imageFileType, array('jpg', 'jpeg', 'png', 'gif', 'webp'))) {
                    if (move_uploaded_file($_FILES['imageUpload']['tmp_name'], $target_file)) {
                        $image_name = pathinfo($target_file, PATHINFO_BASENAME);
                        // Delete old image if exists
                        if (!empty($category['image']) && file_exists($target_dir . $category['image'])) {
                            unlink($target_dir . $category['image']);
                        }
                    }
                } else {
                    echo "<script>alert('Sorry, only JPG, JPEG, PNG, GIF & WEBP files are allowed.');</script>";
                }
            } else {
                echo "<script>alert('Sorry, your file is too large. Maximum size is 5MB.');</script>";
            }
        } else {
            echo "<script>alert('File is not an image.');</script>";
        }
    }
    
    // Update category in database
    $update_sql = "UPDATE categories SET 
        categories = '$categories',
        slug_url = '$slug_url',
        status = '$status',
        meta_title = '$meta_title',
        meta_desc = '$meta_desc',
        meta_key = '$meta_key',
        image = '$image_name'
        WHERE id = $cat_id";
    
    if (mysqli_query($conn, $update_sql)) {
        echo "<script>alert('Category updated successfully!'); window.location.href='categories.php';</script>";
    } else {
        echo "<script>alert('Error updating category: " . mysqli_error($conn) . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="zxx">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Edit Category</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">
    <?php include "links.php"; ?>
    <style>
        .category-form {
            background: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
        }
        .form-control, .form-select {
            border-radius: 6px;
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
        }
        .form-control:focus, .form-select:focus {
            border-color: #7367f0;
            box-shadow: 0 0 0 3px rgba(115,103,240,.15);
        }
        .btn-primary {
            background-color: #7367f0;
            border-color: #7367f0;
            padding: 10px 25px;
            border-radius: 6px;
            font-weight: 500;
        }
        .btn-primary:hover {
            background-color: #5d50e6;
            border-color: #5d50e6;
        }
        .image-preview {
            max-width: 150px;
            max-height: 150px;
            margin-top: 10px;
            border-radius: 4px;
            border: 1px dashed #ddd;
            padding: 5px;
        }
        .current-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }
    </style>
</head>

<body class="crm_body_bg">

    <?php include "header.php"; ?>
    <section class="main_content dashboard_part large_header_bg">

       <div class="container-fluid g-0">
            <div class="row">
                <div class="col-lg-12 p-0">
                    <?php include "top_nav.php"; ?>
                </div>
            </div>
        </div>

        <div class="main_content_iner ">
            <div class="container-fluid p-0">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="white_card card_height_100 mb_30">
                            <div class="white_card_header">
                                <div class="box_header m-0">
                                    <div class="main-title">
                                        <h2 class="m-0">Edit Category</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="white_card_body">
                                <div class="QA_section">
                                    <div class="white_box_tittle list_header">
                                        <div class="box_right d-flex lms_block">
                                            <div class="add_button ms-2">
                                                <a href="view-categories.php" class="btn btn-outline-secondary">Back to Categories</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="QA_table mb_30">
                                        <div class="category-form">
                                            <?php if (isset($success_message)): ?>
                                                <div class="alert alert-success">
                                                    <?php echo $success_message; ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (isset($error_message)): ?>
                                                <div class="alert alert-danger">
                                                    <?php echo $error_message; ?>
                                                </div>
                                            <?php endif; ?>

                                            <form action="" method="post" enctype="multipart/form-data">
                                                <input type="hidden" name="cate_id" value="<?= htmlspecialchars($category['cate_id']) ?>">
                                                
                                                <div class="row">
                                                    <div class="col-md-6 mb-4">
                                                        <label class="form-label">Category Name <span class="text-danger">*</span></label>
                                                        <input type="text" name="categories" 
                                                            value="<?= htmlspecialchars($category['categories']) ?>" 
                                                            class="form-control" required>
                                                    </div>
                                                    
                                                    <div class="col-md-6 mb-4">
                                                        <label class="form-label">Slug URL <span class="text-danger">*</span></label>
                                                        <input type="text" name="slug_url" 
                                                            value="<?= htmlspecialchars($category['slug_url']) ?>" 
                                                            class="form-control" required>
                                                    </div>
                                                    
                                                    <div class="col-md-6 mb-4">
                                                        <label class="form-label">Status <span class="text-danger">*</span></label>
                                                        <select name="status" class="form-select" required>
                                                            <option value="Active" <?= ($category['status'] == 'Active') ? 'selected' : '' ?>>Active</option>
                                                            <option value="Inactive" <?= ($category['status'] == 'Inactive') ? 'selected' : '' ?>>Inactive</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="col-md-6 mb-4">
                                                        <label class="form-label">Category Image</label>
                                                        <input type="file" name="imageUpload" class="form-control" accept="image/*">
                                                        <small class="text-muted">Leave empty to keep current image. Supported formats: JPG, JPEG, PNG, GIF, WEBP</small>
                                                        
                                                        <?php if (!empty($category['image'])): ?>
                                                            <div class="mt-3">
                                                                <label class="form-label">Current Image:</label>
                                                                <div>
                                                                    <img src="uploads/category/<?= htmlspecialchars($category['image']) ?>" 
                                                                        alt="Category Image" class="current-image">
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <div class="col-md-12 mb-4">
                                                        <label class="form-label">Meta Title</label>
                                                        <input type="text" name="meta_title" 
                                                            value="<?= htmlspecialchars($category['meta_title']) ?>" 
                                                            class="form-control">
                                                    </div>
                                                    
                                                    <div class="col-md-12 mb-4">
                                                        <label class="form-label">Meta Description</label>
                                                        <textarea name="meta_desc" class="form-control" rows="3"><?= htmlspecialchars($category['meta_desc']) ?></textarea>
                                                    </div>
                                                    
                                                    <div class="col-md-12 mb-4">
                                                        <label class="form-label">Meta Keywords</label>
                                                        <input type="text" name="meta_key" 
                                                            value="<?= htmlspecialchars($category['meta_key']) ?>" 
                                                            class="form-control" placeholder="Separate keywords with commas">
                                                    </div>
                                                    
                                                    <div class="col-12 mt-4">
                                                        <button type="submit" name="update_category" class="btn btn-primary me-2">
                                                            <i class="fas fa-save me-2"></i> Update Category
                                                        </button>
                                                        <a href="categories.php" class="btn btn-outline-secondary">
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
                    </div>
                </div>
            </div>
        </div>

        <?php include "footer.php"; ?>
    </section>

    <script>
        // Auto-generate slug from category name
        document.querySelector('input[name="categories"]').addEventListener('input', function() {
            const categoryName = this.value;
            const slugInput = document.querySelector('input[name="slug_url"]');
            
            // Only auto-generate if slug is empty or matches the old category name
            if (!slugInput.value || slugInput.value === '<?= $category['slug_url'] ?>') {
                const slug = categoryName
                    .toLowerCase()
                    .trim()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/[\s_-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                slugInput.value = slug;
            }
        });
    </script>
</body>
</html>