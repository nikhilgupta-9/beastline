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
    <title>About | Beastline</title>
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
					<h1>About Beastline – Premium Winter Wear Brand</h1>
					
					<h3>Beastline delivers high-quality winter clothing and jackets designed for warmth, comfort, and modern style.</h3>
					
					<p>
						Beastline is a trusted winter wear brand dedicated to crafting premium jackets, hoodies, sweatshirts, and cold-weather essentials for men and women. Our designs are inspired by urban fashion and built to withstand harsh winters without compromising on style.
					</p>
                    <br>
					<p>
                        Every Beastline product is made using high-grade fabrics, advanced insulation, and durable stitching to ensure long-lasting comfort and protection. From lightweight winter jackets to heavy-duty outerwear, our collection is tailored for everyday wear, travel, and extreme cold conditions.
					</p>
                    
                    <br>
					<p>
						We believe winter fashion should be bold, functional, and affordable. That’s why Beastline focuses on perfect fits, modern silhouettes, and reliable warmth — making us the go-to destination for stylish winter clothing online.
					</p>
				</div>
            </div>  
            <div class="col-lg-6">
            	<div class="about_thumb">
					<img src="<?= $site ?>assets/img/about/a1.jpg" alt="Beastline winter jackets and winter clothing collection">
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
                            <img src="<?= $site ?>assets/img/about/About_icon2.png" alt="">
                        </div>
                        <div class="chose_content">
                            <h3>100% Money Back Guarantee</h3>
                            <p>Erat metus sodales eget dolor consectetuer, porta ut purus at et alias, nulla ornare velit amet</p>

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

    <!--services img area-->
    <div class="about_gallery_section"> 
        <div class="container">
           <div class="about_gallery_container">
                <div class="row">
                    <div class="col-lg-4 col-md-6">
                        <article class="single_gallery_section">
                            <figure>
                                <div class="gallery_thumb">
                                    <img src="<?= $site ?>assets/img/about/about2.jpg" alt="">
                                </div>
                                <figcaption class="about_gallery_content">
                                   <h3>What do we do?</h3>
                                    <p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto</p>
                                </figcaption>
                            </figure>
                        </article>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <article class="single_gallery_section">
                            <figure>
                                <div class="gallery_thumb">
                                    <img src="<?= $site ?>assets/img/about/about3.jpg" alt="">
                                </div>
                                <figcaption class="about_gallery_content">
                                   <h3>Our Mission</h3>
                                    <p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto</p>
                                </figcaption>
                            </figure>
                        </article>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <article class="single_gallery_section col__3">
                            <figure>
                                <div class="gallery_thumb">
                                    <img src="<?= $site ?>assets/img/about/about4.jpg" alt="">
                                </div>
                                <figcaption class="about_gallery_content">
                                   <h3>History Of Us</h3>
                                    <p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto</p>
                                </figcaption>
                            </figure>
                        </article>
                    </div>
                </div> 
            </div>
        </div>      
    </div>
    <!--services img end-->    

    <!--testimonial area start-->
    <div class="faq-client-say-area">
        <div class="container">   
            <div class="row">
                <div class="col-lg-6 col-md-12">
                    <div class="faq-client_title">
                        <h2>What can we do for you ?</h2>
                    </div>
                    <div class="faq-style-wrap" id="faq-five">
                        <!-- Panel-default -->
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h5 class="panel-title">
                                    <a id="octagon" class="collapsed" role="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse1" aria-expanded="true" aria-controls="faq-collapse1"> <span class="button-faq"></span>Fast Free Delivery</a>
                                </h5>
                            </div>
                            <div id="faq-collapse1" class="collapse show" aria-expanded="true" role="tabpanel" data-parent="#faq-five">
                                <div class="panel-body">
                                    <p>Nam liber tempor cum soluta nobis eleifend option.</p>
                                    <p>Congue nihil imperdiet doming id quod mazim placerat facer possim assum. Typi non habent claritatem insitam est usus legentis in iis qui facit eorum claritatem. Investigationes demonstraverunt lectores legere me.
                                    </p>
                                    <p> Claritas est etiam processus dynamicus, qui sequitur mutationem consuetudium lectorum.</p>
                                </div>
                            </div>
                        </div>
                        <!--// Panel-default -->

                        <!-- Panel-default -->
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h5 class="panel-title">
                                    <a class="collapsed" role="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse2" aria-expanded="false" aria-controls="faq-collapse2"> <span class="button-faq"></span>More Than 30 Years In The Business</a>
                                </h5>
                            </div>
                            <div id="faq-collapse2" class="collapse" aria-expanded="false" role="tabpanel" data-parent="#faq-five">
                                <div class="panel-body">
                                    <p>Nam liber tempor cum soluta nobis eleifend option.</p>
                                    <p>Congue nihil imperdiet doming id quod mazim placerat facer possim assum. Typi non habent claritatem insitam est usus legentis in iis qui facit eorum claritatem. Investigationes demonstraverunt lectores legere me.
                                    </p>
                                    <p> Claritas est etiam processus dynamicus, qui sequitur mutationem consuetudium lectorum.</p>
                                </div>
                            </div>
                        </div>
                        <!--// Panel-default -->

                        <!-- Panel-default -->
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h5 class="panel-title">
                                    <a class="collapsed" role="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse3" aria-expanded="false" aria-controls="faq-collapse3"> <span class="button-faq"></span>100% Organic Foods</a>
                                </h5>
                            </div>
                            <div id="faq-collapse3" class="collapse" role="tabpanel" data-parent="#faq-five">
                                <div class="panel-body">
                                    <p>Nam liber tempor cum soluta nobis eleifend option.</p>
                                    <p>Congue nihil imperdiet doming id quod mazim placerat facer possim assum. Typi non habent claritatem insitam est usus legentis in iis qui facit eorum claritatem. Investigationes demonstraverunt lectores legere me.
                                    </p>
                                    <p> Claritas est etiam processus dynamicus, qui sequitur mutationem consuetudium lectorum.</p>
                                </div>
                            </div>
                        </div>
                        <!--// Panel-default -->

                        <!-- Panel-default -->
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h5 class="panel-title">
                                    <a class="collapsed" role="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse4" aria-expanded="false" aria-controls="faq-collapse4"> <span class="button-faq"></span>Best Shopping Strategies</a>
                                </h5>
                            </div>
                            <div id="faq-collapse4" class="collapse" role="tabpanel" data-parent="#faq-five">
                                <div class="panel-body">
                                    <p>Nam liber tempor cum soluta nobis eleifend option.</p>
                                    <p>Congue nihil imperdiet doming id quod mazim placerat facer possim assum. Typi non habent claritatem insitam est usus legentis in iis qui facit eorum claritatem. Investigationes demonstraverunt lectores legere me.
                                    </p>
                                    <p> Claritas est etiam processus dynamicus, qui sequitur mutationem consuetudium lectorum.</p>
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
							<div class="single_testimonial">
								<div class="testimonial_thumb">
									<img src="<?= $site ?>assets/img/about/testimonial1.png" alt="">
								</div>
								<div class="testimonial_content">
								   <p>These guys have been absolutely outstanding. Perfect Themes and the best of all that you have many options to choose! Best Support team ever! Very fast responding! Thank you very much! I highly recommend this theme and these people!</p>
									<h3><a href="#">John Sullivan</a></h3>
									<span>Customer</span>
								</div>
							</div>
							<div class="single_testimonial">
								<div class="testimonial_thumb">
									<img src="<?= $site ?>assets/img/about/testimonial2.png" alt="">
								</div>
								<div class="testimonial_content">
								   <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Error in, mollitia nulla officiis excepturi repudiandae beatae optio, sequi maxime assumenda ipsum exercitationem nostrum ducimus facilis, nesciunt aliquam dicta totam.</p>
									<h3><a href="#">Jenifer Brown</a></h3>
									<span>Manager of AZ</span>
								</div>
							</div>
							<div class="single_testimonial">
								<div class="testimonial_thumb">
									<img src="<?= $site ?>assets/img/about/testimonial3.png" alt="">
								</div>
								<div class="testimonial_content">
								   <p>These guys have been absolutely outstanding. Perfect Themes and the best of all that you have many options to choose! Best Support team ever! Very fast responding! Thank you very much! I highly recommend this theme and these people!</p>
									<h3><a href="#">Kathy Young</a></h3>
									<span>CEO of SunPark</span>
								</div>
							</div>
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