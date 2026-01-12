<?php
include_once(__DIR__ . "/../config/connect.php");
include_once(__DIR__ . "/../util/function.php");

$contact = contact_us();

// Get categories for main menu
$main_categories_sql = "SELECT c.*, 
                               (SELECT COUNT(*) FROM products p WHERE p.pro_cate = c.id AND p.status = 1) as product_count
                        FROM categories c 
                        WHERE c.parent_id = 0 AND c.status = 1 
                        ORDER BY c.display_order";
$main_categories_result = mysqli_query($conn, $main_categories_sql);
$main_categories = [];
while($cat = mysqli_fetch_assoc($main_categories_result)) {
    $main_categories[] = $cat;
}

// Get cart count
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
?>

<!--offcanvas menu area start-->
<div class="off_canvars_overlay"></div>
<div class="offcanvas_menu">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="canvas_open">
                    <a href="javascript:void(0)"><i class="ion-navicon-round"></i></a>
                </div>
                <div class="offcanvas_menu_wrapper">
                    <div class="canvas_close">
                        <a href="javascript:void(0)"><i class="ion-android-close"></i></a>
                    </div>

                    <div id="menu" class="text-left ">
                        <ul class="offcanvas_main_menu">
                            <li class="menu-item-has-children active">
                                <a href="<?= $site ?>">Home</a>
                            </li>
                            
                            <li class="menu-item-has-children">
                                <a href="<?= $site ?>shop/">Shop</a>
                                <?php if (!empty($main_categories)): ?>
                                <ul class="sub-menu">
                                    <li class="menu-item-has-children">
                                        <a href="#">Categories</a>
                                        <ul class="sub-menu">
                                            <?php foreach($main_categories as $category): ?>
                                            <li>
                                                <a href="<?= $site ?>category/<?= $category['slug'] ?>/">
                                                    <?= htmlspecialchars($category['categories']) ?> 
                                                    (<?= $category['product_count'] ?>)
                                                </a>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </li>
                                    <li class="menu-item-has-children">
                                        <a href="#">Shop Pages</a>
                                        <ul class="sub-menu">
                                            <li><a href="<?= $site ?>shop/">All Products</a></li>
                                            <li><a href="<?= $site ?>new-arrivals/">New Arrivals</a></li>
                                            <li><a href="<?= $site ?>trending/">Trending</a></li>
                                            <li><a href="<?= $site ?>sale/">Sale</a></li>
                                        </ul>
                                    </li>
                                    <li class="menu-item-has-children">
                                        <a href="#">Account</a>
                                        <ul class="sub-menu">
                                            <li><a href="<?= $site ?>cart/">Shopping Cart</a></li>
                                            <li><a href="<?= $site ?>wishlist/">Wishlist</a></li>
                                            <li><a href="<?= $site ?>compare/">Compare</a></li>
                                            <li><a href="<?= $site ?>checkout/">Checkout</a></li>
                                        </ul>
                                    </li>
                                </ul>
                                <?php endif; ?>
                            </li>
                            
                            <li class="menu-item-has-children">
                                <a href="<?= $site ?>blog/">Blog</a>
                                <ul class="sub-menu">
                                    <li><a href="<?= $site ?>blog/">Blog Grid</a></li>
                                    <li><a href="<?= $site ?>blog/single-post/">Blog Single</a></li>
                                </ul>
                            </li>
                            
                            <li class="menu-item-has-children">
                                <a href="#">Pages</a>
                                <ul class="sub-menu">
                                    <li><a href="<?= $site ?>about/">About Us</a></li>
                                    <li><a href="<?= $site ?>contact/">Contact Us</a></li>
                                    <li><a href="<?= $site ?>faq/">FAQ</a></li>
                                    <li><a href="<?= $site ?>terms/">Terms & Conditions</a></li>
                                    <li><a href="<?= $site ?>privacy/">Privacy Policy</a></li>
                                    <li><a href="<?= $site ?>shipping/">Shipping Policy</a></li>
                                    <li><a href="<?= $site ?>return/">Return Policy</a></li>
                                    <li><a href="<?= $site ?>404/">404 Page</a></li>
                                </ul>
                            </li>
                            
                            <?php if ($is_logged_in): ?>
                            <li class="menu-item-has-children">
                                <a href="<?= $site ?>my-account/">My Account</a>
                                <ul class="sub-menu">
                                    <li><a href="<?= $site ?>my-account/dashboard/">Dashboard</a></li>
                                    <li><a href="<?= $site ?>my-account/orders/">Orders</a></li>
                                    <li><a href="<?= $site ?>my-account/addresses/">Addresses</a></li>
                                    <li><a href="<?= $site ?>my-account/profile/">Profile</a></li>
                                    <li><a href="<?= $site ?>logout/">Logout</a></li>
                                </ul>
                            </li>
                            <?php else: ?>
                            <li><a href="<?= $site ?>user-login/">Login</a></li>
                            <li><a href="<?= $site ?>register/">Register</a></li>
                            <?php endif; ?>
                            
                            <li><a href="<?= $site ?>contact/">Contact Us</a></li>
                        </ul>
                    </div>
                    <div class="offcanvas_footer">
                        <span><a href="mailto:<?= htmlspecialchars($contact['email']) ?>">
                            <i class="fa fa-envelope-o"></i> <?= htmlspecialchars($contact['email']) ?>
                        </a></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--offcanvas menu area end-->

