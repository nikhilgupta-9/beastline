<?php
session_start();
include_once "config/connect.php";
include_once "util/function.php";

// Calculate cart totals
$subtotal = 0;
$total_quantity = 0;
$cart_items = [];

if(isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach($_SESSION['cart'] as $cart_item_id => $item) {
        // Get product details from database
        $sql = "SELECT p.*, c.categories, b.brand_name 
                FROM products p 
                LEFT JOIN categories c ON p.pro_sub_cate = c.id
                LEFT JOIN brands b ON p.brand_name = b.id
                WHERE p.pro_id = ? AND p.status = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $item['product_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $product = $result->fetch_assoc();
            
            // Get variant details if exists
            $variant_details = [];
            if($item['variant_id']) {
                $variant_sql = "SELECT * FROM product_variants WHERE id = ?";
                $variant_stmt = $conn->prepare($variant_sql);
                $variant_stmt->bind_param("i", $item['variant_id']);
                $variant_stmt->execute();
                $variant_result = $variant_stmt->get_result();
                $variant_details = $variant_result->fetch_assoc();
            }
            
            // Calculate item total
            $item_total = $item['price'] * $item['quantity'];
            $subtotal += $item_total;
            $total_quantity += $item['quantity'];
            
            $cart_items[$cart_item_id] = [
                'product' => $product,
                'variant' => $variant_details,
                'cart_item' => $item,
                'item_total' => $item_total
            ];
        }
    }
}

// Shipping calculation (you can customize this)
$shipping_fee = ($subtotal >= 1000) ? 0 : 49.99; // Free shipping above ₹1000

// Apply discount if any (you can add coupon logic here)
$discount = 0;
$discount_percentage = 0;
$promotion_code = '';

// Check for promotions
if(isset($_SESSION['promotion_code'])) {
    // You can implement coupon/promotion logic here
    $promotion_code = $_SESSION['promotion_code'];
    $discount_percentage = 15; // Example: 15% off
    $discount = ($subtotal * $discount_percentage) / 100;
}

$total = $subtotal - $discount + $shipping_fee;

