<?php
session_start();
include_once "config/connect.php";
include_once "util/function.php";

// Get filter parameters
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;
$brand_id = isset($_GET['brand']) ? intval($_GET['brand']) : 0;
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 100000;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Build query with filters
$sql_pro = "SELECT p.*, c.categories, b.brand_name 
            FROM products p 
            LEFT JOIN categories c ON p.pro_sub_cate = c.id
            LEFT JOIN brands b ON p.brand_name = b.id
            WHERE p.status = 1";

// Apply filters
$conditions = [];
$params = [];
$types = "";

if (!empty($search)) {
    $conditions[] = "(p.pro_name LIKE ? OR p.short_desc LIKE ? OR p.pro_desc LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
    $types .= "sss";
}

if ($category_id > 0) {
    $conditions[] = "p.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

if ($brand_id > 0) {
    $conditions[] = "p.brand_id = ?";
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
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $products = [];
    $total_products = 0;
    
    while ($row = mysqli_fetch_assoc($result)) {
        // Format prices
        $row['formatted_price'] = '₹' . number_format($row['mrp'], 2);
        $row['formatted_sale_price'] = '₹' . number_format($row['selling_price'], 2);
        $products[] = $row;
        $total_products++;
    }
    mysqli_stmt_close($stmt);
} else {
    // Fallback to simple query
    $result = mysqli_query($conn, "SELECT p.*, c.categories, b.brand_name 
                                  FROM products p 
                                  LEFT JOIN categories c ON p.pro_sub_cate = c.id
                                  LEFT JOIN brands b ON p.brand_name = b.id
                                  WHERE p.status = 1 
                                  ORDER BY p.pro_id DESC");
    $products = [];
    $total_products = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $row['formatted_price'] = '₹' . number_format($row['mrp'], 2);
        $row['formatted_sale_price'] = '₹' . number_format($row['selling_price'], 2);
        $products[] = $row;
        $total_products++;
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
    <title>Shop All Products | Beastline</title>
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
                        <h3>Shop All Products</h3>
                        <ul>
                            <li><a href="<?= $site ?>">home</a></li>
                            <li>Shop</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>         
    </div>
    <!--breadcrumbs area end-->
    
    <!--shop  area start-->
    <div class="shop_area shop_fullwidth mb-80">
        <div class="container">
            <!-- Filter and Sort Section -->
            <div class="row mb-30">
                <div class="col-12">
                    <div class="shop_toolbar_wrapper">
                        <div class="row align-items-center">
                            <!-- Left Side - Grid/List View -->
                            <div class="col-lg-3 col-md-4">
                                <div class="shop_toolbar_btn">
                                    <button data-role="grid_3" type="button" class="btn-grid-3 active" data-bs-toggle="tooltip" title="3">
                                        <i class="fa fa-th"></i>
                                    </button>
                                    <button data-role="grid_4" type="button" class="btn-grid-4" data-bs-toggle="tooltip" title="4">
                                        <i class="fa fa-th-large"></i>
                                    </button>
                                    <button data-role="grid_list" type="button" class="btn-list" data-bs-toggle="tooltip" title="List">
                                        <i class="fa fa-list"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Center - Search -->
                            <div class="col-lg-5 col-md-4">
                                <form method="GET" action="<?= $site ?>shop/" class="search_form">
                                    <div class="input-group">
                                        <input type="text" name="search" class="form-control" 
                                               placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
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
                                    <form class="select_option" method="GET" action="<?= $site ?>shop/">
                                        <input type="hidden" name="category" value="<?= $category_id ?>">
                                        <input type="hidden" name="brand" value="<?= $brand_id ?>">
                                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                                        <select name="sort" id="sort" class="form-control" onchange="this.form.submit()">
                                            <option value="newest" <?= $sort_by == 'newest' ? 'selected' : '' ?>>Sort by newest</option>
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
            
            <!-- Products Section -->
            <div class="row">
                <?php if (empty($products)): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center py-5">
                        <h4 class="mb-3">No products found!</h4>
                        <p class="mb-4">Try different filters or search terms.</p>
                        <a href="<?= $site ?>shop/" class="btn btn-primary">Clear Filters</a>
                    </div>
                </div>
                <?php else: ?>
                <div class="product_carousel product_column5 owl-carousel">
                    <?php foreach ($products as $product): 
                        $image_path = !empty($product['pro_img']) ? 
                            $site . 'admin/assets/img/uploads/' . $product['pro_img'] : 
                            $site . 'assets/img/s-product/product.jpg';
                        
                        // Calculate discount percentage
                        $discount = 0;
                        if ($product['mrp'] > $product['selling_price']) {
                            $discount = round((($product['mrp'] - $product['selling_price']) / $product['mrp']) * 100);
                        }
                    ?>
                    <div class="col-lg-3">
                        <article class="single_product">
                            <figure>
                                <div class="product_thumb">
                                    <a class="primary_img" href="<?= $site ?>product-details/<?= $product['slug_url'] ?>">
                                        <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($product['pro_name']) ?>" style="height: 250px; object-fit: cover;">
                                    </a>
                                    <?php if ($discount > 0): ?>
                                    <div class="label_product">
                                        <span class="label_sale"><?= $discount ?>% OFF</span>
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
                                        <?php if (!empty($product['category_name'])): ?>
                                        <div class="product_category mb-2">
                                            <small class="text-muted">
                                                <a href="<?= $site ?>category/<?= $product['category_slug'] ?? '#' ?>">
                                                    <?= htmlspecialchars($product['category_name']) ?>
                                                </a>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                        <div class="price_box">
                                            <?php if ($product['mrp'] > $product['selling_price']): ?>
                                            <span class="old_price"><?= $product['formatted_price'] ?></span>
                                            <?php endif; ?>
                                            <span class="current_price"><?= $product['formatted_sale_price'] ?></span>
                                        </div>
                                    </div>
                                    <div class="add_to_cart mt-3">
                                        <a class="add-to-cart btn btn-primary w-100" href="<?= $site ?>product-details/<?= $product['slug_url'] ?>">
                                            View Product
                                        </a>
                                    </div>
                                </figcaption>
                            </figure>
                        </article>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Product Count -->
            <div class="row mt-30">
                <div class="col-12">
                    <div class="page_amount">
                        <p>Showing <?= count($products) ?> of <?= $total_products ?> results</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--shop  area end-->
    
    <!--footer area start-->
    <?php include_once "includes/footer.php"; ?>
    
    <!-- Quick View Modal -->
    <div class="modal fade" id="quickViewModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="ion-android-close"></i></span>
                </button>
                <div class="modal_body" id="quickViewContent">
                    <!-- Content loaded via AJAX -->
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
    
    <!-- Shop Page JavaScript -->
    <script>
    $(document).ready(function() {
        // Initialize product carousel
        $('.product_carousel').owlCarousel({
            loop: true,
            margin: 20,
            nav: true,
            navText: ['<i class="fa fa-angle-left"></i>', '<i class="fa fa-angle-right"></i>'],
            dots: false,
            responsive: {
                0: {
                    items: 1
                },
                480: {
                    items: 2
                },
                768: {
                    items: 3
                },
                992: {
                    items: 4
                },
                1200: {
                    items: 4
                }
            }
        });
        
        // Grid/List view toggle
        $('.shop_toolbar_btn button').click(function() {
            $('.shop_toolbar_btn button').removeClass('active');
            $(this).addClass('active');
            
            var viewType = $(this).data('role');
            var $shopWrapper = $('.shop_wrapper');
            var $productCarousel = $('.product_carousel');
            
            if (viewType === 'grid_list') {
                // Switch to list view
                $productCarousel.addClass('d-none');
                $shopWrapper.removeClass('d-none');
                $('.single_product').addClass('list_view');
            } else {
                // Switch to grid view
                $productCarousel.removeClass('d-none');
                $shopWrapper.addClass('d-none');
                $('.single_product').removeClass('list_view');
                
                // Update grid columns
                var cols = viewType.split('_')[1];
                $('.shop_wrapper').removeClass('grid_3 grid_4').addClass('grid_' + cols);
            }
        });
        
        // Quick View Modal
        $('#quickViewModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var productId = button.data('product-id');
            
            $.ajax({
                url: '<?= $site ?>ajax/quick-view.php',
                method: 'GET',
                data: { product_id: productId },
                success: function(response) {
                    $('#quickViewContent').html(response);
                },
                error: function() {
                    $('#quickViewContent').html('<div class="text-center py-5"><p>Error loading product details.</p></div>');
                }
            });
        });
        
        // Add to wishlist
        $('.add-to-wishlist').click(function(e) {
            e.preventDefault();
            var productId = $(this).data('product-id');
            var $this = $(this);
            
            $.ajax({
                url: '<?= $site ?>ajax/add-to-wishlist.php',
                method: 'POST',
                data: { product_id: productId },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                    } else {
                        alert(response.message);
                    }
                }
            });
        });
        
        // Price range filter
        $('#slider-range').slider({
            range: true,
            min: 0,
            max: 10000,
            values: [<?= $min_price ?>, <?= $max_price ?>],
            slide: function(event, ui) {
                $('#amount').val('₹' + ui.values[0] + ' - ₹' + ui.values[1]);
                $('#min_price').val(ui.values[0]);
                $('#max_price').val(ui.values[1]);
            }
        });
        $('#amount').val('₹' + $('#slider-range').slider('values', 0) + ' - ₹' + $('#slider-range').slider('values', 1));
    });
    </script>

    <?php include_once "includes/footer-link.php"; ?>

</body>
</html>