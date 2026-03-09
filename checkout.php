<?php
session_start();
include_once "config/connect.php";
include_once "util/function.php";
require_once __DIR__ . '/admin/models/PaymentSmtpSetting.php';

$payment_setting = new PaymentSmtpSetting($conn);
$razorpay_key_id = $payment_setting->getSetting('razorpay', 'api_key');

// Initialize variables
$subtotal = 0;
$total_quantity = 0;
$cart_items = [];
$isBuyNow = isset($_SESSION['buy_now']);

// Check if we have items (either cart or buy now)
if (!$isBuyNow && (!isset($_SESSION['cart']) || empty($_SESSION['cart']))) {
    header("Location: " . $site . "cart");
    exit();
}

// Process cart items (KEEP YOUR EXISTING CART PROCESSING CODE)
$subtotal = 0;
$total_quantity = 0;
$cart_items = [];
$isBuyNow = isset($_SESSION['buy_now']);

// Check if we have items (either cart or buy now)
if (!$isBuyNow && (!isset($_SESSION['cart']) || empty($_SESSION['cart']))) {
    header("Location: " . $site . "cart");
    exit();
}

// print_r($_SESSION['buy_now']);
if ($isBuyNow) {
    // PROCESS BUY NOW SESSION
    $buyNowItem = $_SESSION['buy_now'];

    // Get product details for buy now item
    $sql = "SELECT p.*, c.categories, b.brand_name 
            FROM products p 
            LEFT JOIN categories c ON p.pro_sub_cate = c.id
            LEFT JOIN brands b ON p.brand_name = b.id
            WHERE p.pro_id = ? AND p.status = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $buyNowItem['product_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();

        // Get variant details if exists
        $variant_details = [];
        if ($buyNowItem['variant_id'] > 0) {
            $variant_sql = "SELECT * FROM product_variants WHERE id = ?";
            $variant_stmt = $conn->prepare($variant_sql);
            $variant_stmt->bind_param("i", $buyNowItem['variant_id']);
            $variant_stmt->execute();
            $variant_result = $variant_stmt->get_result();
            $variant_details = $variant_result->fetch_assoc();
        }

        // Calculate item total
        $item_total = $buyNowItem['price'] * $buyNowItem['quantity'];
        $subtotal = $item_total;
        $total_quantity = $buyNowItem['quantity'];

        $cart_items = [
            'buy_now_1' => [
                'product' => $product,
                'variant' => $variant_details,
                'cart_item' => [
                    'product_id' => $buyNowItem['product_id'],
                    'variant_id' => $buyNowItem['variant_id'],
                    'size' => $buyNowItem['size'],
                    'color' => $buyNowItem['color'],
                    'quantity' => $buyNowItem['quantity'],
                    'price' => $buyNowItem['price']
                ],
                'item_total' => $item_total
            ]
        ];
    }
} else {
    // PROCESS REGULAR CART SESSION
    foreach ($_SESSION['cart'] as $cart_item_id => $item) {
        // Get product details
        $sql = "SELECT p.*, c.categories, b.brand_name 
                FROM products p 
                LEFT JOIN categories c ON p.pro_sub_cate = c.id
                LEFT JOIN brands b ON p.brand_name = b.id
                WHERE p.pro_id = ? AND p.status = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $item['product_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();

            // Get variant details if exists
            $variant_details = [];
            if ($item['variant_id']) {
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


// Calculate totals
$shipping_fee = ($subtotal >= 1000) ? 0 : 0;
$discount = 0;
$total = $subtotal - $discount + $shipping_fee;

$contact = contact_us();
?>

<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Checkout | Beastline</title>
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
    <?php include_once "includes/meta_pixel.php" ?>

    <style>
        .checkout-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 15px;
        }

        .checkout-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .card-header {
            background: #f8f9fa;
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
            font-weight: 600;
            font-size: 18px;
        }

        .card-body {
            padding: 25px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-details {
            flex: 2;
        }

        .item-name {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .item-meta {
            font-size: 13px;
            color: #666;
        }

        .item-price {
            font-weight: 600;
            min-width: 100px;
            text-align: right;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .summary-row.total {
            font-size: 18px;
            font-weight: 700;
            border-bottom: none;
            padding-top: 15px;
            color: #000;
        }

        .magic-checkout-btn {
            width: 100%;
            padding: 16px;
            background: #0f0f0f;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }

        .magic-checkout-btn:hover {
            background: #333;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .magic-checkout-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .login-prompt {
            background: #e3f2fd;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .login-link {
            color: #1976d2;
            font-weight: 600;
            text-decoration: none;
        }

        .login-link:hover {
            text-decoration: underline;
        }

        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .loading-spinner {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            font-size: 18px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .badge-cod {
            background: #ff9800;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 10px;
        }

        .secure-badge {
            text-align: center;
            margin-top: 15px;
            color: #666;
            font-size: 13px;
        }

        .secure-badge i {
            color: #28a745;
            margin-right: 5px;
        }
    </style>
</head>

<body>

    <?php include_once "includes/header.php"; ?>

    <!--breadcrumbs area start-->
    <div class="breadcrumbs_area">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="breadcrumb_content">
                        <h3>Checkout</h3>
                        <ul>
                            <li><a href="<?= $site ?>">home</a></li>
                            <li>Checkout</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--breadcrumbs area end-->
    <div class="container">
        <div class="checkout-container">

            <?php if ($isBuyNow): ?>
                <div class="alert alert-info">
                    <i class="fa fa-bolt"></i> <strong>Express Checkout:</strong> You're purchasing a single item
                </div>
            <?php endif; ?>

            <!-- Login Prompt for Guest Users -->
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="login-prompt">
                    <span>
                        <i class="fa fa-user"></i>
                        <strong>Returning customer?</strong> Login for faster checkout
                    </span>
                    <a href="<?= $site ?>user-login?redirect=checkout" class="login-link">
                        Login <i class="fa fa-arrow-right"></i>
                    </a>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Main Order Summary -->
                <div class="col-md-8">
                    <div class="checkout-card">
                        <div class="card-header">
                            <i class="fa fa-shopping-bag me-2"></i> Order Summary (<?= $total_quantity ?> items)
                        </div>
                        <div class="card-body">
                            <?php foreach ($cart_items as $item_data):
                                $product = $item_data['product'];
                                $cart_item = $item_data['cart_item'];
                            ?>
                                <div class="order-item">
                                    <div class="item-details">
                                        <div class="item-name">
                                            <?= htmlspecialchars($product['pro_name']) ?>
                                        </div>
                                        <div class="item-meta">
                                            <?php if (!empty($cart_item['size'])): ?>
                                                <span class="me-3">Size: <?= $cart_item['size'] ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($cart_item['color'])): ?>
                                                <span>Color: <?= $cart_item['color'] ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="item-price">
                                        ₹<?= number_format($cart_item['price'], 2) ?> × <?= $cart_item['quantity'] ?>
                                        <br>
                                        <small class="text-muted">Total: ₹<?= number_format($item_data['item_total'], 2) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- COD Information Card -->
                    <div class="checkout-card mt-3">
                        <div class="card-header">
                            <i class="fa fa-truck me-2"></i> Delivery Information
                        </div>
                        <div class="card-body">
                            <p class="mb-2">
                                <i class="fa fa-check-circle text-success"></i>
                                Free shipping on orders above ₹1000
                            </p>
                            <p class="mb-0">
                                <i class="fa fa-clock-o"></i>
                                Estimated delivery: 3-5 business days
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Price Summary Sidebar -->
                <div class="col-md-4">
                    <div class="checkout-card">
                        <div class="card-header">
                            <i class="fa fa-calculator me-2"></i> Price Details
                        </div>
                        <div class="card-body">
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span>₹<?= number_format($subtotal, 2) ?></span>
                            </div>

                            <?php if ($discount > 0): ?>
                                <div class="summary-row" style="color: #28a745;">
                                    <span>Discount</span>
                                    <span>-₹<?= number_format($discount, 2) ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="summary-row">
                                <span>Shipping</span>
                                <span>
                                    <?php if ($shipping_fee == 0): ?>
                                        <span class="text-success">Free</span>
                                    <?php else: ?>
                                        ₹<?= number_format($shipping_fee, 2) ?>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <div class="summary-row total">
                                <span>Total Amount</span>
                                <span class="text-primary">₹<?= number_format($total, 2) ?></span>
                            </div>

                            <!-- Payment Method Selection -->
                            <div class="mt-4 d-none">
                                <h6 class="fw-bold mb-3">Payment Method</h6>

                                <!-- Razorpay Option (Selected by default) -->
                                <div class="payment-option mb-3 p-3 border rounded selected" style="border-color: #0f0f0f !important; background: #fafafa;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="razorpayPayment" value="razorpay" checked>
                                        <label class="form-check-label fw-bold" for="razorpayPayment">
                                            <img src="<?= $site ?>assets/img/payment/razorpay-logo.jpg" alt="Razorpay" style="height: 25px;" class="me-2">
                                            Razorpay (Cards, UPI, NetBanking)
                                        </label>
                                    </div>
                                    <p class="text-muted small mt-2 mb-0 ms-4">
                                        <i class="fa fa-lock"></i> Secure payment by Razorpay
                                    </p>
                                </div>

                                <!-- COD Option -->
                                <div class="payment-option mb-3 p-3 border rounded">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="codPayment" value="cod">
                                        <label class="form-check-label fw-bold" for="codPayment">
                                            Cash on Delivery (COD)
                                            <span class="badge-cod">Advance ₹200</span>
                                        </label>
                                    </div>
                                    <p class="text-muted small mt-2 mb-0 ms-4">
                                        Pay ₹200 now online, remaining ₹<?= number_format($total - 200, 2) ?> at delivery
                                    </p>
                                </div>
                            </div>

                            <!-- Terms Checkbox -->
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" id="termsCheckbox" checked>
                                <label class="form-check-label small" for="termsCheckbox">
                                    I agree to the <a href="<?= $site ?>terms" target="_blank">Terms & Conditions</a>
                                </label>
                            </div>

                            <!-- Magic Checkout Button -->
                            <button id="magicCheckoutBtn" class="magic-checkout-btn">
                                <i class="fa fa-bolt me-2"></i> Proceed to Secure Checkout
                            </button>

                            <div class="secure-badge">
                                <i class="fa fa-shield"></i> 100% Secure | PCI Compliant
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <i class="fa fa-spinner fa-spin"></i> Processing your order...
        </div>
    </div>

    <?php include_once "includes/footer.php"; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <!-- Magic Checkout Script -->
    <script src="https://checkout.razorpay.com/v1/magic-checkout.js"></script>

    <script>
        $(document).ready(function() {

            // Payment option selection styling
            $('.payment-option').click(function() {
                $('.payment-option').removeClass('selected').css('border-color', '#dee2e6');
                $(this).addClass('selected').css('border-color', '#0f0f0f');
                $(this).find('input[type="radio"]').prop('checked', true);
            });

            // Magic Checkout Button Click
            $('#magicCheckoutBtn').click(function() {
                const btn = $(this);
                const paymentMethod = $('input[name="payment_method"]:checked').val();

                // Validate terms
                if (!$('#termsCheckbox').is(':checked')) {
                    alert('Please agree to the terms and conditions');
                    return;
                }

                btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');
                showLoading();

                // Prepare cart items
                const cartItems = <?= json_encode($cart_items) ?>;

                // Create order using your existing create-magic-order.php
                $.ajax({
                    url: '<?= $site ?>ajax/create-magic-order.php',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        cart_items: JSON.stringify(cartItems),
                        form_data: $('#checkoutForm').serialize()
                    },
                    success: function(response) {
                        if (response.success) {
                            if (paymentMethod === 'razorpay') {
                                openMagicCheckout(response, btn);
                            } else {
                                processCOD(response, btn);
                            }
                        } else {
                            alert('❌ ' + (response.message || 'Failed to create order'));
                            resetButton(btn);
                            hideLoading();
                        }
                    },
                    error: function(xhr) {
                        console.error('Order creation error:', xhr.responseText);
                        alert('❌ Failed to create order. Please try again.');
                        resetButton(btn);
                        hideLoading();
                    }
                });
            });

            function openMagicCheckout(orderData, btn) {
                const options = {
                    key: orderData.key_id,
                    one_click_checkout: true,
                    name: 'Beastline',
                    order_id: orderData.razorpay_order_id,
                    show_coupons: true,

                    // API endpoints
                    shipping_info_url: '<?= $site ?>ajax/shipping-info.php',
                    get_promotions_url: '<?= $site ?>ajax/get-promotions.php',
                    apply_promotion_url: '<?= $site ?>ajax/apply-promotion.php',

                    // Prefill if user is logged in
                    prefill: {
                        name: '<?= $_SESSION['user_name'] ?? '' ?>',
                        email: '<?= $_SESSION['user_email'] ?? '' ?>',
                        contact: '<?= $_SESSION['user_phone'] ?? '' ?>'
                    },

                    theme: {
                        color: '#0f0f0f'
                    },

                    // ✅ IMPORTANT: Use 'handler' not 'onPaymentSuccess'
                    handler: function(paymentResponse) {
                        console.log('========== PAYMENT SUCCESS ==========');
                        console.log('Payment Response:', paymentResponse);

                        // Show verification message
                        const notification = $('<div class="alert alert-info position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index:9999;">Verifying your order...</div>').appendTo('body');

                        // Verify payment
                        $.ajax({
                            url: '<?= $site ?>ajax/verify-payment.php',
                            method: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify({
                                razorpay_payment_id: paymentResponse.razorpay_payment_id,
                                razorpay_order_id: paymentResponse.razorpay_order_id,
                                razorpay_signature: paymentResponse.razorpay_signature,
                                is_cod: false
                            }),
                            dataType: 'json',
                            success: function(verification) {
                                notification.remove();
                                if (verification.success) {
                                    console.log('Redirecting to:', verification.confirmation_url);
                                    window.location.href = verification.confirmation_url;
                                } else {
                                    alert('❌ Verification Failed: ' + verification.message);
                                    resetButton(btn);
                                    hideLoading();
                                }
                            },
                            error: function(xhr) {
                                notification.remove();
                                console.error('Verification Error:', xhr.responseText);
                                alert('❌ Verification failed. Please check console.');
                                resetButton(btn);
                                hideLoading();
                            }
                        });
                    },

                    modal: {
                        ondismiss: function() {
                            console.log('Modal dismissed');
                            resetButton(btn);
                            hideLoading();
                            $.ajax({
                                url: '<?= $site ?>ajax/clear-pending-order.php',
                                method: 'POST'
                            });
                        }
                    }
                };

                const rzp = new Razorpay(options);

                rzp.on('payment.failed', function(response) {
                    console.error('Payment failed:', response);
                    alert('❌ Payment failed: ' + (response.error.description || 'Please try again'));
                    resetButton(btn);
                    hideLoading();
                });

                rzp.open();
                hideLoading();
            }

            function processCOD(orderData, btn) {
                // For COD advance payment
                const options = {
                    key: orderData.key_id,
                    one_click_checkout: true,
                    name: 'Beastline - COD Advance',
                    order_id: orderData.razorpay_order_id,

                    prefill: {
                        name: '<?= $_SESSION['user_name'] ?? '' ?>',
                        email: '<?= $_SESSION['user_email'] ?? '' ?>',
                        contact: '<?= $_SESSION['user_phone'] ?? '' ?>'
                    },

                    theme: {
                        color: '#0f0f0f'
                    },

                    handler: function(paymentResponse) {
                        console.log('COD Payment Success:', paymentResponse);

                        const notification = $('<div class="alert alert-info position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index:9999;">Processing your COD order...</div>').appendTo('body');

                        $.ajax({
                            url: '<?= $site ?>ajax/verify-payment.php',
                            method: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify({
                                razorpay_payment_id: paymentResponse.razorpay_payment_id,
                                razorpay_order_id: paymentResponse.razorpay_order_id,
                                razorpay_signature: paymentResponse.razorpay_signature,
                                is_cod: true
                            }),
                            dataType: 'json',
                            success: function(verification) {
                                notification.remove();
                                if (verification.success) {
                                    window.location.href = verification.confirmation_url;
                                } else {
                                    alert('❌ Verification Failed: ' + verification.message);
                                    resetButton(btn);
                                    hideLoading();
                                }
                            },
                            error: function() {
                                notification.remove();
                                alert('❌ Verification failed. Please contact support.');
                                resetButton(btn);
                                hideLoading();
                            }
                        });
                    },

                    modal: {
                        ondismiss: function() {
                            resetButton(btn);
                            hideLoading();
                        }
                    }
                };

                const rzp = new Razorpay(options);
                rzp.on('payment.failed', function(response) {
                    alert('❌ Payment failed: ' + (response.error.description || 'Please try again'));
                    resetButton(btn);
                    hideLoading();
                });

                rzp.open();
                hideLoading();
            }

            function showLoading() {
                $('#loadingOverlay').show();
            }

            function hideLoading() {
                $('#loadingOverlay').hide();
            }

            function resetButton(btn) {
                btn.prop('disabled', false).html('<i class="fa fa-bolt me-2"></i> Proceed to Secure Checkout');
            }
        });
    </script>

    <!-- Hidden form for compatibility (not shown to users) -->
    <form id="checkoutForm" method="POST" style="display: none;">
        <!-- This form is hidden but needed for your existing create-magic-order.php -->
    </form>

    <?php include_once "includes/footer-link.php"; ?>

</body>

</html>