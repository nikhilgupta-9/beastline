<?php
session_start();
include_once "config/connect.php";
include_once "util/function.php";

// Get category slug from URL
$category_slug = isset($_GET['alias']) ? mysqli_real_escape_string($conn, $_GET['alias']) : '';

// Get category details
$category_sql = "SELECT * FROM categories WHERE slug_url = '$category_slug' AND status = 1 LIMIT 1";
$category_result = mysqli_query($conn, $category_sql);
$category = mysqli_fetch_assoc($category_result);

if (!$category) {
    // Redirect to shop page if category not found
    header("Location: " . $site . "shop/");
    exit();
}

$category_id = $category['id'];
$category_name = $category['categories'];

// Get filter parameters
$brand_id = isset($_GET['brand']) ? intval($_GET['brand']) : 0;
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 100000;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build query with filters similar to shop page
$sql_pro = "
    SELECT 
        p.pro_id,
        p.pro_name,
        p.mrp,
        p.selling_price,
        p.slug_url,
        p.pro_sub_cate,
        p.brand_name,
        p.status,
        p.stock,
        p.short_desc,
        pi.image_url AS pro_img,
        c.categories,
        b.brand_name as brand_name_text
    FROM products p
    LEFT JOIN product_images pi 
        ON p.pro_id = pi.product_id AND pi.is_main = 1
    LEFT JOIN categories c 
        ON p.pro_sub_cate = c.id
    LEFT JOIN brands b 
        ON p.brand_name = b.id
    WHERE p.status = 1 AND p.pro_sub_cate = ? OR p.pro_cate = ?
";

// Apply filters
$conditions = [];
$params = [$category_id, $category_id];
$types = "ii";

if ($brand_id > 0) {
    $conditions[] = "p.brand_name = ?";
    $params[] = $brand_id;
    $types .= "i";
}

if (!empty($conditions)) {
    $sql_pro .= " AND " . implode(" AND ", $conditions);
}

// Add price range filter
$sql_pro .= " AND p.selling_price BETWEEN ? AND ?";
$params[] = $min_price;
$params[] = $max_price;
$types .= "dd";

// Add sorting
switch ($sort_by) {
    case 'price_low':
        $sql_pro .= " ORDER BY p.selling_price ASC";
        break;
    case 'price_high':
        $sql_pro .= " ORDER BY p.selling_price DESC";
        break;
    case 'popular':
        $sql_pro .= " ORDER BY p.pro_id DESC";
        break;
    case 'rating':
        $sql_pro .= " ORDER BY p.pro_id DESC";
        break;
    default: // 'newest'
        $sql_pro .= " ORDER BY p.pro_id DESC";
        break;
}

// Prepare and execute query
$stmt = mysqli_prepare($conn, $sql_pro);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $products = [];
    $total_products = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        // Image fallback safety
        if (empty($row['pro_img'])) {
            $row['pro_img'] = 'no-image.jpg';
        }
        
        // Format prices
        $row['formatted_mrp'] = '₹' . number_format($row['mrp'], 2);
        $row['formatted_selling_price'] = '₹' . number_format($row['selling_price'], 2);
        
        // Calculate discount
        if ($row['mrp'] > $row['selling_price']) {
            $row['discount'] = round((($row['mrp'] - $row['selling_price']) / $row['mrp']) * 100);
        } else {
            $row['discount'] = 0;
        }
        
        $products[] = $row;
        $total_products++;
    }
    mysqli_stmt_close($stmt);
} else {
    // Fallback to simple query
    $fallback_sql = "
        SELECT 
            p.pro_id,
            p.pro_name,
            p.mrp,
            p.selling_price,
            p.slug_url,
            p.pro_sub_cate,
            p.brand_name,
            p.status,
            p.stock,
            p.short_desc,
            pi.image_url AS pro_img,
            c.categories,
            b.brand_name as brand_name_text
        FROM products p
        LEFT JOIN product_images pi 
            ON p.pro_id = pi.product_id AND pi.is_main = 1
        LEFT JOIN categories c 
            ON p.pro_sub_cate = c.id
        LEFT JOIN brands b 
            ON p.brand_name = b.id
        WHERE p.status = 1 AND p.pro_sub_cate = $category_id
        ORDER BY p.pro_id DESC
    ";
    
    $result = mysqli_query($conn, $fallback_sql);
    $products = [];
    $total_products = 0;
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Image fallback safety
            if (empty($row['pro_img'])) {
                $row['pro_img'] = 'no-image.jpg';
            }
            
            $row['formatted_mrp'] = '₹' . number_format($row['mrp'], 2);
            $row['formatted_selling_price'] = '₹' . number_format($row['selling_price'], 2);
            
            // Calculate discount
            if ($row['mrp'] > $row['selling_price']) {
                $row['discount'] = round((($row['mrp'] - $row['selling_price']) / $row['mrp']) * 100);
            } else {
                $row['discount'] = 0;
            }
            
            $products[] = $row;
            $total_products++;
        }
    }
}

