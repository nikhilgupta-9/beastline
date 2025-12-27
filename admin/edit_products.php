<?php include('auth_check.php'); ?>
<?php
include "db-conn.php";
include "functions.php";

if (!isset($_GET['edit_product_details'])) {
    die("Product ID is missing from the URL.");
}

$product_id = intval($_GET['edit_product_details']);

// Handle form submission
if (isset($_POST['update-product'])) {
    $pro_id = intval($_POST['pro_id']);
    $pro_name = mysqli_real_escape_string($conn, $_POST['pro_name']);
    $brand_name = mysqli_real_escape_string($conn, $_POST['brand_name']);
    $pro_cate = mysqli_real_escape_string($conn, $_POST['pro_cate']);
    $pro_sub_cate = mysqli_real_escape_string($conn, $_POST['pro_sub_cate']);
    $short_desc = mysqli_real_escape_string($conn, $_POST['short_desc']);
    $pro_desc = mysqli_real_escape_string($conn, $_POST['pro_desc']);
    $new_arrival = intval($_POST['new_arrival']);
    $trending = intval($_POST['trending']);
    $qty = intval($_POST['qty']);
    $mrp = floatval($_POST['mrp']);
    $selling_price = floatval($_POST['selling_price']);
    $whole_sale_selling_price = floatval($_POST['whole_sale_selling_price']);
    $stock = intval($_POST['stock']);
    $status = intval($_POST['status']);
    $meta_title = mysqli_real_escape_string($conn, $_POST['meta_title']);
    $meta_desc = mysqli_real_escape_string($conn, $_POST['meta_desc']);
    $meta_key = mysqli_real_escape_string($conn, $_POST['meta_key']);
    $is_deal = intval($_POST['is_deal']);
    $is_disabled = intval($_POST['is_disabled']);
    $deal_of_the_day = intval($_POST['deal_of_the_day']);
    
    // Generate slug URL
    $slug_url = generate_slug($pro_name);
    
    // Handle image upload
    $uploaded_images = array();
    
    if (!empty($_FILES['pro_img']['name'][0])) {
        $target_dir = "assets/img/uploads/";
        
        foreach ($_FILES['pro_img']['tmp_name'] as $key => $tmp_name) {
            $file_name = basename($_FILES['pro_img']['name'][$key]);
            $target_file = $target_dir . uniqid() . "_" . $file_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            
            // Check if image file is actual image
            $check = getimagesize($_FILES['pro_img']['tmp_name'][$key]);
            if ($check !== false) {
                // Check file size (5MB limit)
                if ($_FILES['pro_img']['size'][$key] <= 5000000) {
                    // Allow certain file formats
                    if (in_array($imageFileType, array('jpg', 'jpeg', 'png', 'gif', 'webp'))) {
                        if (move_uploaded_file($_FILES['pro_img']['tmp_name'][$key], $target_file)) {
                            $uploaded_images[] = pathinfo($target_file, PATHINFO_BASENAME);
                        }
                    }
                }
            }
        }
    }
    
    // If new images uploaded, use them; otherwise keep existing images
    if (!empty($uploaded_images)) {
        $pro_img = implode(',', $uploaded_images);
        $update_sql = "UPDATE products SET 
            pro_name = '$pro_name',
            brand_name = '$brand_name',
            pro_cate = '$pro_cate',
            pro_sub_cate = '$pro_sub_cate',
            short_desc = '$short_desc',
            description = '$pro_desc',
            new_arrival = '$new_arrival',
            trending = '$trending',
            qty = '$qty',
            mrp = '$mrp',
            selling_price = '$selling_price',
            whole_sale_selling_price = '$whole_sale_selling_price',
            stock = '$stock',
            pro_img = '$pro_img',
            status = '$status',
            slug_url = '$slug_url',
            meta_title = '$meta_title',
            meta_desc = '$meta_desc',
            meta_key = '$meta_key',
            is_deal = '$is_deal',
            is_disabled = '$is_disabled',
            deal_of_the_day = '$deal_of_the_day'
            WHERE pro_id = $pro_id";
    } else {
        $update_sql = "UPDATE products SET 
            pro_name = '$pro_name',
            brand_name = '$brand_name',
            pro_cate = '$pro_cate',
            pro_sub_cate = '$pro_sub_cate',
            short_desc = '$short_desc',
            description = '$pro_desc',
            new_arrival = '$new_arrival',
            trending = '$trending',
            qty = '$qty',
            mrp = '$mrp',
            selling_price = '$selling_price',
            whole_sale_selling_price = '$whole_sale_selling_price',
            stock = '$stock',
            status = '$status',
            slug_url = '$slug_url',
            meta_title = '$meta_title',
            meta_desc = '$meta_desc',
            meta_key = '$meta_key',
            is_deal = '$is_deal',
            is_disabled = '$is_disabled',
            deal_of_the_day = '$deal_of_the_day'
            WHERE pro_id = $pro_id";
    }
    
    if (mysqli_query($conn, $update_sql)) {
        echo "<script>alert('Product updated successfully!'); window.location.href='show-products.php';</script>";
    } else {
        echo "<script>alert('Error updating product: " . mysqli_error($conn) . "');</script>";
    }
}

