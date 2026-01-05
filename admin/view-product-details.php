<?php
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';
require_once __DIR__ . '/models/Setting.php';

// Initialize
$setting = new Setting($conn);

// Get product ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$product_id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch product details with joins for category and brand
$sql = "SELECT p.*, 
               c.categories as category_name,
               sc.categories as subcategory_name,
               b.brand_name as brand_display,
               p.added_on as created_at
        FROM products p
        LEFT JOIN categories c ON p.pro_cate = c.cate_id
        LEFT JOIN categories sc ON p.pro_sub_cate = sc.cate_id
        LEFT JOIN pro_brands b ON p.brand_name = b.id
        WHERE p.pro_id = '$product_id' LIMIT 1";

$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header("Location: products.php");
    exit();
}

$product = mysqli_fetch_assoc($result);

// Parse product images
$main_images = !empty($product['pro_img']) ? explode(",", $product['pro_img']) : [];
$additional_images = !empty($product['additional_images']) ? json_decode($product['additional_images'], true) : [];

// Parse variants if exists
$variants = !empty($product['variants_json']) ? json_decode($product['variants_json'], true) : [];
$attributes = !empty($product['attributes_json']) ? json_decode($product['attributes_json'], true) : [];

// Calculate discount percentage
$discount = 0;
if ($product['mrp'] > 0 && $product['selling_price'] > 0) {
    $discount = round((($product['mrp'] - $product['selling_price']) / $product['mrp']) * 100);
}