$contact = contact_us();
?>
<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Cart | Beastline</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="<?= $site ?>assets/img/favicon/favicon.ico">

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

    <!-- Cart Page Custom CSS -->
    <style>
        /* Mobile Cart Styles */
        .mobile-cart-item {
            display: none;
            background: #fff;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .mobile-cart-header {
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #eaeaea;
            font-weight: 600;
            font-size: 16px;
        }
        
        .mobile-cart-content {
            padding: 15px;
        }
        
        .mobile-product-info {
            display: flex;
            margin-bottom: 15px;
        }
        
        .mobile-product-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .mobile-product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .mobile-product-details {
            flex: 1;
        }
        
        .mobile-product-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 5px;
            color: #333;
        }
        
        .mobile-product-variants {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .mobile-product-price {
            font-weight: 600;
            color: #e50010;
            font-size: 14px;
        }
        
        .mobile-old-price {
            font-size: 12px;
            color: #999;
            text-decoration: line-through;
            margin-right: 5px;
        }
        
        .mobile-cart-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .mobile-quantity-control {
            display: flex;
            align-items: center;
        }
        
        .quantity-btn-mobile {
            width: 30px;
            height: 30px;
            border: 1px solid #ddd;
            background: #fff;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
        }
        
        .quantity-input-mobile {
            width: 40px;
            height: 30px;
            text-align: center;
            border: 1px solid #ddd;
            margin: 0 5px;
        }
        
        .mobile-remove-btn {
            color: #dc3545;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        
        .mobile-remove-btn i {
            margin-right: 5px;
        }
        
        .mobile-order-summary {
            display: none;
            background: #fff;
            border-radius: 8px;
            margin-top: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .mobile-summary-header {
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #eaeaea;
            font-weight: 600;
            font-size: 16px;
        }
        
        .mobile-summary-content {
            padding: 15px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .summary-label {
            color: #666;
        }
        
        .summary-value {
            font-weight: 500;
        }
        
        .discount-row {
            color: #28a745;
        }
        
        .shipping-row {
            color: #666;
        }
        
        .total-row {
            font-weight: 600;
            font-size: 16px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        
        .promo-section {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .promo-title {
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .promo-input-group {
            display: flex;
        }
        
        .promo-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
            font-size: 14px;
        }
        
        .promo-btn {
            padding: 10px 15px;
            background: #e50010;
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            font-weight: 500;
        }
        
        .checkout-btn-mobile {
            display: block;
            width: 100%;
            padding: 15px;
            background: #e50010;
            color: white;
            text-align: center;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin-top: 20px;
            text-decoration: none;
        }
        
        /* Show mobile view on small screens */
        @media (max-width: 768px) {
            .table_desc {
                display: none;
            }
            
            .coupon_area {
                display: none;
            }
            
            .mobile-cart-item {
                display: block;
            }
            
            .mobile-order-summary {
                display: block;
            }
            
            .shopping_cart_area .container {
                padding: 15px;
            }
            
            .breadcrumbs_area {
                padding: 20px 0;
            }
        }
        
        /* Desktop styles remain the same */
        @media (min-width: 769px) {
            .mobile-cart-item,
            .mobile-order-summary {
                display: none;
            }
        }
        
        /* Cart empty state */
        .cart-empty {
            text-align: center;
            padding: 60px 20px;
        }
        
        .cart-empty-icon {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .cart-empty h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .cart-empty p {
            color: #666;
            margin-bottom: 30px;
        }
        
        .continue-shopping-btn {
            display: inline-block;
            padding: 12px 30px;
            background: #e50010;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
        }
        
        /* Promotion banner */
        .promotion-banner {
            background: linear-gradient(90deg, #e50010, #ff6b6b);
            color: white;
            padding: 10px 15px;
            text-align: center;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }

        .quantity-btn {
            width: 30px;
            height: 40px;
            border: 1px solid #ddd;
            background: #fff;
            border-radius: 4px;
            /* display: flex; */
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
        }
    </style>

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
                        <h3>Shopping Cart</h3>
                        <ul>
                            <li><a href="<?= $site ?>">home</a></li>
                            <li>Shopping Cart</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--breadcrumbs area end-->

    <!-- Promotion Banner -->
    <?php if($discount_percentage > 0): ?>
    <div class="container">
        <div class="promotion-banner">
            <i class="fa fa-tag"></i> <?= $discount_percentage ?>% OFF YOUR ORDER
        </div>
    </div>
    <?php endif; ?>

    <!--shopping cart area start -->
    <div class="shopping_cart_area">
        <div class="container">
            <?php if(empty($cart_items)): ?>
                <!-- Empty Cart State -->
                <div class="cart-empty">
                    <div class="cart-empty-icon">
                        <i class="fa fa-shopping-cart"></i>
                    </div>
                    <h3>Your cart is empty</h3>
                    <p>Looks like you haven't added any items to your cart yet.</p>
                    <a href="<?= $site ?>" class="continue-shopping-btn">
                        <i class="fa fa-arrow-left"></i> Continue Shopping
                    </a>
                </div>
            <?php else: ?>
                <!-- Mobile View (Visible on screens < 768px) -->
                <div class="mobile-cart-items">
                    <?php foreach($cart_items as $cart_item_id => $item_data): 
                        $product = $item_data['product'];
                        $variant = $item_data['variant'];
                        $cart_item = $item_data['cart_item'];
                        $item_total = $item_data['item_total'];
                        
                        // Get image
                        $image_path = !empty($product['pro_img']) ? 
                            $site . 'admin/assets/img/uploads/' . $product['pro_img'] : 
                            $site . 'assets/img/s-product/product.jpg';
                    ?>
                    <div class="mobile-cart-item p-2" data-cart-item-id="<?= $cart_item_id ?>">
                        <div class="mobile-product-info">
                            <div class="mobile-product-image">
                                <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($product['pro_name']) ?>">
                            </div>
                            <div class="mobile-product-details">
                                <div class="mobile-product-title"><?= htmlspecialchars($product['pro_name']) ?></div>
                                <?php if(!empty($variant)): ?>
                                <div class="mobile-product-variants">
                                    <?php if(!empty($variant['color'])): ?>
                                        <div>Color: <?= htmlspecialchars($variant['color']) ?></div>
                                    <?php endif; ?>
                                    <?php if(!empty($variant['size'])): ?>
                                        <div>Size: <?= htmlspecialchars($variant['size']) ?></div>
                                    <?php endif; ?>
                                    <?php if(!empty($product['sku'])): ?>
                                        <div>Art. no: <?= htmlspecialchars($product['sku']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                <div class="mobile-product-price">
                                    <?php if($product['mrp'] > $product['selling_price']): ?>
                                        <span class="mobile-old-price">₹<?= number_format($product['mrp'], 2) ?></span>
                                    <?php endif; ?>
                                    ₹<?= number_format($cart_item['price'], 2) ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mobile-cart-actions">
                            <div class="mobile-quantity-control">
                                <button class="quantity-btn-mobile minus" data-cart-item-id="<?= $cart_item_id ?>">-</button>
                                <input type="number" class="quantity-input-mobile" 
                                       value="<?= $cart_item['quantity'] ?>" 
                                       min="1" 
                                       max="<?= $variant ? $variant['quantity'] : $product['quantity'] ?>"
                                       data-cart-item-id="<?= $cart_item_id ?>">
                                <button class="quantity-btn-mobile plus" data-cart-item-id="<?= $cart_item_id ?>">+</button>
                            </div>
                            <div class="mobile-product-total">
                                ₹<?= number_format($item_total, 2) ?>
                            </div>
                        </div>
                        
                        <div style="text-align: right; margin-top: 10px;">
                            <span class="mobile-remove-btn" data-cart-item-id="<?= $cart_item_id ?>">
                                <i class="fa fa-trash"></i> Remove
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Desktop View (Visible on screens >= 768px) -->
                <form id="cartForm" method="POST" action="<?= $site ?>ajax/update-cart.php">
                    <div class="row">
                        <div class="col-12">
                            <div class="table_desc">
                                <div class="cart_page table-responsive">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th class="product_remove">Delete</th>
                                                <th class="product_thumb">Image</th>
                                                <th class="product_name">Product</th>
                                                <th class="product-price">Price</th>
                                                <th class="product_quantity">Quantity</th>
                                                <th class="product_total">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($cart_items as $cart_item_id => $item_data): 
                                                $product = $item_data['product'];
                                                $variant = $item_data['variant'];
                                                $cart_item = $item_data['cart_item'];
                                                $item_total = $item_data['item_total'];
                                                
                                                $image_path = !empty($product['pro_img']) ? 
                                                    $site . 'admin/assets/img/uploads/' . $product['pro_img'] : 
                                                    $site . 'assets/img/s-product/product.jpg';
                                            ?>
                                            <tr data-cart-item-id="<?= $cart_item_id ?>">
                                                <td class="product_remove">
                                                    <a href="#" class="remove-cart-item" data-cart-item-id="<?= $cart_item_id ?>">
                                                        <i class="fa fa-trash-o"></i>
                                                    </a>
                                                </td>
                                                <td class="product_thumb">
                                                    <a href="<?= $site ?>product/<?= $product['slug_url'] ?>">
                                                        <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($product['pro_name']) ?>" style="width: 70px; height: 70px; object-fit: cover;">
                                                    </a>
                                                </td>
                                                <td class="product_name">
                                                    <a href="<?= $site ?>product/<?= $product['slug_url'] ?>"><?= htmlspecialchars($product['pro_name']) ?></a>
                                                    <?php if(!empty($variant)): ?>
                                                    <div class="variant-details" style="font-size: 12px; color: #666; margin-top: 5px;">
                                                        <?php if(!empty($variant['color'])): ?>
                                                            <div>Color: <?= htmlspecialchars($variant['color']) ?></div>
                                                        <?php endif; ?>
                                                        <?php if(!empty($variant['size'])): ?>
                                                            <div>Size: <?= htmlspecialchars($variant['size']) ?></div>
                                                        <?php endif; ?>
                                                        <?php if(!empty($product['sku'])): ?>
                                                            <div>SKU: <?= htmlspecialchars($product['sku']) ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="product-price">₹<?= number_format($cart_item['price'], 2) ?></td>
                                                <td class="product_quantity">
                                                    <div class="quantity-control">
                                                        <button type="button" class="quantity-btn minus" data-cart-item-id="<?= $cart_item_id ?>">-</button>
                                                        <input type="number" 
                                                               name="quantity[<?= $cart_item_id ?>]" 
                                                               value="<?= $cart_item['quantity'] ?>" 
                                                               min="1" 
                                                               max="<?= $variant ? $variant['quantity'] : $product['quantity'] ?>"
                                                               class="quantity-input">
                                                        <button type="button" class="quantity-btn plus" data-cart-item-id="<?= $cart_item_id ?>">+</button>
                                                    </div>
                                                </td>
                                                <td class="product_total">₹<?= number_format($item_total, 2) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="cart_submit">
                                    <button type="button" id="updateCartBtn">Update cart</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Mobile Order Summary (Visible on mobile) -->
                <div class="mobile-order-summary">
                    <div class="mobile-summary-header">
                        ORDER SUMMARY
                    </div>
                    <div class="mobile-summary-content">
                        <!-- Promo Code Section -->
                        <div class="promo-section">
                            <div class="promo-title">DISCOUNTS</div>
                            <div class="promo-input-group">
                                <input type="text" class="promo-input" placeholder="Enter promo code" id="mobilePromoCode" value="<?= htmlspecialchars($promotion_code) ?>">
                                <button type="button" class="promo-btn" id="mobileApplyPromo">APPLY</button>
                            </div>
                        </div>
                        
                        <div class="summary-row">
                            <span class="summary-label">Order value</span>
                            <span class="summary-value">₹<?= number_format($subtotal, 2) ?></span>
                        </div>
                        
                        <?php if($discount > 0): ?>
                        <div class="summary-row discount-row">
                            <span class="summary-label">Promotion (<?= $discount_percentage ?>% off)</span>
                            <span class="summary-value">-₹<?= number_format($discount, 2) ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="summary-row shipping-row">
                            <span class="summary-label">Shipping fee</span>
                            <span class="summary-value">₹<?= number_format($shipping_fee, 2) ?></span>
                        </div>
                        
                        <div class="summary-row total-row">
                            <span class="summary-label">TOTAL</span>
                            <span class="summary-value">₹<?= number_format($total, 2) ?></span>
                        </div>
                        
                        <a href="<?= $site ?>checkout" class="checkout-btn-mobile">
                            CONTINUE TO CHECKOUT
                        </a>
                    </div>
                </div>

                <!--coupon code area start (Desktop View) -->
                <div class="coupon_area">
                    <div class="row">
                        <div class="col-lg-6 col-md-6">
                            <div class="coupon_code left">
                                <h3>Coupon</h3>
                                <div class="coupon_inner">
                                    <p>Enter your coupon code if you have one.</p>
                                    <input type="text" placeholder="Coupon code" id="couponCode" value="<?= htmlspecialchars($promotion_code) ?>">
                                    <button type="button" id="applyCoupon">Apply coupon</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6">
                            <div class="coupon_code right">
                                <h3>Cart Totals</h3>
                                <div class="coupon_inner">
                                    <div class="cart_subtotal">
                                        <p>Subtotal</p>
                                        <p class="cart_amount">₹<?= number_format($subtotal, 2) ?></p>
                                    </div>
                                    
                                    <?php if($discount > 0): ?>
                                    <div class="cart_subtotal">
                                        <p>Discount (<?= $discount_percentage ?>% off)</p>
                                        <p class="cart_amount" style="color: #28a745;">-₹<?= number_format($discount, 2) ?></p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="cart_subtotal">
                                        <p>Shipping</p>
                                        <p class="cart_amount">
                                            <span><?= ($subtotal >= 1000) ? 'Free Shipping' : 'Flat Rate' ?>:</span> 
                                            ₹<?= number_format($shipping_fee, 2) ?>
                                        </p>
                                    </div>
                                    
                                    <div class="cart_subtotal">
                                        <p>Total</p>
                                        <p class="cart_amount">₹<?= number_format($total, 2) ?></p>
                                    </div>
                                    
                                    <div class="checkout_btn">
                                        <a href="<?= $site ?>checkout">Proceed to Checkout</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--coupon code area end-->
            <?php endif; ?>
        </div>
    </div>
    <!--shopping cart area end -->

    <!--footer area start-->
    <?php include_once "includes/footer.php"; ?>
    <?php include_once "includes/footer-link.php"; ?>
    
    <!-- Cart JavaScript -->
    <script>
    $(document).ready(function() {
        // Update quantity (desktop)
        $('.quantity-btn').click(function() {
            var cartItemId = $(this).data('cart-item-id');
            var input = $('input[name="quantity[' + cartItemId + ']"]');
            var currentVal = parseInt(input.val());
            var maxVal = parseInt(input.attr('max'));
            var minVal = parseInt(input.attr('min'));
            
            if($(this).hasClass('plus')) {
                if(currentVal < maxVal) {
                    input.val(currentVal + 1);
                }
            } else if($(this).hasClass('minus')) {
                if(currentVal > minVal) {
                    input.val(currentVal - 1);
                }
            }
            
            // Update cart immediately
            updateCartItem(cartItemId, input.val());
        });
        
        // Update quantity (mobile)
        $('.quantity-btn-mobile').click(function() {
            var cartItemId = $(this).data('cart-item-id');
            var input = $(this).siblings('.quantity-input-mobile');
            var currentVal = parseInt(input.val());
            var maxVal = parseInt(input.attr('max'));
            var minVal = parseInt(input.attr('min'));
            
            if($(this).hasClass('plus')) {
                if(currentVal < maxVal) {
                    input.val(currentVal + 1);
                }
            } else if($(this).hasClass('minus')) {
                if(currentVal > minVal) {
                    input.val(currentVal - 1);
                }
            }
            
            // Update cart immediately
            updateCartItem(cartItemId, input.val());
        });
        
        // Quantity input change (desktop)
        $('.quantity-input').change(function() {
            var cartItemId = $(this).closest('tr').data('cart-item-id');
            updateCartItem(cartItemId, $(this).val());
        });
        
        // Quantity input change (mobile)
        $('.quantity-input-mobile').change(function() {
            var cartItemId = $(this).data('cart-item-id');
            updateCartItem(cartItemId, $(this).val());
        });
        
        // Remove item (desktop)
        $('.remove-cart-item').click(function(e) {
            e.preventDefault();
            var cartItemId = $(this).data('cart-item-id');
            removeCartItem(cartItemId);
        });
        
        // Remove item (mobile)
        $('.mobile-remove-btn').click(function() {
            var cartItemId = $(this).data('cart-item-id');
            removeCartItem(cartItemId);
        });
        
        // Apply coupon (desktop)
        $('#applyCoupon').click(function() {
            var couponCode = $('#couponCode').val();
            applyCoupon(couponCode);
        });
        
        // Apply coupon (mobile)
        $('#mobileApplyPromo').click(function() {
            var couponCode = $('#mobilePromoCode').val();
            applyCoupon(couponCode);
        });
        
        // Update cart button
        $('#updateCartBtn').click(function() {
            updateCart();
        });
        
        // Functions
        function updateCartItem(cartItemId, quantity) {
            $.ajax({
                url: '<?= $site ?>ajax/remove-from-cart.php',
                method: 'POST',
                data: {
                    action: 'update_quantity',
                    cart_item_id: cartItemId,
                    quantity: quantity
                },
                success: function(response) {
                    if(response.success) {
                        // Reload page to update totals
                        location.reload();
                    } else {
                        alert(response.message || 'Error updating cart');
                    }
                }
            });
        }
        
        function removeCartItem(cartItemId) {
            if(confirm('Are you sure you want to remove this item from cart?')) {
                $.ajax({
                    url: '<?= $site ?>ajax/remove-from-cart.php',
                    method: 'POST',
                    data: {
                        action: 'remove_item',
                        cart_item_id: cartItemId
                    },
                    success: function(response) {
                        if(response.success) {
                            // Remove item from DOM
                            $('[data-cart-item-id="' + cartItemId + '"]').remove();
                            // Update cart count in header
                            if(response.cart_count !== undefined) {
                                $('.item_count').text(response.cart_count);
                            }
                            // Reload page if cart is empty
                            if(response.cart_count == 0) {
                                location.reload();
                            } else {
                                // Reload to update totals
                                location.reload();
                            }
                        } else {
                            alert(response.message || 'Error removing item');
                        }
                    }
                });
            }
        }
        
        function applyCoupon(couponCode) {
            $.ajax({
                url: '<?= $site ?>ajax/update-cart.php',
                method: 'POST',
                data: {
                    action: 'apply_coupon',
                    coupon_code: couponCode
                },
                success: function(response) {
                    if(response.success) {
                        location.reload();
                    } else {
                        alert(response.message || 'Invalid coupon code');
                    }
                }
            });
        }
        
        function updateCart() {
            var formData = $('#cartForm').serialize();
            formData += '&action=update_cart';
            
            $.ajax({
                url: '<?= $site ?>ajax/remove-from-cart.php',
                method: 'POST',
                data: formData,
                success: function(response) {
                    if(response.success) {
                        location.reload();
                    } else {
                        alert(response.message || 'Error updating cart');
                    }
                }
            });
        }
    });
    </script>


</body>

</html>