// Get brands for this category
$brands_sql = "
    SELECT DISTINCT b.*, 
           (SELECT COUNT(*) FROM products p2 WHERE p2.brand_name = b.id AND p2.pro_sub_cate = $category_id AND p2.status = 1) as product_count
    FROM brands b
    LEFT JOIN products p ON b.id = p.brand_name
    WHERE p.pro_sub_cate = $category_id AND p.status = 1
    ORDER BY b.brand_name
";
$brands_result = mysqli_query($conn, $brands_sql);
$brands = [];
while ($brand = mysqli_fetch_assoc($brands_result)) {
    $brands[] = $brand;
}

// Get price range for this category
$price_sql = "
    SELECT 
        MIN(selling_price) as min_price,
        MAX(selling_price) as max_price
    FROM products 
    WHERE pro_sub_cate = $category_id AND status = 1
";
$price_result = mysqli_query($conn, $price_sql);
$price_range = mysqli_fetch_assoc($price_result);

// Get categories for sidebar
$categories_sql = "SELECT * FROM categories WHERE status = 1 AND parent_id = 0 ORDER BY categories";
$categories_result = mysqli_query($conn, $categories_sql);
$categories = [];
while ($cat = mysqli_fetch_assoc($categories_result)) {
    $categories[] = $cat;
}

