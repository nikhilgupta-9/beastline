<?php
include_once "config/connect.php";
include_once "util/function.php";
include_once(__DIR__ . "/models/WebsiteSettings.php");

$setting = new Setting($conn);

$contact = contact_us();
$about = fetch_about();
?>
<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title><?= $about['meta_title'] ?></title>
    <meta name="description" content="<?= $about['meta_description'] ?>">
    <meta name="keyword" content="<?= $about['meta_keywords'] ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="<?= $site ?>admin/<?php echo htmlspecialchars($setting->get('favicon')); ?>">

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
                        <h3>about</h3>
                        <ul>
                            <li><a href="<?= $site ?>">home</a></li>
                            <li>about us</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--breadcrumbs area end-->

    <!--about section area -->
    <section class="about_section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="about_content">
                        <h1><?= $about['title'] ?></h1>

                        <h3>Beastline delivers high-quality winter clothing and jackets designed for warmth, comfort, and modern style.</h3>

                        <p>
                            <?= $about['content'] ?>
                        </p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="about_thumb">
                        <img src="<?= $site ?>admin/<?= $about['image_url'] ?>" alt="Beastline winter jackets and winter clothing collection">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!--about section end-->

    <!--chose us area start-->
    <div class="choseus_area" data-bgimg="<?= $site ?>assets/img/about/about-us-policy-bg.jpg">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <div class="single_chose">
                        <div class="chose_icone">
                            <img src="<?= $site ?>assets/img/about/About_icon1.png" alt="">
                        </div>
                        <div class="chose_content">
                            <h3>Creative Design</h3>
                            <p>Erat metus sodales eget dolor consectetuer, porta ut purus at et alias, nulla ornare velit amet</p>

                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="single_chose">
                        <div class="chose_icone">
                            <img src="<?= $site ?>assets/img/about/About_icon2.png" alt="Quality Fabric Icon">
                        </div>
                        <div class="chose_content">
                            <h3>Premium Quality Fabrics</h3>
                            <p>Crafted with carefully selected materials to ensure comfort, durability, and a refined look for every occasion.</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="single_chose chose3">
                        <div class="chose_icone">
                            <img src="<?= $site ?>assets/img/about/About_icon3.png" alt="">
                        </div>
                        <div class="chose_content">
                            <h3>Online Support 24/7</h3>
                            <p>Erat metus sodales eget dolor consectetuer, porta ut purus at et alias, nulla ornare velit amet</p>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--chose us area end-->

    <!--testimonial area start-->
    <div class="faq-client-say-area">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 col-md-12">
                    <div class="faq-client_title">
                        <h2>Frequently Ask Questions</h2>
                    </div>
                    <div class="faq-style-wrap" id="faq-five">
                        <!-- Panel-default -->
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h5 class="panel-title">
                                    <a id="octagon" class="collapsed" role="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse1" aria-expanded="true" aria-controls="faq-collapse1"> <span class="button-faq"></span>What makes Beastline clothing premium quality?</a>
                                </h5>
                            </div>
                            <div id="faq-collapse1" class="collapse show" aria-expanded="true" role="tabpanel" data-parent="#faq-five">
                                <div class="panel-body">
                                    <p>At Beastline, we source only the finest fabrics including Egyptian cotton, premium wool blends, and Italian textiles for our formal wear collection.</p>
                                    <p>Our clothing features reinforced stitching, mother-of-pearl buttons, and attention to tailoring details that ensure durability and sophistication. Each garment undergoes rigorous quality checks before reaching our customers.</p>
                                    <p>We've eliminated middlemen to deliver premium quality directly to you at competitive prices, maintaining our commitment to exceptional craftsmanship.</p>
                                </div>
                            </div>
                        </div>
                        <!--// Panel-default -->

                        <!-- Panel-default -->
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h5 class="panel-title">
                                    <a class="collapsed" role="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse2" aria-expanded="false" aria-controls="faq-collapse2"> <span class="button-faq"></span>How do I choose the right fit for formal wear?</a>
                                </h5>
                            </div>
                            <div id="faq-collapse2" class="collapse" aria-expanded="false" role="tabpanel" data-parent="#faq-five">
                                <div class="panel-body">
                                    <p>Beastline offers three primary fits for our formal collection:</p>
                                    <p><strong>Slim Fit:</strong> Contemporary cut that follows your body shape closely, perfect for modern professional settings.</p>
                                    <p><strong>Regular Fit:</strong> Classic comfort with room for movement, ideal for traditional office environments.</p>
                                    <p><strong>Tailored Fit:</strong> Customized proportions that balance comfort and sophistication, offering the best of both worlds.</p>
                                    <p>Use our detailed size guide and measurement charts available on each product page to find your perfect fit. For personalized assistance, our style consultants are available via chat.</p>
                                </div>
                            </div>
                        </div>
                        <!--// Panel-default -->

                        <!-- Panel-default -->
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h5 class="panel-title">
                                    <a class="collapsed" role="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse3" aria-expanded="false" aria-controls="faq-collapse3"> <span class="button-faq"></span>What is your return and exchange policy for formal wear?</a>
                                </h5>
                            </div>
                            <div id="faq-collapse3" class="collapse" role="tabpanel" data-parent="#faq-five">
                                <div class="panel-body">
                                    <p>We offer a 30-day return and exchange policy on all Beastline formal wear, provided the items are:</p>
                                    <p>- Unworn, unaltered, and in original condition</p>
                                    <p>- With original tags and packaging intact</p>
                                    <p>- Accompanied by the original invoice</p>
                                    <p>For hygiene reasons, certain items like innerwear and accessories are non-returnable unless defective.</p>
                                    <p>Exchanges are free of charge for size/fit issues. Return shipping is complimentary for defective items. Our customer service team ensures hassle-free returns within 7-10 business days.</p>
                                </div>
                            </div>
                        </div>
                        <!--// Panel-default -->

                        <!-- Panel-default -->
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h5 class="panel-title">
                                    <a class="collapsed" role="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse4" aria-expanded="false" aria-controls="faq-collapse4"> <span class="button-faq"></span>How should I care for my Beastline formal clothing?</a>
                                </h5>
                            </div>
                            <div id="faq-collapse4" class="collapse" role="tabpanel" data-parent="#faq-five">
                                <div class="panel-body">
                                    <p>To maintain the premium quality of your Beastline formal wear:</p>
                                    <p><strong>Shirts:</strong> Machine wash cold, gentle cycle. Use mild detergent. Tumble dry low or line dry. Iron on medium heat while slightly damp for best results.</p>
                                    <p><strong>Suits & Blazers:</strong> Dry clean only. Use wooden hangers to maintain shape. Steam gently to remove wrinkles between wears.</p>
                                    <p><strong>Trousers:</strong> Dry clean or machine wash according to fabric care label. Hang immediately after washing to prevent creasing.</p>
                                    <p>Detailed care instructions are provided with each garment. For stubborn stains, we recommend professional cleaning to preserve fabric integrity.</p>
                                </div>
                            </div>
                        </div>
                        <!--// Panel-default -->

                        <!-- Panel-default -->
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h5 class="panel-title">
                                    <a class="collapsed" role="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse5" aria-expanded="false" aria-controls="faq-collapse5"> <span class="button-faq"></span>Do you offer customization or tailoring services?</a>
                                </h5>
                            </div>
                            <div id="faq-collapse5" class="collapse" role="tabpanel" data-parent="#faq-five">
                                <div class="panel-body">
                                    <p>Yes, Beastline offers premium customization services for discerning customers:</p>
                                    <p><strong>Made-to-Measure:</strong> Complete customization of shirts, trousers, and suits with over 50 style options, fabric choices, and personal measurements.</p>
                                    <p><strong>Alterations:</strong> Minor adjustments to ready-to-wear items including hemming, taking in/out, and sleeve adjustments through our partner tailors.</p>
                                    <p><strong>Monogramming:</strong> Personalize your formal wear with discreet monogramming on cuffs, collars, or inner linings.</p>
                                    <p>Custom orders require 3-4 weeks for completion. Visit our Custom Studio section or contact our style consultants for personalized service.</p>
                                </div>
                            </div>
                        </div>
                        <!--// Panel-default -->

                        <!-- Panel-default -->
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h5 class="panel-title">
                                    <a class="collapsed" role="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse6" aria-expanded="false" aria-controls="faq-collapse6"> <span class="button-faq"></span>What shipping options do you offer?</a>
                                </h5>
                            </div>
                            <div id="faq-collapse6" class="collapse" role="tabpanel" data-parent="#faq-five">
                                <div class="panel-body">
                                    <p>Beastline provides multiple shipping options across India:</p>
                                    <p><strong>Standard Delivery:</strong> 5-7 business days - Free on orders above ₹1999</p>
                                    <p><strong>Express Delivery:</strong> 2-3 business days - ₹99 extra</p>
                                    <p><strong>Next-Day Delivery:</strong> Available in metro cities - ₹199 extra (order before 12 PM)</p>
                                    <p><strong>International Shipping:</strong> Available to select countries - Cost varies by destination</p>
                                    <p>All orders are processed within 24 hours. You'll receive tracking information via SMS and email once your order is shipped. For wedding or urgent requirements, contact our customer service for expedited processing.</p>
                                </div>
                            </div>
                        </div>
                        <!--// Panel-default -->
                    </div>

                </div>
                <div class="col-lg-6 col-md-12">
                    <!--testimonial area start-->
                    <div class="testimonial_container testimonial_about">
                        <div class="faq-client_title">
                            <h2>What Our Customers Says ?</h2>
                        </div>
                        <div class="testimonial_wrapper  testimonial_collumn1 owl-carousel">
                            <?php
                            $testimonial = testimonial();
                            foreach ($testimonial as $test) {
                            ?>
                                <div class="single_testimonial">
                                    <div class="testimonial_thumb">
                                        <img src="<?= $site ?>admin/uploads/testimonials/<?= $test['client_photo'] ?>" alt="">
                                    </div>
                                    <div class="testimonial_content">
                                        <p><?= $test['testimonial_text'] ?></p>
                                        <h3><a href="#"><?= $test['client_name'] ?></a></h3>
                                        <span>Customer</span>
                                    </div>
                                </div>
                            <?php } ?>

                        </div>
                    </div>
                    <!--testimonial area end-->
                </div>
            </div>
        </div>
    </div>
    <!--testimonial area end-->


    <?php include_once "includes/footer.php"; ?>
    <?php include_once "includes/footer-link.php"; ?>

</body>

</html>