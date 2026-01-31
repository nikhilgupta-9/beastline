<?php
session_start();
include_once "config/connect.php";
include_once "util/function.php";

// Get search query
$search_query = isset($_GET['q']) ? trim(mysqli_real_escape_string($conn, $_GET['q'])) : '';

// Get filter parameters
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;
$brand_id = isset($_GET['brand']) ? intval($_GET['brand']) : 0;
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 100000;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build search query similar to shop page
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
    WHERE p.status = 1
";

// Apply search conditions
$conditions = [];
$params = [];
$types = "";

if (!empty($search_query)) {
    $conditions[] = "(p.pro_name LIKE ? OR p.short_desc LIKE ? OR p.description LIKE ? OR c.categories LIKE ? OR b.brand_name LIKE ?)";
    $search_term = "%$search_query%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term, $search_term]);
    $types .= "sssss";
}

if ($category_id > 0) {
    $conditions[] = "p.pro_sub_cate = ?";
    $params[] = $category_id;
    $types .= "i";
}

if ($brand_id > 0) {
    $conditions[] = "p.brand_name = ?";
    $params[] = $brand_id;
    $types .= "i";
}

if (!empty($conditions)) {
    $sql_pro .= " AND " . implode(" AND ", $conditions);
} else {
    // If no search query and no filters, show nothing or all products
    // For search page, we might want to show nothing if no query
    if (empty($search_query)) {
        $sql_pro .= " AND 1=0"; // Show no results
    }
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
    default: // 'newest' or 'relevance'
        if (!empty($search_query)) {
            // For search results, you might want to order by relevance
            // Simple relevance: products with search term in name first
            $sql_pro .= " ORDER BY 
                CASE 
                    WHEN p.pro_name LIKE ? THEN 1
                    WHEN p.short_desc LIKE ? THEN 2
                    WHEN p.description LIKE ? THEN 3
                    ELSE 4
                END,
                p.pro_id DESC";
            $search_like = "%$search_query%";
            $params[] = $search_like;
            $params[] = $search_like;
            $params[] = $search_like;
            $types .= "sss";
        } else {
            $sql_pro .= " ORDER BY p.pro_id DESC";
        }
        break;
}