?>
<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title><?= htmlspecialchars($category_name) ?> | Beastline</title>
    <meta name="description" content="<?= htmlspecialchars($category['meta_description'] ?? 'Browse our collection of ' . $category_name) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($category['meta_keywords'] ?? $category_name . ', products, shop, buy') ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="<?= $site ?>assets/img/favicon/favicon.ico">

    <!-- CSS -->
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
                        <h3><?= htmlspecialchars($category_name) ?></h3>
                        <ul>
                            <li><a href="<?= $site ?>">home</a></li>
                            <li><a href="<?= $site ?>shop/">Shop</a></li>
                            <li><?= htmlspecialchars($category_name) ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--breadcrumbs area end-->

    <!--shop area start-->
    <div class="shop_area shop_fullwidth mb-80">
        <div class="container">
            <!-- Category Description -->
            <?php if (!empty($category['category_desc'])): ?>
            <div class="row mb-30">
                <div class="col-12">
                    <div class="category_description bg-light p-4 rounded">
                        <h4 class="mb-3">About <?= htmlspecialchars($category_name) ?></h4>
                        <p class="mb-0"><?= htmlspecialchars($category['category_desc']) ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

           
            <!-- Products Section -->
            <div class="row">
                <?php if (empty($products)): ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center py-5">
                            <h4 class="mb-3">No products found in <?= htmlspecialchars($category_name) ?>!</h4>
                            <p class="mb-4">Try different filters or browse other categories.</p>
                            <a href="<?= $site ?>category/sale" class="btn btn-primary">Browse All Products</a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $index => $product): 
                        // Determine secondary image
                        $secondary_img_num = ($index % 4) + 2; // This will cycle through 2, 3, 4, 5, etc.

                        // Calculate discount if available
                        $show_sale = isset($product['mrp']) && isset($product['selling_price']) &&
                            $product['mrp'] > $product['selling_price'];

                        // Primary image path - similar to featured products
                        if (isset($product['pro_img']) && strpos($product['pro_img'], 'assets/') === false) {
                            $primary_img = $site . 'admin/assets/img/uploads/' . $product['pro_img'];
                        } else {
                            $primary_img = isset($product['pro_img']) ? $product['pro_img'] : "assets/img/product/product" . (($index * 2) + 1) . ".jpg";
                        }
                    ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 col-6 mb-4">
                            <article class="single_product">
                                <figure>
                                    <div class="product_thumb">
                                        <a class="primary_img" href="<?= $site ?>product-details/<?= $product['slug_url'] ?>">
                                            <img src="<?= $primary_img ?>" alt="<?= htmlspecialchars($product['pro_name']) ?>" >
                                        </a>
                                        

                                        <?php if ($show_sale): ?>
                                            <div class="label_product">
                                                <span class="label_sale">Sale</span>
                                                <?php if ($product['discount'] > 0): ?>
                                                    <span class="label_discount">-<?= $product['discount'] ?>%</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($product['stock'] <= 0): ?>
                                            <div class="label_product">
                                                <span class="label_sale" style="background: #dc3545;">Out of Stock</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <figcaption class="product_content">
                                        <div class="product_content_inner">
                                            <h4 class="product_name">
                                                <a href="<?= $site ?>product-details/<?= $product['slug_url'] ?>">
                                                    <?= htmlspecialchars($product['pro_name']) ?>
                                                </a>
                                            </h4>
                                            
                                        
                                            <div class="price_box">
                                                <?php if ($show_sale): ?>
                                                    <span class="old_price"><?= $product['formatted_mrp'] ?></span>
                                                <?php endif; ?>
                                                <span class="current_price"><?= $product['formatted_selling_price'] ?></span>
                                            </div>
                                        </div>
                                        <!-- <div class="add_to_cart">
                                            <a class="add-to-cart" href="<?= $site ?>product-details/<?= $product['slug_url'] ?>">View Product</a>
                                        </div> -->
                                    </figcaption>
                                </figure>
                            </article>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Product Count -->
            <div class="row mt-30">
                <div class="col-12">
                    <div class="page_amount">
                        <p>Showing <?= count($products) ?> of <?= $total_products ?> results in <?= htmlspecialchars($category_name) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--shop area end-->

    <!-- Related Categories (Optional) -->
    <?php
    // Get related categories (same parent or sibling categories)
    $related_categories_sql = "
        SELECT c.* FROM categories c 
        WHERE c.status = 1 AND c.id != $category_id 
        ORDER BY RAND() LIMIT 4
    ";
    $related_result = mysqli_query($conn, $related_categories_sql);
    $related_categories = [];
    while ($cat = mysqli_fetch_assoc($related_result)) {
        $related_categories[] = $cat;
    }
    
    if (!empty($related_categories)):
    ?>
    <div class="related_categories_area mb-80">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="section_title text-center mb-40">
                        <h3>Explore Related Categories</h3>
                    </div>
                </div>
            </div>
            <div class="row">
                <?php foreach ($related_categories as $related_cat): 
                    $related_slug = !empty($related_cat['slug_url']) ? $related_cat['slug_url'] : 
                                    strtolower(str_replace(' ', '-', $related_cat['categories']));
                ?>
                    <div class="col-lg-3 col-md-3 col-sm-6">
                        <div class="single_category text-center mb-30">
                            <div class="category_thumb">
                                <a href="<?= $site ?>category/<?= $related_slug ?>">
                                    <img src="<?= $site ?>admin/uploads/category/<?= $related_cat['image'] ?>" alt="<?= htmlspecialchars($related_cat['categories']) ?>">
                                </a>
                            </div>
                            <div class="category_name mt-3">
                                <h4><a href="<?= $site ?>category/<?= $related_slug ?>"><?= htmlspecialchars($related_cat['categories']) ?></a></h4>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!--footer area start-->
    <?php include_once "includes/footer.php"; ?>

    <!-- JS -->
    <!--jquery min js-->
    <script src="<?= $site ?>assets/js/vendor/jquery-3.4.1.min.js"></script>
    <!--popper min js-->
    <script src="<?= $site ?>assets/js/popper.js"></script>
    <!--bootstrap min js-->
    <script src="<?= $site ?>assets/js/bootstrap.min.js"></script>
    <!--owl carousel min js-->
    <script src="<?= $site ?>assets/js/owl.carousel.min.js"></script>
    <!--slick min js-->
    <script src="<?= $site ?>assets/js/slick.min.js"></script>
    <!--magnific popup min js-->
    <script src="<?= $site ?>assets/js/jquery.magnific-popup.min.js"></script>
    <!-- Plugins JS -->
    <script src="<?= $site ?>assets/js/plugins.js"></script>

    <!-- Category Page JavaScript -->
    <script>
        $(document).ready(function() {
            // Mobile filter toggle
            $('.mobile-filter-toggle').click(function() {
                $('.filter_sidebar').toggleClass('d-none');
            });

            // Price range slider (if you want to use slider instead of inputs)
            <?php if ($price_range['min_price'] !== null && $price_range['max_price'] !== null): ?>
            $('#price-slider').slider({
                range: true,
                min: <?= $price_range['min_price'] ?? 0 ?>,
                max: <?= $price_range['max_price'] ?? 10000 ?>,
                values: [<?= $min_price ?>, <?= $max_price ?>],
                slide: function(event, ui) {
                    $('#min-price-display').text('₹' + ui.values[0]);
                    $('#max-price-display').text('₹' + ui.values[1]);
                    $('#min_price').val(ui.values[0]);
                    $('#max_price').val(ui.values[1]);
                }
            });
            <?php endif; ?>
        });
    </script>

    <style>
        .filter_sidebar {
            transition: all 0.3s ease;
        }
        
        .product_brand small {
            font-size: 12px;
        }
        
        .category_description {
            background: linear-gradient(to right, #f8f9fa, #e9ecef);
        }
        
        .single_category img {
            width: 100%;
            height: 480px;
            object-fit: cover;
            border-radius: 8px;
            transition: transform 0.3s ease;
        }
        
        .single_category img:hover {
            transform: scale(1.05);
        }
        
        @media (max-width: 768px) {
            .filter_sidebar {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                overflow-y: auto;
                z-index: 1050;
                background: white;
                padding: 20px;
                display: none;
            }
            
            .filter_sidebar:not(.d-none) {
                display: block;
            }
            
            .mobile-filter-toggle {
                display: block;
                margin-bottom: 15px;
            }
        }
    </style>

    <?php include_once "includes/footer-link.php"; ?>

</body>

</html>