<!-- desktop menu  -->
<header>
    <div class="main_header sticky-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-2 col-md-4 offset-md-4 offset-lg-0 col-5 offset-3 col-sm-5">
                    <div class="logo">
                        <a href="<?= $site ?>">
                            <img src="<?= $site ?>assets/img/logo/beastline-logo.png" alt="Beastline">
                        </a>
                    </div>
                </div>
                <div class="col-lg-8">
                    <!--main menu start-->
                    <div class="main_menu menu_position">
                        <nav>
                            <ul>
                                <li><a href="<?= $site ?>">Home</a></li>
                                
                                <li class="mega_items">
                                    <a href="<?= $site ?>shop/">Shop <i class="fa fa-angle-down"></i></a>
                                    <div class="mega_menu">
                                        <ul class="mega_menu_inner">
                                            <!-- Categories Column -->
                                            <li>
                                                <a href="#" class="fw-bold">Categories</a>
                                                <ul>
                                                    <?php 
                                                    // Show first 6 main categories
                                                    $counter = 0;
                                                    foreach($main_categories as $category): 
                                                        if ($counter >= 6) break;
                                                    ?>
                                                    <li>
                                                        <a href="<?= $site ?>shop/<?= $category['slug_url'] ?>/">
                                                            <?= htmlspecialchars($category['categories']) ?> 
                                                            <span class="badge bg-light text-dark ms-1">
                                                                <?= $category['product_count'] ?>
                                                            </span>
                                                        </a>
                                                    </li>
                                                    <?php 
                                                        $counter++;
                                                    endforeach; 
                                                    ?>
                                                    <?php if (count($main_categories) > 6): ?>
                                                    <li>
                                                        <a  href="<?= $site ?>shop/categories/" class="text-primary fw-bold">
                                                            View All Categories <i class="fa fa-arrow-right ms-1"></i>
                                                        </a>
                                                    </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </li>
                                            
                                            <!-- Shop Pages Column -->
                                            <li>
                                                <a href="#" class="fw-bold">Shop Pages</a>
                                                <ul>
                                                    <li><a href="<?= $site ?>shop/">All Products</a></li>
                                                    <li><a href="<?= $site ?>new-arrivals/">New Arrivals</a></li>
                                                    <li><a href="<?= $site ?>trending/">Trending Products</a></li>
                                                    <li><a href="<?= $site ?>sale/">Sale Products</a></li>
                                                    <li><a href="<?= $site ?>best-sellers/">Best Sellers</a></li>
                                                    <li><a href="<?= $site ?>featured/">Featured Products</a></li>
                                                </ul>
                                            </li>
                                            
                                            <!-- Brands Column -->
                                            <li>
                                                <a href="#" class="fw-bold">Brands</a>
                                                <ul>
                                                    <?php 
                                                    // Get top brands
                                                    $brands_sql = "SELECT b.*, COUNT(p.pro_id) as product_count 
                                                                   FROM pro_brands b 
                                                                   LEFT JOIN products p ON b.id = p.brand_name AND p.status = 1
                                                                   WHERE b.status = 1 
                                                                   GROUP BY b.id 
                                                                   ORDER BY b.brand_name 
                                                                   LIMIT 6";
                                                    $brands_result = mysqli_query($conn, $brands_sql);
                                                    while($brand = mysqli_fetch_assoc($brands_result)):
                                                    ?>
                                                    <li>
                                                        <a href="<?= $site ?>brand/<?= strtolower(str_replace(' ', '-', $brand['brand_name'])) ?>/">
                                                            <?= htmlspecialchars($brand['brand_name']) ?>
                                                            <span class="badge bg-light text-dark ms-1">
                                                                <?= $brand['product_count'] ?>
                                                            </span>
                                                        </a>
                                                    </li>
                                                    <?php endwhile; ?>
                                                    <li>
                                                        <a href="<?= $site ?>brands/" class="text-primary">
                                                            View All Brands <i class="fa fa-arrow-right ms-1"></i>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </li>
                                            
                                            <!-- Special Offers Column -->
                                            <li>
                                                <a href="#" class="fw-bold">Special Offers</a>
                                                <ul>
                                                    <li><a href="<?= $site ?>deal-of-the-day/">Deal of the Day</a></li>
                                                    <li><a href="<?= $site ?>weekly-deals/">Weekly Deals</a></li>
                                                    <li><a href="<?= $site ?>clearance-sale/">Clearance Sale</a></li>
                                                    <li><a href="<?= $site ?>bundle-offers/">Bundle Offers</a></li>
                                                    <li><a href="<?= $site ?>gift-vouchers/">Gift Vouchers</a></li>
                                                    <li><a href="<?= $site ?>flash-sale/">Flash Sale</a></li>
                                                </ul>
                                            </li>
                                        </ul>
                                    </div>
                                </li>
                                
                                
                                <li><a href="<?= $site ?>about/">About us</a></li>
                                
                                <li>
                                    <a href="<?= $site ?>blog/">Blog <i class="fa fa-angle-down"></i></a>
                                    <ul class="sub_menu pages">
                                        <li><a href="<?= $site ?>blog/">Blog Grid</a></li>
                                        <li><a href="<?= $site ?>blog/single-post/">Blog Single</a></li>
                                        <li><a href="<?= $site ?>blog/category/fashion/">Fashion</a></li>
                                        <li><a href="<?= $site ?>blog/category/lifestyle/">Lifestyle</a></li>
                                        <li><a href="<?= $site ?>blog/category/tips/">Shopping Tips</a></li>
                                    </ul>
                                </li>
                                
                                <li><a href="<?= $site ?>contact/">Contact Us</a></li>
                                
                                <li>
                                    <a href="#">Pages <i class="fa fa-angle-down"></i></a>
                                    <ul class="sub_menu pages">
                                        <?php if ($is_logged_in): ?>
                                        <li><a href="<?= $site ?>my-account/">My Account</a></li>
                                        <li><a href="<?= $site ?>my-account/orders/">Order History</a></li>
                                        <li><a href="<?= $site ?>my-account/wishlist/">Wishlist</a></li>
                                        <li><a href="<?= $site ?>my-account/profile/">Profile</a></li>
                                        <?php else: ?>
                                        <li><a href="<?= $site ?>user-login/">Login</a></li>
                                        <li><a href="<?= $site ?>register/">Register</a></li>
                                        <?php endif; ?>
                                        <li><a href="<?= $site ?>cart/">Shopping Cart</a></li>
                                        <li><a href="<?= $site ?>checkout/">Checkout</a></li>
                                        <li><a href="<?= $site ?>faq/">FAQ</a></li>
                                        <li><a href="<?= $site ?>terms/">Terms & Conditions</a></li>
                                        <li><a href="<?= $site ?>privacy/">Privacy Policy</a></li>
                                        <li><a href="<?= $site ?>shipping/">Shipping Info</a></li>
                                        <li><a href="<?= $site ?>return/">Return Policy</a></li>
                                        <li><a href="<?= $site ?>track-order/">Track Order</a></li>
                                    </ul>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <!--main menu end-->
                </div>
                <div class="col-lg-2 col-md-4 col-sm-4 col-4">
                    <div class="header_account_area">
                        <!-- Search -->
                        <div class="header_account_list search_list">
                            <a href="javascript:void(0)"><span class="pe-7s-search"></span></a>
                            <div class="dropdown_search">
                                <form action="<?= $site ?>search/" method="GET" id="headerSearchForm">
                                    <input name="q" placeholder="Search products..." type="text" id="headerSearchInput">
                                    <button type="submit"><span class="pe-7s-search"></span></button>
                                </form>
                                <div id="searchSuggestions" class="search-suggestions"></div>
                            </div>
                        </div>

                        <!-- Cart -->
                        <div class="header_account_list mini_cart_wrapper_trigger">
                            <a href="<?= $site ?>cart/" class="cart-trigger">
                                <span class="pe-7s-shopbag"></span>
                                <span class="item_count"><?= $cart_count ?></span>
                            </a>
                        </div>

                        <!-- User Account -->
                        <div class="language_currency header_account_list">
                            <a href="#"><span class="pe-7s-user"></span></a>
                            <ul class="dropdown_currency">
                                <?php if ($is_logged_in): 
                                    // Get user info
                                    $user_sql = "SELECT first_name, last_name FROM users WHERE id = " . $_SESSION['user_id'];
                                    $user_result = mysqli_query($conn, $user_sql);
                                    $user = mysqli_fetch_assoc($user_result);
                                ?>
                                    <li class="user-welcome">
                                        <span class="text-muted small">
                                            Hi, <?= htmlspecialchars($user['first_name'] ?? 'User') ?>!
                                        </span>
                                    </li>
                                    <li><a href="<?= $site ?>my-account/"><i class="fa fa-user me-2"></i>My Account</a></li>
                                    <li><a href="<?= $site ?>my-account/orders/"><i class="fa fa-shopping-bag me-2"></i>Orders</a></li>
                                    <li><a href="<?= $site ?>my-account/wishlist/"><i class="fa fa-heart me-2"></i>Wishlist</a></li>
                                    <li><a href="<?= $site ?>my-account/profile/"><i class="fa fa-cog me-2"></i>Settings</a></li>
                                    <li class="dropdown-divider"></li>
                                    <li><a href="<?= $site ?>logout/"><i class="fa fa-sign-out me-2"></i>Logout</a></li>
                                <?php else: ?>
                                    <li><a href="<?= $site ?>user-login/"><i class="fa fa-sign-in me-2"></i>Login</a></li>
                                    <li><a href="<?= $site ?>register/"><i class="fa fa-user-plus me-2"></i>Register</a></li>
                                    <li class="dropdown-divider"></li>
                                    <li><a href="<?= $site ?>track-order/"><i class="fa fa-truck me-2"></i>Track Order</a></li>
                                    <li><a href="<?= $site ?>help/"><i class="fa fa-question-circle me-2"></i>Help</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--mini cart (side cart)-->
    <div class="mini_cart" id="miniCartSidebar">
        <div class="cart_gallery">
            <div class="cart_close">
                <div class="cart_text">
                    <h3>Shopping Cart</h3>
                </div>
                <div class="mini_cart_close">
                    <a href="javascript:void(0)"><i class="ion-android-close"></i></a>
                </div>
            </div>
            
            <div id="miniCartContent">
                <!-- Cart items will be loaded here via AJAX -->
                <div class="empty-cart text-center py-5">
                    <i class="pe-7s-cart" style="font-size: 60px; color: #ddd;"></i>
                    <p class="mt-3">Your cart is empty</p>
                    <a href="<?= $site ?>shop/" class="btn btn-dark mt-2">Continue Shopping</a>
                </div>
            </div>
        </div>
        <div class="mini_cart_table" id="miniCartTotals" style="display: none;">
            <div class="cart_table_border">
                <div class="cart_total">
                    <span>Subtotal:</span>
                    <span class="price" id="cartSubtotal">₹0.00</span>
                </div>
                <div class="cart_total mt-10">
                    <span>Total:</span>
                    <span class="price" id="cartTotal">₹0.00</span>
                </div>
            </div>
        </div>
        <div class="mini_cart_footer" id="miniCartActions" style="display: none;">
            <div class="cart_button">
                <a href="<?= $site ?>cart/"><i class="fa fa-shopping-cart"></i> View cart</a>
            </div>
            <div class="cart_button">
                <a href="<?= $site ?>checkout/"><i class="fa fa-sign-in"></i> Checkout</a>
            </div>
        </div>
    </div>
    <!--mini cart end-->
