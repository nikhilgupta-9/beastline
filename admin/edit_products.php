<?php
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';
require_once __DIR__ . '/models/setting.php';

// Initialize
$setting = new Setting($conn);

if (!isset($_GET['edit_product_details'])) {
    die("Product ID is missing from the URL.");
}

$product_id = intval($_GET['edit_product_details']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update-product'])) {
    $pro_id = intval($_POST['pro_id']);

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Update main products table
        $pro_name = mysqli_real_escape_string($conn, $_POST['pro_name']);
        $brand_name = mysqli_real_escape_string($conn, $_POST['brand_name']);
        $pro_cate = mysqli_real_escape_string($conn, $_POST['pro_cate']);
        $pro_sub_cate = mysqli_real_escape_string($conn, $_POST['pro_sub_cate']);
        $sku = mysqli_real_escape_string($conn, $_POST['sku']);
        $product_type = mysqli_real_escape_string($conn, $_POST['product_type'] ?? '');
        $short_desc = mysqli_real_escape_string($conn, $_POST['short_desc']);
        $pro_desc = mysqli_real_escape_string($conn, $_POST['pro_desc']);
        $new_arrival = intval($_POST['new_arrival'] ?? 0);
        $trending = intval($_POST['trending'] ?? 0);
        $qty = intval($_POST['qty'] ?? 0);
        $mrp = floatval($_POST['mrp'] ?? 0);
        $selling_price = floatval($_POST['selling_price'] ?? 0);
        $whole_sale_selling_price = floatval($_POST['whole_sale_selling_price'] ?? 0);
        $weight = floatval($_POST['weight'] ?? 0);
        $stock = mysqli_real_escape_string($conn, $_POST['stock'] ?? 'in_stock');
        $status = intval($_POST['status'] ?? 1);
        $meta_title = mysqli_real_escape_string($conn, $_POST['meta_title'] ?? '');
        $meta_desc = mysqli_real_escape_string($conn, $_POST['meta_desc'] ?? '');
        $meta_key = mysqli_real_escape_string($conn, $_POST['meta_key'] ?? '');
        $is_deal = intval($_POST['is_deal'] ?? 0);
        $is_disabled = intval($_POST['is_disabled'] ?? 0);
        $deal_of_the_day = intval($_POST['deal_of_the_day'] ?? 0);
        $material = mysqli_real_escape_string($conn, $_POST['material'] ?? '');
        $fit_type = mysqli_real_escape_string($conn, $_POST['fit_type'] ?? '');
        $season = mysqli_real_escape_string($conn, $_POST['season'] ?? '');
        $care_instructions = mysqli_real_escape_string($conn, $_POST['care_instructions'] ?? '');
        $video_url = mysqli_real_escape_string($conn, $_POST['video_url'] ?? '');
        $tags = mysqli_real_escape_string($conn, $_POST['tags'] ?? '');

        // Generate slug URL
        $slug_url = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $pro_name)));

        // Handle main image upload
        $pro_img = $product['pro_img'] ?? '';
        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === 0) {
            $upload_dir = 'assets/img/uploads/';
            $file_name = 'product-' . time() . '-' . $_FILES['main_image']['name'];
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['main_image']['tmp_name'], $target_path)) {
                // Delete old main image if exists
                if (!empty($product['pro_img']) && file_exists($upload_dir . $product['pro_img'])) {
                    unlink($upload_dir . $product['pro_img']);
                }
                $pro_img = $file_name;
            }
        }

        // Update products table
        $update_sql = "UPDATE products SET 
            pro_name = '$pro_name',
            brand_name = '$brand_name',
            pro_cate = '$pro_cate',
            pro_sub_cate = '$pro_sub_cate',
            sku = '$sku',
            product_type = '$product_type',
            short_desc = '$short_desc',
            description = '$pro_desc',
            new_arrival = '$new_arrival',
            trending = '$trending',
            qty = '$qty',
            mrp = '$mrp',
            selling_price = '$selling_price',
            whole_sale_selling_price = '$whole_sale_selling_price',
            weight = '$weight',
            stock = '$stock',
            pro_img = '$pro_img',
            status = '$status',
            slug_url = '$slug_url',
            meta_title = '$meta_title',
            meta_desc = '$meta_desc',
            meta_key = '$meta_key',
            is_deal = '$is_deal',
            is_disabled = '$is_disabled',
            deal_of_the_day = '$deal_of_the_day',
            material = '$material',
            fit_type = '$fit_type',
            season = '$season',
            care_instructions = '$care_instructions',
            video_url = '$video_url',
            tags = '$tags',
            updated_on = NOW()
            WHERE pro_id = $pro_id";

        if (!mysqli_query($conn, $update_sql)) {
            throw new Exception("Error updating product: " . mysqli_error($conn));
        }

        // Handle product attributes
        $attributes = json_decode($_POST['attributes_json'] ?? '[]', true);

        // Delete existing attributes
        // mysqli_query($conn, "DELETE FROM product_attributes WHERE product_id = $pro_id");

        // Insert new attributes
        if (!empty($attributes)) {
            $display_order = 1;
            foreach ($attributes as $attr) {
                $attr_name = mysqli_real_escape_string($conn, $attr['name']);
                $attr_value = mysqli_real_escape_string($conn, $attr['value']);
                $attr_sql = "INSERT INTO product_attributes (product_id, attribute_name, attribute_value, display_order) 
                            VALUES ($pro_id, '$attr_name', '$attr_value', $display_order)";
                mysqli_query($conn, $attr_sql);
                $display_order++;
            }
        }

        // Handle product variants
        $variants = json_decode($_POST['variants_json'] ?? '[]', true);

        if (!empty($variants)) {

            foreach ($variants as $index => $variant) {

                $variant_id = intval($variant['db_id'] ?? 0); // pass DB id from frontend
                $color = mysqli_real_escape_string($conn, $variant['color']);
                $size = mysqli_real_escape_string($conn, $variant['size']);
                $sku = mysqli_real_escape_string($conn, $variant['sku']);
                $price = floatval($variant['price']);
                $compare_at_price = floatval($variant['compare_at_price']);
                $quantity = intval($variant['quantity']);

                $image_name = $variant['existing_image'] ?? '';

                // new image upload?
                if (isset($_FILES['variant_images']['name'][$index]) 
                    && $_FILES['variant_images']['error'][$index] === 0) {

                    $upload_dir = 'assets/img/uploads/variants/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                    $file_name = 'variant-' . $pro_id . '-' . time() . '-' . basename($_FILES['variant_images']['name'][$index]);

                    if (move_uploaded_file(
                        $_FILES['variant_images']['tmp_name'][$index],
                        $upload_dir . $file_name
                    )) {
                        $image_name = $file_name;
                    }
                }



                if ($variant_id > 0) {

                    // UPDATE existing
                    $sql = "UPDATE product_variants SET 
                        color='$color',
                        size='$size',
                        sku='$sku',
                        price=$price,
                        compare_at_price=$compare_at_price,
                        quantity=$quantity,
                        image='$image_name'
                    WHERE id=$variant_id";

                    mysqli_query($conn, $sql);
                } else {

                    // INSERT new
                    $sql = "INSERT INTO product_variants 
                    (product_id,color,size,sku,price,compare_at_price,quantity,image,status)
                    VALUES
                    ($pro_id,'$color','$size','$sku',$price,$compare_at_price,$quantity,'$image_name',1)";

                    mysqli_query($conn, $sql);
                }
            }
        }


        // Handle product images
        $upload_dir = 'assets/img/uploads/';

        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === 0) {

            // remove old DB record
            mysqli_query($conn, "DELETE FROM product_images WHERE product_id = $pro_id AND is_main = 1");

            // insert new one
            $main_img_sql = "INSERT INTO product_images (product_id, image_url, is_main, display_order) 
                            VALUES ($pro_id, '$pro_img', 1, 0)";
            mysqli_query($conn, $main_img_sql);
        }


        // Handle additional images
        $additional_images = [];
        if (isset($_FILES['additional_images']) && count($_FILES['additional_images']['name']) > 0) {
            $display_order = 1;
            foreach ($_FILES['additional_images']['name'] as $key => $name) {
                if ($_FILES['additional_images']['error'][$key] === 0) {
                    $file_name = 'product-' . time() . '-' . $key . '-' . $name;
                    $target_path = $upload_dir . $file_name;

                    if (move_uploaded_file($_FILES['additional_images']['tmp_name'][$key], $target_path)) {
                        $additional_images[] = $file_name;
                        $img_sql = "INSERT INTO product_images (product_id, image_url, is_main, display_order) 
                                   VALUES ($pro_id, '$file_name', 0, $display_order)";
                        mysqli_query($conn, $img_sql);
                        $display_order++;
                    }
                }
            }
        }

        // Commit transaction
        mysqli_commit($conn);

        echo "<script>alert('Product updated successfully!'); window.location.href='view-product-details.php?id=" . $pro_id . "';</script>";
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        echo "<script>alert('Error updating product: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// Fetch product details with all related data
$sql = "SELECT p.*, 
               c.categories as category_name,
               sc.categories as subcategory_name,
               b.brand_name as brand_display
        FROM products p
        LEFT JOIN categories c ON p.pro_cate = c.id
        LEFT JOIN categories sc ON p.pro_sub_cate = sc.id
        LEFT JOIN pro_brands b ON p.brand_name = b.id
        WHERE p.pro_id = $product_id LIMIT 1";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $product = mysqli_fetch_assoc($result);
} else {
    die("Product not found.");
}