// Prepare and execute query
$stmt = mysqli_prepare($conn, $sql_pro);
if ($stmt) {
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
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
    // Fallback to simple query if search is provided
    $products = [];
    $total_products = 0;
    
    if (!empty($search_query)) {
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
            WHERE p.status = 1 
                AND (p.pro_name LIKE '%$search_query%' 
                    OR p.short_desc LIKE '%$search_query%' 
                    OR p.description LIKE '%$search_query%'
                    OR c.categories LIKE '%$search_query%'
                    OR b.brand_name LIKE '%$search_query%')
            ORDER BY p.pro_id DESC
        ";
        
        $result = mysqli_query($conn, $fallback_sql);
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
}

// Get categories for filtering
$categories_sql = "SELECT * FROM categories WHERE status = 1 ORDER BY categories";
$categories_result = mysqli_query($conn, $categories_sql);
$categories = [];
while ($cat = mysqli_fetch_assoc($categories_result)) {
    $categories[] = $cat;
}

// Get brands for filtering
$brands_sql = "SELECT * FROM brands ORDER BY brand_name";
$brands_result = mysqli_query($conn, $brands_sql);
$brands = [];
while ($brand = mysqli_fetch_assoc($brands_result)) {
    $brands[] = $brand;
}

$contact = contact_us();
?>
<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title><?= !empty($search_query) ? 'Search Results for: ' . htmlspecialchars($search_query) : 'Search Products' ?> | Beastline</title>
    <meta name="description" content="">
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
                        <h3>Search Results<?= !empty($search_query) ? ' for: ' . htmlspecialchars($search_query) : '' ?></h3>
                        <ul>
                            <li><a href="<?= $site ?>">home</a></li>
                            <li>Search</li>
                            <?php if (!empty($search_query)): ?>
                                <li><?= htmlspecialchars($search_query) ?></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--breadcrumbs area end-->

    <!--search results area start-->
    <div class="shop_area shop_fullwidth mb-80">
        <div class="container">
            <!-- Search Info -->
            <div class="row mb-30">
                <div class="col-12">
                    <div class="search_info">
                        <?php if (!empty($search_query)): ?>
                            <h4>Found <?= $total_products ?> product<?= $total_products != 1 ? 's' : '' ?> for "<?= htmlspecialchars($search_query) ?>"</h4>
                        <?php else: ?>
                            <h4>Enter search term to find products</h4>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Filter and Sort Section -->
            <?php if (!empty($search_query)): ?>
            <div class="row mb-30">
                <div class="col-12">
                    <div class="shop_toolbar_wrapper">
                        <div class="row align-items-left">
                            <!-- Center - Search -->
                            <div class="col-lg-5 col-md-4">
                                <form method="GET" action="<?= $site ?>search/" class="search_form">
                                    <div class="input-group">
                                        <input type="text" name="q" class="form-control"
                                            placeholder="Search products..." value="<?= htmlspecialchars($search_query) ?>" required>
                                        <input type="hidden" name="category" value="<?= $category_id ?>">
                                        <input type="hidden" name="brand" value="<?= $brand_id ?>">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="pe-7s-search"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Right Side - Sort -->
                            <div class="col-lg-4 col-md-4">
                                <div class="niceselect_option">
                                    <form class="select_option" method="GET" action="<?= $site ?>search/">
                                        <input type="hidden" name="q" value="<?= htmlspecialchars($search_query) ?>">
                                        <input type="hidden" name="category" value="<?= $category_id ?>">
                                        <input type="hidden" name="brand" value="<?= $brand_id ?>">
                                        <select name="sort" id="sort" class="form-control" onchange="this.form.submit()">
                                            <option value="newest" <?= $sort_by == 'newest' ? 'selected' : '' ?>>Sort by relevance</option>
                                            <option value="price_low" <?= $sort_by == 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                                            <option value="price_high" <?= $sort_by == 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                                            <option value="popular" <?= $sort_by == 'popular' ? 'selected' : '' ?>>Most Popular</option>
                                        </select>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Sidebar (Optional) -->
            <div class="row mb-30">
                <div class="col-12">
                    <div class="filter_sidebar">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="filter_group">
                                    <h5>Categories</h5>
                                    <form method="GET" action="<?= $site ?>search/" class="filter_form">
                                        <input type="hidden" name="q" value="<?= htmlspecialchars($search_query) ?>">
                                        <input type="hidden" name="brand" value="<?= $brand_id ?>">
                                        <input type="hidden" name="sort" value="<?= $sort_by ?>">
                                        <select name="category" class="form-control" onchange="this.form.submit()">
                                            <option value="0">All Categories</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category['id'] ?>" <?= $category_id == $category['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($category['categories']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="filter_group">
                                    <h5>Brands</h5>
                                    <form method="GET" action="<?= $site ?>search/" class="filter_form">
                                        <input type="hidden" name="q" value="<?= htmlspecialchars($search_query) ?>">
                                        <input type="hidden" name="category" value="<?= $category_id ?>">
                                        <input type="hidden" name="sort" value="<?= $sort_by ?>">
                                        <select name="brand" class="form-control" onchange="this.form.submit()">
                                            <option value="0">All Brands</option>
                                            <?php foreach ($brands as $brand): ?>
                                                <option value="<?= $brand['id'] ?>" <?= $brand_id == $brand['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($brand['brand_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Products Section -->
            <div class="row">
                <?php if (empty($products) && !empty($search_query)): ?>
                    <div class="col-12">
                        <div class="alert alert-warning text-center py-5">
                            <h4 class="mb-3">No products found for "<?= htmlspecialchars($search_query) ?>"!</h4>
                            <p class="mb-4">Try different keywords or check the spelling.</p>
                            <div class="suggestions mt-4">
                                <h5>Suggestions:</h5>
                                <ul class="list-inline">
                                    <li class="list-inline-item"><a href="<?= $site ?>shop/" class="btn btn-outline-primary">Browse All Products</a></li>
                                    <li class="list-inline-item"><a href="<?= $site ?>categories/" class="btn btn-outline-primary">Browse Categories</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php elseif (empty($search_query)): ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center py-5">
                            <h4 class="mb-3">Search for products</h4>
                            <p class="mb-4">Enter a search term in the box above to find products.</p>
                            <div class="popular_searches mt-4">
                                <h5>Popular Searches:</h5>
                                <ul class="list-inline">
                                    <?php
                                    $popular_searches = ['T-Shirts', 'Jeans', 'Shoes', 'Watches', 'Bags'];
                                    foreach ($popular_searches as $term):
                                    ?>
                                        <li class="list-inline-item">
                                            <a href="<?= $site ?>search/?q=<?= urlencode($term) ?>" class="btn btn-outline-secondary"><?= $term ?></a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $index => $product): 
                        // Determine secondary image
                        $secondary_img_num = ($index % 4) + 2; // This will cycle through 2, 3, 4, 5, etc.

                        // Calculate discount if available
                        $show_sale = isset($product['mrp']) && isset($product['selling_price']) &&
                            $product['mrp'] > $product['selling_price'];

                        // Primary image path
                        if (isset($product['pro_img']) && strpos($product['pro_img'], 'assets/') === false) {
                            $primary_img = $site . 'admin/assets/img/uploads/' . $product['pro_img'];
                        } else {
                            $primary_img = isset($product['pro_img']) ? $product['pro_img'] : "assets/img/product/product" . (($index * 2) + 1) . ".jpg";
                        }
                    ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                            <article class="single_product">
                                <figure>
                                    <div class="product_thumb">
                                        <a class="primary_img" href="<?= $site ?>product-details/<?= $product['slug_url'] ?>">
                                            <img src="<?= $primary_img ?>" alt="<?= htmlspecialchars($product['pro_name']) ?>" style="height: 300px; object-fit: cover;">
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
                                            <?php if (!empty($product['short_desc'])): ?>
                                                <p class="product_desc mb-2">
                                                    <small><?= substr(htmlspecialchars($product['short_desc']), 0, 50) ?>...</small>
                                                </p>
                                            <?php endif; ?>
                                            <?php if (!empty($product['categories'])): ?>
                                                <div class="product_category mb-2">
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars($product['categories']) ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                            <div class="price_box">
                                                <?php if ($show_sale): ?>
                                                    <span class="old_price"><?= $product['formatted_mrp'] ?></span>
                                                <?php endif; ?>
                                                <span class="current_price"><?= $product['formatted_selling_price'] ?></span>
                                            </div>
                                        </div>
                                       
                                    </figcaption>
                                </figure>
                            </article>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Product Count -->
            <?php if (!empty($search_query) && !empty($products)): ?>
            <div class="row mt-30">
                <div class="col-12">
                    <div class="page_amount">
                        <p>Showing <?= count($products) ?> of <?= $total_products ?> results for "<?= htmlspecialchars($search_query) ?>"</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <!--search results area end-->

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

    <!-- Search Page JavaScript -->
    <script>
        $(document).ready(function() {
            // Highlight search term in results
            <?php if (!empty($search_query)): ?>
            function highlightText(text) {
                const searchTerm = "<?= addslashes($search_query) ?>";
                const regex = new RegExp(`(${searchTerm})`, 'gi');
                return text.replace(regex, '<span class="highlight">$1</span>');
            }

            // Apply highlighting to product names and descriptions
            $('.product_name a').each(function() {
                const originalText = $(this).text();
                $(this).html(highlightText(originalText));
            });

            $('.product_desc').each(function() {
                const originalText = $(this).text();
                $(this).html(highlightText(originalText));
            });
            <?php endif; ?>

            // Search suggestions for header (if you want to keep it)
            $('#headerSearchInput').on('input', function() {
                const query = $(this).val();
                if (query.length >= 2) {
                    $.ajax({
                        url: '<?= $site ?>ajax/search-suggestions.php',
                        method: 'GET',
                        data: { q: query },
                        success: function(response) {
                            $('#searchSuggestions').html(response).show();
                        }
                    });
                } else {
                    $('#searchSuggestions').hide();
                }
            });

            // Close suggestions when clicking outside
            $(document).click(function(e) {
                if (!$(e.target).closest('.search_list').length) {
                    $('#searchSuggestions').hide();
                }
            });
        });
    </script>

    <!-- Add some CSS for highlighting -->
    <style>
        .highlight {
            background-color: #fff9c4;
            padding: 0 2px;
            border-radius: 2px;
            font-weight: bold;
        }
        
        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        
        .suggestion-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }
        
        .suggestion-item:hover {
            background-color: #f8f9fa;
        }
        
        .filter_sidebar {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .filter_group {
            margin-bottom: 15px;
        }
        
        .filter_group h5 {
            margin-bottom: 10px;
            font-size: 16px;
            font-weight: 600;
        }
    </style>

    <?php include_once "includes/footer-link.php"; ?>

</body>

</html>