<?php include('auth_check.php'); ?>
<?php
include "db-conn.php";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add-categories'])) {
    // Retrieve values from POST
    $categories = mysqli_real_escape_string($conn, $_POST['cate_name']);
    $meta_title = mysqli_real_escape_string($conn, $_POST['meta_title']);
    $meta_desc = mysqli_real_escape_string($conn, $_POST['meta_desc']);
    $meta_key = mysqli_real_escape_string($conn, $_POST['meta_key']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Generate slug URL from category name
    $slug_url = generate_slug($categories);
    
    // Generate unique category ID
    $cate_id = uniqid();
    
    // Handle image upload
    $image_name = '';
    
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
                    } else {
                        echo "<script>alert('Sorry, there was an error uploading your file.');</script>";
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
    
    // Insert category into database
    $insert_sql = "INSERT INTO categories (cate_id, categories, meta_title, meta_desc, meta_key, image, slug_url, status, added_on) 
                   VALUES ('$cate_id', '$categories', '$meta_title', '$meta_desc', '$meta_key', '$image_name', '$slug_url', '$status', NOW())";
    
    if (mysqli_query($conn, $insert_sql)) {
        echo "<script>alert('Category added successfully!'); window.location.href='categories.php';</script>";
    } else {
        echo "<script>alert('Error adding category: " . mysqli_error($conn) . "');</script>";
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
    
    if (empty($text)) {
        return 'n-a';
    }
    
    return $text;
}
?>
<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Add Category</title>
    <link rel="icon" href="img/logo.png" type="image/png">

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
            display: none;
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
            <div class="container-fluid p-0 sm_padding_15px">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="white_card card_height_100 mb_30">
                            <div class="white_card_header">
                                <div class="box_header m-0">
                                    <div class="main-title">
                                        <h2 class="m-0">Add New Category</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="white_card_body">
                                <div class="card-body">
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

                                    <form id="myform" action="" method="post" enctype="multipart/form-data">
                                        <div class="row mb-3">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="cate_name">Category Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="cate_name" id="cate_name" 
                                                    placeholder="Enter category name" required />
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="meta_title">Meta Title</label>
                                                <input type="text" class="form-control" name="meta_title" id="meta_title" 
                                                    placeholder="Enter meta title" />
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="meta_key">Meta Keywords</label>
                                                <input type="text" class="form-control" name="meta_key" id="meta_key" 
                                                    placeholder="Enter meta keywords (separate with commas)" />
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="meta_desc">Meta Description</label>
                                                <textarea class="form-control" name="meta_desc" id="meta_desc" 
                                                    placeholder="Enter meta description" rows="1"></textarea>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                                                <select id="status" name="status" class="form-control" required>
                                                    <option value="1">Active</option>
                                                    <option value="0">Inactive</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="imageUpload">Category Image</label>
                                                <input type="file" class="form-control" name="imageUpload" id="imageUpload" 
                                                    accept="image/*" onchange="previewImage(this)" />
                                                <small class="text-muted">Supported formats: JPG, JPEG, PNG, GIF, WEBP. Max size: 5MB</small>
                                                <img id="imagePreview" class="image-preview" alt="Image Preview">
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-md-12">
                                                <div class="alert alert-info">
                                                    <strong>Note:</strong> Slug URL will be automatically generated from the category name.
                                                </div>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary" name="add-categories">
                                            <i class="fas fa-plus me-2"></i> Add Category
                                        </button>
                                        <a href="categories.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-2"></i> Cancel
                                        </a>
                                    </form>
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
        // Image preview function
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const file = input.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
                preview.src = '';
            }
        }

        // Auto-generate meta title from category name
        document.getElementById('cate_name').addEventListener('input', function() {
            const categoryName = this.value;
            const metaTitle = document.getElementById('meta_title');
            
            // Only auto-generate if meta title is empty
            if (!metaTitle.value) {
                metaTitle.value = categoryName;
            }
        });

        // Auto-generate meta description from category name
        document.getElementById('cate_name').addEventListener('input', function() {
            const categoryName = this.value;
            const metaDesc = document.getElementById('meta_desc');
            
            // Only auto-generate if meta description is empty
            if (!metaDesc.value) {
                metaDesc.value = "Explore our " + categoryName + " collection. Find the best " + categoryName + " products at great prices.";
            }
        });

        // Auto-generate meta keywords from category name
        document.getElementById('cate_name').addEventListener('input', function() {
            const categoryName = this.value;
            const metaKey = document.getElementById('meta_key');
            
            // Only auto-generate if meta keywords is empty
            if (!metaKey.value) {
                metaKey.value = categoryName.toLowerCase() + ", buy " + categoryName.toLowerCase() + ", " + categoryName.toLowerCase() + " products";
            }
        });

        // Form validation
        document.getElementById('myform').addEventListener('submit', function(event) {
            const categoryName = document.getElementById('cate_name').value.trim();
            const status = document.getElementById('status').value;
            
            if (!categoryName) {
                alert('Please enter a category name.');
                event.preventDefault();
                return;
            }
            
            if (!status) {
                alert('Please select a status.');
                event.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>