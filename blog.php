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
    <title>Blog | Beastline</title>
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
                       <h3>blog</h3>
                        <ul>
                            <li><a href="<?= $site ?>">home</a></li>
                            <li>blog</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>         
    </div>
    <!--breadcrumbs area end-->
    
    <!--blog area start-->
    <div class="blog_page_section mb-80">
        <div class="container">
            <div class="row">
                <div class="col-lg-9 col-md-12">
                    <div class="blog_wrapper">
                        <div class="single_blog">
							<div class="blog_thumb">
								<a href="blog-details.html"><img src="assets/img/blog/blog1.jpg" alt=""></a>
							</div>
							<div class="blog_content">
								<div class="post_date">
									<span>Posted by: <a href="#">admin</a> / On : <a href="#">	July 06, 2021</a></span>
								</div>
							   <h4 class="post_title"><a href="blog-details.html"> 	Blog image post (sticky)</a></h4>
							   <p>Donec vitae hendrerit arcu, sit amet faucibus nisl. Cras pretium arcu ex. Aenean posuere libero eu augue condimentum rhoncus. Praesent ornare tortor ac ante egestas hendrerit. Aliquam et metus pharetra, bibendum massa nec, fermentum odio. </p>
							   <footer class="blog_footer">
									<a href="blog-details.html">+ Read More</a>
								</footer>
							</div>
                        </div>
                        <div class="single_blog">
							<div class="blog_thumb blog_thumb_active owl-carousel">
								<div class="single_blog_thumb">
									<a href="#"><img src="assets/img/blog/blog3.jpg" alt=""></a>
								</div>
								<div class="single_blog_thumb">
									<a href="#"><img src="assets/img/blog/blog4.jpg" alt=""></a>
								</div>
								<div class="single_blog_thumb">
									<a href="#"><img src="assets/img/blog/blog5.jpg" alt=""></a>
								</div>
								<div class="single_blog_thumb">
									<a href="#"><img src="assets/img/blog/blog6.jpg" alt=""></a>
								</div>
							</div>
							<div class="blog_content">
								<div class="post_date">
									<span>Posted by: <a href="#">admin</a> / On : <a href="#">	July 06, 2021</a></span>
								</div>
							   <h4 class="post_title"><a href="blog-details.html"> 	Post with Gallery</a></h4>
							   <p>Donec vitae hendrerit arcu, sit amet faucibus nisl. Cras pretium arcu ex. Aenean posuere libero eu augue condimentum rhoncus. Praesent ornare tortor ac ante egestas hendrerit. Aliquam et metus pharetra, bibendum massa nec, fermentum odio. </p>
							   <footer class="blog_footer">
									<a href="blog-details.html">+ Read More</a>
								</footer>
							</div>
                        </div>
                        <div class="single_blog">
							<div class="blog_thumb">
								<img src="assets/img/blog/blog4.jpg" alt="">
							</div>
							<div class="blog_content">
							    <div class="post_date">
									<span>Posted by: <a href="#">admin</a> / On : <a href="#">	July 06, 2021</a></span>
								</div>
							    <h4 class="post_title"><a href="blog-details.html"> 	Post with Audio</a></h4>
								<div class="blog_aduio_icone">
									<audio controls>
									  <source src="http://www.jplayer.org/audio/mp3/TSP-01-Cro_magnon_man.mp3?1" type="audio/mp3">
									</audio>
								</div>
								<p>Donec vitae hendrerit arcu, sit amet faucibus nisl. Cras pretium arcu ex. Aenean posuere libero eu augue condimentum rhoncus. Praesent ornare tortor ac ante egestas hendrerit. Aliquam et metus pharetra, bibendum massa nec, fermentum odio. </p>
							   <footer class="blog_footer">
									<a href="blog-details.html">+ Read More</a>
								</footer>
							</div>
                        </div>
                        <div class="single_blog">
							<div class="blog_thumb">
								<iframe src="https://www.youtube.com/embed/2Zt8va_6HRk"  allow="autoplay; encrypted-media" allowfullscreen></iframe>
							</div>
							<div class="blog_content">
							   <div class="post_date">
									<span>Posted by: <a href="#">admin</a> / On : <a href="#">	July 06, 2021</a></span>
								</div>
							   <h4 class="post_title"><a href="blog-details.html"> Post with Video</a></h4>
							   <p>Donec vitae hendrerit arcu, sit amet faucibus nisl. Cras pretium arcu ex. Aenean posuere libero eu augue condimentum rhoncus. Praesent ornare tortor ac ante egestas hendrerit. Aliquam et metus pharetra, bibendum massa nec, fermentum odio. </p>
							   <footer class="blog_footer">
									<a href="blog-details.html">+ Read More</a>
								</footer>
							</div>
                        </div>
                        <div class="single_blog">
							<div class="blog_thumb">
								<a href="blog-details.html"><img src="assets/img/blog/blog5.jpg" alt=""></a>
							</div>
							<div class="blog_content">
								<div class="post_date">
									<span>Posted by: <a href="#">admin</a> / On : <a href="#">	July 06, 2021</a></span>
								</div>
							   <h4 class="post_title"><a href="blog-details.html"> Maecenas ultricies</a></h4>
							   <p>Donec vitae hendrerit arcu, sit amet faucibus nisl. Cras pretium arcu ex. Aenean posuere libero eu augue condimentum rhoncus. Praesent ornare tortor ac ante egestas hendrerit. Aliquam et metus pharetra, bibendum massa nec, fermentum odio. </p>
							   <footer class="blog_footer">
									<a href="blog-details.html">+ Read More</a>
								</footer>
							</div>
                        </div>
                        <div class="single_blog">
							<div class="blog_thumb">
								<a href="blog-details.html"><img src="assets/img/blog/blog6.jpg" alt=""></a>
							</div>
							<div class="blog_content">
								<div class="post_date">
									<span>Posted by: <a href="#">admin</a> / On : <a href="#">	July 06, 2021</a></span>
								</div>
							   <h4 class="post_title"><a href="blog-details.html"> Praesent imperdiet</a></h4>
							   <p>Donec vitae hendrerit arcu, sit amet faucibus nisl. Cras pretium arcu ex. Aenean posuere libero eu augue condimentum rhoncus. Praesent ornare tortor ac ante egestas hendrerit. Aliquam et metus pharetra, bibendum massa nec, fermentum odio. </p>
							   <footer class="blog_footer">
									<a href="blog-details.html">+ Read More</a>
								</footer>
							</div>
                        </div>
                    </div>
                    <!--blog pagination area start-->
                    <div class="blog_pagination">
                        <div class="pagination">
                            <ul>
                                <li class="current">1</li>
                                <li><a href="#">2</a></li>
                                <li><a href="#">3</a></li>
                                <li class="next"><a href="#">next</a></li>
                                <li><a href="#">>></a></li>
                            </ul>
                        </div>
                    </div>
                    <!--blog pagination area end-->
                </div>  
                <div class="col-lg-3 col-md-12">
                    <div class="blog_sidebar_widget">
                        <div class="widget_list widget_search">
                            <div class="widget_title">
                                <h3>Search</h3>
                            </div>
                            <form action="#">
                                <input placeholder="Search..." type="text">
                                <button type="submit">search</button>
                            </form>
                        </div>
                        <div class="widget_list comments">
                           <div class="widget_title">
                                <h3>Recent Comments</h3>
                            </div>
                            <div class="post_wrapper">
                                <div class="post_thumb">
                                    <a href="blog-details.html"><img src="assets/img/blog/comment2.png.jpg" alt=""></a>
                                </div>
                                <div class="post_info">
                                    <span> <a href="#">demo</a> says:</span>
                                    <a href="blog-details.html">Quisque semper nunc</a>
                                </div>
                            </div>
                             <div class="post_wrapper">
                                <div class="post_thumb">
                                    <a href="blog-details.html"><img src="assets/img/blog/comment2.png.jpg" alt=""></a>
                                </div>
                                <div class="post_info">
                                    <span><a href="#">admin</a> says:</span>
                                    <a href="blog-details.html">Quisque orci porta...</a>
                                </div>
                            </div>
                            <div class="post_wrapper">
                                <div class="post_thumb">
                                    <a href="blog-details.html"><img src="assets/img/blog/comment2.png.jpg" alt=""></a>
                                </div>
                                <div class="post_info">
                                    <span><a href="#">demo</a> says:</span>
                                    <a href="blog-details.html">Quisque semper nunc</a>
                                </div>
                            </div>
                        </div>
                        <div class="widget_list widget_post">
                            <div class="widget_title">
                                <h3>Recent Posts</h3>
                            </div>
                            <div class="post_wrapper">
                                <div class="post_thumb">
                                    <a href="blog-details.html"><img src="assets/img/blog/blogs1.jpg" alt=""></a>
                                </div>
                                <div class="post_info">
                                    <h4><a href="blog-details.html">Blog image post</a></h4>
                                    <span>July 06, 2021 </span>
                                </div>
                            </div>
                             <div class="post_wrapper">
                                <div class="post_thumb">
                                    <a href="blog-details.html"><img src="assets/img/blog/blogs2.jpg" alt=""></a>
                                </div>
                                <div class="post_info">
                                    <h4><a href="blog-details.html">Post with Gallery</a></h4>
                                    <span>July 06, 2021 </span>
                                </div>
                            </div>
                             <div class="post_wrapper">
                                <div class="post_thumb">
                                    <a href="blog-details.html"><img src="assets/img/blog/blogs3.jpg" alt=""></a>
                                </div>
                                <div class="post_info">
                                    <h4><a href="blog-details.html">Post with Audio</a></h4>
                                    <span>July 06, 2021 </span>
                                </div>
                            </div>
                        </div>
                        <div class="widget_list widget_categories">
                            <div class="widget_title">
                                <h3>Categories</h3>
                            </div>
                            <ul>
                                <li><a href="#">Audio</a></li>
                                <li><a href="#">Company</a></li>
                                <li><a href="#">Gallery</a></li>
                                <li><a href="#">Image</a></li>
                            </ul>
                        </div>
                        <div class="widget_list widget_tag">
                            <div class="widget_title">
                                <h3>Tag products</h3>
                            </div>
                            <div class="tag_widget">
                                <ul>
                                    <li><a href="#">asian</a></li>
                                    <li><a href="#">brown</a></li>
                                    <li><a href="#">euro</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--blog area end-->
   
    <!--footer area start-->
    <?php include_once "includes/footer.php"; ?>
    <?php include_once "includes/footer-link.php"; ?>
</body>

</html>