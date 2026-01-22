<?php
include_once(__DIR__. "/../models/WebsiteSettings.php");
$setting = new Setting($conn);
?>
<!-- Footer Start -->
<footer class="footer_widgets" style="background-color: #000000; color: #ffffff;">
    <div class="container">
        <!-- Newsletter Section -->
        <div class="newsletter_section pt-5 pb-4">
            <div class="row">
                <div class="col-12">
                    <div class="newsletter_area text-center">
                        <div class="section_title mb-3">
                            <h2 style="color: #ffffff; font-weight: 600;">STAY IN THE LOOP</h2>
                        </div>
                        <div class="newsletter_desc mb-4">
                            <p style="color: #cccccc;">Sign up for our newsletter to receive updates on new arrivals, special offers and style inspiration.</p>
                        </div>
                        <div class="subscribe_form">
                            <form id="mc-form" class="mc-form footer-newsletter">
                                <input id="mc-email" type="email" autocomplete="off" placeholder="Your email address" required
                                    style="background-color: #333333; border: 1px solid #444444; color: #ffffff; padding: 12px 20px; border-radius: 4px; width: 400px; max-width: 100%;" />
                                <button id="mc-submit" type="submit"
                                    style="background-color: #ffffff; color: #000000; border: none; padding: 12px 30px; border-radius: 4px; font-weight: 600; margin-left: 10px; display: flex;
    align-items: center;
    justify-content: center;">
                                    SUBSCRIBE
                                </button>
                            </form>
                            <div class="mailchimp-alerts mt-3">
                                <div class="mailchimp-submitting"></div>
                                <div class="mailchimp-success"></div>
                                <div class="mailchimp-error"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Footer Links -->
        <div class="footer_middle py-5" style="border-top: 1px solid #333333; border-bottom: 1px solid #333333;">
            <div class="row">
                <!-- Shop Section -->
                <div class="col-lg-3 col-md-6 col-sm-6 mb-4 mb-md-0">
                    <div class="footer_widget">
                        <h3 class="footer_title mb-4" style="color: #ffffff; font-size: 16px; font-weight: 600; text-transform: uppercase;">SHOP</h3>
                        <ul class="footer_links list-unstyled">
                            <li class="mb-2"><a href="<?= $site ?>category/men" style="color: #cccccc; text-decoration: none; transition: color 0.3s;">Men's Clothing</a></li>
                            <li class="mb-2"><a href="<?= $site ?>category/women" style="color: #cccccc; text-decoration: none; transition: color 0.3s;">Women's Clothing</a></li>
                            <li class="mb-2"><a href="<?= $site ?>category/shoes" style="color: #cccccc; text-decoration: none; transition: color 0.3s;">Shoes</a></li>
                            <li class="mb-2"><a href="<?= $site ?>category/perfume" style="color: #cccccc; text-decoration: none; transition: color 0.3s;">Perfumes</a></li>
                            <li class="mb-2"><a href="<?= $site ?>category/new-arrivals" style="color: #cccccc; text-decoration: none; transition: color 0.3s;">New Arrivals</a></li>
                            <li class="mb-2"><a href="<?= $site ?>category/bestsellers" style="color: #cccccc; text-decoration: none; transition: color 0.3s;">Bestsellers</a></li>
                            <li class="mb-2"><a href="<?= $site ?>category/sale" style="color: #cccccc; text-decoration: none; transition: color 0.3s;">Sale</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="col-lg-3 col-md-6 col-sm-6 mb-4 mb-md-0">
                    <div class="footer_widget">
                        <h3 class="footer_title mb-4" style="color: #ffffff; font-size: 16px; font-weight: 600; text-transform: uppercase;">HELP</h3>
                        <ul class="footer_links list-unstyled">
                            <li class="mb-2"><a href="<?= $site ?>contact/" style="color: #cccccc; text-decoration: none; transition: color 0.3s;">Contact Us</a></li>
                            <li class="mb-2"><a href="<?= $site ?>policy/shipping-policy" style="color: #cccccc; text-decoration: none; transition: color 0.3s;">Shipping & Delivery</a></li>
                            <li class="mb-2"><a href="<?= $site ?>policy/return-refund-policy" style="color: #cccccc; text-decoration: none; transition: color 0.3s;">Returns & Exchanges</a></li>
                            <li class="mb-2"><a href="<?= $site ?>size-guide" style="color: #cccccc; text-decoration: none; transition: color 0.3s;">Size Guide</a></li>
                            <li class="mb-2"><a href="<?= $site ?>faq/" style="color: #cccccc; text-decoration: none; transition: color 0.3s;">FAQ</a></li>
                            <li class="mb-2"><a href="<?= $site ?>policy/privacy-policy" style="color: #cccccc; text-decoration: none; transition: color 0.3s;">Privacy Policy</a></li>
                        </ul>
                    </div>
                </div>

                <!-- About Beastline -->
                <div class="col-lg-3 col-md-6 col-sm-6 mb-4 mb-md-0">
                    <div class="footer_widget">
                        <h3 class="footer_title mb-4" style="color: #ffffff; font-size: 16px; font-weight: 600; text-transform: uppercase;">ABOUT BEASTLINE</h3>
                        <ul class="footer_links list-unstyled">
                            <li class="mb-2"><a href="<?= $site ?>about/" style="color: #cccccc; text-decoration: none; transition: color 0.3s;">About Us</a></li>
                            <li class="mb-2"><a href="<?= $site ?>blogs/" style="color: #cccccc; text-decoration: none; transition: color 0.3s;">Blogs</a></li>
                            <li class="mb-2"><a href="<?= $site ?>user-login/" style="color: #cccccc; text-decoration: none; transition: color 0.3s;">Login</a></li>
                            <li class="mb-2"><a href="<?= $site ?>register/" style="color: #cccccc; text-decoration: none; transition: color 0.3s;">Register</a></li>
                            <!-- <li class="mb-2"><a href="<?= $site ?>store-locator" style="color: #cccccc; text-decoration: none; transition: color 0.3s;">Store Locator</a></li> -->
                            <li class="mb-2"><a href="<?= $site ?>gift-cards" style="color: #cccccc; text-decoration: none; transition: color 0.3s;">Gift Cards</a></li>
                            <li class="mb-2"><a href="<?= $site ?>my-account/" style="color: #cccccc; text-decoration: none; transition: color 0.3s;">My Account</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Follow Us & App -->
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <div class="footer_widget">
                        <h3 class="footer_title mb-4" style="color: #ffffff; font-size: 16px; font-weight: 600; text-transform: uppercase;">FOLLOW US</h3>
                        <div class="footer_social mb-4">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <a href="<?= htmlspecialchars($setting->get('facebook_url')) ?>"
                                        style="color: #cccccc; text-decoration: none; display: flex; align-items: center; transition: color 0.3s; justify-content: center;">
                                        <i class="fa fa-facebook mr-2" aria-hidden="true" style="width: 20px;"></i>
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="<?= htmlspecialchars($setting->get('instagram_url')) ?>"
                                        style="color: #cccccc; text-decoration: none; display: flex; align-items: center; transition: color 0.3s; justify-content: center;">
                                        <i class="fa fa-instagram mr-2" aria-hidden="true" style="width: 20px;"></i>
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="<?= htmlspecialchars($setting->get('twitter_url')) ?>"
                                        style="color: #cccccc; text-decoration: none; display: flex; align-items: center; transition: color 0.3s; justify-content: center;">
                                        <i class="fa fa-twitter mr-2" aria-hidden="true" style="width: 20px;"></i>
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="<?= htmlspecialchars($setting->get('youtube_url')) ?>"
                                        style="color: #cccccc; text-decoration: none; display: flex; align-items: center; transition: color 0.3s; justify-content: center;">
                                        <i class="fa fa-youtube-play mr-2" aria-hidden="true" style="width: 20px;"></i>
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="<?= htmlspecialchars($setting->get('pinterest_url')) ?>"
                                        style="color: #cccccc; text-decoration: none; display: flex; align-items: center; transition: color 0.3s; justify-content: center;">
                                        <i class="fa fa-pinterest mr-2" aria-hidden="true" style="width: 20px;"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="app_download mt-4">
                            <h3 class="footer_title mb-3" style="color: #ffffff; font-size: 16px; font-weight: 600; text-transform: uppercase;">DOWNLOAD OUR APP</h3>
                            <div class="app_links d-flex flex-column">
                                <a href="#" class="app_store mb-2">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/9/91/Download_on_the_App_Store_RGB_blk.svg/1024px-Download_on_the_App_Store_RGB_blk.svg.png" alt="App Store" style="max-width: 120px;">
                                </a>
                                <a href="#" class="play_store">
                                    <img src="https://c.clc2l.com/t/g/o/google-playstore-Iauj7q.png" alt="Google Play" style="max-width: 50px;">
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer_bottom py-4">
            <div class="row align-items-center">
                <div class="col-lg-8 col-md-8 mb-3 mb-md-0">
                    <div class="footer_bottom_left d-flex flex-column flex-md-row align-items-center align-items-md-start">
                        <div class="footer_logo mb-3 mb-md-0 mr-md-3">
                            <a href="<?= $site ?>">
                                <img src="<?= $site ?>assets/img/logo/footer-logo.png" alt="Beastline" style="max-height: 60px;">
                            </a>
                        </div>
                        <div class="copyright_area">
                            <p style="color: #999999; font-size: 14px; margin: 0;">
                                <?= htmlspecialchars($setting->get('copyright_text')) ?> |
                                <a href="<?= $site ?>policy/terms-conditions" style="color: #cccccc; text-decoration: none; transition: color 0.3s;">Terms & Conditions</a> |
                                <a href="<?= $site ?>policy/privacy-policy" style="color: #cccccc; text-decoration: none; transition: color 0.3s;">Privacy Policy</a> |
                                <a href="<?= $site ?>policy/cookie-policy" style="color: #cccccc; text-decoration: none; transition: color 0.3s;">Cookies</a>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-4">
                    <div class="footer_bottom_right text-center text-md-right">
                        <div class="payment_methods mb-3">
                            <p style="color: #999999; font-size: 14px; margin-bottom: 5px;">We accept:</p>
                            <a href="#">
                                <img src="<?= $site ?>assets/img/icon/payment.png" alt="Payment Methods" style="max-width: 200px;">
                            </a>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