// Fetch product attributes
$attributes_sql = "SELECT * FROM product_attributes WHERE product_id = $product_id ORDER BY display_order";
$attributes_result = mysqli_query($conn, $attributes_sql);
$attributes = [];
while ($attr = mysqli_fetch_assoc($attributes_result)) {
    $attributes[] = [
        'name' => $attr['attribute_name'],
        'value' => $attr['attribute_value']
    ];
}



// Fetch product images
$images_sql = "SELECT * FROM product_images WHERE product_id = $product_id ORDER BY display_order";
$images_result = mysqli_query($conn, $images_sql);
$main_image = null;
$additional_images = [];
while ($img = mysqli_fetch_assoc($images_result)) {
    if ($img['is_main'] == 1) {
        $main_image = $img['image_url'];
    } else {
        $additional_images[] = $img['image_url'];
    }
}

// If main image not in product_images table, use pro_img from products table
if (empty($main_image) && !empty($product['pro_img'])) {
    $main_image = $product['pro_img'];
}

$variants = [];
$variant_sql = "SELECT * FROM product_variants WHERE product_id = $product_id ORDER BY id ASC";
$variant_res = mysqli_query($conn, $variant_sql);

while ($row = mysqli_fetch_assoc($variant_res)) {
    $variants[] = [
        'db_id' => $row['id'],
        'color' => $row['color'],
        'size' => $row['size'],
        'sku' => $row['sku'],
        'price' => $row['price'],
        'compare_at_price' => $row['compare_at_price'],
        'quantity' => $row['quantity'],
        'image' => $row['image'],
        'existing_image' => $row['image']
    ];
}

