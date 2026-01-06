<?php
include_once "config/connect.php";
include_once "util/function.php";

// Get current category from slug
$category_slug = $_GET['alias'] ?? '';
$category = get_category_by_slug($category_slug);

if (!$category) {
    // Redirect to shop page if category not found
    header("Location: " . $site . "shop/");
    exit;
}

$category_id = $category['id'];
$category_name = $category['categories'];

// Get category hierarchy for breadcrumbs
$category_hierarchy = get_category_hierarchy($category_id);

// Get filter parameters
$filters = [
    'min_price' => $_GET['min_price'] ?? null,
    'max_price' => $_GET['max_price'] ?? null,
    'brands' => isset($_GET['brand']) ? (array)$_GET['brand'] : [],
    'colors' => isset($_GET['color']) ? (array)$_GET['color'] : [],
    'sizes' => isset($_GET['size']) ? (array)$_GET['size'] : [],
    'materials' => isset($_GET['material']) ? (array)$_GET['material'] : [],
    'sort' => $_GET['sort'] ?? 'newest'
];

// Get products with filters
$products = get_filtered_products($category_id, $filters);

// Get filter data
$categories = get_categories_for_sidebar();
$brands = get_brands_for_filter();
$colors = get_colors_for_filter();
$sizes = get_sizes_for_filter();
$price_range = get_price_range($category_id);

$contact = contact_us();
?>

