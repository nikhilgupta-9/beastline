<?php
include_once "config/connect.php";
include_once "util/function.php";

$contact = contact_us();
?>
<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>FAQ | Beastline - Fashion & Lifestyle</title>
    <meta name="description" content="Find answers to frequently asked questions about Beastline's products, shipping, returns, and customer service.">
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
    
    <!-- FAQ Custom CSS -->
    <style>
        .faq-category-tabs {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 30px;
        }
        .faq-category-tabs .nav-tabs {
            border-bottom: none;
            flex-wrap: nowrap;
            overflow-x: auto;
        }
        .faq-category-tabs .nav-tabs .nav-link {
            border: none;
            border-radius: 5px;
            color: #666;
            font-weight: 600;
            padding: 10px 20px;
            margin-right: 10px;
            white-space: nowrap;
            transition: all 0.3s ease;
        }
        .faq-category-tabs .nav-tabs .nav-link.active {
            background: #232323;
            color: white;
            box-shadow: 0 4px 15px rgba(199, 161, 122, 0.2);
        }
        .faq-category-tabs .nav-tabs .nav-link:hover {
            background: rgba(199, 161, 122, 0.1);
            color: #232323;
        }
        .faq-search-box {
            max-width: 400px;
            margin: 0 auto 40px;
        }
        .faq-search-box .input-group {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .faq-search-box input {
            border: none;
            padding: 15px 20px;
            border-radius: 8px 0 0 8px;
        }
        .faq-search-box button {
            background: #232323;
            border: none;
            color: white;
            padding: 0 25px;
            border-radius: 0 8px 8px 0;
        }
        .faq-quick-links {
            background: #232323;
            color: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .faq-quick-links h5 {
            color: white;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .faq-quick-links a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            background:rgb(255 255 255 / 29%);
            border-radius: 5px;
            margin: 5px;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .faq-quick-links a:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }
        .faq-contact-cta {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            margin-top: 40px;
            border-left: 4px solid #232323;
        }
        .faq-accordion .card {
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .faq-accordion .card-header {
            background: transparent;
            border: none;
            padding: 0;
        }
        .faq-accordion .btn-link {
            width: 100%;
            text-align: left;
            color: #333;
            font-weight: 600;
            padding: 20px;
            text-decoration: none;
            position: relative;
            background: transparent;
            border: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .faq-accordion .btn-link:hover {
            color: #232323;
        }
        .faq-accordion .btn-link:focus {
            box-shadow: none;
        }
        .faq-accordion .btn-link:after {
            content: '\f107';
            font-family: 'FontAwesome';
            font-size: 18px;
            color: #232323;
            transition: transform 0.3s ease;
        }
        .faq-accordion .btn-link.collapsed:after {
            content: '\f105';
        }
        .faq-accordion .card-body {
            padding: 20px;
            border-top: 1px solid #eee;
            color: #666;
            line-height: 1.8;
        }
        .faq-accordion .card-body ul {
            padding-left: 20px;
            margin-bottom: 15px;
        }
        .faq-accordion .card-body li {
            margin-bottom: 8px;
        }
        .faq-category-title {
            position: relative;
            padding-bottom: 15px;
            margin-bottom: 25px;
            color: #333;
        }
        .faq-category-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 2px;
            background: #232323;
        }
        .faq-help-section {
            background: #f9f9f9;
            border-radius: 10px;
            padding: 30px;
            margin-top: 40px;
        }
        .help-box {
            text-align: center;
            padding: 20px;
        }
        .help-icon {
            width: 60px;
            height: 60px;
            background: #232323;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 24px;
        }
        @media (max-width: 768px) {
            .faq-category-tabs .nav-tabs .nav-link {
                padding: 8px 15px;
                font-size: 14px;
                margin-right: 5px;
            }
            .faq-search-box {
                max-width: 100%;
            }
            .faq-accordion .btn-link {
                padding: 15px;
                font-size: 15px;
            }
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
                        <h3>Frequently Asked Questions</h3>
                        <ul>
                            <li><a href="<?= $site ?>">Home</a></li>
                            <li>FAQ</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>         
    </div>
    <!--breadcrumbs area end-->
    
    <!--faq intro area start-->
    <div class="faq_intro_area">
        <div class="container">   
            <div class="row">
                <div class="col-12">
                    <div class="faq_intro_wrapper text-center mb-5">
                        <h2>How Can We Help You?</h2>
                        <p class="mb-4">Find quick answers to common questions about Beastline products, orders, shipping, and more.</p>
                        
                        <!-- Search Box -->
                        <div class="faq-search-box">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search for answers..." id="faqSearch">
                                <button class="btn" type="button" id="searchButton">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Quick Links -->
                        <div class="faq-quick-links">
                            <h5 class="text-center mb-4">Quick Links</h5>
                            <div class="text-center">
                                <a href="#orders">Order Status</a>
                                <a href="#shipping">Shipping Info</a>
                                <a href="#returns">Returns & Exchanges</a>
                                <a href="#sizing">Size Guide</a>
                                <a href="#payment">Payment Methods</a>
                                <a href="#products">Product Care</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div> 
        </div>    
    </div>
    <!--faq intro area end-->
    
    <!--faq categories area start-->
    <div class="faq_categories_area">
        <div class="container">
            <div class="faq-category-tabs">
                <ul class="nav nav-tabs justify-content-center" id="faqCategoryTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab" aria-controls="orders" aria-selected="true">
                            <i class="fa fa-shopping-cart me-2"></i> Orders
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="shipping-tab" data-bs-toggle="tab" data-bs-target="#shipping" type="button" role="tab" aria-controls="shipping" aria-selected="false">
                            <i class="fa fa-truck me-2"></i> Shipping
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="returns-tab" data-bs-toggle="tab" data-bs-target="#returns" type="button" role="tab" aria-controls="returns" aria-selected="false">
                            <i class="fa fa-exchange-alt me-2"></i> Returns
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="sizing-tab" data-bs-toggle="tab" data-bs-target="#sizing" type="button" role="tab" aria-controls="sizing" aria-selected="false">
                            <i class="fa fa-tshirt me-2"></i> Sizing
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment" type="button" role="tab" aria-controls="payment" aria-selected="false">
                            <i class="fa fa-credit-card me-2"></i> Payment
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab" aria-controls="products" aria-selected="false">
                            <i class="fa fa-cube me-2"></i> Products
                        </button>
                    </li>
                </ul>
            </div>
            
            <div class="tab-content" id="faqCategoryContent">
                
                <!-- Orders Tab -->
                <div class="tab-pane fade show active" id="orders" role="tabpanel" aria-labelledby="orders-tab">
                    <h4 class="faq-category-title"><i class="fa fa-shopping-cart me-2"></i> Order Questions</h4>
                    <div class="faq-accordion" id="ordersAccordion">
                        <div class="card">
                            <div class="card-header" id="orderHeading1">
                                <button class="btn btn-link" data-bs-toggle="collapse" data-bs-target="#orderCollapse1" aria-expanded="true" aria-controls="orderCollapse1">
                                    How can I track my order?
                                </button>
                            </div>
                            <div id="orderCollapse1" class="collapse show" aria-labelledby="orderHeading1" data-parent="#ordersAccordion">
                                <div class="card-body">
                                    <p>Once your order is shipped, you will receive a confirmation email with a tracking number and link. You can also track your order by:</p>
                                    <ul>
                                        <li>Logging into your Beastline account</li>
                                        <li>Using the tracking number in your shipping confirmation email</li>
                                        <li>Contacting our customer service team</li>
                                    </ul>
                                    <p>Orders are typically processed within 1-2 business days. During peak seasons, processing may take 3-4 business days.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header" id="orderHeading2">
                                <button class="btn btn-link collapsed" data-bs-toggle="collapse" data-bs-target="#orderCollapse2" aria-expanded="false" aria-controls="orderCollapse2">
                                    Can I modify or cancel my order after placing it?
                                </button>
                            </div>
                            <div id="orderCollapse2" class="collapse" aria-labelledby="orderHeading2" data-parent="#ordersAccordion">
                                <div class="card-body">
                                    <p>We can only modify or cancel orders that haven't been processed for shipping yet. To request a change:</p>
                                    <ul>
                                        <li>Contact our customer service immediately at support@beastline.com</li>
                                        <li>Include your order number and requested changes</li>
                                        <li>Call us at +91-XXXXXXXXXX within 1 hour of placing your order</li>
                                    </ul>
                                    <p><strong>Note:</strong> Once your order is shipped, modifications cannot be made. You can return items following our return policy.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header" id="orderHeading3">
                                <button class="btn btn-link collapsed" data-bs-toggle="collapse" data-bs-target="#orderCollapse3" aria-expanded="false" aria-controls="orderCollapse3">
                                    How do I create an account on Beastline?
                                </button>
                            </div>
                            <div id="orderCollapse3" class="collapse" aria-labelledby="orderHeading3" data-parent="#ordersAccordion">
                                <div class="card-body">
                                    <p>Creating a Beastline account is simple and offers many benefits:</p>
                                    <ul>
                                        <li><strong>Step 1:</strong> Click on "My Account" in the top navigation</li>
                                        <li><strong>Step 2:</strong> Select "Register" and enter your details</li>
                                        <li><strong>Step 3:</strong> Verify your email address</li>
                                        <li><strong>Step 4:</strong> Start shopping!</li>
                                    </ul>
                                    <p><strong>Account Benefits:</strong> Faster checkout, order tracking, wishlist, exclusive offers, and personalized recommendations.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header" id="orderHeading4">
                                <button class="btn btn-link collapsed" data-bs-toggle="collapse" data-bs-target="#orderCollapse4" aria-expanded="false" aria-controls="orderCollapse4">
                                    What should I do if I haven't received my order confirmation email?
                                </button>
                            </div>
                            <div id="orderCollapse4" class="collapse" aria-labelledby="orderHeading4" data-parent="#ordersAccordion">
                                <div class="card-body">
                                    <p>If you haven't received your order confirmation email, please check:</p>
                                    <ul>
                                        <li>Your spam or junk mail folder</li>
                                        <li>That the email address was entered correctly</li>
                                        <li>Your account's order history if you ordered as a registered user</li>
                                    </ul>
                                    <p>If you still can't find it, please <a href="contact.php">contact our customer service</a> with your order details, and we'll resend the confirmation.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Shipping Tab -->
                <div class="tab-pane fade" id="shipping" role="tabpanel" aria-labelledby="shipping-tab">
                    <h4 class="faq-category-title"><i class="fa fa-truck me-2"></i> Shipping Information</h4>
                    <div class="faq-accordion" id="shippingAccordion">
                        <div class="card">
                            <div class="card-header" id="shippingHeading1">
                                <button class="btn btn-link" data-bs-toggle="collapse" data-bs-target="#shippingCollapse1" aria-expanded="true" aria-controls="shippingCollapse1">
                                    What are your shipping options and delivery times?
                                </button>
                            </div>
                            <div id="shippingCollapse1" class="collapse show" aria-labelledby="shippingHeading1" data-parent="#shippingAccordion">
                                <div class="card-body">
                                    <p>We offer several shipping options across India:</p>
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Shipping Method</th>
                                                <th>Delivery Time</th>
                                                <th>Cost</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Standard Shipping</td>
                                                <td>5-8 business days</td>
                                                <td>Free on orders above ₹1999</td>
                                            </tr>
                                            <tr>
                                                <td>Express Shipping</td>
                                                <td>3-5 business days</td>
                                                <td>₹199</td>
                                            </tr>
                                            <tr>
                                                <td>Next-Day Delivery*</td>
                                                <td>1-2 business days</td>
                                                <td>₹299</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <p><small>*Available in select metropolitan areas only. Order before 2 PM for next-day delivery.</small></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header" id="shippingHeading2">
                                <button class="btn btn-link collapsed" data-bs-toggle="collapse" data-bs-target="#shippingCollapse2" aria-expanded="false" aria-controls="shippingCollapse2">
                                    Do you ship internationally?
                                </button>
                            </div>
                            <div id="shippingCollapse2" class="collapse" aria-labelledby="shippingHeading2" data-parent="#shippingAccordion">
                                <div class="card-body">
                                    <p>Yes! We ship Beastline products worldwide. International shipping typically takes 7-15 business days depending on the destination. International shipping charges vary by country and will be calculated at checkout.</p>
                                    <p><strong>Popular Destinations:</strong></p>
                                    <ul>
                                        <li>USA & Canada: 10-12 business days</li>
                                        <li>UK & Europe: 8-10 business days</li>
                                        <li>Australia & New Zealand: 12-15 business days</li>
                                        <li>Middle East: 7-9 business days</li>
                                    </ul>
                                    <p><strong>Note:</strong> Customers are responsible for any customs duties, taxes, or import fees charged by their country.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header" id="shippingHeading3">
                                <button class="btn btn-link collapsed" data-bs-toggle="collapse" data-bs-target="#shippingCollapse3" aria-expanded="false" aria-controls="shippingCollapse3">
                                    How do I estimate shipping costs?
                                </button>
                            </div>
                            <div id="shippingCollapse3" class="collapse" aria-labelledby="shippingHeading3" data-parent="#shippingAccordion">
                                <div class="card-body">
                                    <p>Shipping costs are calculated based on:</p>
                                    <ul>
                                        <li>Delivery location</li>
                                        <li>Package weight and dimensions</li>
                                        <li>Selected shipping method</li>
                                        <li>Current promotions (free shipping on orders above ₹1999)</li>
                                    </ul>
                                    <p>To estimate shipping costs:</p>
                                    <ol>
                                        <li>Add items to your cart</li>
                                        <li>Proceed to checkout</li>
                                        <li>Enter your shipping address</li>
                                        <li>Shipping options and costs will be displayed</li>
                                    </ol>
                                    <p>You can also use our <a href="contact.php">shipping calculator</a> or contact customer service for a quote.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Returns Tab -->
                <div class="tab-pane fade" id="returns" role="tabpanel" aria-labelledby="returns-tab">
                    <h4 class="faq-category-title"><i class="fa fa-exchange-alt me-2"></i> Returns & Exchanges</h4>
                    <div class="faq-accordion" id="returnsAccordion">
                        <div class="card">
                            <div class="card-header" id="returnsHeading1">
                                <button class="btn btn-link" data-bs-toggle="collapse" data-bs-target="#returnsCollapse1" aria-expanded="true" aria-controls="returnsCollapse1">
                                    What is your return policy?
                                </button>
                            </div>
                            <div id="returnsCollapse1" class="collapse show" aria-labelledby="returnsHeading1" data-parent="#returnsAccordion">
                                <div class="card-body">
                                    <p>We offer a 30-day return policy from the date of delivery. To be eligible for a return:</p>
                                    <ul>
                                        <li>Items must be unused, unworn, and in original condition</li>
                                        <li>All original tags and packaging must be intact</li>
                                        <li>Proof of purchase (order number) is required</li>
                                    </ul>
                                    <p><strong>Return Process:</strong></p>
                                    <ol>
                                        <li>Initiate return request via your account or contact customer service</li>
                                        <li>Receive return authorization and instructions</li>
                                        <li>Pack items securely with original packaging</li>
                                        <li>Ship using the provided return label</li>
                                        <li>Refund processed within 7-10 business days of receiving return</li>
                                    </ol>
                                    <p><strong>Non-Returnable Items:</strong> Perfumes (opened), personalized items, and intimate apparel.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header" id="returnsHeading2">
                                <button class="btn btn-link collapsed" data-bs-toggle="collapse" data-bs-target="#returnsCollapse2" aria-expanded="false" aria-controls="returnsCollapse2">
                                    How do exchanges work?
                                </button>
                            </div>
                            <div id="returnsCollapse2" class="collapse" aria-labelledby="returnsHeading2" data-parent="#returnsAccordion">
                                <div class="card-body">
                                    <p>We offer hassle-free exchanges for size or color changes:</p>
                                    <ul>
                                        <li>Exchanges must be initiated within 30 days of delivery</li>
                                        <li>Item must be in new, unworn condition with original tags</li>
                                        <li>We cover return shipping for exchanges</li>
                                    </ul>
                                    <p><strong>Exchange Process:</strong></p>
                                    <ol>
                                        <li>Contact customer service for exchange authorization</li>
                                        <li>Return the original item following provided instructions</li>
                                        <li>Once received, we'll ship the replacement item</li>
                                        <li>If the new item has a different price, we'll process the difference</li>
                                    </ol>
                                    <p>For faster service, you can place a new order for the desired item and return the original for a refund.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header" id="returnsHeading3">
                                <button class="btn btn-link collapsed" data-bs-toggle="collapse" data-bs-target="#returnsCollapse3" aria-expanded="false" aria-controls="returnsCollapse3">
                                    How long do refunds take to process?
                                </button>
                            </div>
                            <div id="returnsCollapse3" class="collapse" aria-labelledby="returnsHeading3" data-parent="#returnsAccordion">
                                <div class="card-body">
                                    <p>Refunds are typically processed as follows:</p>
                                    <ul>
                                        <li><strong>Credit/Debit Cards:</strong> 7-10 business days after we receive your return</li>
                                        <li><strong>UPI & Digital Wallets:</strong> 3-5 business days</li>
                                        <li><strong>Net Banking:</strong> 5-7 business days</li>
                                        <li><strong>Store Credit:</strong> Immediate upon return approval</li>
                                    </ul>
                                    <p><strong>Important Notes:</strong></p>
                                    <ul>
                                        <li>The refund will be issued to the original payment method</li>
                                        <li>Shipping charges are non-refundable unless the return is due to our error</li>
                                        <li>You will receive email confirmation when your refund is processed</li>
                                        <li>Contact your bank if you haven't received the refund after 10 business days</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sizing Tab -->
                <div class="tab-pane fade" id="sizing" role="tabpanel" aria-labelledby="sizing-tab">
                    <h4 class="faq-category-title"><i class="fa fa-tshirt me-2"></i> Sizing Information</h4>
                    <div class="faq-accordion" id="sizingAccordion">
                        <div class="card">
                            <div class="card-header" id="sizingHeading1">
                                <button class="btn btn-link" data-bs-toggle="collapse" data-bs-target="#sizingCollapse1" aria-expanded="true" aria-controls="sizingCollapse1">
                                    How do I find my correct size?
                                </button>
                            </div>
                            <div id="sizingCollapse1" class="collapse show" aria-labelledby="sizingHeading1" data-parent="#sizingAccordion">
                                <div class="card-body">
                                    <p>Finding your perfect fit is easy with Beastline:</p>
                                    <ul>
                                        <li><strong>Size Guide:</strong> Visit our <a href="size.php">detailed size guide</a> for comprehensive measurements</li>
                                        <li><strong>Product Pages:</strong> Each product has specific size charts</li>
                                        <li><strong>Fit Notes:</strong> Check product descriptions for fit information (Slim, Regular, Relaxed)</li>
                                    </ul>
                                    <p><strong>Measurement Tips:</strong></p>
                                    <ol>
                                        <li>Use a soft measuring tape</li>
                                        <li>Measure over thin clothing</li>
                                        <li>Keep the tape parallel to the floor</li>
                                        <li>Don't pull too tight - leave a finger's width of space</li>
                                    </ol>
                                    <p>Still unsure? <a href="contact.php">Contact our style experts</a> for personalized size recommendations!</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header" id="sizingHeading2">
                                <button class="btn btn-link collapsed" data-bs-toggle="collapse" data-bs-target="#sizingCollapse2" aria-expanded="false" aria-controls="sizingCollapse2">
                                    Are Beastline sizes true to size?
                                </button>
                            </div>
                            <div id="sizingCollapse2" class="collapse" aria-labelledby="sizingHeading2" data-parent="#sizingAccordion">
                                <div class="card-body">
                                    <p>Most Beastline products are true to size, but here are specific guidelines:</p>
                                    <ul>
                                        <li><strong>Shirts:</strong> True to size. If between sizes, choose larger for comfort</li>
                                        <li><strong>Pants:</strong> True to size. Check individual product for fit type</li>
                                        <li><strong>Shoes:</strong> True to size. We recommend measuring your foot for accuracy</li>
                                        <li><strong>Perfumes:</strong> Available in standard fragrance sizes</li>
                                    </ul>
                                    <p><strong>Customer Feedback:</strong> Check customer reviews for real-life sizing experiences. Many reviewers mention if items run large or small.</p>
                                    <p><strong>Fit Guarantee:</strong> If the fit isn't perfect, we offer free exchanges within 30 days.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header" id="sizingHeading3">
                                <button class="btn btn-link collapsed" data-bs-toggle="collapse" data-bs-target="#sizingCollapse3" aria-expanded="false" aria-controls="sizingCollapse3">
                                    What if the size I ordered doesn't fit?
                                </button>
                            </div>
                            <div id="sizingCollapse3" class="collapse" aria-labelledby="sizingHeading3" data-parent="#sizingAccordion">
                                <div class="card-body">
                                    <p>No worries! We make size exchanges easy:</p>
                                    <ul>
                                        <li><strong>Free Exchange:</strong> We offer free size exchanges within 30 days</li>
                                        <li><strong>Easy Process:</strong> Contact customer service for exchange authorization</li>
                                        <li><strong>Quick Turnaround:</strong> New size shipped once return is received</li>
                                    </ul>
                                    <p><strong>Exchange Options:</strong></p>
                                    <ol>
                                        <li>Direct exchange for different size of same item</li>
                                        <li>Exchange for different item (price difference applies)</li>
                                        <li>Return for full refund and place new order</li>
                                    </ol>
                                    <p><strong>Pro Tip:</strong> For fastest service, place a new order for the correct size while processing the return of the original item.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Tab -->
                <div class="tab-pane fade" id="payment" role="tabpanel" aria-labelledby="payment-tab">
                    <h4 class="faq-category-title"><i class="fa fa-credit-card me-2"></i> Payment Methods</h4>
                    <div class="faq-accordion" id="paymentAccordion">
                        <div class="card">
                            <div class="card-header" id="paymentHeading1">
                                <button class="btn btn-link" data-bs-toggle="collapse" data-bs-target="#paymentCollapse1" aria-expanded="true" aria-controls="paymentCollapse1">
                                    What payment methods do you accept?
                                </button>
                            </div>
                            <div id="paymentCollapse1" class="collapse show" aria-labelledby="paymentHeading1" data-parent="#paymentAccordion">
                                <div class="card-body">
                                    <p>We accept a wide range of secure payment methods:</p>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Digital Payments:</strong></p>
                                            <ul>
                                                <li>Credit/Debit Cards (Visa, MasterCard, Rupay, American Express)</li>
                                                <li>UPI (PhonePe, Google Pay, Paytm, BHIM)</li>
                                                <li>Digital Wallets (Paytm Wallet, Amazon Pay, Mobikwik)</li>
                                                <li>Net Banking (All major Indian banks)</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Other Options:</strong></p>
                                            <ul>
                                                <li>EMI Options (3-12 months, zero-cost EMI available)</li>
                                                <li>Cash on Delivery (Available across India)</li>
                                                <li>Beastline Gift Cards</li>
                                                <li>International Cards (For overseas customers)</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <p><strong>Security:</strong> All payments are processed through secure, encrypted gateways. We never store your payment information.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header" id="paymentHeading2">
                                <button class="btn btn-link collapsed" data-bs-toggle="collapse" data-bs-target="#paymentCollapse2" aria-expanded="false" aria-controls="paymentCollapse2">
                                    Is Cash on Delivery available?
                                </button>
                            </div>
                            <div id="paymentCollapse2" class="collapse" aria-labelledby="paymentHeading2" data-parent="#paymentAccordion">
                                <div class="card-body">
                                    <p>Yes! Cash on Delivery (COD) is available for most locations across India.</p>
                                    <ul>
                                        <li><strong>COD Limit:</strong> Maximum order value of ₹10,000</li>
                                        <li><strong>COD Charges:</strong> ₹49 per order</li>
                                        <li><strong>COD Advance:</strong> ₹200 per order</li>
                                        <li><strong>Availability:</strong> Check during checkout for your location</li>
                                        <li><strong>Payment:</strong> Exact cash amount to the delivery person</li>
                                    </ul>
                                    <p><strong>COD Restrictions:</strong></p>
                                    <ul>
                                        <li>First-time customers may have lower limits</li>
                                        <li>Not available for international orders</li>
                                        <li>High-value orders may require advance payment</li>
                                        <li>Certain remote locations may not support COD</li>
                                    </ul>
                                    <p><strong>Tip:</strong> For faster delivery, we recommend prepaid orders which are processed immediately.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header" id="paymentHeading3">
                                <button class="btn btn-link collapsed" data-bs-toggle="collapse" data-bs-target="#paymentCollapse3" aria-expanded="false" aria-controls="paymentCollapse3">
                                    How do EMI options work?
                                </button>
                            </div>
                            <div id="paymentCollapse3" class="collapse" aria-labelledby="paymentHeading3" data-parent="#paymentAccordion">
                                <div class="card-body">
                                    <p>We offer flexible EMI options through various banks and financial partners:</p>
                                    <ul>
                                        <li><strong>Minimum Order:</strong> ₹3,000 for EMI eligibility</li>
                                        <strong>Tenure:</strong> 3, 6, 9, or 12 months (varies by bank)</li>
                                        <li><strong>Zero-Cost EMI:</strong> Available on select banks and products</li>
                                        <li><strong>Processing Fee:</strong> May apply depending on bank and tenure</li>
                                    </ul>
                                    <p><strong>Participating Banks:</strong> HDFC, ICICI, Axis, SBI, Kotak, Citibank, Standard Chartered, and more.</p>
                                    <p><strong>How to Use EMI:</strong></p>
                                    <ol>
                                        <li>Add products to cart (minimum ₹3,000)</li>
                                        <li>Proceed to checkout</li>
                                        <li>Select "Credit Card EMI" or "Debit Card EMI" option</li>
                                        <li>Choose your bank and preferred tenure</li>
                                        <li>Complete the payment process</li>
                                    </ol>
                                    <p>EMI details will be confirmed by your bank. Contact your bank for specific terms and conditions.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Products Tab -->
                <div class="tab-pane fade" id="products" role="tabpanel" aria-labelledby="products-tab">
                    <h4 class="faq-category-title"><i class="fa fa-cube me-2"></i> Product Information</h4>
                    <div class="faq-accordion" id="productsAccordion">
                        <div class="card">
                            <div class="card-header" id="productsHeading1">
                                <button class="btn btn-link" data-bs-toggle="collapse" data-bs-target="#productsCollapse1" aria-expanded="true" aria-controls="productsCollapse1">
                                    What materials are Beastline products made from?
                                </button>
                            </div>
                            <div id="productsCollapse1" class="collapse show" aria-labelledby="productsHeading1" data-parent="#productsAccordion">
                                <div class="card-body">
                                    <p>At Beastline, we use premium materials for superior quality and comfort:</p>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Shirts:</strong></p>
                                            <ul>
                                                <li>Premium Cotton & Cotton Blends</li>
                                                <li>Linen for summer collections</li>
                                                <li>Performance Fabrics with moisture-wicking</li>
                                                <li>Organic Cotton options available</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Pants:</strong></p>
                                            <ul>
                                                <li>High-Quality Denim</li>
                                                <li>Chinos with stretch technology</li>
                                                <li>Linen & Cotton Blends</li>
                                                <li>Performance Trousers</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <p><strong>Shoes:</strong></p>
                                            <ul>
                                                <li>Genuine Leather</li>
                                                <li>Breathable Mesh</li>
                                                <li>Premium Suede</li>
                                                <li>Eco-friendly Materials</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Perfumes:</strong></p>
                                            <ul>
                                                <li>Premium Essential Oils</li>
                                                <li>Natural Extracts</li>
                                                <li>Alcohol-based formulations</li>
                                                <li>Long-lasting Concentrations</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <p>All materials are carefully selected for durability, comfort, and sustainability.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header" id="productsHeading2">
                                <button class="btn btn-link collapsed" data-bs-toggle="collapse" data-bs-target="#productsCollapse2" aria-expanded="false" aria-controls="productsCollapse2">
                                    How do I care for my Beastline products?
                                </button>
                            </div>
                            <div id="productsCollapse2" class="collapse" aria-labelledby="productsHeading2" data-parent="#productsAccordion">
                                <div class="card-body">
                                    <p>Proper care ensures your Beastline products last longer:</p>
                                    
                                    <p><strong>Shirts & Clothing:</strong></p>
                                    <ul>
                                        <li>Machine wash cold or warm (check care label)</li>
                                        <li>Use mild detergent</li>
                                        <li>Tumble dry low or line dry</li>
                                        <li>Iron on appropriate temperature setting</li>
                                        <li>Store folded or on hangers</li>
                                    </ul>
                                    
                                    <p><strong>Shoes:</strong></p>
                                    <ul>
                                        <li>Clean with appropriate cleaner for material</li>
                                        <li>Use shoe trees to maintain shape</li>
                                        <li>Rotate between pairs to extend life</li>
                                        <li>Store in cool, dry place</li>
                                        <li>Use waterproof spray for leather shoes</li>
                                    </ul>
                                    
                                    <p><strong>Perfumes:</strong></p>
                                    <ul>
                                        <li>Store in cool, dark place away from sunlight</li>
                                        <li>Keep bottles tightly closed</li>
                                        <li>Avoid extreme temperatures</li>
                                        <li>Use within 3-5 years of opening</li>
                                    </ul>
                                    
                                    <p>Detailed care instructions are included with each product.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header" id="productsHeading3">
                                <button class="btn btn-link collapsed" data-bs-toggle="collapse" data-bs-target="#productsCollapse3" aria-expanded="false" aria-controls="productsCollapse3">
                                    Are Beastline products authentic and original?
                                </button>
                            </div>
                            <div id="productsCollapse3" class="collapse" aria-labelledby="productsHeading3" data-parent="#productsAccordion">
                                <div class="card-body">
                                    <p>Absolutely! Beastline guarantees 100% authentic and original products:</p>
                                    <ul>
                                        <li><strong>Direct Sourcing:</strong> We work directly with manufacturers and brands</li>
                                        <li><strong>Quality Assurance:</strong> Every product undergoes strict quality checks</li>
                                        <li><strong>Authenticity Promise:</strong> We guarantee the authenticity of all products</li>
                                        <li><strong>Brand Partnerships:</strong> Official partnerships with leading brands</li>
                                    </ul>
                                    
                                    <p><strong>How to Verify Authenticity:</strong></p>
                                    <ul>
                                        <li>Check for Beastline authenticity tags</li>
                                        <li>Verify through brand verification systems when available</li>
                                        <li>Compare with brand specifications</li>
                                        <li>Contact customer service for verification</li>
                                    </ul>
                                    
                                    <p><strong>Our Promise:</strong> If you ever receive a product that doesn't meet our authenticity standards, we'll provide a full refund and investigate immediately.</p>
                                    
                                    <p>Shop with confidence knowing all Beastline products are genuine and backed by our authenticity guarantee.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Still Need Help Section -->
            <div class="faq-contact-cta my-4">
                <h4 class="mb-3">Still Have Questions?</h4>
                <p class="mb-4">Our customer service team is here to help you 7 days a week.</p>
                <div class="row">
                    <div class="col-md-4">
                        <div class="help-box">
                            <div class="help-icon">
                                <i class="fa fa-phone"></i>
                            </div>
                            <h6>Call Us</h6>
                            <p>+91-<?= $contact['phone'] ?><br>Mon-Sun: 9 AM - 9 PM</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="help-box">
                            <div class="help-icon">
                                <i class="fa fa-envelope"></i>
                            </div>
                            <h6>Email Us</h6>
                            <p><?= $contact['email'] ?><br>Response within 24 hours</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="help-box">
                            <div class="help-icon">
                                <i class="fa fa-comments"></i>
                            </div>
                            <h6>Live Chat</h6>
                            <p>Available 24/7<br>Click the chat icon below</p>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="<?= $site ?>contact/" class="button me-2">Contact Us</a>
                    <a href="<?= $site ?>" class="button ">Continue Shopping</a>
                </div>
            </div>
        </div>
    </div>
    <!--faq categories area end-->
    
    <!--footer area start-->
    <?php include_once "includes/footer.php"; ?>
    <?php include_once "includes/footer-link.php"; ?>

    <!-- Bootstrap JS for Tabs and Accordion -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- FAQ Search Functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // FAQ Search Functionality
            const searchInput = document.getElementById('faqSearch');
            const searchButton = document.getElementById('searchButton');
            
            function searchFAQs() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                if (searchTerm === '') return;
                
                // Get all FAQ content
                const faqQuestions = document.querySelectorAll('.faq-accordion .btn-link');
                const faqAnswers = document.querySelectorAll('.faq-accordion .card-body');
                
                let found = false;
                
                // Hide all first
                faqQuestions.forEach(q => q.parentElement.parentElement.style.display = 'none');
                
                // Show matching FAQs
                faqQuestions.forEach((question, index) => {
                    const questionText = question.textContent.toLowerCase();
                    const answerText = faqAnswers[index] ? faqAnswers[index].textContent.toLowerCase() : '';
                    
                    if (questionText.includes(searchTerm) || answerText.includes(searchTerm)) {
                        question.parentElement.parentElement.style.display = 'block';
                        // Open the matching FAQ
                        if (!question.classList.contains('collapsed')) {
                            const collapseId = question.getAttribute('data-bs-target');
                            const collapseElement = document.querySelector(collapseId);
                            if (collapseElement) {
                                new bootstrap.Collapse(collapseElement, {toggle: true});
                            }
                        }
                        found = true;
                    }
                });
                
                // Show message if no results found
                if (!found) {
                    alert('No FAQs found matching your search. Try different keywords or contact our customer service.');
                    // Show all FAQs again
                    faqQuestions.forEach(q => q.parentElement.parentElement.style.display = 'block');
                }
            }
            
            // Event listeners for search
            searchButton.addEventListener('click', searchFAQs);
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchFAQs();
                }
            });
            
            // Save active tab
            const faqTab = localStorage.getItem('activeFaqTab');
            if (faqTab) {
                const tabElement = document.querySelector(`[data-bs-target="${faqTab}"]`);
                if (tabElement) {
                    const tab = new bootstrap.Tab(tabElement);
                    tab.show();
                }
            }
            
            // Update active tab on change
            document.querySelectorAll('#faqCategoryTab button').forEach(tab => {
                tab.addEventListener('shown.bs.tab', function(event) {
                    localStorage.setItem('activeFaqTab', event.target.getAttribute('data-bs-target'));
                });
            });
            
            // Quick links scroll to section
            document.querySelectorAll('.faq-quick-links a').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href').substring(1);
                    const targetTab = document.querySelector(`#${targetId}-tab`);
                    if (targetTab) {
                        const tab = new bootstrap.Tab(targetTab);
                        tab.show();
                        
                        // Scroll to the section
                        setTimeout(() => {
                            document.getElementById(targetId).scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }, 300);
                    }
                });
            });
            
            // Print FAQ functionality
            const printButton = document.createElement('button');
            printButton.innerHTML = '<i class="fa fa-print me-2"></i>Print FAQ';
            printButton.className = 'btn btn-outline-secondary btn-sm';
            printButton.style.position = 'fixed';
            printButton.style.bottom = '20px';
            printButton.style.right = '20px';
            printButton.style.zIndex = '1000';
            printButton.addEventListener('click', function() {
                window.print();
            });
            document.body.appendChild(printButton);
        });
    </script>

</body>
</html>