// Fetch categories
$categories_query = "SELECT * FROM `categories` WHERE `parent_id` = 0 AND `status` = 1 ORDER BY display_order ASC";
$categories = mysqli_query($conn, $categories_query);

// Fetch subcategories based on product's category
$subcategories_query = "SELECT * FROM `categories` WHERE `parent_id` = '{$product['pro_cate']}' AND `status` = 1 ORDER BY display_order ASC";
$subcategories = mysqli_query($conn, $subcategories_query);

// Fetch brands from pro_brands table
$brands_query = "SELECT * FROM `pro_brands` WHERE `status` = 1 ORDER BY `brand_name` ASC";
$brands = mysqli_query($conn, $brands_query);

// Predefined options
$colors_list = ['Black', 'White', 'Red', 'Blue', 'Green', 'Yellow', 'Purple', 'Pink', 'Orange', 'Brown', 'Grey', 'Navy', 'Maroon', 'Beige', 'Cream', 'Multi-color'];
$sizes_clothing = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '28', '30', '32', '34', '36', '38', '40', '42'];
$sizes_shoes = ['6', '7', '8', '9', '10', '11', '12', '13'];
$material_options = ['Cotton', 'Polyester', 'Denim', 'Leather', 'Suede', 'Wool', 'Silk', 'Linen', 'Nylon', 'Spandex', 'Canvas'];
$fit_options = ['Regular', 'Slim', 'Loose', 'Athletic', 'Relaxed'];
$season_options = ['All Season', 'Summer', 'Winter', 'Spring', 'Fall'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Edit Product | Beastline Clothing & Shoes</title>
    <link rel="icon" href="<?php echo htmlspecialchars($setting->get('favicon', 'assets/img/logo.png')); ?>" type="image/png">
    <?php include "links.php"; ?>

    <!-- Include CKEditor -->
    <script src="https://cdn.ckeditor.com/4.21.0/standard/ckeditor.js"></script>
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

        <div class="main_content_iner">
            <div class="container-fluid p-0 sm_padding_15px">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="white_card card_height_100 mb_30">
                            <div class="white_card_header">
                                <div class="box_header m-0">
                                    <div class="main-title">
                                        <h2 class="m-0">Edit Product</h2>
                                        <p class="text-muted mb-0">Update product details for your clothing or shoe item</p>
                                    </div>
                                    <div class="action-buttons">
                                        <a href="<?= BASE_URL ?>product-details/<?= $product['slug_url'] ?>" class="btn btn-outline-info btn-sm">
                                            <i class="fas fa-eye me-2"></i>View
                                        </a>
                                        <a href="products.php" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-arrow-left me-2"></i>Back to List
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="white_card_body">
                                <form id="productForm" action="" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="pro_id" value="<?= $product['pro_id'] ?>">

                                    <!-- Tab Navigation -->
                                    <ul class="nav nav-tabs mb-3" id="productTab" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab">Basic Info</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="pricing-tab" data-bs-toggle="tab" data-bs-target="#pricing" type="button" role="tab">Pricing & Inventory</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="attributes-tab" data-bs-toggle="tab" data-bs-target="#attributes" type="button" role="tab">Attributes</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="variants-tab" data-bs-toggle="tab" data-bs-target="#variants" type="button" role="tab">Variants</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="media-tab" data-bs-toggle="tab" data-bs-target="#media" type="button" role="tab">Media</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="seo-tab" data-bs-toggle="tab" data-bs-target="#seo" type="button" role="tab">SEO & Status</button>
                                        </li>
                                    </ul>

                                    <div class="tab-content" id="productTabContent">

                                        <!-- Basic Information Tab -->
                                        <div class="tab-pane fade show active" id="basic" role="tabpanel" aria-labelledby="basic-tab">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Product Name *</label>
                                                    <input type="text" class="form-control" name="pro_name" required value="<?= htmlspecialchars($product['pro_name']) ?>">
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">SKU *</label>
                                                    <input type="text" class="form-control" name="sku" required value="<?= htmlspecialchars($product['sku'] ?? '') ?>">
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Brand *</label>
                                                    <select class="form-control" name="brand_name" required>
                                                        <option value="">Select Brand</option>
                                                        <?php while ($brand = mysqli_fetch_assoc($brands)): ?>
                                                            <option value="<?= $brand['id'] ?>" <?= $brand['id'] == $product['brand_name'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($brand['brand_name']) ?>
                                                            </option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Product Type *</label>
                                                    <select class="form-control" name="product_type" id="productType" required>
                                                        <option value="">Select Type</option>
                                                        <option value="clothing" <?= ($product['product_type'] ?? '') == 'clothing' ? 'selected' : '' ?>>Clothing</option>
                                                        <option value="shoes" <?= ($product['product_type'] ?? '') == 'shoes' ? 'selected' : '' ?>>Shoes</option>
                                                        <option value="accessories" <?= ($product['product_type'] ?? '') == 'accessories' ? 'selected' : '' ?>>Accessories</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Category *</label>
                                                    <select class="form-control" name="pro_cate" id="mainCategory" required onchange="getSubcategories(this.value)">
                                                        <option value="">Select Category</option>
                                                        <?php while ($category = mysqli_fetch_assoc($categories)): ?>
                                                            <option value="<?= $category['id'] ?>" <?= $category['id'] == $product['pro_cate'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($category['categories']) ?>
                                                            </option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Sub Category</label>
                                                    <select class="form-control" name="pro_sub_cate" id="subCategory">
                                                        <option value="">Select Sub Category</option>
                                                        <?php while ($subcat = mysqli_fetch_assoc($subcategories)): ?>
                                                            <option value="<?= $subcat['id'] ?>" <?= $subcat['id'] == $product['pro_sub_cate'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($subcat['categories']) ?>
                                                            </option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>

                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">Short Description *</label>
                                                    <textarea class="form-control" name="short_desc" id="short_desc" rows="3" required><?= htmlspecialchars($product['short_desc']) ?></textarea>
                                                </div>

                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">Full Description *</label>
                                                    <textarea class="form-control" name="pro_desc" id="pro_desc" rows="6" required><?= htmlspecialchars($product['description']) ?></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Pricing & Inventory Tab -->
                                        <div class="tab-pane fade" id="pricing" role="tabpanel" aria-labelledby="pricing-tab">
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">MRP *</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">₹</span>
                                                        <input type="number" class="form-control" name="mrp" step="0.01" required value="<?= $product['mrp'] ?>">
                                                    </div>
                                                </div>

                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Selling Price *</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">₹</span>
                                                        <input type="number" class="form-control" name="selling_price" step="0.01" required value="<?= $product['selling_price'] ?>">
                                                    </div>
                                                </div>

                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Wholesale Price</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">₹</span>
                                                        <input type="number" class="form-control" name="whole_sale_selling_price" step="0.01" value="<?= $product['whole_sale_selling_price'] ?>">
                                                    </div>
                                                </div>

                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Weight (kg)</label>
                                                    <input type="number" class="form-control" name="weight" step="0.01" value="<?= $product['weight'] ?? 0 ?>">
                                                </div>

                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Stock Status *</label>
                                                    <input type="number" class="form-control" name="stock" value="<?= $product['stock'] ?? 0 ?>">
                                                    <!-- <select class="form-control" name="stock" required>
                                                        <option value="in_stock" <?= $product['stock'] == 'in_stock' ? 'selected' : '' ?>>In Stock</option>
                                                        <option value="low_stock" <?= $product['stock'] == 'low_stock' ? 'selected' : '' ?>>Low Stock</option>
                                                        <option value="out_of_stock" <?= $product['stock'] == 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                                                    </select> -->
                                                </div>

                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Quantity</label>
                                                    <input type="number" class="form-control" name="qty" min="0" value="<?= $product['qty'] ?>">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Attributes Tab -->
                                        <div class="tab-pane fade" id="attributes" role="tabpanel" aria-labelledby="attributes-tab">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Available Colors</label>
                                                    <div class="checkbox-group" id="colorOptions">
                                                        <?php foreach ($colors_list as $color): ?>
                                                            <label class="form-check form-check-inline">
                                                                <input class="form-check-input" type="checkbox" name="colors[]" value="<?= $color ?>">
                                                                <span class="form-check-label"><?= $color ?></span>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Available Sizes</label>
                                                    <div id="sizeOptionsContainer">
                                                        <!-- Sizes will be loaded based on product type -->
                                                    </div>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Material/Fabric</label>
                                                    <select class="form-control" name="material">
                                                        <option value="">Select Material</option>
                                                        <?php foreach ($material_options as $material): ?>
                                                            <option value="<?= $material ?>" <?= ($product['material'] ?? '') == $material ? 'selected' : '' ?>><?= $material ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Fit Type</label>
                                                    <select class="form-control" name="fit_type">
                                                        <option value="">Select Fit</option>
                                                        <?php foreach ($fit_options as $fit): ?>
                                                            <option value="<?= $fit ?>" <?= ($product['fit_type'] ?? '') == $fit ? 'selected' : '' ?>><?= $fit ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Season</label>
                                                    <select class="form-control" name="season">
                                                        <option value="">Select Season</option>
                                                        <?php foreach ($season_options as $season): ?>
                                                            <option value="<?= $season ?>" <?= ($product['season'] ?? '') == $season ? 'selected' : '' ?>><?= $season ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Care Instructions</label>
                                                    <input type="text" class="form-control" name="care_instructions" value="<?= htmlspecialchars($product['care_instructions'] ?? '') ?>">
                                                </div>

                                                <div class="col-12 mb-3">
                                                    <label class="form-label">Additional Attributes</label>
                                                    <div class="input-group mb-2">
                                                        <input type="text" class="form-control" id="attrName" placeholder="Attribute name">
                                                        <input type="text" class="form-control" id="attrValue" placeholder="Attribute value">
                                                        <button type="button" class="btn btn-outline-primary" onclick="addAttribute()">Add</button>
                                                    </div>
                                                    <div class="attribute-tags" id="attributeTags">
                                                        <?php foreach ($attributes as $index => $attr): ?>
                                                            <span class="badge bg-light text-dark p-2 me-2 mb-2">
                                                                <?= htmlspecialchars($attr['name']) ?>: <?= htmlspecialchars($attr['value']) ?>
                                                                <button type="button" class="btn-close ms-2" onclick="removeAttribute(<?= $index ?>)"></button>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <input type="hidden" name="attributes_json" id="attributesJson" value='<?= json_encode($attributes) ?>'>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Variants Tab -->
                                        <div class="tab-pane fade" id="variants" role="tabpanel" aria-labelledby="variants-tab">
                                            <div class="row">
                                                <div class="col-12 mb-3">
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-info-circle me-2"></i>
                                                        Select colors and sizes in Attributes tab, then click "Generate Variants"
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <button type="button" class="btn btn-success mb-3" onclick="generateVariants()">
                                                        <i class="fas fa-sync-alt me-2"></i> Generate Variants
                                                    </button>

                                                    <div id="variantsContainer">
                                                        <?php if (!empty($variants)): ?>
                                                            <div class="table-responsive">
                                                                <table class="table table-bordered">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Color</th>
                                                                            <th>Size</th>
                                                                            <th>SKU</th>
                                                                            <th>Price</th>
                                                                            <th>Quantity</th>
                                                                            <th>Image</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php foreach ($variants as $index => $variant): ?>
                                                                            <tr>
                                                                                <td>
                                                                                    <input type="hidden" name="variants[<?= $index ?>][db_id]" value="<?= intval($variant['db_id'] ?? 0) ?>">

                                                                                    <input type="hidden" name="variants[<?= $index ?>][color]"
                                                                                        value="<?= htmlspecialchars($variant['color']) ?>">
                                                                                    <?= htmlspecialchars($variant['color']) ?>
                                                                                </td>
                                                                                <td>
                                                                                    <input type="hidden" name="variants[<?= $index ?>][size]"
                                                                                        value="<?= htmlspecialchars($variant['size']) ?>">
                                                                                    <?= htmlspecialchars($variant['size']) ?>
                                                                                </td>
                                                                                <td>
                                                                                    <input type="text" class="form-control form-control-sm"
                                                                                        name="variants[<?= $index ?>][sku]"
                                                                                        value="<?= htmlspecialchars($variant['sku']) ?>">
                                                                                </td>
                                                                                <td>
                                                                                    <input type="number" class="form-control form-control-sm"
                                                                                        name="variants[<?= $index ?>][price]"
                                                                                        value="<?= $variant['price'] ?>" step="0.01">
                                                                                </td>
                                                                                <td>
                                                                                    <input type="number" class="form-control form-control-sm"
                                                                                        name="variants[<?= $index ?>][quantity]"
                                                                                        value="<?= $variant['quantity'] ?>" min="0">
                                                                                </td>
                                                                                <td>
                                                                                    <?php if (!empty($variant['image'])): ?>
                                                                                        <div class="mb-1">
                                                                                            <img src="assets/img/uploads/variants/<?= htmlspecialchars($variant['image']) ?>"
                                                                                                style="width: 50px; height: 50px; object-fit: cover;"
                                                                                                class="img-thumbnail">
                                                                                            <input type="hidden"
                                                                                                name="variants[<?= $index ?>][existing_image]"
                                                                                                value="<?= htmlspecialchars($variant['image']) ?>">
                                                                                            <small class="d-block">Current image</small>
                                                                                        </div>
                                                                                    <?php endif; ?>
                                                                                    <input type="file" class="form-control form-control-sm"
                                                                                        name="variant_images[<?= $index ?>]" accept="image/*">
                                                                                </td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        <?php else: ?>
                                                            <p class="text-muted">No variants generated yet.</p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <input type="hidden" name="variants_json" id="variantsJson" value='<?= json_encode($variants) ?>'>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Media Tab -->
                                        <div class="tab-pane fade" id="media" role="tabpanel" aria-labelledby="media-tab">
                                            <div class="row">
                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">Main Product Image</label><br>
                                                    <small>Image size 600 X 698px</small>
                                                    <?php if (!empty($main_image)): ?>
                                                        <div class="mb-2">
                                                            <img src="assets/img/uploads/<?= htmlspecialchars($main_image) ?>"
                                                                alt="Current Image" style="max-width: 200px;" class="img-thumbnail">
                                                        </div>
                                                    <?php endif; ?>
                                                    <input type="file" class="form-control" name="main_image" accept="image/*">
                                                </div>

                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">Additional Images</label><br>
                                                    <small>Image size 600 X 698px</small>
                                                    <?php if (!empty($additional_images)): ?>
                                                        <div class="mb-2">
                                                            <?php foreach ($additional_images as $img): ?>
                                                                <img src="assets/img/uploads/<?= htmlspecialchars($img) ?>"
                                                                    alt="Additional Image" style="width: 100px; height: 100px; object-fit: cover;" class="img-thumbnail me-2 mb-2">
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <input type="file" class="form-control" name="additional_images[]" accept="image/*" multiple>
                                                </div>

                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">Product Video URL</label>
                                                    <input type="url" class="form-control" name="video_url" value="<?= htmlspecialchars($product['video_url'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- SEO & Status Tab -->
                                        <div class="tab-pane fade" id="seo" role="tabpanel" aria-labelledby="seo-tab">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Meta Title</label>
                                                    <input type="text" class="form-control" name="meta_title" value="<?= htmlspecialchars($product['meta_title']) ?>">
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Meta Keywords</label>
                                                    <input type="text" class="form-control" name="meta_key" value="<?= htmlspecialchars($product['meta_key']) ?>">
                                                </div>

                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">Meta Description</label>
                                                    <textarea class="form-control" name="meta_desc" rows="3"><?= htmlspecialchars($product['meta_desc']) ?></textarea>
                                                </div>

                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">Product Tags</label>
                                                    <input type="text" class="form-control" name="tags" value="<?= htmlspecialchars($product['tags'] ?? '') ?>">
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Special Tags</label>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="new_arrival" value="1" <?= $product['new_arrival'] == '1' ? 'checked' : '' ?>>
                                                        <label class="form-check-label">New Arrival</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="trending" value="1" <?= $product['trending'] == '1' ? 'checked' : '' ?>>
                                                        <label class="form-check-label">Trending</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="is_deal" value="1" <?= $product['is_deal'] == '1' ? 'checked' : '' ?>>
                                                        <label class="form-check-label">Special Deal</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="deal_of_the_day" value="1" <?= $product['deal_of_the_day'] == '1' ? 'checked' : '' ?>>
                                                        <label class="form-check-label">Deal of the Day</label>
                                                    </div>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Status *</label>
                                                    <select class="form-control" name="status" required>
                                                        <option value="1" <?= $product['status'] == '1' ? 'selected' : '' ?>>Active</option>
                                                        <option value="0" <?= $product['status'] == '0' ? 'selected' : '' ?>>Inactive</option>
                                                        <option value="2" <?= $product['status'] == '2' ? 'selected' : '' ?>>Draft</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mt-4">
                                        <div class="col-12">
                                            <button type="submit" name="update-product" class="btn btn-primary px-5">
                                                <i class="fas fa-save me-2"></i> Update Product
                                            </button>
                                            <a href="products.php" class="btn btn-outline-secondary px-5">
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

        <?php include "includes/footer.php"; ?>
    </section>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Color Picker -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-colorpicker/3.4.0/js/bootstrap-colorpicker.min.js"></script>
    <!-- JavaScript -->

    <script>
        // Initialize CKEditor
        document.addEventListener('DOMContentLoaded', function() {
            CKEDITOR.replace('short_desc');
            CKEDITOR.replace('pro_desc');

            // Load size options based on current product type
            const productType = document.getElementById('productType').value;
            if (productType) {
                loadSizeOptions(productType);
            }
        });

        // Function to get subcategories
        function getSubcategories(categoryId) {
            if (!categoryId) {
                document.getElementById('subCategory').innerHTML = '<option value="">Select Sub Category</option>';
                return;
            }

            fetch('functions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_sub_category_by_id&category_id=' + categoryId
                })
                .then(response => response.text())
                .then(data => {
                    document.getElementById('subCategory').innerHTML = data;
                })
                .catch(error => console.error('Error:', error));
        }

        // Load size options based on product type
        function loadSizeOptions(productType) {
            const container = document.getElementById('sizeOptionsContainer');
            let sizeOptions = '';

            if (productType === 'clothing') {
                <?php foreach ($sizes_clothing as $size): ?>
                    sizeOptions += `
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="sizes[]" value="<?= $size ?>">
                            <span class="form-check-label"><?= $size ?></span>
                        </div>`;
                <?php endforeach; ?>
            } else if (productType === 'shoes') {
                <?php foreach ($sizes_shoes as $size): ?>
                    sizeOptions += `
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="sizes[]" value="<?= $size ?>">
                            <span class="form-check-label"><?= $size ?></span>
                        </div>`;
                <?php endforeach; ?>
            }

            container.innerHTML = sizeOptions;
        }

        // Event listener for product type change
        document.getElementById('productType').addEventListener('change', function() {
            loadSizeOptions(this.value);
        });

        // Attributes management
        let attributes = <?= json_encode($attributes) ?>;

        function addAttribute() {
            const name = document.getElementById('attrName').value.trim();
            const value = document.getElementById('attrValue').value.trim();

            if (name && value) {
                attributes.push({
                    name,
                    value
                });
                updateAttributeTags();
                document.getElementById('attrName').value = '';
                document.getElementById('attrValue').value = '';
            }
        }

        function updateAttributeTags() {
            const container = document.getElementById('attributeTags');
            container.innerHTML = '';

            attributes.forEach((attr, index) => {
                const tag = document.createElement('span');
                tag.className = 'badge bg-light text-dark p-2 me-2 mb-2';
                tag.innerHTML = `
                    ${attr.name}: ${attr.value}
                    <button type="button" class="btn-close ms-2" onclick="removeAttribute(${index})"></button>
                `;
                container.appendChild(tag);
            });

            document.getElementById('attributesJson').value = JSON.stringify(attributes);
        }

        function removeAttribute(index) {
            attributes.splice(index, 1);
            updateAttributeTags();
        }

        // Generate variants
        function generateVariants() {
            const selectedColors = Array.from(document.querySelectorAll('input[name="colors[]"]:checked'))
                .map(cb => cb.value);
            const selectedSizes = Array.from(document.querySelectorAll('input[name="sizes[]"]:checked'))
                .map(cb => cb.value);

            if (selectedColors.length === 0 || selectedSizes.length === 0) {
                alert('Please select at least one color and one size in Attributes tab');
                return;
            }

            const variants = [];
            let index = 0;

            selectedColors.forEach(color => {
                selectedSizes.forEach(size => {
                    // Generate SKU based on product SKU, color and size
                    const baseSku = document.querySelector('input[name="sku"]').value;
                    const variantSku = `${baseSku}-${color.substring(0, 3).toUpperCase()}-${size.toUpperCase()}`;

                    variants.push({
                        id: index,
                        color: color,
                        size: size,
                        sku: variantSku,
                        price: document.querySelector('input[name="selling_price"]').value || 0,
                        compare_at_price: document.querySelector('input[name="mrp"]').value || 0,
                        quantity: document.querySelector('input[name="qty"]').value || 0,
                        existing_image: '' // For existing images on edit
                    });

                    index++;
                });
            });

            // Update variants JSON
            document.getElementById('variantsJson').value = JSON.stringify(variants);

            // Generate HTML table
            let html = `
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Color</th>
                        <th>Size</th>
                        <th>SKU</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Image</th>
                    </tr>
                </thead>
                <tbody>`;

            variants.forEach((variant, idx) => {
                html += `
            <tr>
                <td>
                    <input type="hidden" name="variants[${idx}][color]" value="${variant.color}">
                    ${variant.color}
                </td>
                <td>
                    <input type="hidden" name="variants[${idx}][size]" value="${variant.size}">
                    ${variant.size}
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" 
                           name="variants[${idx}][sku]" value="${variant.sku}">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm" 
                           name="variants[${idx}][price]" value="${variant.price}" step="0.01">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm" 
                           name="variants[${idx}][quantity]" value="${variant.quantity}" min="0">
                </td>
                <td>
                    <input type="file" class="form-control form-control-sm" 
                           name="variant_images[${idx}]" accept="image/*">
                </td>
            </tr>`;
            });

            html += `</tbody></table></div>`;

            document.getElementById('variantsContainer').innerHTML = html;
        }

        // Form validation
        document.getElementById('productForm').addEventListener('submit', function(e) {
            const productName = document.querySelector('input[name="pro_name"]').value.trim();
            const sku = document.querySelector('input[name="sku"]').value.trim();
            const sellingPrice = parseFloat(document.querySelector('input[name="selling_price"]').value);
            const mrp = parseFloat(document.querySelector('input[name="mrp"]').value);

            if (!productName) {
                alert('Product name is required');
                e.preventDefault();
                return false;
            }

            if (!sku) {
                alert('SKU is required');
                e.preventDefault();
                return false;
            }

            if (sellingPrice > mrp) {
                if (!confirm('Selling price is higher than MRP. Continue anyway?')) {
                    e.preventDefault();
                    return false;
                }
            }

            return true;
        });
    </script>
</body>

</html>