// Fetch product details
$sql = "SELECT * FROM products WHERE pro_id = $product_id";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $product = mysqli_fetch_assoc($result);
} else {
    die("Product not found.");
}

// Fetch categories
$category_query = "SELECT * FROM `categories` ORDER BY id DESC";
$categories = mysqli_query($conn, $category_query);

// Fetch subcategories based on product's category
$subcategories = array();
if ($product['pro_cate']) {
    $subcategory_query = "SELECT * FROM `sub_categories` WHERE cate_id = '{$product['pro_cate']}' ORDER BY id DESC";
    $subcategories_result = mysqli_query($conn, $subcategory_query);
    if ($subcategories_result) {
        $subcategories = mysqli_fetch_all($subcategories_result, MYSQLI_ASSOC);
    }
}

// Fetch all brands
$brands_query = "SELECT * FROM `brands` ORDER BY id DESC";
$brands = mysqli_query($conn, $brands_query);

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
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Edit Product | Admin Panel</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">
    <?php include "links.php"; ?>
    <style>
        .product-form {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 30px;
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
        .card-header {
            background: #fff;
            border-bottom: 1px solid #eee;
            padding: 20px 30px;
        }
        .main-title h2 {
            color: #2c2c2c;
            font-weight: 600;
        }
        .btn-primary {
            background-color: #7367f0;
            border-color: #7367f0;
            padding: 10px 25px;
            border-radius: 6px;
            font-weight: 500;
            letter-spacing: 0.5px;
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
        .image-thumbnail {
            width: 100px;
            height: 100px;
            object-fit: cover;
            margin-right: 10px;
            margin-bottom: 10px;
            border: 1px solid #eee;
            border-radius: 4px;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        .status-active {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        .status-inactive {
            background-color: #ffebee;
            color: #c62828;
        }
        .price-input {
            position: relative;
        }
        .price-input:before {
            content: "₹";
            position: absolute;
            left: 12px;
            top: 38px;
            font-weight: 500;
            color: #495057;
            z-index: 1;
        }
        .price-input input {
            padding-left: 30px;
        }
        .current-images {
            margin-top: 15px;
        }
        .image-container {
            position: relative;
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .image-container img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .additional-flags {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
    </style>
</head>

<body class="crm_body_bg">
    <?php include "header.php"; ?>

    <section class="main_content dashboard_part">
        <div class="container-fluid g-0">
            <div class="row">
                <div class="col-lg-12 p-0">
                    <?php include "top_nav.php"; ?>
                </div>
            </div>
        </div>

        <div class="main_content_iner">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <div class="page-header mb-4">
                            <div class="d-flex align-items-center justify-content-between">
                                <h2 class="mb-0">Edit Product</h2>
                                <a href="show-products.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i> Back to Products
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-12">
                        <div class="product-form">
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
                            
                            <form action="" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="pro_id" value="<?= $product['pro_id'] ?>">
                                
                                <div class="row">
                                    <!-- Basic Information -->
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Product Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="pro_name" 
                                            value="<?= htmlspecialchars($product['pro_name']) ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Brand Name</label>
                                        <select class="form-select" name="brand_name">
                                            <option value="">Select Brand</option>
                                            <?php while ($brand = mysqli_fetch_assoc($brands)): ?>
                                                <option value="<?= $brand['brand_name'] ?>" <?= ($brand['brand_name'] == $product['brand_name']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars(ucwords($brand['brand_name'])) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Category <span class="text-danger">*</span></label>
                                        <select class="form-select" name="pro_cate" required onchange="get_subcategory(this.value)">
                                            <option value="">Select Category</option>
                                            <?php 
                                            // Reset categories pointer
                                            mysqli_data_seek($categories, 0);
                                            while ($category = mysqli_fetch_assoc($categories)): ?>
                                                <option value="<?= $category['cate_id'] ?>" <?= ($category['cate_id'] == $product['pro_cate']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars(ucwords($category['categories'])) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Sub Category</label>
                                        <select class="form-select" name="pro_sub_cate" id="subcate_id">
                                            <option value="">Select Sub Category</option>
                                            <?php foreach ($subcategories as $subcategory): ?>
                                                <option value="<?= $subcategory['sub_cate_id'] ?>" <?= ($subcategory['sub_cate_id'] == $product['pro_sub_cate']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars(ucwords($subcategory['sub_categories'])) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Stock <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="stock" 
                                            value="<?= htmlspecialchars($product['stock']) ?>" required min="0">
                                    </div>
                                    
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="qty" 
                                            value="<?= htmlspecialchars($product['qty']) ?>" required min="0">
                                    </div>
                                    
                                    <!-- Pricing -->
                                    <div class="col-md-4 mb-4">
                                        <label class="form-label">MRP <span class="text-danger">*</span></label>
                                        <div class="price-input">
                                            <input type="number" step="0.01" class="form-control" name="mrp" 
                                                value="<?= htmlspecialchars($product['mrp']) ?>" required min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-4">
                                        <label class="form-label">Selling Price <span class="text-danger">*</span></label>
                                        <div class="price-input">
                                            <input type="number" step="0.01" class="form-control" name="selling_price" 
                                                value="<?= htmlspecialchars($product['selling_price']) ?>" required min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-4">
                                        <label class="form-label">Wholesale Price</label>
                                        <div class="price-input">
                                            <input type="number" step="0.01" class="form-control" name="whole_sale_selling_price" 
                                                value="<?= htmlspecialchars($product['whole_sale_selling_price']) ?>" min="0">
                                        </div>
                                    </div>
                                    
                                    <!-- Images -->
                                    <div class="col-md-12 mb-4">
                                        <label class="form-label">Product Images</label>
                                        <input type="file" class="form-control" name="pro_img[]" multiple accept="image/*">
                                        <small class="text-muted">You can select multiple images. Supported formats: JPG, JPEG, PNG, GIF, WEBP. Max size: 5MB per image.</small>
                                        
                                        <?php if (!empty($product['pro_img'])): ?>
                                            <div class="current-images mt-3">
                                                <label class="form-label">Current Images:</label>
                                                <div class="d-flex flex-wrap">
                                                    <?php 
                                                    $images = explode(',', $product['pro_img']);
                                                    foreach ($images as $img): 
                                                        if (!empty(trim($img))): ?>
                                                            <div class="image-container">
                                                                <img src="assets/img/uploads/<?= htmlspecialchars(trim($img)) ?>" 
                                                                    alt="Product Image" class="image-thumbnail">
                                                            </div>
                                                        <?php endif;
                                                    endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Descriptions -->
                                    <div class="col-md-12 mb-4">
                                        <label class="form-label">Short Description <span class="text-danger">*</span></label>
                                        <textarea class="form-control" name="short_desc" rows="3" required><?= htmlspecialchars($product['short_desc']) ?></textarea>
                                    </div>
                                    
                                    <div class="col-md-12 mb-4">
                                        <label class="form-label">Long Description <span class="text-danger">*</span></label>
                                        <textarea class="form-control" name="pro_desc" id="pro_desc" rows="5" required><?= htmlspecialchars($product['description']) ?></textarea>
                                    </div>
                                    
                                    <!-- Basic Flags -->
                                    <div class="col-md-4 mb-4">
                                        <label class="form-label">New Arrival</label>
                                        <select class="form-select" name="new_arrival" required>
                                            <option value="0" <?= $product['new_arrival'] == 0 ? 'selected' : '' ?>>No</option>
                                            <option value="1" <?= $product['new_arrival'] == 1 ? 'selected' : '' ?>>Yes</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 mb-4">
                                        <label class="form-label">Trending</label>
                                        <select class="form-select" name="trending" required>
                                            <option value="0" <?= $product['trending'] == 0 ? 'selected' : '' ?>>No</option>
                                            <option value="1" <?= $product['trending'] == 1 ? 'selected' : '' ?>>Yes</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 mb-4">
                                        <label class="form-label">Status <span class="text-danger">*</span></label>
                                        <select class="form-select" name="status" required>
                                            <option value="1" <?= $product['status'] == 1 ? 'selected' : '' ?>>Active</option>
                                            <option value="0" <?= $product['status'] == 0 ? 'selected' : '' ?>>Inactive</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Additional Flags -->
                                    <div class="col-12">
                                        <div class="additional-flags">
                                            <h5 class="mb-3">Additional Flags</h5>
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Is Deal</label>
                                                    <select class="form-select" name="is_deal">
                                                        <option value="0" <?= $product['is_deal'] == 0 ? 'selected' : '' ?>>No</option>
                                                        <option value="1" <?= $product['is_deal'] == 1 ? 'selected' : '' ?>>Yes</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Is Disabled</label>
                                                    <select class="form-select" name="is_disabled">
                                                        <option value="0" <?= $product['is_disabled'] == 0 ? 'selected' : '' ?>>No</option>
                                                        <option value="1" <?= $product['is_disabled'] == 1 ? 'selected' : '' ?>>Yes</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Deal of the Day</label>
                                                    <select class="form-select" name="deal_of_the_day">
                                                        <option value="0" <?= $product['deal_of_the_day'] == 0 ? 'selected' : '' ?>>No</option>
                                                        <option value="1" <?= $product['deal_of_the_day'] == 1 ? 'selected' : '' ?>>Yes</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- SEO -->
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Meta Title</label>
                                        <input type="text" class="form-control" name="meta_title" 
                                            value="<?= htmlspecialchars($product['meta_title']) ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Meta Keywords</label>
                                        <input type="text" class="form-control" name="meta_key" 
                                            value="<?= htmlspecialchars($product['meta_key']) ?>">
                                    </div>
                                    
                                    <div class="col-md-12 mb-4">
                                        <label class="form-label">Meta Description</label>
                                        <textarea class="form-control" name="meta_desc" rows="2"><?= htmlspecialchars($product['meta_desc']) ?></textarea>
                                    </div>
                                    
                                    <!-- Submit -->
                                    <div class="col-12 mt-4">
                                        <button type="submit" name="update-product" class="btn btn-primary me-2">
                                            <i class="fas fa-save me-2"></i> Update Product
                                        </button>
                                        <a href="show-products.php" class="btn btn-outline-secondary">
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

        <?php include "footer.php"; ?>

        <script src="https://cdn.ckeditor.com/4.21.0/standard/ckeditor.js"></script>
        <script>
            CKEDITOR.replace('short_desc');
            CKEDITOR.replace('pro_desc');
            
            function get_subcategory(cate_id) {
                if (cate_id === "") {
                    $("#subcate_id").html('<option value="">Select Sub Category</option>');
                    return;
                }
                
                $.ajax({
                    url: 'functions.php',
                    method: 'post',
                    data: { cate_id: cate_id },
                    error: function() {
                        alert("Something went wrong while loading subcategories");
                    },
                    success: function(data) {
                        $("#subcate_id").html(data);
                    }
                });
            }
        </script>
    </body>
</html>