// Format dates
$created_at = date('d M Y, h:i A', strtotime($product['created_at']));
$updated_at = !empty($product['updated_on']) ? date('d M Y, h:i A', strtotime($product['updated_on'])) : 'Not updated yet';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title><?= htmlspecialchars($product['pro_name']) ?> | Product Details</title>
    <link rel="icon" href="<?php echo htmlspecialchars($setting->get('favicon', 'assets/img/logo.png')); ?>" type="image/png">
    <?php include "links.php"; ?>
    
    <!-- Include Slick Carousel for image gallery -->
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css"/>
    
    <style>
        .white_card {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .product-header {
            border-bottom: 1px solid #eaeaea;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .product-title {
            font-size: 24px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .product-subtitle {
            color: #6c757d;
            font-size: 14px;
        }
        
        .product-sku {
            background: #f8f9fa;
            padding: 8px 15px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 14px;
            display: inline-block;
        }
        
        .image-gallery {
            border: 1px solid #eaeaea;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }
        
        .main-image {
            width: 100%;
            height: 400px;
            object-fit: contain;
            background: #f8f9fa;
            padding: 20px;
        }
        
        .thumbnails {
            display: flex;
            gap: 10px;
            padding: 15px;
            background: #f8f9fa;
            border-top: 1px solid #eaeaea;
            overflow-x: auto;
        }
        
        .thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .thumbnail:hover,
        .thumbnail.active {
            border-color: #007bff;
            transform: scale(1.05);
        }
        
        .detail-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .detail-title {
            font-weight: 600;
            color: #495057;
            margin-bottom: 15px;
            font-size: 16px;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 8px;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: #6c757d;
            font-weight: 500;
        }
        
        .detail-value {
            color: #495057;
            font-weight: 500;
        }
        
        .price-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .original-price {
            font-size: 18px;
            text-decoration: line-through;
            opacity: 0.8;
        }
        
        .selling-price {
            font-size: 32px;
            font-weight: 700;
            margin: 10px 0;
        }
        
        .discount-badge {
            background: #ff6b6b;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        
        .status-active {
            background-color: #d1f7c4;
            color: #0c831f;
        }
        
        .status-inactive {
            background-color: #ffeaea;
            color: #ff5252;
        }
        
        .stock-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 500;
            display: inline-block;
        }
        
        .stock-in-stock {
            background-color: #d1f7c4;
            color: #0c831f;
        }
        
        .stock-low {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .stock-out {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .tag-badge {
            background: #e9ecef;
            color: #495057;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            display: inline-block;
            margin: 2px;
        }
        
        .variant-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            background: #fff;
        }
        
        .variant-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .variant-sku {
            font-family: monospace;
            font-size: 12px;
            background: #f8f9fa;
            padding: 3px 8px;
            border-radius: 4px;
        }
        
        .description-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            line-height: 1.6;
        }
        
        .description-box h5 {
            color: #495057;
            margin-bottom: 15px;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 8px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-print {
            background: #6c757d;
            color: white;
            border: none;
        }
        
        .btn-print:hover {
            background: #5a6268;
            color: white;
        }
        
        .meta-section {
            background: #e9ecef;
            border-radius: 8px;
            padding: 15px;
            font-size: 12px;
            color: #6c757d;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .main-image {
                height: 300px;
            }
            
            .thumbnail {
                width: 60px;
                height: 60px;
            }
            
            .selling-price {
                font-size: 24px;
            }
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
            <div class="container-fluid p-0">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <div class="white_card">
                            <!-- Product Header -->
                            <div class="product-header">
                                <div class="d-flex justify-content-between align-items-start flex-wrap">
                                    <div>
                                        <h1 class="product-title"><?= htmlspecialchars($product['pro_name']) ?></h1>
                                        <p class="product-subtitle mb-2">
                                            <span class="product-sku">SKU: <?= htmlspecialchars($product['sku'] ?? 'N/A') ?></span>
                                            <span class="ms-3">Product ID: <?= htmlspecialchars($product['pro_id']) ?></span>
                                        </p>
                                        <div class="d-flex align-items-center gap-2 mt-2">
                                            <span class="status-badge <?= $product['status'] == '1' ? 'status-active' : 'status-inactive' ?>">
                                                <?= $product['status'] == '1' ? 'Active' : ($product['status'] == '2' ? 'Draft' : 'Inactive') ?>
                                            </span>
                                            <span class="stock-badge <?= 
                                                $product['stock'] == 'in_stock' ? 'stock-in-stock' : 
                                                ($product['stock'] == 'low_stock' ? 'stock-low' : 'stock-out') 
                                            ?>">
                                                <?= ucfirst(str_replace('_', ' ', $product['stock'])) ?>
                                            </span>
                                            <?php if($product['new_arrival'] == '1'): ?>
                                                <span class="tag-badge" style="background:#7367f0;color:white;">New Arrival</span>
                                            <?php endif; ?>
                                            <?php if($product['trending'] == '1'): ?>
                                                <span class="tag-badge" style="background:#28c76f;color:white;">Trending</span>
                                            <?php endif; ?>
                                            <?php if($product['is_deal'] == '1'): ?>
                                                <span class="tag-badge" style="background:#ff9f43;color:white;">Special Deal</span>
                                            <?php endif; ?>
                                            <?php if($product['deal_of_the_day'] == '1'): ?>
                                                <span class="tag-badge" style="background:#ea5455;color:white;">Deal of the Day</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="action-buttons">
                                        <a href="edit_products.php?edit_product_details=<?= $product['pro_id'] ?>" 
                                           class="btn btn-primary">
                                            <i class="fas fa-edit me-2"></i>Edit Product
                                        </a>
                                        <a href="view-products.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-left me-2"></i>Back to List
                                        </a>
                                        <button onclick="window.print()" class="btn btn-print">
                                            <i class="fas fa-print me-2"></i>Print
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Left Column: Product Images -->
                                <div class="col-lg-5 mb-4">
                                    <div class="image-gallery">
                                        <!-- Main Image -->
                                        <div id="mainImageContainer">
                                            <?php if (!empty($main_images[0])): ?>
                                                <img src="assets/img/uploads/<?= htmlspecialchars($main_images[0]) ?>" 
                                                     alt="<?= htmlspecialchars($product['pro_name']) ?>" 
                                                     class="main-image" id="mainImage">
                                            <?php else: ?>
                                                <div class="main-image d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-image fa-4x text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Thumbnails -->
                                        <?php if (count($main_images) > 1 || count($additional_images) > 0): ?>
                                            <div class="thumbnails">
                                                <?php foreach ($main_images as $index => $image): ?>
                                                    <img src="assets/img/uploads/<?= htmlspecialchars($image) ?>" 
                                                         alt="Product Image <?= $index + 1 ?>"
                                                         class="thumbnail <?= $index == 0 ? 'active' : '' ?>"
                                                         data-image="assets/img/uploads/<?= htmlspecialchars($image) ?>"
                                                         onclick="changeMainImage(this)">
                                                <?php endforeach; ?>
                                                
                                                <?php if (!empty($additional_images)): ?>
                                                    <?php foreach ($additional_images as $image): ?>
                                                        <img src="<?= htmlspecialchars($image) ?>" 
                                                             alt="Additional Image"
                                                             class="thumbnail"
                                                             data-image="<?= htmlspecialchars($image) ?>"
                                                             onclick="changeMainImage(this)">
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Product Description -->
                                    <?php if (!empty($product['description'])): ?>
                                        <div class="description-box mt-4">
                                            <h5>Product Description</h5>
                                            <?= htmlspecialchars_decode($product['description']) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Short Description -->
                                    <?php if (!empty($product['short_desc'])): ?>
                                        <div class="description-box mt-4">
                                            <h5>Short Description</h5>
                                            <?= htmlspecialchars_decode($product['short_desc']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Right Column: Product Details -->
                                <div class="col-lg-7">
                                    <!-- Price Section -->
                                    <div class="price-section">
                                        <div class="original-price">MRP: ₹<?= number_format($product['mrp'], 2) ?></div>
                                        <div class="selling-price">₹<?= number_format($product['selling_price'], 2) ?></div>
                                        <?php if ($discount > 0): ?>
                                            <div class="discount-badge">Save <?= $discount ?>%</div>
                                        <?php endif; ?>
                                        <?php if ($product['whole_sale_selling_price'] > 0): ?>
                                            <div class="mt-3">
                                                <small>Wholesale Price: ₹<?= number_format($product['whole_sale_selling_price'], 2) ?></small>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="row">
                                        <!-- Basic Information -->
                                        <div class="col-md-6 mb-4">
                                            <div class="detail-card">
                                                <div class="detail-title">Basic Information</div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Brand</span>
                                                    <span class="detail-value"><?= htmlspecialchars($product['brand_display'] ?? 'N/A') ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Category</span>
                                                    <span class="detail-value"><?= htmlspecialchars($product['category_name'] ?? 'N/A') ?></span>
                                                </div>
                                                <?php if (!empty($product['subcategory_name'])): ?>
                                                    <div class="detail-item">
                                                        <span class="detail-label">Sub Category</span>
                                                        <span class="detail-value"><?= htmlspecialchars($product['subcategory_name']) ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="detail-item">
                                                    <span class="detail-label">Product Type</span>
                                                    <span class="detail-value"><?= htmlspecialchars($product['product_type'] ?? 'N/A') ?></span>
                                                </div>
                                                <?php if (!empty($product['material'])): ?>
                                                    <div class="detail-item">
                                                        <span class="detail-label">Material</span>
                                                        <span class="detail-value"><?= htmlspecialchars($product['material']) ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!empty($product['season'])): ?>
                                                    <div class="detail-item">
                                                        <span class="detail-label">Season</span>
                                                        <span class="detail-value"><?= htmlspecialchars($product['season']) ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Inventory & Stock -->
                                        <div class="col-md-6 mb-4">
                                            <div class="detail-card">
                                                <div class="detail-title">Inventory & Stock</div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Stock Quantity</span>
                                                    <span class="detail-value"><?= number_format($product['qty'] ?? 0) ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Stock Status</span>
                                                    <span class="detail-value">
                                                        <span class="stock-badge <?= 
                                                            $product['stock'] == 'in_stock' ? 'stock-in-stock' : 
                                                            ($product['stock'] == 'low_stock' ? 'stock-low' : 'stock-out') 
                                                        ?>">
                                                            <?= ucfirst(str_replace('_', ' ', $product['stock'])) ?>
                                                        </span>
                                                    </span>
                                                </div>
                                                <?php if (!empty($product['weight'])): ?>
                                                    <div class="detail-item">
                                                        <span class="detail-label">Weight</span>
                                                        <span class="detail-value"><?= number_format($product['weight'], 2) ?> kg</span>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!empty($product['care_instructions'])): ?>
                                                    <div class="detail-item">
                                                        <span class="detail-label">Care Instructions</span>
                                                        <span class="detail-value"><?= htmlspecialchars($product['care_instructions']) ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Product Attributes -->
                                        <?php if (!empty($attributes)): ?>
                                            <div class="col-12 mb-4">
                                                <div class="detail-card">
                                                    <div class="detail-title">Product Attributes</div>
                                                    <div class="row">
                                                        <?php foreach ($attributes as $attr): ?>
                                                            <div class="col-md-6">
                                                                <div class="detail-item">
                                                                    <span class="detail-label"><?= htmlspecialchars($attr['name']) ?></span>
                                                                    <span class="detail-value"><?= htmlspecialchars($attr['value']) ?></span>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Color & Size Options -->
                                        <?php if (!empty($product['color_options']) || !empty($product['size_options'])): ?>
                                            <div class="col-12 mb-4">
                                                <div class="detail-card">
                                                    <div class="detail-title">Available Options</div>
                                                    <?php if (!empty($product['color_options'])): ?>
                                                        <?php $colors = json_decode($product['color_options'], true); ?>
                                                        <div class="mb-3">
                                                            <div class="detail-label mb-2">Colors:</div>
                                                            <div class="d-flex flex-wrap gap-2">
                                                                <?php foreach ($colors as $color): ?>
                                                                    <span class="tag-badge"><?= htmlspecialchars($color) ?></span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($product['size_options'])): ?>
                                                        <?php $sizes = json_decode($product['size_options'], true); ?>
                                                        <div>
                                                            <div class="detail-label mb-2">Sizes:</div>
                                                            <div class="d-flex flex-wrap gap-2">
                                                                <?php foreach ($sizes as $size): ?>
                                                                    <span class="tag-badge"><?= htmlspecialchars($size) ?></span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Product Variants -->
                                        <?php if (!empty($variants)): ?>
                                            <div class="col-12 mb-4">
                                                <div class="detail-card">
                                                    <div class="detail-title">Product Variants</div>
                                                    <div class="row">
                                                        <?php foreach ($variants as $variant): ?>
                                                            <div class="col-md-6 mb-3">
                                                                <div class="variant-card">
                                                                    <div class="variant-header">
                                                                        <div>
                                                                            <strong><?= htmlspecialchars($variant['color'] ?? 'N/A') ?></strong>
                                                                            <span class="ms-2">Size: <?= htmlspecialchars($variant['size'] ?? 'N/A') ?></span>
                                                                        </div>
                                                                        <span class="variant-sku"><?= htmlspecialchars($variant['sku'] ?? 'N/A') ?></span>
                                                                    </div>
                                                                    <div class="detail-item">
                                                                        <span class="detail-label">Price</span>
                                                                        <span class="detail-value">₹<?= number_format($variant['price'] ?? 0, 2) ?></span>
                                                                    </div>
                                                                    <div class="detail-item">
                                                                        <span class="detail-label">Quantity</span>
                                                                        <span class="detail-value"><?= number_format($variant['quantity'] ?? 0) ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- SEO Information -->
                                        <div class="col-12 mb-4">
                                            <div class="detail-card">
                                                <div class="detail-title">SEO Information</div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Meta Title</span>
                                                    <span class="detail-value"><?= htmlspecialchars($product['meta_title'] ?? 'N/A') ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Meta Description</span>
                                                    <span class="detail-value"><?= htmlspecialchars($product['meta_desc'] ?? 'N/A') ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Meta Keywords</span>
                                                    <span class="detail-value"><?= htmlspecialchars($product['meta_key'] ?? 'N/A') ?></span>
                                                </div>
                                                <?php if (!empty($product['slug_url'])): ?>
                                                    <div class="detail-item">
                                                        <span class="detail-label">Slug URL</span>
                                                        <span class="detail-value">
                                                            <a href="<?= htmlspecialchars($product['slug_url']) ?>" target="_blank">
                                                                View Product Page
                                                            </a>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Meta Information -->
                                        <div class="col-12">
                                            <div class="meta-section">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="detail-item">
                                                            <span class="detail-label">Created On</span>
                                                            <span class="detail-value"><?= $created_at ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="detail-item">
                                                            <span class="detail-label">Last Updated</span>
                                                            <span class="detail-value"><?= $updated_at ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Actions -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <!-- <a href="add-product-page-banner.php?id=<?= $product['pro_id'] ?>" 
                                               class="btn btn-outline-primary">
                                                <i class="fas fa-image me-2"></i>Manage Banner
                                            </a>
                                            <a href="multiple_img.php?id=<?= $product['pro_id'] ?>" 
                                               class="btn btn-outline-secondary ms-2">
                                                <i class="fas fa-images me-2"></i>Manage Images
                                            </a> -->
                                        </div>
                                        <div>
                                            <a href="product_delete.php?delete=<?= $product['pro_id'] ?>" 
                                               class="btn btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to delete this product?')">
                                                <i class="fas fa-trash-alt me-2"></i>Delete Product
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include "includes/footer.php"; ?>
    </section>

    <!-- Include jQuery and Slick Carousel -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js"></script>
    
    <script>
        // Image Gallery Functionality
        function changeMainImage(element) {
            const mainImage = document.getElementById('mainImage');
            const thumbnails = document.querySelectorAll('.thumbnail');
            
            // Update main image
            mainImage.src = element.getAttribute('data-image');
            
            // Update active thumbnail
            thumbnails.forEach(thumb => {
                thumb.classList.remove('active');
            });
            element.classList.add('active');
        }
        
        // Initialize Slick Carousel for thumbnails on mobile
        $(document).ready(function() {
            if ($(window).width() < 768) {
                $('.thumbnails').slick({
                    dots: false,
                    infinite: false,
                    speed: 300,
                    slidesToShow: 4,
                    slidesToScroll: 1,
                    responsive: [
                        {
                            breakpoint: 576,
                            settings: {
                                slidesToShow: 3,
                                slidesToScroll: 1
                            }
                        }
                    ]
                });
            }
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Zoom functionality for main image
            const mainImage = document.getElementById('mainImage');
            if (mainImage) {
                mainImage.addEventListener('click', function() {
                    this.classList.toggle('zoom');
                });
            }
        });
        
        // Add CSS for zoom effect
        const style = document.createElement('style');
        style.textContent = `
            .main-image.zoom {
                transform: scale(1.5);
                cursor: zoom-out;
                transition: transform 0.3s;
            }
            .main-image:not(.zoom) {
                cursor: zoom-in;
                transition: transform 0.3s;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>