<!-- Footer End -->

<!-- Modal area start-->
<div class="modal fade" id="modal_box" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><i class="ion-android-close"></i></span>
            </button>
            <div class="modal_body">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-5 col-md-5 col-sm-12">
                            <div class="modal_tab">
                                <div class="tab-content product-details-large">
                                    <div class="tab-pane fade show active" id="tab1" role="tabpanel">
                                        <div class="modal_tab_img">
                                            <a href="#"><img src="<?= $site ?>assets/img/product/productbig1.jpg" alt=""></a>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="tab2" role="tabpanel">
                                        <div class="modal_tab_img">
                                            <a href="#"><img src="<?= $site ?>assets/img/product/productbig2.jpg" alt=""></a>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="tab3" role="tabpanel">
                                        <div class="modal_tab_img">
                                            <a href="#"><img src="<?= $site ?>assets/img/product/productbig3.jpg" alt=""></a>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="tab4" role="tabpanel">
                                        <div class="modal_tab_img">
                                            <a href="#"><img src="<?= $site ?>assets/img/product/productbig4.jpg" alt=""></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal_tab_button">
                                    <ul class="nav product_navactive owl-carousel" role="tablist">
                                        <li>
                                            <a class="nav-link active" data-bs-toggle="tab" href="#tab1" role="tab" aria-controls="tab1" aria-selected="false">
                                                <img src="<?= $site ?>assets/img/product/product3.jpg" alt="">
                                            </a>
                                        </li>
                                        <li>
                                            <a class="nav-link" data-bs-toggle="tab" href="#tab2" role="tab" aria-controls="tab2" aria-selected="false">
                                                <img src="<?= $site ?>assets/img/product/product8.jpg" alt="">
                                            </a>
                                        </li>
                                        <li>
                                            <a class="nav-link button_three" data-bs-toggle="tab" href="#tab3" role="tab" aria-controls="tab3" aria-selected="false">
                                                <img src="<?= $site ?>assets/img/product/product1.jpg" alt="">
                                            </a>
                                        </li>
                                        <li>
                                            <a class="nav-link" data-bs-toggle="tab" href="#tab4" role="tab" aria-controls="tab4" aria-selected="false">
                                                <img src="<?= $site ?>assets/img/product/product6.jpg" alt="">
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7 col-md-7 col-sm-12">
                            <div class="modal_right">
                                <div class="modal_title mb-10">
                                    <h2>Donec Ac Tempus</h2>
                                </div>
                                <div class="modal_price mb-10">
                                    <span class="new_price">$64.99</span>
                                    <span class="old_price">$78.99</span>
                                </div>
                                <div class="modal_description mb-15">
                                    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Mollitia iste laborum ad impedit pariatur esse optio tempora sint ullam autem deleniti nam in quos qui nemo ipsum numquam, reiciendis maiores quidem aperiam, rerum vel recusandae</p>
                                </div>
                                <div class="variants_selects">
                                    <div class="variants_size">
                                        <h2>size</h2>
                                        <select class="select_option">
                                            <option selected value="1">s</option>
                                            <option value="1">m</option>
                                            <option value="1">l</option>
                                            <option value="1">xl</option>
                                            <option value="1">xxl</option>
                                        </select>
                                    </div>
                                    <div class="variants_color">
                                        <h2>color</h2>
                                        <select class="select_option">
                                            <option selected value="1">purple</option>
                                            <option value="1">violet</option>
                                            <option value="1">black</option>
                                            <option value="1">pink</option>
                                            <option value="1">orange</option>
                                        </select>
                                    </div>
                                    <div class="modal_add_to_cart">
                                        <form action="#">
                                            <input min="1" max="100" step="2" value="1" type="number">
                                            <button type="submit">add to cart</button>
                                        </form>
                                    </div>
                                </div>
                                <div class="modal_social">
                                    <h2>Share this product</h2>
                                    <ul>
                                        <li class="facebook"><a href="#"><i class="fa fa-facebook"></i></a></li>
                                        <li class="twitter"><a href="#"><i class="fa fa-twitter"></i></a></li>
                                        <li class="pinterest"><a href="#"><i class="fa fa-pinterest"></i></a></li>
                                        <li class="google-plus"><a href="#"><i class="fa fa-google-plus"></i></a></li>
                                        <li class="linkedin"><a href="#"><i class="fa fa-linkedin"></i></a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal area end-->

<!-- Add these CSS styles to your style.css or in a style tag -->
<style>
    /* Footer Link Hover Effects */
    .footer_links a:hover,
    .footer_social a:hover,
    .copyright_area a:hover {
        color: #ffffff !important;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .footer_bottom_right {
            text-align: center !important;
            margin-top: 20px;
        }

        .footer_bottom_left {
            align-items: center !important;
            text-align: center;
        }

        .subscribe_form input {
            width: 100% !important;
            margin-bottom: 10px;
        }

        .subscribe_form button {
            width: 100% !important;
            margin-left: 0 !important;
        }
    }
</style>