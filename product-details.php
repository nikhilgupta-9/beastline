<?php
session_start();
include_once "config/connect.php";
include_once "util/function.php";

$contact = contact_us();

// Get product slug from URL
$slug = basename($_SERVER['REQUEST_URI']);
$slug = explode('?', $slug)[0]; // Remove query parameters if any

// Get product details
$product_sql = "SELECT p.*, 
                       c.categories as category_name,
                       c.slug_url as category_slug,
                       b.brand_name
                FROM products p
                LEFT JOIN categories c ON p.pro_cate = c.id
                LEFT JOIN pro_brands b ON p.brand_name = b.id
                WHERE p.slug_url = ? AND p.status = 1";

$product_stmt = $conn->prepare($product_sql);
$product_stmt->bind_param("s", $slug);
$product_stmt->execute();
$product_result = $product_stmt->get_result();

if ($product_result->num_rows == 0) {
    // Product not found
    header("Location: " . $site . "404/");
    exit();
}

$product = $product_result->fetch_assoc();
$product_id = $product['pro_id'];

// Get product images
$images_sql = "SELECT * FROM product_images 
               WHERE product_id = ? 
               ORDER BY is_main DESC, display_order ASC";
$images_stmt = $conn->prepare($images_sql);
$images_stmt->bind_param("i", $product_id);
$images_stmt->execute();
$images_result = $images_stmt->get_result();
$product_images = [];
$main_image = null;

while ($image = $images_result->fetch_assoc()) {
    if ($image['is_main'] == 1) {
        $main_image = $image;
    }
    $product_images[] = $image;
}

// If no main image found, use first image or default
if (!$main_image && count($product_images) > 0) {
    $main_image = $product_images[0];
}

// Get product variants
$variants_sql = "SELECT * FROM product_variants 
                 WHERE product_id = ? AND status = 1 
                 ORDER BY color, size";
$variants_stmt = $conn->prepare($variants_sql);
$variants_stmt->bind_param("i", $product_id);
$variants_stmt->execute();
$variants_result = $variants_stmt->get_result();
$variants = [];
$available_colors = [];
$available_sizes = [];
$variant_stock = 0;

while ($variant = $variants_result->fetch_assoc()) {
    $variants[] = $variant;

    // Collect unique colors
    if ($variant['color'] && !in_array($variant['color'], $available_colors)) {
        $available_colors[] = $variant['color'];
    }

    // Collect unique sizes
    if ($variant['size'] && !in_array($variant['size'], $available_sizes)) {
        $available_sizes[] = $variant['size'];
    }

    // Calculate total stock
    $variant_stock += $variant['quantity'];
}

// Calculate if product has variants
$has_variants = !empty($variants);
$total_stock = $has_variants ? $variant_stock : $product['stock'];


// Get upsell products with proper column names
$upsell_sql = "SELECT 
                    p.pro_id,
                    p.pro_name,
                    p.mrp,
                    p.selling_price,
                    p.slug_url,
                    p.trending,
                    p.new_arrival,
                    p.is_deal,
                    p.deal_of_the_day,
                    pi.image_url as pro_img
               FROM products p
               LEFT JOIN product_images pi ON p.pro_id = pi.product_id AND pi.is_main = 1
               WHERE (p.trending = 1 OR p.new_arrival = 1 OR p.is_deal = 1 OR p.deal_of_the_day = 1)
               AND p.pro_id != ? 
               AND p.status = 1 
               ORDER BY RAND() 
               LIMIT 6";

$upsell_stmt = $conn->prepare($upsell_sql);
$upsell_stmt->bind_param("i", $product_id);
$upsell_stmt->execute();
$upsell_result = $upsell_stmt->get_result();
$upsell_products = [];

while ($upsell = $upsell_result->fetch_assoc()) {
    // Ensure we have a valid image
    if (empty($upsell['pro_img'])) {
        $upsell['pro_img'] = 'no-image.jpg';
    }
    $upsell_products[] = $upsell;
}
$upsell_stmt->close();

// Get product reviews count and average rating
$reviews_sql = "SELECT COUNT(*) as review_count, 
                       COALESCE(AVG(rating), 0) as avg_rating 
                FROM product_reviews 
                WHERE product_id = ? AND status = 1";
$reviews_stmt = $conn->prepare($reviews_sql);
$reviews_stmt->bind_param("i", $product_id);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();
$review_stats = $reviews_result->fetch_assoc();

if (!$review_stats) {
    $review_stats = ['review_count' => 0, 'avg_rating' => 0];
}

// Get recent reviews
$recent_reviews_sql = "
SELECT 
    pr.rating,
    pr.review_message,
    pr.reviewer_name,
    pr.reviewver_img,
    pr.created_at
FROM product_reviews pr
WHERE pr.product_id = ? AND pr.status = 1
ORDER BY pr.created_at DESC
LIMIT 5
";

$recent_reviews_stmt = $conn->prepare($recent_reviews_sql);
$recent_reviews_stmt->bind_param("i", $product_id);
$recent_reviews_stmt->execute();
$recent_reviews_result = $recent_reviews_stmt->get_result();
$recent_reviews = [];

while ($review = $recent_reviews_result->fetch_assoc()) {
    $recent_reviews[] = $review;
}

// Update view count
$view_sql = "UPDATE products SET views = COALESCE(views, 0) + 1 WHERE pro_id = ?";
$view_stmt = $conn->prepare($view_sql);
$view_stmt->bind_param("i", $product_id);
$view_stmt->execute();

// Get cart count for header
$cart_count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;

// Set page title and meta
$page_title = $product['meta_title'] ?: $product['pro_name'] . " | Beastline";
$meta_description = htmlspecialchars(strip_tags($product['short_desc'] ?: $product['description']));
$meta_keywords = $product['tags'] ? $product['tags'] : $product['meta_key'];


$colorMap = [
    'red'   => '#ff0000',
    'blue'  => '#007bff',
    'green' => '#28a745',
    'black' => '#000000',
    'white' => '#ffffff',
    'grey'  => '#6c757d',
    'gray'  => '#6c757d',
    'yellow' => '#ffc107',
    'orange' => '#fd7e14',
    'pink'  => '#e83e8c',
    'purple' => '#6f42c1',
    'brown' => '#795548'
];

