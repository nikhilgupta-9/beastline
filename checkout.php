<?php
session_start();
include_once "config/connect.php";
include_once "util/function.php";
require_once __DIR__ . '/admin/models/PaymentSmtpSetting.php';

$payment_setting = new PaymentSmtpSetting($conn);
$razorpay_key_id = $payment_setting->getSetting('razorpay', 'api_key');

// Redirect if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: " . $site . "cart");
    exit();
}

// Calculate cart totals
$subtotal = 0;
$total_quantity = 0;
$cart_items = [];

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

// Shipping calculation
$shipping_fee = ($subtotal >= 1000) ? 0 : 49.99;

// Apply discount if any
$discount = 0;
$discount_percentage = 0;
$promotion_code = '';

if (isset($_SESSION['promotion_code'])) {
    $promotion_code = $_SESSION['promotion_code'];
    $discount_percentage = 15; // Example: 15% off
    $discount = ($subtotal * $discount_percentage) / 100;
}

$total = $subtotal - $discount + $shipping_fee;

// Cash on Delivery advance payment
$cod_advance = 200;
$cod_remaining = $total - $cod_advance;

// Get user data if logged in
$user_data = [];
if (isset($_SESSION['user_id'])) {
    $user_sql = "SELECT * FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $_SESSION['user_id']);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_data = $user_result->fetch_assoc();
}

// Get countries list
$countries = [
    'IN' => 'India',
    'US' => 'United States',
    'GB' => 'United Kingdom',
    'CA' => 'Canada',
    'AU' => 'Australia',
    'AE' => 'United Arab Emirates',
    'SG' => 'Singapore',
    'MY' => 'Malaysia',
    'JP' => 'Japan',
    'KR' => 'South Korea',
    'CN' => 'China',
    'DE' => 'Germany',
    'FR' => 'France',
    'IT' => 'Italy',
    'ES' => 'Spain',
    'NL' => 'Netherlands',
    'BR' => 'Brazil',
    'MX' => 'Mexico',
    'RU' => 'Russia',
    'ZA' => 'South Africa'
];

$contact = contact_us();

// Get Razorpay keys
// $razorpay_sql = "SELECT * FROM payment_settings WHERE payment_method = 'razorpay'";
// $razorpay_result = $conn->query($razorpay_sql);
// $razorpay_settings = $razorpay_result->fetch_assoc();