<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title><?= htmlspecialchars($category_name) ?> | Beastline</title>
    <meta name="description" content="<?= htmlspecialchars($category['meta_description'] ?? '') ?>">
    <meta name="keywords" content="<?= htmlspecialchars($category['meta_keywords'] ?? '') ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="<?= $site ?>assets/img/favicon/favicon.ico">

    <!-- CSS -->
    <!-- CSS 
    ========================= -->
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
                        <h3><?= $category['categories'] ?? 'Shop' ?></h3>
                        <ul>
                            <li><a href="<?= $site ?>">home</a></li>
                            <li><?= $category['categories'] ?? 'shop' ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--breadcrumbs area end-->

    <!--shop area start-->
    <div class="shop_area shop_reverse mb-80">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-12">
                    <!--sidebar widget start-->
                    <aside class="sidebar_widget">
                        <div class="widget_inner">
                            <!-- Categories -->
                            <div class="widget_list widget_categories">
                                <h3>Categories</h3>
                                <ul>
                                    <?php foreach ($categories as $cat): ?>
                                        <li class="widget_sub_categories sub_categories<?= $cat['id'] ?>">
                                            <a href="javascript:void(0)"><?= htmlspecialchars($cat['categories']) ?>
                                                <span>(<?= $cat['product_count'] ?>)</span>
                                            </a>
                                            <?php
                                            $subcategories = get_subcategories($cat['id']);
                                            if (!empty($subcategories)):
                                            ?>
                                                <ul class="widget_dropdown_categories dropdown_categories<?= $cat['id'] ?>">
                                                    <?php foreach ($subcategories as $subcat): ?>
                                                        <li>
                                                            <a href="<?= $site ?>category/<?= $subcat['slug'] ?>">
                                                                <?= htmlspecialchars($subcat['categories']) ?>
                                                            </a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <!-- Price Filter -->
                            <div class="widget_list widget_filter">
                                <h3>Filter by price</h3>
                                <form id="priceFilterForm">
                                    <div id="slider-range"
                                        data-min="<?= $price_range['min_price'] ?? 0 ?>"
                                        data-max="<?= $price_range['max_price'] ?? 10000 ?>"></div>
                                    <button type="button" id="applyPriceFilter">Filter</button>
                                    <input type="text" name="price_range" id="amount" readonly />
                                </form>
                            </div>

                            <!-- Color Filter -->
                            <div class="widget_list widget_color">
                                <h3>Select By Color</h3>
                                <ul>
                                    <?php foreach ($colors as $color): ?>
                                        <li>
                                            <label class="color_filter">
                                                <input type="checkbox" name="color" value="<?= htmlspecialchars($color['color']) ?>"
                                                    class="filter-checkbox" <?= in_array($color['color'], $filters['colors']) ? 'checked' : '' ?>>
                                                <span class="color_dot" style="background-color: <?= strtolower($color['color']) ?>"></span>
                                                <?= htmlspecialchars($color['color']) ?>
                                                <span>(<?= $color['product_count'] ?>)</span>
                                            </label>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <!-- Size Filter -->
                            <div class="widget_list widget_color">
                                <h3>Select By Size</h3>
                                <ul>
                                    <?php foreach ($sizes as $size): ?>
                                        <li>
                                            <label class="size_filter">
                                                <input type="checkbox" name="size" value="<?= htmlspecialchars($size['size']) ?>"
                                                    class="filter-checkbox" <?= in_array($size['size'], $filters['sizes']) ? 'checked' : '' ?>>
                                                <?= htmlspecialchars($size['size']) ?>
                                                <span>(<?= $size['product_count'] ?>)</span>
                                            </label>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <!-- Brand Filter -->
                            <div class="widget_list widget_brand">
                                <h3>Brand</h3>
                                <ul>
                                    <?php foreach ($brands as $brand): ?>
                                        <li>
                                            <label class="brand_filter">
                                                <input type="checkbox" name="brand" value="<?= $brand['id'] ?>"
                                                    class="filter-checkbox" <?= in_array($brand['id'], $filters['brands']) ? 'checked' : '' ?>>
                                                <?= htmlspecialchars($brand['brand_name']) ?>
                                                <span>(<?= $brand['product_count'] ?>)</span>
                                            </label>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <!-- Tags -->
                            <div class="widget_list tags_widget">
                                <h3>Product tags</h3>
                                <div class="tag_cloud">
                                    <a href="#" class="tag-filter" data-tag="men">Men</a>
                                    <a href="#" class="tag-filter" data-tag="women">Women</a>
                                    <a href="#" class="tag-filter" data-tag="new">New</a>
                                    <a href="#" class="tag-filter" data-tag="sale">Sale</a>
                                    <a href="#" class="tag-filter" data-tag="trending">Trending</a>
                                </div>
                            </div>

                            <!-- Clear Filters -->
                            <div class="widget_list">
                                <button type="button" id="clearFilters" class="btn btn-outline-secondary btn-sm">Clear All Filters</button>
                            </div>
                        </div>
                    </aside>
                    <!--sidebar widget end-->
                </div>

                <div class="col-lg-9 col-md-12">
                    <!--shop toolbar start-->
                    <div class="shop_toolbar_wrapper">
                        <div class="shop_toolbar_btn">
                            <button data-role="grid_4" type="button" class="active btn-grid-4" data-bs-toggle="tooltip" title="4"></button>
                            <button data-role="grid_3" type="button" class="btn-grid-3" data-bs-toggle="tooltip" title="3"></button>
                            <button data-role="grid_list" type="button" class="btn-list" data-bs-toggle="tooltip" title="List"></button>
                        </div>
                        <div class="niceselect_option">
                            <form id="sortForm">
                                <select name="sort" id="sortSelect" class="filter-select">
                                    <option value="newest" <?= $filters['sort'] == 'newest' ? 'selected' : '' ?>>Sort by newness</option>
                                    <option value="price_low_high" <?= $filters['sort'] == 'price_low_high' ? 'selected' : '' ?>>Sort by price: low to high</option>
                                    <option value="price_high_low" <?= $filters['sort'] == 'price_high_low' ? 'selected' : '' ?>>Sort by price: high to low</option>
                                    <option value="name_asc" <?= $filters['sort'] == 'name_asc' ? 'selected' : '' ?>>Sort by name: A-Z</option>
                                    <option value="name_desc" <?= $filters['sort'] == 'name_desc' ? 'selected' : '' ?>>Sort by name: Z-A</option>
                                    <option value="popular" <?= $filters['sort'] == 'popular' ? 'selected' : '' ?>>Sort by popularity</option>
                                </select>
                            </form>
                        </div>
                        <div class="page_amount">
                            <p>Showing <span id="productCount"><?= count($products) ?></span> results</p>
                        </div>
                    </div>
                    <!--shop toolbar end-->

                    <!-- Products Grid -->
                    <div id="productsContainer" class="row shop_wrapper">
                        <?php if (empty($products)): ?>
                            <div class="col-12">
                                <div class="alert alert-info">No products found in this category.</div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($products as $pro):
                                // Get product variants for quick view
                                $variants_sql = "SELECT * FROM product_variants WHERE product_id = {$pro['pro_id']}";
                                $variants_result = mysqli_query($conn, $variants_sql);
                                $variants = [];
                                while ($variant = mysqli_fetch_assoc($variants_result)) {
                                    $variants[] = $variant;
                                }
                            ?>
                                <div class="col-lg-4 col-md-4 col-sm-6 col-12 ">
                                    <div class="single_product" data-product-id="<?= $pro['pro_id'] ?>">
                                        <div class="product_thumb">
                                            <a class="primary_img" href="<?= $site ?>product-details/<?= $pro['slug_url'] ?>">
                                                <img src="<?= $site ?>admin/assets/img/uploads/<?= $pro['pro_img'] ?>" alt="<?= htmlspecialchars($pro['pro_name']) ?>">
                                            </a>
                                            <a class="secondary_img" href="<?= $site ?>product-details/<?= $pro['slug_url'] ?>">
                                                <?php
                                                // Get secondary image
                                                $secondary_img_sql = "SELECT image_url FROM product_images 
                                                                 WHERE product_id = {$pro['pro_id']} AND is_main = 0 
                                                                 ORDER BY display_order LIMIT 1";
                                                $secondary_result = mysqli_query($conn, $secondary_img_sql);
                                                if ($secondary_img = mysqli_fetch_assoc($secondary_result)): ?>
                                                    <img src="<?= $site ?>admin/assets/img/uploads/<?= $secondary_img['image_url'] ?>" alt="<?= htmlspecialchars($pro['pro_name']) ?>">
                                                <?php else: ?>
                                                    <img src="<?= $site ?>assets/img/product/product2.jpg" alt="<?= htmlspecialchars($pro['pro_name']) ?>">
                                                <?php endif; ?>
                                            </a>
                                            <?php if ($pro['mrp'] > $pro['selling_price']): ?>
                                                <div class="label_product">
                                                    <span class="label_sale">Sale</span>
                                                    <?php
                                                    $discount = round((($pro['mrp'] - $pro['selling_price']) / $pro['mrp']) * 100);
                                                    if ($discount > 0): ?>
                                                        <span class="label_discount">-<?= $discount ?>%</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="action_links">
                                                <ul>
                                                    <li class="quick_button">
                                                        <a href="#" data-bs-toggle="modal" data-bs-target="#quickViewModal"
                                                            data-product-id="<?= $pro['pro_id'] ?>" title="quick view">
                                                            <span class="pe-7s-search"></span>
                                                        </a>
                                                    </li>
                                                    <li class="wishlist">
                                                        <a href="#" class="add-to-wishlist" data-product-id="<?= $pro['pro_id'] ?>" title="Add to Wishlist">
                                                            <span class="pe-7s-like"></span>
                                                        </a>
                                                    </li>
                                                    <li class="compare">
                                                        <a href="#" class="add-to-compare" data-product-id="<?= $pro['pro_id'] ?>" title="Add to Compare">
                                                            <span class="pe-7s-edit"></span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="product_content grid_content">
                                            <div class="product_content_inner">
                                                <h4 class="product_name">
                                                    <a href="<?= $site ?>product-details/<?= $pro['slug_url'] ?>">
                                                        <?= htmlspecialchars($pro['pro_name']) ?>
                                                    </a>
                                                </h4>
                                                <div class="price_box">
                                                    <?php if ($pro['mrp'] > $pro['selling_price']): ?>
                                                        <span class="old_price">₹ <?= number_format($pro['mrp'], 2) ?></span>
                                                    <?php endif; ?>
                                                    <span class="current_price">₹ <?= number_format($pro['selling_price'], 2) ?></span>
                                                </div>
                                            </div>
                                            <div class="add_to_cart">
                                                <a href="#" class="add-to-cart-btn" data-product-id="<?= $pro['pro_id'] ?>">Add to cart</a>
                                            </div>
                                        </div>
                                        <div class="product_content list_content">
                                            <h4 class="product_name">
                                                <a href="<?= $site ?>product-details/<?= $pro['slug_url'] ?>">
                                                    <?= htmlspecialchars($pro['pro_name']) ?>
                                                </a>
                                            </h4>
                                            <div class="price_box">
                                                <?php if ($pro['mrp'] > $pro['selling_price']): ?>
                                                    <span class="old_price">₹ <?= number_format($pro['mrp'], 2) ?></span>
                                                <?php endif; ?>
                                                <span class="current_price">₹ <?= number_format($pro['selling_price'], 2) ?></span>
                                            </div>
                                            <div class="product_rating">
                                                <ul>
                                                    <!-- Add rating stars here -->
                                                </ul>
                                            </div>
                                            <div class="product_desc">
                                                <p><?= htmlspecialchars(substr($pro['short_desc'], 0, 150)) ?>...</p>
                                            </div>
                                            <div class="add_to_cart shop_list_cart">
                                                <a href="#" class="add-to-cart-btn" data-product-id="<?= $pro['pro_id'] ?>">Add to cart</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination -->
                    <div class="shop_toolbar t_bottom">
                        <div class="pagination">
                            <ul id="pagination">
                                <!-- Pagination will be loaded via JavaScript -->
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--shop area end-->

    <!--footer area start-->
    <?php include_once "includes/footer.php"; ?>

    <!--footer area end-->

    <!-- Quick View Modal -->
    <div class="modal fade" id="quickViewModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="ion-android-close"></i></span>
                </button>
                <div class="modal_body">
                    <div class="container">
                        <div class="row" id="quickViewContent">
                            <!-- Quick view content will be loaded via AJAX -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mini Cart Dropdown -->
    <div class="mini_cart_wrapper" id="miniCartDropdown" style="display: none;">
        <div class="mini_cart_inner">
            <div class="mini_cart_header">
                <h3>Shopping Cart</h3>
            </div>
            <div class="mini_cart_body" id="miniCartItems">
                <!-- Cart items will be loaded here -->
            </div>
            <div class="mini_cart_footer">
                <div class="cart_total">
                    <span>Total:</span>
                    <span class="price" id="cartTotal">₹0.00</span>
                </div>
                <div class="cart_buttons">
                    <a href="<?= $site ?>cart/" class="btn btn-outline-dark">View Cart</a>
                    <a href="<?= $site ?>checkout/" class="btn btn-dark">Checkout</a>
                </div>
            </div>
        </div>
    </div>

    <?php include_once "includes/footer-link.php"; ?>


    <script>
        $(document).ready(function() {
            // Initialize price slider
            var minPrice = <?= $price_range['min_price'] ?? 0 ?>;
            var maxPrice = <?= $price_range['max_price'] ?? 10000 ?>;

            $("#slider-range").slider({
                range: true,
                min: minPrice,
                max: maxPrice,
                values: [<?= $filters['min_price'] ?? $price_range['min_price'] ?? 0 ?>, <?= $filters['max_price'] ?? $price_range['max_price'] ?? 10000 ?>],
                slide: function(event, ui) {
                    $("#amount").val("₹" + ui.values[0] + " - ₹" + ui.values[1]);
                }
            });
            $("#amount").val("₹" + $("#slider-range").slider("values", 0) + " - ₹" + $("#slider-range").slider("values", 1));

            // Cart count
            function updateCartCount() {
                $.ajax({
                    url: '<?= $site ?>ajax/get-cart-count.php',
                    method: 'GET',
                    success: function(response) {
                        $('.item_count').text(response.count);
                    }
                });
            }

            // Initialize cart count
            updateCartCount();

            // Apply filters
            function applyFilters() {
                var filters = {
                    min_price: $("#slider-range").slider("values", 0),
                    max_price: $("#slider-range").slider("values", 1),
                    brands: [],
                    colors: [],
                    sizes: [],
                    sort: $("#sortSelect").val()
                };

                // Get selected brands
                $('input[name="brand"]:checked').each(function() {
                    filters.brands.push($(this).val());
                });

                // Get selected colors
                $('input[name="color"]:checked').each(function() {
                    filters.colors.push($(this).val());
                });

                // Get selected sizes
                $('input[name="size"]:checked').each(function() {
                    filters.sizes.push($(this).val());
                });

                // Update URL without reloading page
                var url = new URL(window.location.href);
                url.searchParams.set('min_price', filters.min_price);
                url.searchParams.set('max_price', filters.max_price);
                url.searchParams.delete('brand');
                url.searchParams.delete('color');
                url.searchParams.delete('size');

                filters.brands.forEach(function(brand) {
                    url.searchParams.append('brand', brand);
                });

                filters.colors.forEach(function(color) {
                    url.searchParams.append('color', color);
                });

                filters.sizes.forEach(function(size) {
                    url.searchParams.append('size', size);
                });

                url.searchParams.set('sort', filters.sort);

                // Load products via AJAX
                $.ajax({
                    url: '<?= $site ?>ajax/load-products.php?category_id=<?= $category_id ?>&' + url.searchParams.toString(),
                    method: 'GET',
                    beforeSend: function() {
                        $('#productsContainer').html('<div class="col-12 text-center"><div class="spinner-border" role="status"></div></div>');
                    },
                    success: function(response) {
                        $('#productsContainer').html(response);
                        $('#productCount').text($('#productsContainer .single_product').length);
                        updateURL(url.toString());
                    }
                });
            }

            // Update browser URL without reload
            function updateURL(url) {
                window.history.pushState({
                    path: url
                }, '', url);
            }

            // Event listeners for filters
            $('.filter-checkbox').change(function() {
                applyFilters();
            });

            $('#sortSelect').change(function() {
                applyFilters();
            });

            $('#applyPriceFilter').click(function() {
                applyFilters();
            });

            $('#clearFilters').click(function() {
                $('.filter-checkbox').prop('checked', false);
                $("#slider-range").slider("values", [minPrice, maxPrice]);
                $("#amount").val("₹" + minPrice + " - ₹" + maxPrice);
                $("#sortSelect").val('newest');
                applyFilters();
            });

            // Add to cart AJAX
            $(document).on('click', '.add-to-cart-btn', function(e) {
                e.preventDefault();

                var productId = $(this).data('product-id');
                var hasVariants = $(this).data('has-variants');
                var button = $(this);

                // If product has variants, redirect to product page
                if (hasVariants == 1) {
                    window.location.href = '<?= $site ?>product-details/' + $(this).data('product-slug');
                    return;
                }

                $.ajax({
                    url: '<?= $site ?>ajax/add-to-cart.php',
                    method: 'POST',
                    data: {
                        product_id: productId,
                        quantity: 1,
                        action: 'add_to_cart'
                    },
                    beforeSend: function() {
                        button.html('<span class="spinner-border spinner-border-sm"></span> Adding...');
                        button.prop('disabled', true);
                    },
                    success: function(response) {
                        if (response.success) {
                            button.html('<i class="fa fa-check"></i> Added');
                            $('.item_count').text(response.cart_count);

                            // Show success toast/notification
                            showNotification(response.message, 'success');

                            // Update mini cart
                            updateMiniCart();

                            setTimeout(function() {
                                button.html('Add to cart');
                                button.prop('disabled', false);
                            }, 1500);
                        } else {
                            showNotification(response.message, 'error');
                            button.html('Add to cart');
                            button.prop('disabled', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        showNotification('Error adding to cart. Please try again.', 'error');
                        button.html('Add to cart');
                        button.prop('disabled', false);
                    }
                });
            });

            // Add to wishlist AJAX
            $(document).on('click', '.add-to-wishlist', function(e) {
                e.preventDefault();
                var productId = $(this).data('product-id');
                var button = $(this);

                $.ajax({
                    url: '<?= $site ?>ajax/add-to-wishlist.php',
                    method: 'POST',
                    data: {
                        product_id: productId,
                        action: 'add_to_wishlist'
                    },
                    beforeSend: function() {
                        button.find('span').addClass('text-danger');
                    },
                    success: function(response) {
                        if (response.success) {
                            button.find('span').removeClass('pe-7s-like').addClass('fa fa-heart text-danger');
                            alert(response.message);
                        } else {
                            if (response.message.includes('login')) {
                                window.location.href = '<?= $site ?>user-login/';
                            } else {
                                alert(response.message);
                            }
                        }
                    }
                });
            });

            // Quick view modal
            $('#quickViewModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var productId = button.data('product-id');

                $.ajax({
                    url: '<?= $site ?>ajax/quick-view.php',
                    method: 'GET',
                    data: {
                        product_id: productId
                    },
                    success: function(response) {
                        $('#quickViewContent').html(response);
                    }
                });
            });

            // Update mini cart dropdown
            function updateMiniCart() {
                $.ajax({
                    url: '<?= $site ?>ajax/get-mini-cart.php',
                    method: 'GET',
                    success: function(response) {
                        $('#miniCartItems').html(response.items);
                        $('#cartTotal').text('₹' + response.total);
                    }
                });
            }

            // Toggle mini cart dropdown
            $('.mini_cart_wrapper_trigger').click(function(e) {
                e.preventDefault();
                updateMiniCart();
                $('#miniCartDropdown').toggle();
            });

            // Close dropdown when clicking outside
            $(document).click(function(e) {
                if (!$(e.target).closest('.header_account_area').length) {
                    $('#miniCartDropdown').hide();
                }
            });
        });
    </script>
</body>

</html>