?>
<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($meta_description) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($meta_keywords) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="<?= $site ?>assets/img/favicon/favicon.ico">

    <!--bootstrap min css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/bootstrap.min.css">
    <!--owl carousel min css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/owl.carousel.min.css">
    <!--slick min css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/slick.css">
    <!--magnific popup min css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/magnific-popup.css">
    <!--font awesome css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/font.awesome.css">
    <!--ionicons css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/ionicons.min.css">
    <!--7 stroke icons css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/pe-icon-7-stroke.css">
    <!--animate css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/animate.css">
    <!--jquery ui min css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/jquery-ui.min.css">
    <!--plugins css-->
    <link rel="stylesheet" href="<?= $site ?>assets/css/plugins.css">

    <!-- Main Style CSS -->
    <link rel="stylesheet" href="<?= $site ?>assets/css/style.css">

    <!--modernizr min js here-->
    <script src="<?= $site ?>assets/js/vendor/modernizr-3.7.1.min.js"></script>

</head>
<style>
    /* Myntra Style Carousel */
    .myntra-style-main-image {
        border-radius: 8px;
        overflow: hidden;
    }

    .main-image-container {
        position: relative;
        background: white;
        border: 1px solid #eaeaea;
    }

    .carousel-nav-btn {
        opacity: 0;
        transition: all 0.3s ease;
        transform: translateY(-50%);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .carousel-nav-btn i {
        font-size: 16px;
        color: #333;
    }

    .main-image-container:hover .carousel-nav-btn {
        opacity: 1;
    }

    .carousel-nav-btn:hover {
        background: white !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
        transform: translateY(-50%) scale(1.1);
    }

    .main-image-wrapper {
        padding: 20px;
    }

    .main-product-img {
        transition: opacity 0.3s ease;
    }

    /* Thumbnail Styles */
    .myntra-thumbnail-carousel {
        padding: 0 30px;
    }

    .thumbnail-container {
        overflow: hidden;
    }

    .product-thumbnails {
        padding: 5px 0;
    }

    .thumbnail-link {
        transition: all 0.3s ease;
        border: 2px solid transparent !important;
    }

    .thumbnail-link.active {
        border-color: #ff3f6c !important;
        transform: scale(1.05);
        box-shadow: 0 4px 8px rgba(255, 63, 108, 0.2);
    }

    .thumbnail-link:hover:not(.active) {
        border-color: #ddd !important;
    }

    .thumb-nav-btn {
        opacity: 0.8;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .thumb-nav-btn:hover {
        opacity: 1;
        transform: translateY(-50%) scale(1.1);
        background: white !important;
    }

    .thumb-nav-btn i {
        color: #555;
    }

    .image-counter {
        font-family: Arial, sans-serif;
        font-weight: 500;
    }

    .zoom-indicator {
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    /* Image transition effect */
    .main-product-img.fade-effect {
        animation: fadeInOut 0.4s ease;
    }

    @keyframes fadeInOut {
        0% {
            opacity: 0.7;
        }

        50% {
            opacity: 0.3;
        }

        100% {
            opacity: 1;
        }
    }

    /* Responsive Styles */
    @media (max-width: 768px) {
        .main-image-container {
            min-height: 400px !important;
        }

        .myntra-thumbnail-carousel {
            padding: 0 20px;
        }

        .thumbnail-link {
            width: 60px !important;
            height: 60px !important;
        }

        .carousel-nav-btn {
            opacity: 1;
            width: 36px !important;
            height: 36px !important;
        }

        .zoom-indicator {
            display: none;
        }
    }

    @media (max-width: 576px) {
        .main-image-container {
            min-height: 350px !important;
        }

        .thumbnail-link {
            width: 50px !important;
            height: 50px !important;
        }
    }
</style>

<body>


    <!--header area start-->
    <?php include_once "includes/header.php" ?>
    <!--header area end-->

    <!--breadcrumbs area start-->
    <div class="breadcrumbs_area">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="breadcrumb_content">
                        <ul>
                            <li><a href="<?= $site ?>">home</a></li>
                            <?php if ($product['category_slug']): ?>
                                <li><a href="<?= $site ?>category/<?= $product['category_slug'] ?>/"><?= htmlspecialchars($product['category_name']) ?></a></li>
                            <?php endif; ?>
                            <li><?= htmlspecialchars($product['pro_name']) ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--breadcrumbs area end-->
    <style>
        /* Active/Selected color */
        .color-option.selected {
            position: relative;
        }

        .color-option.selected .color-swatch {
            border: 2px solid #000 !important;
            transform: scale(1.1);
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.2);
        }

        .color-option.selected .color-swatch::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 12px;
            font-weight: bold;
        }

        /* Active/Selected size */
        .size-option-btn {
            min-width: 40px;
            height: 40px;
            border: 1px solid #ddd;
            background: #fff;
            margin-right: 8px;
            margin-bottom: 8px;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .size-option-btn:hover {
            border-color: #666;
        }

        .size-option-btn.selected {
            background: #000;
            color: #fff;
            border-color: #000;
            font-weight: 600;
        }

        .size-option-btn.out-of-stock {
            opacity: 0.5;
            cursor: not-allowed;
            position: relative;
        }

        .size-option-btn.out-of-stock::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.5);
        }

        /* Quantity selector */


        .quantity-input {
            width: 60px;
            height: 40px;
            border: 1px solid #ddd;
            border-left: none;
            border-right: none;
            text-align: center;
            font-size: 16px;
            padding: 0 10px;
        }

        /* Variant notification */
        #variantNotification {
            padding: 12px 15px;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            margin: 15px 0;
            color: #856404;
            font-size: 14px;
        }

        /* Selected variant details */
        #selectedVariantDetails {
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            margin: 15px 0;
        }

        #variantPrice {
            font-size: 16px;
            margin-bottom: 5px;
        }

        #variantStock {
            font-size: 14px;
        }

        /* Add to cart button */


        .add-to-cart-btn:hover:not(:disabled) {
            background: #333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .add-to-cart-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
    <!--product details start-->
    <div class="product_details mb-80">
        <div class="container">
            <div class="row">
                <div class="col-lg-5 col-md-5">
                    <div class="product-details-tab position-relative overflow-hidden">

                        <!-- Main Image Container -->
                        <div class="myntra-style-main-image mb-3">
                            <?php if ($main_image): ?>
                                <div class="main-image-container position-relative overflow-hidden rounded"
                                    style="background-color: #f8f9fa; min-height: 500px;">

                                    <!-- Navigation Arrows -->
                                    <?php if (count($product_images) > 1): ?>
                                        <button class="carousel-nav-btn prev-btn position-absolute top-50 start-0 translate-middle-y"
                                            style="left: 15px; z-index: 10; background: white; border-radius: 50%; width: 40px; height: 40px; border: 1px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                            <i class="fa fa-chevron-left"></i>
                                        </button>

                                        <button class="carousel-nav-btn next-btn position-absolute top-50 end-0 translate-middle-y"
                                            style="right: 15px; z-index: 10; background: white; border-radius: 50%; width: 40px; height: 40px; border: 1px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                            <i class="fa fa-chevron-right"></i>
                                        </button>
                                    <?php endif; ?>

                                    <!-- Main Image -->
                                    <div class="main-image-wrapper d-flex justify-content-center align-items-center w-100 h-100">
                                        <a href="<?= $site ?>admin/assets/img/uploads/<?= $main_image['image_url'] ?>"
                                            class="magnific-popup-image d-block w-100 h-100 text-center">
                                            <img id="main-product-image"
                                                src="<?= $site ?>admin/assets/img/uploads/<?= $main_image['image_url'] ?>"
                                                alt="<?= htmlspecialchars($product['pro_name']) ?>"
                                                class="img-fluid main-product-img"
                                                style="max-height: 100%; max-width: 100%; object-fit: contain;"
                                                data-current-index="0">
                                        </a>
                                    </div>

                                    <!-- Image Counter (e.g., 1/5) -->
                                    <?php if (count($product_images) > 1): ?>
                                        <div class="image-counter position-absolute bottom-0 end-0 bg-dark bg-opacity-75 text-white px-3 py-1 rounded-start"
                                            style="font-size: 14px;">
                                            <span class="current-index">1</span>/<span class="total-images"><?= count($product_images) ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Zoom/Loupe Icon (like Myntra) -->
                                    <div class="zoom-indicator position-absolute" style="bottom: 15px; left: 15px; z-index: 5;">
                                        <span class="badge bg-light text-dark px-3 py-2" style="font-size: 12px; font-weight: normal;">
                                            <i class="fa fa-search me-1"></i> Mouse over image to zoom
                                        </span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Thumbnail Carousel -->
                        <?php if (count($product_images) > 1): ?>
                            <div class="myntra-thumbnail-carousel mt-3">
                                <div class="thumbnail-container position-relative">
                                    <!-- Thumbnail Navigation Arrows -->
                                    <button class="thumb-nav-btn thumb-prev-btn position-absolute start-0 top-50 translate-middle-y bg-white border-0 rounded-circle shadow-sm"
                                        style="width: 30px; height: 30px; z-index: 5; left: -15px;">
                                        <i class="fa fa-chevron-left fs-6"></i>
                                    </button>

                                    <button class="thumb-nav-btn thumb-next-btn position-absolute end-0 top-50 translate-middle-y bg-white border-0 rounded-circle shadow-sm"
                                        style="width: 30px; height: 30px; z-index: 5; right: -15px;">
                                        <i class="fa fa-chevron-right fs-6"></i>
                                    </button>

                                    <!-- Thumbnail Images -->
                                    <div class="thumbnails-wrapper overflow-hidden">
                                        <ul class="product-thumbnails d-flex justify-content-start list-unstyled mb-0 ps-0"
                                            style="transition: transform 0.3s ease; gap: 10px;">

                                            <?php foreach ($product_images as $index => $image): ?>
                                                <li class="thumbnail-item flex-shrink-0">
                                                    <a href="javascript:void(0);"
                                                        class="thumbnail-link d-block border rounded overflow-hidden <?= $index == 0 ? 'active' : '' ?>"
                                                        data-image="<?= $site ?>admin/assets/img/uploads/<?= $image['image_url'] ?>"
                                                        data-index="<?= $index ?>"
                                                        style="width: 80px; height: 80px;">
                                                        <img src="<?= $site ?>admin/assets/img/uploads/<?= $image['image_url'] ?>"
                                                            alt="<?= htmlspecialchars($product['pro_name']) ?> - Thumbnail <?= $index + 1 ?>"
                                                            class="img-fluid w-100 h-100"
                                                            style="object-fit: cover; cursor: pointer;">
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>

                                        </ul>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
                <div class="col-lg-7 col-md-7">
                    <div class="product_d_right">
                        <form id="productForm">
                            <!-- REQUIRED -->
                            <input type="hidden" name="action" value="add_to_cart">
                            <input type="hidden" name="product_id" value="<?= $product_id ?>">
                            <input type="hidden" name="selling_price" value="<?= $product['selling_price'] ?>">

                            <!-- Variant related -->
                            <input type="hidden" name="variant_id" id="selected_variant_id">
                            <!-- <input type="hidden" name="color" id="selected_color">
                            <input type="hidden" name="size" id="selected_size"> -->



                            <div class="productd_title_nav">
                                <h1><a href="#"><?= htmlspecialchars($product['pro_name']) ?></a></h1>

                                <!-- Product SKU -->
                                <?php if ($product['sku']): ?>
                                    <div class="product_sku" style="margin-top: 10px;">
                                        <span style="color: #666;">SKU: <?= htmlspecialchars($product['sku']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <!-- Stock Status -->
                                <div class="stock-status" style="margin-top: 10px;">
                                    <?php if ($total_stock > 10): ?>
                                        <span style="color: #28a745; font-weight: 500;">
                                            <i class="fa fa-check-circle"></i> In Stock
                                        </span>
                                    <?php elseif ($total_stock > 0 && $total_stock <= 10): ?>
                                        <span style="color: #ffc107; font-weight: 500;">
                                            <i class="fa fa-exclamation-triangle"></i> Only <?= $total_stock ?> left in stock
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #dc3545; font-weight: 500;">
                                            <i class="fa fa-times-circle"></i> Out of Stock
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Product Rating -->
                            <div class="product_ratting">
                                <ul>
                                    <?php
                                    $avg_rating = $review_stats['avg_rating'] ?? 0;
                                    $full_stars = floor($avg_rating);
                                    $has_half_star = ($avg_rating - $full_stars) >= 0.5;

                                    for ($i = 1; $i <= 5; $i++):
                                        if ($i <= $full_stars): ?>
                                            <li><a href="#"><i class="ion-android-star"></i></a></li>
                                        <?php elseif ($has_half_star && $i == $full_stars + 1): ?>
                                            <li><a href="#"><i class="ion-android-star-half"></i></a></li>
                                        <?php else: ?>
                                            <li><a href="#"><i class="ion-android-star-outline"></i></a></li>
                                    <?php endif;
                                    endfor; ?>
                                    <li class="review">
                                        <a href="#reviews">
                                            (<?= $review_stats['review_count'] ?> customer review<?= $review_stats['review_count'] != 1 ? 's' : '' ?>)
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <!-- Price -->
                            <div class="price_box">
                                <?php if ($product['mrp'] > $product['selling_price']):
                                    $discount_percentage = round((($product['mrp'] - $product['selling_price']) / $product['mrp']) * 100);
                                ?>
                                    <span class="old_price">₹ <?= number_format($product['mrp'], 2) ?></span>
                                    <span class="current_price">₹ <?= number_format($product['selling_price'], 2) ?></span>
                                    <span style="color: #e50010; font-weight: 500; margin-left: 10px;">
                                        Save <?= $discount_percentage ?>%
                                    </span>
                                <?php else: ?>
                                    <span class="current_price">₹ <?= number_format($product['selling_price'], 2) ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- Product Description -->
                            <div class="product_desc">
                                <p><?= $product['short_desc'] ?></p>
                            </div>

                            <!-- Color Variants -->
                            <?php if (!empty($available_colors)): ?>
                                <div class="product_variant color">
                                    <label>Select Color:</label>
                                    <ul>
                                        <?php foreach ($available_colors as $color):
                                            $colorKey = strtolower(trim($color));
                                            $bgColor = $colorMap[$colorKey] ?? '#cccccc'; // default if unknown
                                        ?>
                                            <li class="color-option" data-color="<?= htmlspecialchars($color) ?>">
                                                <a href="#" title="<?= htmlspecialchars($color) ?>">
                                                    <?php
                                                    $textColor = in_array($colorKey, ['white', 'yellow']) ? '#000' : '#fff';
                                                    ?>

                                                    <span class="color-swatch"
                                                        style="background-color: <?= $bgColor ?>; color: <?= $textColor ?>;">
                                                        <?= ucfirst(htmlspecialchars($color)) ?>
                                                    </span>

                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <input type="hidden" name="color" id="selected_color" value="">
                                </div>
                            <?php endif; ?>


                            <!-- Size Variants -->
                            <?php if (!empty($available_sizes)): ?>
                                <div class="product_variant size" style="margin-top: 20px;">
                                    <!-- <h3>Size</h3> -->
                                    <label>Select Size:</label>
                                    <div class="size-options">
                                        <?php foreach ($available_sizes as $size):
                                            // Check if size is in stock
                                            $size_in_stock = false;
                                            foreach ($variants as $variant) {
                                                if ($variant['size'] == $size && $variant['quantity'] > 0) {
                                                    $size_in_stock = true;
                                                    break;
                                                }
                                            }
                                        ?>
                                            <button type="button"
                                                class="size-option-btn <?= !$size_in_stock ? 'out-of-stock' : '' ?>"
                                                data-size="<?= htmlspecialchars($size) ?>"
                                                <?= !$size_in_stock ? 'disabled' : '' ?>>
                                                <?= htmlspecialchars($size) ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="size" id="selected_size" value="">
                                </div>
                            <?php endif; ?>

                            <!-- Variant Notification -->
                            <div id="variantNotification" style="display: none; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; margin: 15px 0;">
                                Please select both color and size before adding to cart.
                            </div>

                            <!-- Selected Variant Details -->
                            <div id="selectedVariantDetails" style="display: none; margin: 15px 0; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                                <div id="variantPrice"></div>
                                <div id="variantStock"></div>
                            </div>

                            <!-- Quantity -->
                            <div class="product_variant quantity ">
                                <label>Quantity</label>
                                <div class="quantity-selector">
                                    <button type="button" class="quantity-btn minus">-</button>
                                    <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?= $total_stock ?>" class="quantity-input">
                                    <button type="button" class="quantity-btn plus">+</button>
                                </div>

                                <!-- Add to Cart Button -->
                                <button id="addToCartBtn" type="submit" class="button add-to-cart-btn"
                                    style="margin-left: 20px;"
                                    <?= $total_stock == 0 ? 'disabled' : '' ?>>
                                    <?= $total_stock > 0 ? 'Add to cart' : 'Out of Stock' ?>
                                </button>
                            </div>

                            <!-- Product Actions -->
                            <div class="product_d_action">
                                <ul>
                                    <li>
                                        <a href="#" class="add-to-wishlist"
                                            data-product-id="<?= $product_id ?>"
                                            title="Add to Wishlist">
                                            + Add to Wishlist
                                        </a>
                                    </li>

                                </ul>
                            </div>

                            <!-- Product Meta -->
                            <div class="product_meta">
                                <?php if ($product['category_name']): ?>
                                    <span>Category: <a href="<?= $site ?>category/<?= $product['category_slug'] ?>/"><?= htmlspecialchars($product['category_name']) ?></a></span>
                                <?php endif; ?>

                                <?php if ($product['brand_name']): ?>
                                    <br><span>Brand: <a href="<?= $site ?>brand/<?= strtolower(str_replace(' ', '-', $product['brand_name'])) ?>/"><?= htmlspecialchars($product['brand_name']) ?></a></span>
                                <?php endif; ?>

                                <?php if ($product['product_type']): ?>
                                    <br><span>Type: <?= htmlspecialchars($product['product_type']) ?></span>
                                <?php endif; ?>

                                <?php if ($product['fit_type']): ?>
                                    <br><span>Fit: <?= htmlspecialchars($product['fit_type']) ?></span>
                                <?php endif; ?>
                            </div>
                        </form>

                        <!-- Social Share -->
                        <div class="priduct_social">
                            <ul>
                                <li><a class="facebook" href="#" title="facebook"><i class="fa fa-facebook"></i> Like</a></li>
                                <li><a class="twitter" href="#" title="twitter"><i class="fa fa-twitter"></i> tweet</a></li>
                                <li><a class="pinterest" href="#" title="pinterest"><i class="fa fa-pinterest"></i> save</a></li>
                                <li><a class="google-plus" href="#" title="google +"><i class="fa fa-google-plus"></i> share</a></li>
                                <li><a class="linkedin" href="#" title="linkedin"><i class="fa fa-linkedin"></i> linked</a></li>
                            </ul>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--product details end-->

    <!--product info start-->
    <div class="product_d_info mb-77">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="product_d_inner">
                        <div class="product_info_button">
                            <ul class="nav" role="tablist">
                                <li>
                                    <a class="active" data-bs-toggle="tab" href="#description" role="tab" aria-controls="description" aria-selected="false">Description</a>
                                </li>
                                <li>
                                    <a data-bs-toggle="tab" href="#specification" role="tab" aria-controls="specification" aria-selected="false">Specification</a>
                                </li>
                                <li>
                                    <a data-bs-toggle="tab" href="#reviews" role="tab" aria-controls="reviews" aria-selected="false">Reviews (<?= $review_stats['review_count'] ?>)</a>
                                </li>
                            </ul>
                        </div>
                        <div class="tab-content">
                            <!-- Description Tab -->
                            <div class="tab-pane fade show active" id="description" role="tabpanel">
                                <div class="product_info_content">
                                    <?= $product['description'] ?>

                                    <?php if ($product['material']): ?>
                                        <h4 style="margin-top: 20px;">Material & Care</h4>
                                        <p><?= htmlspecialchars($product['material']) ?></p>
                                    <?php endif; ?>

                                    <?php if ($product['care_instructions']): ?>
                                        <h4>Care Instructions</h4>
                                        <p><?= htmlspecialchars($product['care_instructions']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Specification Tab -->
                            <div class="tab-pane fade" id="specification" role="tabpanel">
                                <div class="product_d_table">
                                    <form action="#">
                                        <table>
                                            <tbody>
                                                <?php if ($product['product_type']): ?>
                                                    <tr>
                                                        <td class="first_child">Product Type</td>
                                                        <td><?= htmlspecialchars($product['product_type']) ?></td>
                                                    </tr>
                                                <?php endif; ?>

                                                <?php if ($product['fit_type']): ?>
                                                    <tr>
                                                        <td class="first_child">Fit Type</td>
                                                        <td><?= htmlspecialchars($product['fit_type']) ?></td>
                                                    </tr>
                                                <?php endif; ?>

                                                <?php if ($product['season']): ?>
                                                    <tr>
                                                        <td class="first_child">Season</td>
                                                        <td><?= htmlspecialchars($product['season']) ?></td>
                                                    </tr>
                                                <?php endif; ?>

                                                <?php if ($product['material']): ?>
                                                    <tr>
                                                        <td class="first_child">Material</td>
                                                        <td><?= htmlspecialchars($product['material']) ?></td>
                                                    </tr>
                                                <?php endif; ?>

                                                <?php if ($product['weight']): ?>
                                                    <tr>
                                                        <td class="first_child">Weight</td>
                                                        <td><?= htmlspecialchars($product['weight']) ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </form>
                                </div>
                            </div>

                            <!-- Reviews Tab -->
                            <div class="tab-pane fade" id="reviews" role="tabpanel">
                                <div class="reviews_wrapper">
                                    <!-- Reviews Summary -->
                                    <div class="reviews_summary mb-30">
                                        <h3>Customer Reviews</h3>
                                        <div class="average_rating">
                                            <h4><?= number_format($review_stats['avg_rating'] ?? 0, 1) ?> out of 5</h4>
                                            <div class="product_ratting" style="margin: 10px 0;">
                                                <ul>
                                                    <?php for ($i = 1; $i <= 5; $i++):
                                                        if ($i <= floor($review_stats['avg_rating'] ?? 0)): ?>
                                                            <li><a href="#"><i class="ion-android-star"></i></a></li>
                                                        <?php else: ?>
                                                            <li><a href="#"><i class="ion-android-star-outline"></i></a></li>
                                                    <?php endif;
                                                    endfor; ?>
                                                </ul>
                                            </div>
                                            <p>Based on <?= $review_stats['review_count'] ?> review<?= $review_stats['review_count'] != 1 ? 's' : '' ?></p>
                                        </div>
                                    </div>

                                    <?php if (count($recent_reviews) > 0): ?>
                                        <h4>Recent Reviews</h4>
                                        <?php foreach ($recent_reviews as $review): ?>
                                            <div class="reviews_comment_box">
                                                <div class="comment_thmb">
                                                    <img src="<?= $review['reviewver_img'] ?: $site . 'assets/img/blog/comment2.jpg' ?>">

                                                </div>
                                                <div class="comment_text">
                                                    <div class="reviews_meta">
                                                        <div class="star_rating">
                                                            <ul>
                                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                    <?php if ($i <= $review['rating']): ?>
                                                                        <li><a href="#"><i class="ion-android-star"></i></a></li>
                                                                    <?php else: ?>
                                                                        <li><a href="#"><i class="ion-android-star-outline"></i></a></li>
                                                                    <?php endif; ?>
                                                                <?php endfor; ?>
                                                            </ul>
                                                        </div>
                                                        <p>
                                                            <strong>
                                                                <?= htmlspecialchars($review['reviewer_name']) ?>
                                                            </strong>
                                                            - <?= date('F d, Y', strtotime($review['created_at'])) ?>
                                                        </p>
                                                        <p><?= htmlspecialchars($review['review_message']) ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p>No reviews yet. Be the first to review this product!</p>
                                    <?php endif; ?>

                                    <!-- Add Review Form -->
                                    <div class="comment_title">
                                        <h2>Add a Review</h2>
                                        <p>Your email address will not be published. Required fields are marked *</p>
                                    </div>

                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <div class="product_review_form">
                                            <form id="reviewForm" action="<?= $site ?>ajax/add-review.php" method="POST">
                                                <input type="hidden" name="product_id" value="<?= $product_id ?>">

                                                <div class="product_ratting mb-10">
                                                    <h3>Your Rating *</h3>
                                                    <div class="rating-stars">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <a href="#" class="star" data-value="<?= $i ?>">
                                                                <i class="ion-android-star-outline"></i>
                                                            </a>
                                                        <?php endfor; ?>
                                                        <input type="hidden" name="rating" id="rating" value="5" required>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-12">
                                                        <label for="review_comment">Your Review *</label>
                                                        <textarea name="comment" id="review_comment" rows="5" required></textarea>
                                                    </div>
                                                </div>
                                                <button type="submit" class="button">Submit Review</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <p>Please <a href="<?= $site ?>user-login/" class="text-primary text-bold">login</a> to write a review.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--product info end-->


    <!-- Upsell Products -->
    <?php if (count($upsell_products) > 0): ?>
        <section class="product_area upsell_products mb-60">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="section_title psec_title">
                            <h2>You May Also Like</h2>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="product_carousel product_column5 owl-carousel">
                        <?php foreach ($upsell_products as $index => $upsell):
                            $upsell_discount = $upsell['mrp'] > $upsell['selling_price'] ?
                                round((($upsell['mrp'] - $upsell['selling_price']) / $upsell['mrp']) * 100) : 0;

                            // Determine secondary image - use pattern or database
                            $secondary_img_num = ($index % 4) + 2;
                        ?>
                            <div class="col-lg-3">
                                <article class="single_product">
                                    <figure>
                                        <div class="product_thumb">
                                            <a class="primary_img" href="<?= $site ?>product-details/<?= $upsell['slug_url'] ?>">
                                                <img src="<?= $site ?>admin/assets/img/uploads/<?= $upsell['pro_img'] ?>"
                                                    alt="<?= htmlspecialchars($upsell['pro_name']) ?>"
                                                    style="height: 300px; object-fit: cover;">
                                            </a>
                                            <a class="secondary_img" href="<?= $site ?>product-details/<?= $upsell['slug_url'] ?>">
                                                <?php
                                                // Get secondary image from database
                                                $sec_img_sql = "SELECT image_url FROM product_images 
                                                            WHERE product_id = ? AND is_main = 0 
                                                            ORDER BY display_order LIMIT 1";
                                                $sec_stmt = $conn->prepare($sec_img_sql);
                                                $sec_stmt->bind_param("i", $upsell['pro_id']);
                                                $sec_stmt->execute();
                                                $sec_result = $sec_stmt->get_result();
                                                if ($sec_img = $sec_result->fetch_assoc()): ?>
                                                    <img src="<?= $site ?>admin/assets/img/uploads/<?= $sec_img['image_url'] ?>"
                                                        alt="<?= htmlspecialchars($upsell['pro_name']) ?>"
                                                        style="height: 300px; object-fit: cover;">
                                                <?php else: ?>
                                                    <!-- Fallback to pattern-based images -->
                                                    <img src="<?= $site ?>assets/img/product/product<?= $secondary_img_num ?>.jpg"
                                                        alt="<?= htmlspecialchars($upsell['pro_name']) ?>"
                                                        style="height: 300px; object-fit: cover;">
                                                <?php endif;
                                                $sec_stmt->close();
                                                ?>
                                            </a>

                                            <?php if ($upsell['trending'] == 1): ?>
                                                <div class="label_product">
                                                    <span class="label_new">Trending</span>
                                                </div>
                                            <?php elseif ($upsell['new_arrival'] == 1): ?>
                                                <div class="label_product">
                                                    <span class="label_new">New</span>
                                                </div>
                                            <?php elseif ($upsell_discount > 0): ?>
                                                <div class="label_product">
                                                    <span class="label_sale">Sale</span>
                                                    <span class="label_discount">-<?= $upsell_discount ?>%</span>
                                                </div>
                                            <?php endif; ?>

                                        </div>
                                        <figcaption class="product_content">
                                            <div class="product_content_inner">
                                                <h4 class="product_name">
                                                    <a href="<?= $site ?>product-details/<?= $upsell['slug_url'] ?>">
                                                        <?= htmlspecialchars($upsell['pro_name']) ?>
                                                    </a>
                                                </h4>
                                                <div class="price_box">
                                                    <?php if ($upsell['mrp'] > $upsell['selling_price']): ?>
                                                        <span class="old_price">₹ <?= number_format($upsell['mrp'], 2) ?></span>
                                                    <?php endif; ?>
                                                    <span class="current_price">₹ <?= number_format($upsell['selling_price'], 2) ?></span>
                                                </div>
                                            </div>
                                            <div class="add_to_cart">
                                                <a class="add-to-cart" href="<?= $site ?>product-details/<?= $upsell['slug_url'] ?>">View Product</a>
                                            </div>
                                        </figcaption>
                                    </figure>
                                </article>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!--footer area start-->
    <?php include_once "includes/footer.php"; ?>
    <!--footer area end-->

    <!-- modal area start-->
    <div class="modal fade" id="modal_box" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="ion-android-close"></i></span>
                </button>
                <div class="modal_body">
                    <div class="container">
                        <div class="row">
                            <div class="col-lg-5 col-md-5 col-sm-12">
                                <div class="modal_tab">
                                    <div class="tab-content product-details-large">
                                        <!-- Quick view content will be loaded here via AJAX -->
                                    </div>
                                    <div class="modal_tab_button">
                                        <!-- Thumbnails will be loaded here via AJAX -->
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-7 col-md-7 col-sm-12">
                                <div class="modal_right">
                                    <!-- Quick view details will be loaded here via AJAX -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- modal area end-->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mainImage = document.getElementById('main-product-image');
            const thumbnailLinks = document.querySelectorAll('.thumbnail-link');
            const currentIndexSpan = document.querySelector('.current-index');
            const totalImagesSpan = document.querySelector('.total-images');
            const mainPrevBtn = document.querySelector('.prev-btn');
            const mainNextBtn = document.querySelector('.next-btn');
            const thumbPrevBtn = document.querySelector('.thumb-prev-btn');
            const thumbNextBtn = document.querySelector('.thumb-next-btn');
            const thumbnailsWrapper = document.querySelector('.thumbnails-wrapper');
            const thumbnailsList = document.querySelector('.product-thumbnails');
            const popupLink = document.querySelector('.magnific-popup-image');

            let currentIndex = 0;
            const totalImages = thumbnailLinks.length;

            // Initialize
            if (totalImagesSpan) totalImagesSpan.textContent = totalImages;

            // Function to update main image
            function updateMainImage(index) {
                if (index < 0) index = totalImages - 1;
                if (index >= totalImages) index = 0;

                currentIndex = index;
                const activeThumb = thumbnailLinks[index];

                // Update main image
                const newImageUrl = activeThumb.getAttribute('data-image');
                mainImage.src = newImageUrl;
                mainImage.setAttribute('data-current-index', index);

                // Update popup link
                if (popupLink) {
                    popupLink.href = newImageUrl;
                }

                // Update active thumbnail
                thumbnailLinks.forEach(link => link.classList.remove('active'));
                activeThumb.classList.add('active');

                // Update counter
                if (currentIndexSpan) {
                    currentIndexSpan.textContent = index + 1;
                }

                // Center active thumbnail in view
                centerActiveThumbnail();

                // Reinitialize zoom if needed
                reinitializeZoom();
            }

            // Function to center active thumbnail
            function centerActiveThumbnail() {
                if (!thumbnailsWrapper || !thumbnailsList) return;

                const activeThumb = document.querySelector('.thumbnail-link.active');
                if (!activeThumb) return;

                const containerWidth = thumbnailsWrapper.offsetWidth;
                const thumbWidth = activeThumb.offsetWidth + 10; // 10px for gap
                const thumbOffset = activeThumb.offsetLeft;

                // Calculate scroll position
                const scrollPosition = thumbOffset - (containerWidth / 2) + (thumbWidth / 2);

                thumbnailsList.style.transform = `translateX(-${scrollPosition}px)`;
            }

            // Function to reinitialize zoom
            function reinitializeZoom() {
                // If using elevateZoom plugin
                if (typeof $.fn.elevateZoom !== 'undefined') {
                    $('.main-image-container').removeData('elevateZoom');
                    $('#main-product-image').removeData('elevateZoom');

                    // Reinitialize with Myntra-like settings
                    $('#main-product-image').elevateZoom({
                        zoomType: "inner",
                        cursor: "crosshair",
                        zoomWindowFadeIn: 300,
                        zoomWindowFadeOut: 300,
                        scrollZoom: true,
                        zoomWindowWidth: 400,
                        zoomWindowHeight: 400,
                        borderSize: 1,
                        lensSize: 200
                    });
                }
            }

            // Thumbnail click event
            thumbnailLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const index = parseInt(this.getAttribute('data-index'));
                    updateMainImage(index);
                });
            });

            // Main navigation buttons
            if (mainPrevBtn) {
                mainPrevBtn.addEventListener('click', function() {
                    updateMainImage(currentIndex - 1);
                });
            }

            if (mainNextBtn) {
                mainNextBtn.addEventListener('click', function() {
                    updateMainImage(currentIndex + 1);
                });
            }

            // Thumbnail carousel navigation
            let thumbScrollPosition = 0;
            const thumbScrollStep = 100;

            if (thumbPrevBtn) {
                thumbPrevBtn.addEventListener('click', function() {
                    thumbScrollPosition = Math.max(0, thumbScrollPosition - thumbScrollStep);
                    thumbnailsList.style.transform = `translateX(-${thumbScrollPosition}px)`;
                });
            }

            if (thumbNextBtn) {
                thumbNextBtn.addEventListener('click', function() {
                    const maxScroll = thumbnailsList.scrollWidth - thumbnailsWrapper.offsetWidth;
                    thumbScrollPosition = Math.min(maxScroll, thumbScrollPosition + thumbScrollStep);
                    thumbnailsList.style.transform = `translateX(-${thumbScrollPosition}px)`;
                });
            }

            // Keyboard navigation
            document.addEventListener('keydown', function(e) {
                if (e.key === 'ArrowLeft') {
                    updateMainImage(currentIndex - 1);
                } else if (e.key === 'ArrowRight') {
                    updateMainImage(currentIndex + 1);
                }
            });

            // Swipe functionality for touch devices
            let touchStartX = 0;
            let touchEndX = 0;

            const mainContainer = document.querySelector('.main-image-container');
            if (mainContainer) {
                mainContainer.addEventListener('touchstart', function(e) {
                    touchStartX = e.changedTouches[0].screenX;
                });

                mainContainer.addEventListener('touchend', function(e) {
                    touchEndX = e.changedTouches[0].screenX;
                    handleSwipe();
                });
            }

            function handleSwipe() {
                const swipeThreshold = 50;
                const diff = touchStartX - touchEndX;

                if (Math.abs(diff) > swipeThreshold) {
                    if (diff > 0) {
                        // Swipe left - next image
                        updateMainImage(currentIndex + 1);
                    } else {
                        // Swipe right - previous image
                        updateMainImage(currentIndex - 1);
                    }
                }
            }

            // Initialize zoom on first load
            setTimeout(() => {
                reinitializeZoom();
                centerActiveThumbnail();
            }, 500);

            // Hover effect for thumbnails
            thumbnailLinks.forEach(link => {
                const img = link.querySelector('img');

                link.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.05)';
                    this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
                });

                link.addEventListener('mouseleave', function() {
                    if (!this.classList.contains('active')) {
                        this.style.transform = 'scale(1)';
                        this.style.boxShadow = 'none';
                    }
                });
            });
        });
    </script>
    <?php include_once "includes/footer-link.php"; ?>

    <!-- Product Details Specific JavaScript -->
    <script>
        $(document).ready(function() {
            // Initialize product image zoom
            if ($('#zoom1').length) {
                $('#zoom1').elevateZoom({
                    gallery: 'gallery_01',
                    cursor: 'pointer',
                    galleryActiveClass: 'active',
                    imageCrossfade: true,
                    loadingIcon: '<?= $site ?>assets/img/loader.gif'
                });
            }

            // Quantity controls
            $('.quantity-btn.minus').click(function() {
                var quantityInput = $('#quantity');
                var currentVal = parseInt(quantityInput.val());
                if (currentVal > 1) {
                    quantityInput.val(currentVal - 1);
                }
            });

            $('.quantity-btn.plus').click(function() {
                var quantityInput = $('#quantity');
                var currentVal = parseInt(quantityInput.val());
                var maxStock = parseInt(quantityInput.attr('max'));
                if (currentVal < maxStock) {
                    quantityInput.val(currentVal + 1);
                }
            });

            // Color selection
            $('.color-option').click(function(e) {
                e.preventDefault();
                var color = $(this).data('color');

                // Update selected color
                $('.color-option').removeClass('selected');
                $(this).addClass('selected');
                $('#selected_color').val(color);

                // Update variant details
                updateVariantDetails();
            });

            // Size selection
            $('.size-option-btn:not(.out-of-stock)').click(function() {
                var size = $(this).data('size');

                // Update selected size
                $('.size-option-btn').removeClass('selected');
                $(this).addClass('selected');
                $('#selected_size').val(size);

                // Update variant details
                updateVariantDetails();
            });

            // Function to update variant details
            function updateVariantDetails() {
                var color = $('#selected_color').val();
                var size = $('#selected_size').val();

                if (color && size) {
                    // Hide notification
                    $('#variantNotification').hide();

                    // AJAX call to get variant details
                    $.ajax({
                        url: '<?= $site ?>ajax/get-variant-details.php',
                        method: 'POST',
                        dataType: 'json', // ✅ REQUIRED
                        data: {
                            product_id: <?= $product_id ?>,
                            color: color,
                            size: size
                        },
                        success: function(response) {
                            console.log('Variant response:', response); // debug

                            if (response.success && response.variant) {

                                // ✅ SET VARIANT ID
                                $('#selected_variant_id').val(response.variant.id);

                                console.log('Variant ID set:', response.variant.id);

                                // price
                                if (response.variant.price > 0) {
                                    $('#variantPrice').html('Price: <strong>₹ ' + response.variant.price + '</strong>');
                                } else {
                                    $('#variantPrice').html('');
                                }

                                // stock
                                if (response.variant.stock <= 10) {
                                    $('#variantStock').html('Stock: <span style="color:#ffc107;">Only ' + response.variant.stock + ' left</span>');
                                } else {
                                    $('#variantStock').html('Stock: <span style="color:#28a745;">In Stock</span>');
                                }

                                $('#quantity').attr('max', response.variant.stock);
                                $('#selectedVariantDetails').show();
                            } else {
                                console.log('Variant not found');
                            }
                        }
                    });

                }
            }

            // add to cart function 
            $('#productForm').submit(function(e) {
                e.preventDefault();

                var button = $('#addToCartBtn');

                var hasVariants = <?= $has_variants ? 'true' : 'false' ?>;
                if (hasVariants) {
                    if (!$('#selected_color').val() || !$('#selected_size').val()) {
                        $('#variantNotification').show();
                        return false;
                    }
                }

                var formData = $(this).serialize();

                button.html('<span class="spinner-border spinner-border-sm"></span> Adding...');
                button.prop('disabled', true);

                $.ajax({
                    url: '<?= $site ?>ajax/add-to-cart.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',

                    success: function(response) {

                        if (response.success) {

                            button.html('Added ✓');

                            if (response.cart_count !== undefined) {
                                $('.item_count').text(response.cart_count);
                            }

                            showNotification(response.message, 'success');

                            setTimeout(function() {
                                button.html('Add to cart');
                                button.prop('disabled', false);
                            }, 1500);

                        } else {
                            button.html('Add to cart');
                            button.prop('disabled', false);
                            showNotification(response.message, 'error');
                        }
                    },

                    error: function(xhr) {
                        console.error(xhr.responseText);
                        button.html('Add to cart');
                        button.prop('disabled', false);
                        showNotification('Server error occurred', 'error');
                    }
                });
            });


            // Rating stars for review form
            $('.rating-stars .star').click(function(e) {
                e.preventDefault();

                var rating = $(this).data('value');
                $('#rating').val(rating);

                // Update star display
                $('.rating-stars .star').each(function() {
                    var starValue = $(this).data('value');
                    var icon = $(this).find('i');

                    if (starValue <= rating) {
                        icon.removeClass('ion-android-star-outline').addClass('ion-android-star');
                    } else {
                        icon.removeClass('ion-android-star').addClass('ion-android-star-outline');
                    }
                });
            });

            // Review form submission
            $('#reviewForm').submit(function(e) {
                e.preventDefault();

                var form = $(this);
                var button = form.find('button[type="submit"]');

                button.html('<span class="spinner-border spinner-border-sm"></span> Submitting...');
                button.prop('disabled', true);

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        if (response.success) {
                            showNotification(response.message || 'Review submitted successfully!', 'success');
                            form[0].reset();

                            // Reset stars
                            $('.rating-stars .star').each(function() {
                                var icon = $(this).find('i');
                                icon.removeClass('ion-android-star').addClass('ion-android-star-outline');
                            });
                            $('.rating-stars .star:first-child').find('i').removeClass('ion-android-star-outline').addClass('ion-android-star');
                            $('#rating').val(5);

                            // Reload page after 2 seconds to show updated reviews
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            showNotification(response.message || 'Error submitting review', 'error');
                        }
                        button.html('Submit Review');
                        button.prop('disabled', false);
                    },
                    error: function() {
                        showNotification('Error submitting review. Please try again.', 'error');
                        button.html('Submit Review');
                        button.prop('disabled', false);
                    }
                });
            });

            // Social share links
            $('.priduct_social a').click(function(e) {
                e.preventDefault();

                var platform = $(this).hasClass('facebook') ? 'Facebook' :
                    $(this).hasClass('twitter') ? 'Twitter' :
                    $(this).hasClass('pinterest') ? 'Pinterest' :
                    $(this).hasClass('google-plus') ? 'Google+' :
                    'LinkedIn';

                // You can implement actual sharing functionality here
                alert('Share this product on ' + platform);
            });
        });
    </script>
    <script>
        $(function() {

            $('.rating-stars .star').click(function(e) {
                e.preventDefault();
                let val = $(this).data('value');
                $('#rating').val(val);

                $('.rating-stars .star i')
                    .removeClass('ion-android-star')
                    .addClass('ion-android-star-outline');

                $('.rating-stars .star').each(function() {
                    if ($(this).data('value') <= val) {
                        $(this).find('i')
                            .removeClass('ion-android-star-outline')
                            .addClass('ion-android-star');
                    }
                });
            });

            $('#reviewForm').submit(function(e) {
                e.preventDefault();

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            alert(res.message);
                            location.reload();
                        } else {
                            alert(res.message);
                        }
                    },
                    error: function() {
                        alert('Server error. Please try again.');
                    }
                });
            });

        });
    </script>

</body>

</html>