// $razorpay_key_id = $razorpay_settings['api_key'] ?? '';
?>
<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Checkout | Beastline</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="<?= $site ?>assets/img/favicon/favicon.ico">

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

    <!-- Razorpay -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

    <!-- Checkout Custom CSS -->
    <style>
        .payment-method-option {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .payment-method-option:hover {
            border-color: #e50010;
        }

        .payment-method-option.selected {
            border-color: #e50010;
            background-color: #fff8f8;
        }

        .payment-method-option input[type="radio"] {
            margin-right: 10px;
        }

        .payment-method-option label {
            margin: 0;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
        }

        .payment-method-details {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 14px;
            color: #666;
        }

        .cod-info {
            color: #e50010;
            font-weight: 500;
            margin-top: 10px;
        }

        .cod-breakdown {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
        }

        .cod-breakdown p {
            margin: 5px 0;
            font-size: 14px;
        }

        .razorpay-logo {
            height: 25px;
            margin-left: 10px;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .checkout_form {
                padding: 15px;
            }

            .payment-method-option {
                padding: 12px;
            }

            .order_table {
                font-size: 14px;
            }

            .cod-breakdown {
                font-size: 13px;
            }
        }

        /* Form Validation */
        .error-message {
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }

        .form-control.error {
            border-color: #dc3545;
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
            color: #fff;
            font-size: 20px;
        }

        /* Order Summary */
        .order-summary-mobile {
            display: none;
            background: #fff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .order-summary-mobile {
                display: block;
            }

            .order_table {
                display: none;
            }
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .summary-total {
            font-weight: 600;
            font-size: 16px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
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

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <i class="fa fa-spinner fa-spin"></i> Processing...
        </div>
    </div>

    <!--Checkout page section-->
    <div class="Checkout_section" id="accordion">
        <div class="container">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="user-actions">
                            <h3>
                                <i class="fa fa-user" aria-hidden="true"></i>
                                Welcome back, <?= htmlspecialchars($user_data['first_name'] ?? 'Customer') ?>!
                                <a class="Returning" href="<?= $site ?>logout">Logout</a>
                            </h3>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col-12">
                        <div class="user-actions">
                            <h3>
                                <i class="fa fa-file-o" aria-hidden="true"></i>
                                Returning customer?
                                <a class="Returning" href="#" data-bs-toggle="collapse" data-bs-target="#checkout_login" aria-expanded="true">Click here to login</a>
                            </h3>
                            <div id="checkout_login" class="collapse" data-parent="#accordion">
                                <div class="checkout_info">
                                    <p>If you have shopped with us before, please login to pre-fill your details.</p>
                                    <form id="loginForm" action="<?= $site ?>ajax/login.php" method="POST">
                                        <div class="form_group">
                                            <label>Email <span>*</span></label>
                                            <input type="email" name="email" required>
                                        </div>
                                        <div class="form_group">
                                            <label>Password <span>*</span></label>
                                            <input type="password" name="password" required>
                                        </div>
                                        <div class="form_group group_3 ">
                                            <button type="submit">Login</button>
                                            <label for="remember_box">
                                                <input id="remember_box" name="remember" type="checkbox">
                                                <span> Remember me </span>
                                            </label>
                                        </div>
                                        <a href="<?= $site ?>forgot-password">Forgot your password?</a>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="checkout_form">
                <form id="checkoutForm" action="<?= $site ?>ajax/process-order.php" method="POST">
                    <div class="row">
                        <div class="col-lg-6 col-md-6">
                            <h3>Billing Details</h3>
                            <div class="row">
                                <div class="checkout_info">
                                    <div class="row">
                                        <div class="col-lg-6 mb-20">
                                            <label>First Name <span>*</span></label><br>
                                            <input type="text" name="billing_first_name"
                                                value="<?= htmlspecialchars($user_data['first_name'] ?? '') ?>"
                                                required>
                                            <div class="error-message" id="billing_first_name_error"></div>
                                        </div>
                                        <div class="col-lg-6 mb-20">
                                            <label>Last Name <span>*</span></label><br>
                                            <input type="text" name="billing_last_name"
                                                value="<?= htmlspecialchars($user_data['last_name'] ?? '') ?>"
                                                required>
                                            <div class="error-message" id="billing_last_name_error"></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6 mb-20">
                                            <label>Email Address <span>*</span></label><br>
                                            <input type="email" name="billing_email"
                                                value="<?= htmlspecialchars($user_data['email'] ?? '') ?>"
                                                required>
                                            <div class="error-message" id="billing_email_error"></div>
                                        </div>
                                        <div class="col-6 mb-20">
                                            <label>Phone <span>*</span></label><br>
                                            <input type="tel" name="billing_phone"
                                                value="<?= htmlspecialchars($user_data['phone'] ?? '') ?>"
                                                required pattern="[0-9]{10}">
                                            <div class="error-message" id="billing_phone_error"></div>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-20">
                                        <label for="billing_country">Country <span>*</span></label>
                                        <select class="select_option" name="billing_country" id="billing_country" required>
                                            <option value="">Select Country</option>
                                            <?php foreach ($countries as $code => $name): ?>
                                                <option value="<?= $code ?>"
                                                    <?= (isset($user_data['country']) && $user_data['country'] == $code) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($name) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="error-message" id="billing_country_error"></div>
                                    </div>
                                    <div class="col-12 mb-20">
                                        <label>Street Address <span>*</span></label>
                                        <input type="text" name="billing_address_1"
                                            placeholder="House number and street name"
                                            value="<?= htmlspecialchars($user_data['address'] ?? '') ?>"
                                            required>
                                        <div class="error-message" id="billing_address_1_error"></div>
                                    </div>
                                    <div class="col-12 mb-20">
                                        <input type="text" name="billing_address_2"
                                            placeholder="Apartment, suite, unit etc. (optional)"
                                            value="<?= htmlspecialchars($user_data['address2'] ?? '') ?>">
                                    </div>
                                    <div class="row">
                                        <div class="col-6 mb-20">
                                            <label>Town / City <span>*</span></label>
                                            <input type="text" name="billing_city"
                                                value="<?= htmlspecialchars($user_data['city'] ?? '') ?>"
                                                required>
                                            <div class="error-message" id="billing_city_error"></div>
                                        </div>
                                        <div class="col-6 mb-20">
                                            <label>State <span>*</span></label>
                                            <input type="text" name="billing_state"
                                                value="<?= htmlspecialchars($user_data['state'] ?? '') ?>"
                                                required>
                                            <div class="error-message" id="billing_state_error"></div>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 mb-20">
                                        <label>Postcode / ZIP <span>*</span></label>
                                        <input type="text" name="billing_postcode"
                                            value="<?= htmlspecialchars($user_data['postcode'] ?? '') ?>"
                                            required>
                                        <div class="error-message" id="billing_postcode_error"></div>
                                    </div>
                                </div>
                                <div class="col-12 mb-20 mt-5">
                                    <input id="different_shipping" name="different_shipping" type="checkbox">
                                    <label for="different_shipping" data-bs-toggle="collapse" data-bs-target="#shipping_address_section" class="fw-bold">
                                        Ship to a different address?
                                    </label>
                                    <div class="checkout_info">
                                        <div id="shipping_address_section" class="collapse">
                                            <div class="row mt-3">
                                                <div class="col-lg-6 mb-20">
                                                    <label>First Name <span>*</span></label>
                                                    <input type="text" name="shipping_first_name">
                                                </div>
                                                <div class="col-lg-6 mb-20">
                                                    <label>Last Name <span>*</span></label>
                                                    <input type="text" name="shipping_last_name">
                                                </div>
                                                <div class="col-12 mb-20">
                                                    <label>Email Address <span>*</span></label>
                                                    <input type="email" name="shipping_email">
                                                </div>
                                                <div class="col-12 mb-20">
                                                    <label>Phone <span>*</span></label>
                                                    <input type="tel" name="shipping_phone">
                                                </div>
                                                <div class="col-12 mb-20">
                                                    <label for="shipping_country">Country <span>*</span></label>
                                                    <select class="select_option" name="shipping_country" id="shipping_country">
                                                        <option value="">Select Country</option>
                                                        <?php foreach ($countries as $code => $name): ?>
                                                            <option value="<?= $code ?>"><?= htmlspecialchars($name) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-12 mb-20">
                                                    <label>Street Address <span>*</span></label>
                                                    <input type="text" name="shipping_address_1" placeholder="House number and street name">
                                                </div>
                                                <div class="col-12 mb-20">
                                                    <input type="text" name="shipping_address_2" placeholder="Apartment, suite, unit etc. (optional)">
                                                </div>
                                                <div class="col-12 mb-20">
                                                    <label>Town / City <span>*</span></label>
                                                    <input type="text" name="shipping_city">
                                                </div>
                                                <div class="col-12 mb-20">
                                                    <label>State <span>*</span></label>
                                                    <input type="text" name="shipping_state">
                                                </div>
                                                <div class="col-lg-6 mb-20">
                                                    <label>Postcode / ZIP <span>*</span></label>
                                                    <input type="text" name="shipping_postcode">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="order-notes">
                                        <label for="order_note" class="fw-bold">Order Notes</label>
                                        <textarea id="order_note" name="order_note" placeholder="Notes about your order, e.g. special notes for delivery."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 col-md-6">
                            <!-- Mobile Order Summary -->
                            <div class="order-summary-mobile">
                                <h4>Order Summary</h4>
                                <?php foreach ($cart_items as $item_data): ?>
                                    <div class="summary-item">
                                        <span><?= htmlspecialchars($item_data['product']['pro_name']) ?> × <?= $item_data['cart_item']['quantity'] ?></span>
                                        <span>₹<?= number_format($item_data['item_total'], 2) ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <div class="summary-item">
                                    <span>Subtotal</span>
                                    <span>₹<?= number_format($subtotal, 2) ?></span>
                                </div>
                                <?php if ($discount > 0): ?>
                                    <div class="summary-item">
                                        <span>Discount</span>
                                        <span style="color: #28a745;">-₹<?= number_format($discount, 2) ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="summary-item">
                                    <span>Shipping</span>
                                    <span>₹<?= number_format($shipping_fee, 2) ?></span>
                                </div>
                                <div class="summary-item summary-total">
                                    <span>Total</span>
                                    <span>₹<?= number_format($total, 2) ?></span>
                                </div>
                            </div>

                            <h3>Your order</h3>
                            <div class="order_table table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cart_items as $item_data):
                                            $product = $item_data['product'];
                                            $cart_item = $item_data['cart_item'];
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($product['pro_name']) ?>
                                                    <?php if (!empty($cart_item['color'])): ?>
                                                        <br><small>Color: <?= htmlspecialchars($cart_item['color']) ?></small>
                                                    <?php endif; ?>
                                                    <?php if (!empty($cart_item['size'])): ?>
                                                        <br><small>Size: <?= htmlspecialchars($cart_item['size']) ?></small>
                                                    <?php endif; ?>
                                                    <strong> × <?= $cart_item['quantity'] ?></strong>
                                                </td>
                                                <td>₹<?= number_format($item_data['item_total'], 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>Cart Subtotal</th>
                                            <td>₹<?= number_format($subtotal, 2) ?></td>
                                        </tr>
                                        <?php if ($discount > 0): ?>
                                            <tr>
                                                <th>Discount (<?= $discount_percentage ?>% off)</th>
                                                <td style="color: #28a745;">-₹<?= number_format($discount, 2) ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <tr>
                                            <th>Shipping</th>
                                            <td><strong>₹<?= number_format($shipping_fee, 2) ?></strong></td>
                                        </tr>
                                        <tr class="order_total">
                                            <th>Order Total</th>
                                            <td><strong>₹<?= number_format($total, 2) ?></strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <h3 class="mt-4">Payment Method</h3>
                            <div class="payment_method">
                                <!-- Razorpay Payment -->
                                <div class="payment-method-option" id="razorpayOption">
                                    <label>
                                        <input class="input-radio" type="radio" name="payment_method" value="razorpay" required style="width: 15px;">
                                        Razorpay (Credit/Debit Card, UPI, NetBanking, Wallets)
                                        <img src="<?= $site ?>assets/img/payment/razorpay-logo.jpg" alt="Razorpay" class="razorpay-logo">
                                    </label>
                                    <div class="payment-method-details">
                                        Secure payment by Razorpay. All major payment methods accepted.
                                    </div>
                                </div>

                                <!-- Cash on Delivery -->
                                <div class="payment-method-option" id="codOption">
                                    <label>
                                        <input type="radio" name="payment_method" value="cod" style="width: 15px;">
                                        Cash on Delivery (COD)
                                    </label>
                                    <div class="payment-method-details">
                                        Pay when you receive your order.
                                        <div class="cod-info">
                                            Note: ₹200 advance payment required for COD orders
                                        </div>
                                        <div class="cod-breakdown">
                                            <p><strong>Payment Breakdown:</strong></p>
                                            <p>Advance Payment (Online): ₹200</p>
                                            <p>Remaining (Cash on Delivery): ₹<?= number_format($cod_remaining, 2) ?></p>
                                            <p><strong>Total: ₹<?= number_format($total, 2) ?></strong></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="error-message" id="payment_method_error"></div>

                                <!-- Terms and Conditions -->
                                <div class="form_group mt-4">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="terms" required>
                                        I have read and agree to the website <a href="<?= $site ?>terms" target="_blank" class="text-danger">terms and conditions</a> *
                                    </label>
                                    <div class="error-message" id="terms_error"></div>
                                </div>

                                <!-- Hidden fields -->
                                <input type="hidden" name="order_total" value="<?= $total ?>">
                                <input type="hidden" name="cod_advance" value="<?= $cod_advance ?>">
                                <input type="hidden" name="cod_remaining" value="<?= $cod_remaining ?>">

                                <div class="order_button mt-4">
                                    <button type="submit" id="placeOrderBtn" class="btn btn-primary btn-lg w-100">
                                        Place Order
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!--Checkout page section end-->

    <!--footer area start-->
    <?php include_once "includes/footer.php"; ?>

    <!-- Checkout JavaScript -->
    <script>
        $(document).ready(function() {
            // Payment method selection
            $('.payment-method-option').click(function() {
                $('.payment-method-option').removeClass('selected');
                $(this).addClass('selected');
                $(this).find('input[type="radio"]').prop('checked', true);
            });

            // Login form submission
            $('#loginForm').submit(function(e) {
                e.preventDefault();
                var formData = $(this).serialize();

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.message || 'Login failed');
                        }
                    }
                });
            });

            // Checkout form submission
            $('#checkoutForm').submit(function(e) {
                e.preventDefault();

                // Validate form
                if (!validateForm()) {
                    return false;
                }

                var paymentMethod = $('input[name="payment_method"]:checked').val();

                if (paymentMethod === 'razorpay') {
                    processRazorpayPayment();
                } else if (paymentMethod === 'cod') {
                    processCODOrder();
                } else {
                    showError('payment_method_error', 'Please select a payment method');
                    return false;
                }
            });

            // Form validation
            function validateForm() {
                var isValid = true;

                // Clear previous errors
                $('.error-message').hide();
                $('.form-control').removeClass('error');

                // Validate required fields
                var requiredFields = [
                    'billing_first_name',
                    'billing_last_name',
                    'billing_email',
                    'billing_phone',
                    'billing_country',
                    'billing_address_1',
                    'billing_city',
                    'billing_state',
                    'billing_postcode'
                ];

                requiredFields.forEach(function(field) {
                    var value = $('[name="' + field + '"]').val().trim();
                    if (!value) {
                        showError(field + '_error', 'This field is required');
                        $('[name="' + field + '"]').addClass('error');
                        isValid = false;
                    }
                });

                // Validate email
                var email = $('[name="billing_email"]').val();
                if (email && !validateEmail(email)) {
                    showError('billing_email_error', 'Please enter a valid email address');
                    $('[name="billing_email"]').addClass('error');
                    isValid = false;
                }

                // Validate phone
                var phone = $('[name="billing_phone"]').val();
                if (phone && !validatePhone(phone)) {
                    showError('billing_phone_error', 'Please enter a valid 10-digit phone number');
                    $('[name="billing_phone"]').addClass('error');
                    isValid = false;
                }

                // Validate payment method
                if (!$('input[name="payment_method"]:checked').val()) {
                    showError('payment_method_error', 'Please select a payment method');
                    isValid = false;
                }

                // Validate terms
                if (!$('input[name="terms"]').is(':checked')) {
                    showError('terms_error', 'You must agree to the terms and conditions');
                    isValid = false;
                }

                return isValid;
            }

            function validateEmail(email) {
                var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }

            function validatePhone(phone) {
                var re = /^\d{10}$/;
                return re.test(phone);
            }

            function showError(elementId, message) {
                $('#' + elementId).text(message).show();
            }

            function showLoading() {
                $('#loadingOverlay').show();
            }

            function hideLoading() {
                $('#loadingOverlay').hide();
            }

            // Razorpay payment processing
            function processRazorpayPayment() {
                showLoading();

                // First create order
                $.ajax({
                    url: '<?= $site ?>ajax/create-order.php',
                    method: 'POST',
                    data: {
                        action: 'create_order',
                        payment_method: 'razorpay',
                        order_total: <?= $total ?>,
                        form_data: $('#checkoutForm').serialize()
                    },
                    success: function(response) {
                        if (response.success && response.order_id) {
                            // Initialize Razorpay
                            var options = {
                                "key": "<?= $razorpay_key_id ?>",
                                "amount": <?= $total * 100 ?>, // Amount in paise
                                "currency": "INR",
                                "name": "Beastline",
                                "description": "Order Payment",
                                "order_id": response.razorpay_order_id,
                                "handler": function(razorpayResponse) {
                                    // Payment successful, process order
                                    processOrderAfterPayment(razorpayResponse, response.order_id);
                                },
                                "prefill": {
                                    "name": $('[name="billing_first_name"]').val() + ' ' + $('[name="billing_last_name"]').val(),
                                    "email": $('[name="billing_email"]').val(),
                                    "contact": $('[name="billing_phone"]').val()
                                },
                                "theme": {
                                    "color": "#e50010"
                                },
                                "modal": {
                                    "ondismiss": function() {
                                        hideLoading();
                                    }
                                }
                            };

                            var rzp = new Razorpay(options);
                            rzp.open();
                        } else {
                            hideLoading();
                            alert(response.message || 'Error creating order');
                        }
                    },
                    error: function() {
                        hideLoading();
                        alert('Error processing payment. Please try again.');
                    }
                });
            }

            function createCODOrder(paymentResponse) {
                $.ajax({
                    url: '<?= $site ?>ajax/create-order.php',
                    method: 'POST',
                    data: {
                        action: 'create_cod_order',
                        form_data: $('#checkoutForm').serialize(),
                        cod_advance: <?= $cod_advance ?>,
                        cod_remaining: <?= $cod_remaining ?>
                    },
                    success: function(response) {
                        if (response.success) {
                            // Now process the advance payment
                            processCODAdvancePayment(paymentResponse, response.order_id, response.cod_advance);
                        } else {
                            hideLoading();
                            alert(response.message || 'Error creating order');
                        }
                    },
                    error: function() {
                        hideLoading();
                        alert('Error creating order. Please contact support.');
                    }
                });
            }

            function processCODAdvancePayment(paymentResponse, orderId, codAdvance) {
                $.ajax({
                    url: '<?= $site ?>ajax/process-order.php',
                    method: 'POST',
                    data: {
                        action: 'complete_cod_advance',
                        payment_response: paymentResponse,
                        order_id: orderId,
                        cod_advance: codAdvance
                    },
                    success: function(response) {
                        hideLoading();
                        if (response.success) {
                            window.location.href = '<?= $site ?>order-confirmation/' + response.order_id;
                        } else {
                            alert(response.message || 'Error processing advance payment');
                        }
                    },
                    error: function() {
                        hideLoading();
                        alert('Error processing advance payment. Please contact support.');
                    }
                });
            }

            function processOrderAfterPayment(paymentResponse, orderId) {
                $.ajax({
                    url: '<?= $site ?>ajax/process-order.php',
                    method: 'POST',
                    data: {
                        action: 'complete_payment',
                        payment_response: paymentResponse,
                        order_id: orderId,
                        form_data: $('#checkoutForm').serialize()
                    },
                    success: function(response) {
                        hideLoading();
                        if (response.success) {
                            window.location.href = '<?= $site ?>order-confirmation/' + response.order_id;
                        } else {
                            alert(response.message || 'Error processing order');
                        }
                    },
                    error: function() {
                        hideLoading();
                        alert('Error processing order. Please contact support.');
                    }
                });
            }
        });
    </script>

    <?php include_once "includes/footer-link.php"; ?>

</body>

</html>