</header>

<!-- JavaScript for dynamic header -->
<script>
$(document).ready(function() {
    // Load mini cart on page load
    loadMiniCart();
    
    // Toggle mini cart sidebar
    $('.mini_cart_wrapper_trigger').click(function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('.mini_cart').addClass('active');
        $('.off_canvars_overlay').addClass('active');
        loadMiniCart();
    });
    
    // Close mini cart
    $('.mini_cart_close a, .off_canvars_overlay').click(function() {
        $('.mini_cart').removeClass('active');
        $('.off_canvars_overlay').removeClass('active');
    });
    
    // Load mini cart content via AJAX
    function loadMiniCart() {
        $.ajax({
            url: '<?= $site ?>ajax/get-mini-cart-sidebar.php',
            method: 'GET',
            beforeSend: function() {
                $('#miniCartContent').html('<div class="text-center py-4"><div class="spinner-border spinner-border-sm"></div> Loading...</div>');
            },
            success: function(response) {
                $('#miniCartContent').html(response.content);
                $('#cartSubtotal').text('₹' + response.subtotal);
                $('#cartTotal').text('₹' + response.total);
                
                if (response.item_count > 0) {
                    $('#miniCartTotals').show();
                    $('#miniCartActions').show();
                } else {
                    $('#miniCartTotals').hide();
                    $('#miniCartActions').hide();
                }
            }
        });
    }
    
    // Update cart count
    function updateCartCount() {
        $.ajax({
            url: '<?= $site ?>ajax/get-cart-count.php',
            method: 'GET',
            success: function(response) {
                $('.item_count').text(response.count);
            }
        });
    }
    
    // Auto-update cart count every 30 seconds
    setInterval(updateCartCount, 30000);
    
    // Search suggestions
    $('#headerSearchInput').on('input', function() {
        var query = $(this).val();
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
    
    // Close search suggestions on click outside
    $(document).click(function(e) {
        if (!$(e.target).closest('.search_list').length) {
            $('#searchSuggestions').hide();
        }
    });
});
</script>