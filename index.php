<?php
include_once(__DIR__ . '/util/bootstrap.php');
include_once(__DIR__ . "/config/connect.php");
include_once(__DIR__ . "/util/function.php");
include_once(__DIR__ . "/models/WebsiteSettings.php");

$setting = new Setting($conn);
$banners = get_banner();
?>
<!doctype html>
<html class="no-js" lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<title>Beastline | Home </title>
	<meta name="description" content="">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- Favicon -->
	<link rel="shortcut icon" type="image/x-icon" href="admin/<?php echo htmlspecialchars($setting->get('favicon')); ?>">

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
<style>
	/* Banner Video Base Fix */
	.banner_thumb {
		position: relative;
		overflow: hidden;
		/* border-radius: 12px; */
	}

	.banner_thumb video {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	/* Text Overlay */
	/* .banner_text1 {
		position: absolute;
		inset: 0;
		display: flex;
		align-items: center;
		justify-content: center;
		background: rgba(0, 0, 0, 0.35);
	} */

	.banner_text1_inner {
		text-align: center;
		color: #fff;
	}

	.banner_text1_inner h3 {
		font-size: 26px;
		font-weight: 700;
		line-height: 1.2;
	}

	.banner_text1_inner a {
		display: inline-block;
		margin-top: 10px;
		padding: 8px 18px;
		background: #000;
		color: #fff;
		text-transform: uppercase;
		font-size: 13px;
		letter-spacing: 1px;
		border-radius: 4px;
	}

	/* ===== MOBILE RESPONSIVE ===== */
	@media (max-width: 767px) {

		.banner_area {
			margin-bottom: 20px;
		}

		.single_banner {
			height: 360px;
		}

		.banner_thumb {
			height: 100%;
		}

		.banner_thumb video {
			height: 100%;
		}

		.banner_text1_inner h3 {
			font-size: 20px;
		}

		.banner_text1_inner a {
			font-size: 12px;
			padding: 7px 16px;
		}
	}

	/* ===== TABLET ===== */
	@media (max-width: 991px) {

		.single_banner {
			height: 360px;
		}

		.banner_text1_inner h3 {
			font-size: 22px;
		}
	}
</style>

<body>



	<!--header area start-->
	<?php include_once "includes/header.php" ?>

	<!--slider area start-->
	<section class="slider_section mb-100">
		<div class="slider_area owl-carousel">
			<?php
			foreach ($banners as $b) {
			?>
				<div class="single_slider d-flex align-items-center" data-bgimg="<?= $site ?>admin/<?= $b['banner_path'] ?>">
					<div class="container">
						<div class="row">
							<div class="col-12">
								<div class="slider_content">
									<h2>Get 30% Off &amp; Free Shipping </h2>
									<h1><?= $b['title'] ?></h1>
									<p>
										<?= $b['description'] ?>
									</p>
									<a href="<?= $site ?>shop.php">Shop Now +</a>
								</div>
							</div>
						</div>
					</div>
				</div>
			<?php
			}
			?>
			<div class="single_slider d-flex align-items-center" data-bgimg="assets/img/slider/s2.png">
				<div class="container">
					<div class="row">
						<div class="col-12">
							<div class="slider_content">
								<h2>Big sale up to 20% off </h2>
								<h1>london style </h1>
								<p>
									An exclusive selection of this season’s trends. <span>Exclusively online </span>
								</p>
								<a href="shop.html">Shop Now </a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
	<!--slider area end-->

	<!--banner area start-->
	<!-- <div class="banner_area mb-95">
        <div class="container">
            <div class="row no-gutters">
                <div class="col-lg-6 col-md-6">
                    <div class="single_banner">
                        <div class="banner_thumb">
                            <a href="shop.html"><img src="assets/img/bg/banner1.jpg" alt=""></a>
                            <div class="banner_text1">
                                <div class="banner_text1_inner">
                            		<h3>Men’s <br> Collections</h3>
                            		<a href="shop.html">shop now</a>
                            	</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6">
                    <div class="single_banner">
                        <div class="banner_thumb">
                            <a href="shop.html"><img src="assets/img/bg/banner2.jpg" alt=""></a>
                            <div class="banner_text1">
                                <div class="banner_text1_inner">
									<h3>Women’s <br> Collections</h3>
									<a href="shop.html">shop now</a>
                            	</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> -->

	<div class="banner_area mb-95">
		<div class="container">
			<div class="row no-gutters">

				<!-- Video Banner 1 -->
				<div class="col-lg-4 col-md-4 p-2">
					<div class="single_banner">
						<div class="banner_thumb">
							<a href="shop.html">
								<video autoplay muted loop playsinline class="w-100">
									<source src="assets/videos/shirt-v1.mp4" type="video/mp4">
								</video>
							</a>
							<div class="banner_text1">
								<div class="banner_text1_inner">
									<h3>Shirt <br> Collections</h3>
									<a href="<?= $site ?>shop/shirt/">shop now</a>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Video Banner 2 -->
				<div class="col-lg-4 col-md-4 p-2">
					<div class="single_banner">
						<div class="banner_thumb">
							<a href="shop.html">
								<video autoplay muted loop playsinline class="w-100">
									<source src="assets/videos/pant-v1.mp4" type="video/mp4">
								</video>
							</a>
							<div class="banner_text1">
								<div class="banner_text1_inner">
									<h3>Pant <br> Collections</h3>
									<a href="<?= $site ?>shop/pants/">shop now</a>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Video Banner 3 -->
				<div class="col-lg-4 col-md-4 p-2">
					<div class="single_banner">
						<div class="banner_thumb">
							<a href="shop.html">
								<video autoplay muted loop playsinline class="w-100">
									<source src="assets/videos/v5.mp4" type="video/mp4">
								</video>
							</a>
							<div class="banner_text1">
								<div class="banner_text1_inner">
									<h3>Shoes <br> Collection</h3>
									<a href="<?= $site ?>shop/shoes/">shop now</a>
								</div>
							</div>
						</div>
					</div>
				</div>

			</div>
		</div>
	</div>

	<!--banner area end-->

	<!--categories product area start-->
	<div class="categories_product_area   mb-92">
		<div class="container">
			<div class="row">
				<div class="col-12">
					<div class="section_title">
						<h2>Top Categories</h2>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="product_carousel product_column4 owl-carousel">
					<?php
					$category = get_category_home();
					foreach ($category as $cate) {
					?>
						<div class="col-lg-3">
							<article class="single_categories">
								<figure>
									<div class="categories_thumb">
										<a href="<?= $site ?>shop/<?= $cate['slug_url'] ?>">
											<img src="<?= $site ?>admin/uploads/category/<?= $cate['image'] ?>" alt="<?= $cate['categories'] ?>">
										</a>
									</div>
									<figcaption class="categories_content">
										<h4 class="product_name"><a href="<?= $site ?>shop/<?= $cate['slug_url'] ?>"><?= $cate['categories'] ?></a></h4>
										<div class="product_collection">
											<p>13 Products</p>
											<a href="<?= $site ?>shop/<?= $cate['slug_url'] ?>">+ Shop Collection</a>
										</div>
									</figcaption>
								</figure>
							</article>
						</div>
					<?php
					}
					?>
					<div class="col-lg-3">
						<article class="single_categories">
							<figure>
								<div class="categories_thumb">
									<a href="product-details.html"><img src="assets/img/s-product/category2.jpg" alt=""></a>
								</div>
								<figcaption class="categories_content">
									<h4 class="product_name"><a href="shop.html">Blazer</a></h4>
									<div class="product_collection">
										<p>13 Products</p>
										<a href="shop.html">+ Shop Collection</a>
									</div>
								</figcaption>
							</figure>
						</article>
					</div>

				</div>
			</div>
		</div>
	</div>
	<!--categories product area end-->

	<!--testimonial area start-->
	<div class="testimonial_area mb-95">
		<div class="container">
			<div class="row">
				<div class="col-12">
					<div class="section_title">
						<h2>Testimonials</h2>
					</div>
				</div>
			</div>
			<div class="testimonial_container">
				<div class="row">
					<div class="col-12">
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
				</div>
			</div>
		</div>
	</div>
	<!--testimonial area end-->

	<!--product area start-->
	<div class="product_area  mb-95">
		<div class="container">
			<div class="row">
				<div class="col-12">
					<div class="section_title product_shop_title">
						<h2>Featured products </h2>
					</div>
					<div class="product_shop_collection">
						<a href="shop.html">Shop all collection</a>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="product_carousel product_column5 owl-carousel">
					<?php
					$fetaured_products = get_featured_product();

					// If no featured products, create placeholder array
					if (empty($fetaured_products)) {
						$fetaured_products = [
							[
								'pro_id' => 1,
								'pro_name' => 'Eodem modo vels is mattis antes facilisis',
								'pro_img' => 'product3.jpg',
								'mrp' => 86.00,
								'selling_price' => 82.00,
								'is_on_sale' => true
							],
							[
								'pro_id' => 2,
								'pro_name' => 'Epicuri per lobortis eleifend eget laoreet',
								'pro_img' => 'product5.jpg',
								'mrp' => 82.00,
								'selling_price' => 77.00,
								'is_on_sale' => true
							],
							[
								'pro_id' => 3,
								'pro_name' => 'Fusce ultricies dolor vitae tristique suscipit',
								'pro_img' => 'product7.jpg',
								'mrp' => 90.00,
								'selling_price' => 88.00,
								'is_on_sale' => true
							],
							[
								'pro_id' => 4,
								'pro_name' => 'Product Name 4',
								'pro_img' => 'product2.jpg',
								'mrp' => 84.00,
								'selling_price' => 79.00,
								'is_on_sale' => true
							]
						];
					}

					foreach ($fetaured_products as $index => $f_p):
						// Determine secondary image
						$secondary_img_num = ($index % 4) + 2; // This will cycle through 2, 3, 4, 5, etc.

						// Calculate discount if available
						$show_sale = isset($f_p['mrp']) && isset($f_p['selling_price']) &&
							$f_p['mrp'] > $f_p['selling_price'];

						// Format prices
						$old_price = isset($f_p['mrp']) ? '$' . number_format($f_p['mrp'], 2) : '$0.00';
						$current_price = isset($f_p['selling_price']) ? '$' . number_format($f_p['selling_price'], 2) : '$0.00';

						// Product link
						$product_link = isset($f_p['pro_id']) ? "product-details.php?id={$f_p['pro_id']}" : "product-details.html";

						// Primary image path
						if (isset($f_p['pro_img']) && strpos($f_p['pro_img'], 'assets/') === false) {
							$primary_img = $site . 'admin/assets/img/uploads/' . $f_p['pro_img'];
						} else {
							$primary_img = isset($f_p['pro_img']) ? $f_p['pro_img'] : "assets/img/product/product" . (($index * 2) + 1) . ".jpg";
						}
					?>

						<!-- Each carousel item should be a single product -->
						<article class="single_product">
							<figure>
								<div class="product_thumb">
									<a class="primary_img" href="<?= $product_link ?>">
										<img src="<?= $primary_img ?>" alt="<?= htmlspecialchars($f_p['pro_name']) ?>">
									</a>
									<a class="secondary_img" href="<?= $product_link ?>">
										<img src="assets/img/product/product<?= $secondary_img_num ?>.jpg" alt="<?= htmlspecialchars($f_p['pro_name']) ?>">
									</a>

									<?php if ($show_sale): ?>
										<div class="label_product">
											<span class="label_sale">Sale</span>
											<?php
											$discount = round((($f_p['mrp'] - $f_p['selling_price']) / $f_p['mrp']) * 100);
											if ($discount > 0): ?>
												<span class="label_discount">-<?= $discount ?>%</span>
											<?php endif; ?>
										</div>
									<?php endif; ?>

									<div class="action_links">
										<ul>
											<li class="quick_button">
												<a href="#" data-bs-toggle="modal"
													data-bs-target="#modal_box_<?= $f_p['pro_id'] ?? $index ?>"
													title="quick view">
													<span class="pe-7s-search"></span>
												</a>
											</li>
											<li class="wishlist">
												<a href="wishlist.php?add_to_wishlist=<?= $f_p['pro_id'] ?? $index ?>"
													title="Add to Wishlist">
													<span class="pe-7s-like"></span>
												</a>
											</li>
											<li class="compare">
												<a href="compare.php?add_to_compare=<?= $f_p['pro_id'] ?? $index ?>"
													title="Add to Compare">
													<span class="pe-7s-edit"></span>
												</a>
											</li>
										</ul>
									</div>
								</div>
								<figcaption class="product_content">
									<div class="product_content_inner">
										<h4 class="product_name">
											<a href="<?= $product_link ?>">
												<?= htmlspecialchars($f_p['pro_name']) ?>
											</a>
										</h4>
										<div class="price_box">
											<?php if ($show_sale): ?>
												<span class="old_price"><?= $old_price ?></span>
											<?php endif; ?>
											<span class="current_price"><?= $current_price ?></span>
										</div>
									</div>
									<div class="add_to_cart">
										<a href="cart.php?add_to_cart=<?= $f_p['pro_id'] ?? $index ?>">Add to cart</a>
									</div>
								</figcaption>
							</figure>
						</article>

					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>
	<!--product area end-->

	<!--banner area start-->
	<div class="banner_area">
		<div class="container-fluid p-0">
			<div class="row no-gutters">
				<div class="col-lg-6 col-md-6">
					<div class="single_banner">
						<div class="banner_thumb">
							<a href="shop.html"><img src="assets/img/bg/banner3.jpg" alt=""></a>
							<div class="banner_text2">
								<h3>S/S-20 <br> Collections</h3>
								<a href="shop.html">shop now</a>
							</div>
						</div>
					</div>
				</div>
				<div class="col-lg-6 col-md-6">
					<div class="single_banner">
						<div class="banner_thumb">
							<a href="shop.html"><img src="assets/img/bg/banner4.jpg" alt=""></a>
							<div class="banner_text2">
								<h3>A/W-20 <br> Collections</h3>
								<a href="shop.html">shop now</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!--banner area end-->

	<!--discount banner area start-->
	<div class="discount_banner_area mb-95">
		<div class="container-fluid p-0">
			<div class="banner_thumb">
				<a href="shop.html"><img src="assets/img/bg/banner5.jpg" alt=""></a>
				<div class="banner_text3">
					<h3>Minimalist Spring Collection</h3>
					<h2>up TO 40% off</h2>
					<p>An exclusive selection of this season’s trends. <span>Exclusively online!</span></p>
					<a href="shop.html">shop now</a>
				</div>
			</div>
		</div>
	</div>
	<!--discount banner area end-->

	<!--product area start-->
	<div class="product_area  mb-95">
		<div class="container">
			<div class="row">
				<div class="col-12">
					<div class="product_header">
						<div class="section_title">
							<h2>Our Categories</h2>
						</div>
						<div class="product_tab_btn">
							<ul class="nav" role="tablist">
								<li>
									<a class="active" data-bs-toggle="tab" href="#tennis" role="tab" aria-controls="tennis" aria-selected="true">
										+ Tennis
									</a>
								</li>
								<li>
									<a data-bs-toggle="tab" href="#fitness" role="tab" aria-controls="fitness" aria-selected="false">
										+ Fitness
									</a>
								</li>
								<li>
									<a data-bs-toggle="tab" href="#football" role="tab" aria-controls="football" aria-selected="false">
										+ Football
									</a>
								</li>
							</ul>
						</div>
					</div>
				</div>
			</div>
			<div class="product_container">
				<div class="tab-content">
					<div class="tab-pane fade show active" id="tennis" role="tabpanel">
						<div class="row">
							<div class="product_carousel product_column5 owl-carousel">
								<div class="col-lg-3">
									<article class="single_product">
										<figure>
											<div class="product_thumb">
												<a class="primary_img" href="product-details.html"><img src="assets/img/product/product3.jpg" alt=""></a>
												<a class="secondary_img" href="product-details.html"><img src="assets/img/product/product4.jpg" alt=""></a>
												<div class="action_links">
													<ul>
														<li class="quick_button"><a href="#" data-bs-toggle="modal" data-bs-target="#modal_box" title="quick view"> <span class="pe-7s-search"></span></a></li>
														<li class="wishlist"><a href="wishlist.html" title="Add to Wishlist"><span class="pe-7s-like"></span></a></li>
														<li class="compare"><a href="#" title="Add to Compare"><span class="pe-7s-edit"></span></a></li>
													</ul>
												</div>
											</div>
											<figcaption class="product_content">
												<div class="product_content_inner">
													<h4 class="product_name"><a href="product-details.html">Eodem modo vel are mattis ante facilisis</a></h4>
													<div class="price_box">
														<span class="old_price">$86.00</span>
														<span class="current_price">$82.00</span>
													</div>
												</div>
												<div class="add_to_cart">
													<a href="cart.html">Add to cart</a>
												</div>
											</figcaption>
										</figure>
									</article>
								</div>
								<div class="col-lg-3">
									<article class="single_product">
										<figure>
											<div class="product_thumb">
												<a class="primary_img" href="product-details.html"><img src="assets/img/product/product5.jpg" alt=""></a>
												<a class="secondary_img" href="product-details.html"><img src="assets/img/product/product6.jpg" alt=""></a>
												<div class="label_product">
													<span class="label_sale">Sale</span>
												</div>
												<div class="action_links">
													<ul>
														<li class="quick_button"><a href="#" data-bs-toggle="modal" data-bs-target="#modal_box" title="quick view"> <span class="pe-7s-search"></span></a></li>
														<li class="wishlist"><a href="wishlist.html" title="Add to Wishlist"><span class="pe-7s-like"></span></a></li>
														<li class="compare"><a href="#" title="Add to Compare"><span class="pe-7s-edit"></span></a></li>
													</ul>
												</div>
											</div>
											<figcaption class="product_content">
												<div class="product_content_inner">
													<h4 class="product_name"><a href="product-details.html">Epicuri per lobortis eleifend eget laoreet</a></h4>
													<div class="price_box">
														<span class="old_price">$82.00</span>
														<span class="current_price">$77.00</span>
													</div>
												</div>
												<div class="add_to_cart">
													<a href="cart.html">Add to cart</a>
												</div>
											</figcaption>
										</figure>
									</article>
								</div>
								<div class="col-lg-3">
									<article class="single_product">
										<figure>
											<div class="product_thumb">
												<a class="primary_img" href="product-details.html"><img src="assets/img/product/product9.jpg" alt=""></a>
												<a class="secondary_img" href="product-details.html"><img src="assets/img/product/product10.jpg" alt=""></a>
												<div class="label_product">
													<span class="label_sale">Sale</span>
												</div>
												<div class="action_links">
													<ul>
														<li class="quick_button"><a href="#" data-bs-toggle="modal" data-bs-target="#modal_box" title="quick view"> <span class="pe-7s-search"></span></a></li>
														<li class="wishlist"><a href="wishlist.html" title="Add to Wishlist"><span class="pe-7s-like"></span></a></li>
														<li class="compare"><a href="#" title="Add to Compare"><span class="pe-7s-edit"></span></a></li>
													</ul>
												</div>
											</div>
											<figcaption class="product_content">
												<div class="product_content_inner">
													<h4 class="product_name"><a href="product-details.html">Kaoreet lobortis sagittis laoreet metus feugiat</a></h4>
													<div class="price_box">
														<span class="old_price">$94.00</span>
														<span class="current_price">$92.00</span>
													</div>
												</div>
												<div class="add_to_cart">
													<a href="cart.html">Add to cart</a>
												</div>
											</figcaption>
										</figure>
									</article>
								</div>
								<div class="col-lg-3">
									<article class="single_product">
										<figure>
											<div class="product_thumb">
												<a class="primary_img" href="product-details.html"><img src="assets/img/product/product13.jpg" alt=""></a>
												<a class="secondary_img" href="product-details.html"><img src="assets/img/product/product14.jpg" alt=""></a>
												<div class="action_links">
													<ul>
														<li class="quick_button"><a href="#" data-bs-toggle="modal" data-bs-target="#modal_box" title="quick view"> <span class="pe-7s-search"></span></a></li>
														<li class="wishlist"><a href="wishlist.html" title="Add to Wishlist"><span class="pe-7s-like"></span></a></li>
														<li class="compare"><a href="#" title="Add to Compare"><span class="pe-7s-edit"></span></a></li>
													</ul>
												</div>
											</div>
											<figcaption class="product_content">
												<div class="product_content_inner">
													<h4 class="product_name"><a href="product-details.html">Ornare sed consequat nisl eget mi porttitor</a></h4>
													<div class="price_box">
														<span class="old_price">$76.00</span>
														<span class="current_price">$73.00</span>
													</div>
												</div>
												<div class="add_to_cart">
													<a href="cart.html">Add to cart</a>
												</div>
											</figcaption>
										</figure>
									</article>
								</div>
								<div class="col-lg-3">
									<article class="single_product">
										<figure>
											<div class="product_thumb">
												<a class="primary_img" href="product-details.html"><img src="assets/img/product/product4.jpg" alt=""></a>
												<a class="secondary_img" href="product-details.html"><img src="assets/img/product/product3.jpg" alt=""></a>
												<div class="label_product">
													<span class="label_sale">Sale</span>
												</div>
												<div class="action_links">
													<ul>
														<li class="quick_button"><a href="#" data-bs-toggle="modal" data-bs-target="#modal_box" title="quick view"> <span class="pe-7s-search"></span></a></li>
														<li class="wishlist"><a href="wishlist.html" title="Add to Wishlist"><span class="pe-7s-like"></span></a></li>
														<li class="compare"><a href="#" title="Add to Compare"><span class="pe-7s-edit"></span></a></li>
													</ul>
												</div>
											</div>
											<figcaption class="product_content">
												<div class="product_content_inner">
													<h4 class="product_name"><a href="product-details.html">Donec eu libero ac dapibus urna placerat</a></h4>
													<div class="price_box">
														<span class="old_price">$87.00</span>
														<span class="current_price">$78.00</span>
													</div>
												</div>
												<div class="add_to_cart">
													<a href="cart.html">Add to cart</a>
												</div>
											</figcaption>
										</figure>
									</article>
								</div>
								<div class="col-lg-3">
									<article class="single_product">
										<figure>
											<div class="product_thumb">
												<a class="primary_img" href="product-details.html"><img src="assets/img/product/product8.jpg" alt=""></a>
												<a class="secondary_img" href="product-details.html"><img src="assets/img/product/product7.jpg" alt=""></a>
												<div class="action_links">
													<ul>
														<li class="quick_button"><a href="#" data-bs-toggle="modal" data-bs-target="#modal_box" title="quick view"> <span class="pe-7s-search"></span></a></li>
														<li class="wishlist"><a href="wishlist.html" title="Add to Wishlist"><span class="pe-7s-like"></span></a></li>
														<li class="compare"><a href="#" title="Add to Compare"><span class="pe-7s-edit"></span></a></li>
													</ul>
												</div>
											</div>
											<figcaption class="product_content">
												<div class="product_content_inner">
													<h4 class="product_name"><a href="product-details.html">Eodem modo vel mattis ante facilisis</a></h4>
													<div class="price_box">
														<span class="old_price">$86.00</span>
														<span class="current_price">$82.00</span>
													</div>
												</div>
												<div class="add_to_cart">
													<a href="cart.html">Add to cart</a>
												</div>
											</figcaption>
										</figure>
									</article>
								</div>
							</div>
						</div>
					</div>
					<div class="tab-pane fade" id="fitness" role="tabpanel">
						<div class="row">
							<div class="product_carousel product_column5 owl-carousel">
								<div class="col-lg-3">
									<article class="single_product">
										<figure>
											<div class="product_thumb">
												<a class="primary_img" href="product-details.html"><img src="assets/img/product/product4.jpg" alt=""></a>
												<a class="secondary_img" href="product-details.html"><img src="assets/img/product/product3.jpg" alt=""></a>
												<div class="label_product">
													<span class="label_sale">Sale</span>
												</div>
												<div class="action_links">
													<ul>
														<li class="quick_button"><a href="#" data-bs-toggle="modal" data-bs-target="#modal_box" title="quick view"> <span class="pe-7s-search"></span></a></li>
														<li class="wishlist"><a href="wishlist.html" title="Add to Wishlist"><span class="pe-7s-like"></span></a></li>
														<li class="compare"><a href="#" title="Add to Compare"><span class="pe-7s-edit"></span></a></li>
													</ul>
												</div>
											</div>
											<figcaption class="product_content">
												<div class="product_content_inner">
													<h4 class="product_name"><a href="product-details.html">Donec eu libero ac dapibus urna placerat</a></h4>
													<div class="price_box">
														<span class="old_price">$87.00</span>
														<span class="current_price">$78.00</span>
													</div>
												</div>
												<div class="add_to_cart">
													<a href="cart.html">Add to cart</a>
												</div>
											</figcaption>
										</figure>
									</article>
								</div>
								<div class="col-lg-3">
									<article class="single_product">
										<figure>
											<div class="product_thumb">
												<a class="primary_img" href="product-details.html"><img src="assets/img/product/product8.jpg" alt=""></a>
												<a class="secondary_img" href="product-details.html"><img src="assets/img/product/product7.jpg" alt=""></a>
												<div class="action_links">
													<ul>
														<li class="quick_button"><a href="#" data-bs-toggle="modal" data-bs-target="#modal_box" title="quick view"> <span class="pe-7s-search"></span></a></li>
														<li class="wishlist"><a href="wishlist.html" title="Add to Wishlist"><span class="pe-7s-like"></span></a></li>
														<li class="compare"><a href="#" title="Add to Compare"><span class="pe-7s-edit"></span></a></li>
													</ul>
												</div>
											</div>
											<figcaption class="product_content">
												<div class="product_content_inner">
													<h4 class="product_name"><a href="product-details.html">Eodem modo vel mattis ante facilisis</a></h4>
													<div class="price_box">
														<span class="old_price">$86.00</span>
														<span class="current_price">$82.00</span>
													</div>
												</div>
												<div class="add_to_cart">
													<a href="cart.html">Add to cart</a>
												</div>
											</figcaption>
										</figure>
									</article>
								</div>
								<div class="col-lg-3">
									<article class="single_product">
										<figure>
											<div class="product_thumb">
												<a class="primary_img" href="product-details.html"><img src="assets/img/product/product3.jpg" alt=""></a>
												<a class="secondary_img" href="product-details.html"><img src="assets/img/product/product4.jpg" alt=""></a>
												<div class="action_links">
													<ul>
														<li class="quick_button"><a href="#" data-bs-toggle="modal" data-bs-target="#modal_box" title="quick view"> <span class="pe-7s-search"></span></a></li>
														<li class="wishlist"><a href="wishlist.html" title="Add to Wishlist"><span class="pe-7s-like"></span></a></li>
														<li class="compare"><a href="#" title="Add to Compare"><span class="pe-7s-edit"></span></a></li>
													</ul>
												</div>
											</div>
											<figcaption class="product_content">
												<div class="product_content_inner">
													<h4 class="product_name"><a href="product-details.html">Eodem modo vel mattis ante facilisis</a></h4>
													<div class="price_box">
														<span class="old_price">$86.00</span>
														<span class="current_price">$82.00</span>
													</div>
												</div>
												<div class="add_to_cart">
													<a href="cart.html">Add to cart</a>
												</div>
											</figcaption>
										</figure>
									</article>
								</div>
								<div class="col-lg-3">
									<article class="single_product">
										<figure>
											<div class="product_thumb">
												<a class="primary_img" href="product-details.html"><img src="assets/img/product/product5.jpg" alt=""></a>
												<a class="secondary_img" href="product-details.html"><img src="assets/img/product/product6.jpg" alt=""></a>
												<div class="label_product">
													<span class="label_sale">Sale</span>
												</div>
												<div class="action_links">
													<ul>
														<li class="quick_button"><a href="#" data-bs-toggle="modal" data-bs-target="#modal_box" title="quick view"> <span class="pe-7s-search"></span></a></li>
														<li class="wishlist"><a href="wishlist.html" title="Add to Wishlist"><span class="pe-7s-like"></span></a></li>
														<li class="compare"><a href="#" title="Add to Compare"><span class="pe-7s-edit"></span></a></li>
													</ul>
												</div>
											</div>
											<figcaption class="product_content">
												<div class="product_content_inner">
													<h4 class="product_name"><a href="product-details.html">Epicuri per lobortis eleifend eget laoreet</a></h4>
													<div class="price_box">
														<span class="old_price">$82.00</span>
														<span class="current_price">$77.00</span>
													</div>
												</div>
												<div class="add_to_cart">
													<a href="cart.html">Add to cart</a>
												</div>
											</figcaption>
										</figure>
									</article>
								</div>
								<div class="col-lg-3">
									<article class="single_product">
										<figure>
											<div class="product_thumb">
												<a class="primary_img" href="product-details.html"><img src="assets/img/product/product9.jpg" alt=""></a>
												<a class="secondary_img" href="product-details.html"><img src="assets/img/product/product10.jpg" alt=""></a>
												<div class="label_product">
													<span class="label_sale">Sale</span>
												</div>
												<div class="action_links">
													<ul>
														<li class="quick_button"><a href="#" data-bs-toggle="modal" data-bs-target="#modal_box" title="quick view"> <span class="pe-7s-search"></span></a></li>
														<li class="wishlist"><a href="wishlist.html" title="Add to Wishlist"><span class="pe-7s-like"></span></a></li>
														<li class="compare"><a href="#" title="Add to Compare"><span class="pe-7s-edit"></span></a></li>
													</ul>
												</div>
											</div>
											<figcaption class="product_content">
												<div class="product_content_inner">
													<h4 class="product_name"><a href="product-details.html">Kaoreet lobortis sagittis laoreet metus feugiat</a></h4>
													<div class="price_box">
														<span class="old_price">$94.00</span>
														<span class="current_price">$92.00</span>
													</div>
												</div>
												<div class="add_to_cart">
													<a href="cart.html">Add to cart</a>
												</div>
											</figcaption>
										</figure>
									</article>
								</div>
								<div class="col-lg-3">
									<article class="single_product">
										<figure>
											<div class="product_thumb">
												<a class="primary_img" href="product-details.html"><img src="assets/img/product/product13.jpg" alt=""></a>
												<a class="secondary_img" href="product-details.html"><img src="assets/img/product/product14.jpg" alt=""></a>
												<div class="action_links">
													<ul>
														<li class="quick_button"><a href="#" data-bs-toggle="modal" data-bs-target="#modal_box" title="quick view"> <span class="pe-7s-search"></span></a></li>
														<li class="wishlist"><a href="wishlist.html" title="Add to Wishlist"><span class="pe-7s-like"></span></a></li>
														<li class="compare"><a href="#" title="Add to Compare"><span class="pe-7s-edit"></span></a></li>
													</ul>
												</div>
											</div>
											<figcaption class="product_content">
												<div class="product_content_inner">
													<h4 class="product_name"><a href="product-details.html">Ornare sed consequat nisl eget mi porttitor</a></h4>
													<div class="price_box">
														<span class="old_price">$76.00</span>
														<span class="current_price">$73.00</span>
													</div>
												</div>
												<div class="add_to_cart">
													<a href="cart.html">Add to cart</a>
												</div>
											</figcaption>
										</figure>
									</article>
								</div>

							</div>
						</div>
					</div>
					<div class="tab-pane fade" id="football" role="tabpanel">
						<div class="row">
							<div class="product_carousel product_column5 owl-carousel">
								<div class="col-lg-3">
									<article class="single_product">
										<figure>
											<div class="product_thumb">
												<a class="primary_img" href="product-details.html"><img src="assets/img/product/product9.jpg" alt=""></a>
												<a class="secondary_img" href="product-details.html"><img src="assets/img/product/product10.jpg" alt=""></a>
												<div class="label_product">
													<span class="label_sale">Sale</span>
												</div>
												<div class="action_links">
													<ul>
														<li class="quick_button"><a href="#" data-bs-toggle="modal" data-bs-target="#modal_box" title="quick view"> <span class="pe-7s-search"></span></a></li>
														<li class="wishlist"><a href="wishlist.html" title="Add to Wishlist"><span class="pe-7s-like"></span></a></li>
														<li class="compare"><a href="#" title="Add to Compare"><span class="pe-7s-edit"></span></a></li>
													</ul>
												</div>
											</div>
											<figcaption class="product_content">
												<div class="product_content_inner">
													<h4 class="product_name"><a href="product-details.html">Kaoreet lobortis sagittis laoreet metus feugiat</a></h4>
													<div class="price_box">
														<span class="old_price">$94.00</span>
														<span class="current_price">$92.00</span>
													</div>
												</div>
												<div class="add_to_cart">
													<a href="cart.html">Add to cart</a>
												</div>
											</figcaption>
										</figure>
									</article>
								</div>
								<div class="col-lg-3">
									<article class="single_product">
										<figure>
											<div class="product_thumb">
												<a class="primary_img" href="product-details.html"><img src="assets/img/product/product13.jpg" alt=""></a>
												<a class="secondary_img" href="product-details.html"><img src="assets/img/product/product14.jpg" alt=""></a>
												<div class="action_links">
													<ul>
														<li class="quick_button"><a href="#" data-bs-toggle="modal" data-bs-target="#modal_box" title="quick view"> <span class="pe-7s-search"></span></a></li>
														<li class="wishlist"><a href="wishlist.html" title="Add to Wishlist"><span class="pe-7s-like"></span></a></li>
														<li class="compare"><a href="#" title="Add to Compare"><span class="pe-7s-edit"></span></a></li>
													</ul>
												</div>
											</div>
											<figcaption class="product_content">
												<div class="product_content_inner">
													<h4 class="product_name"><a href="product-details.html">Ornare sed consequat nisl eget mi porttitor</a></h4>
													<div class="price_box">
														<span class="old_price">$76.00</span>
														<span class="current_price">$73.00</span>
													</div>
												</div>
												<div class="add_to_cart">
													<a href="cart.html">Add to cart</a>
												</div>
											</figcaption>
										</figure>
									</article>
								</div>
								<div class="col-lg-3">
									<article class="single_product">
										<figure>
											<div class="product_thumb">
												<a class="primary_img" href="product-details.html"><img src="assets/img/product/product3.jpg" alt=""></a>
												<a class="secondary_img" href="product-details.html"><img src="assets/img/product/product4.jpg" alt=""></a>
												<div class="action_links">
													<ul>
														<li class="quick_button"><a href="#" data-bs-toggle="modal" data-bs-target="#modal_box" title="quick view"> <span class="pe-7s-search"></span></a></li>
														<li class="wishlist"><a href="wishlist.html" title="Add to Wishlist"><span class="pe-7s-like"></span></a></li>
														<li class="compare"><a href="#" title="Add to Compare"><span class="pe-7s-edit"></span></a></li>
													</ul>
												</div>
											</div>
											<figcaption class="product_content">
												<div class="product_content_inner">
													<h4 class="product_name"><a href="product-details.html">Eodem modo vel mattis ante facilisis</a></h4>
													<div class="price_box">
														<span class="old_price">$86.00</span>
														<span class="current_price">$82.00</span>
													</div>
												</div>
												<div class="add_to_cart">
													<a href="cart.html">Add to cart</a>
												</div>
											</figcaption>
										</figure>
									</article>
								</div>
								<div class="col-lg-3">
									<article class="single_product">
										<figure>
											<div class="product_thumb">
												<a class="primary_img" href="product-details.html"><img src="assets/img/product/product5.jpg" alt=""></a>
												<a class="secondary_img" href="product-details.html"><img src="assets/img/product/product6.jpg" alt=""></a>
												<div class="label_product">
													<span class="label_sale">Sale</span>
												</div>
												<div class="action_links">
													<ul>
														<li class="quick_button"><a href="#" data-bs-toggle="modal" data-bs-target="#modal_box" title="quick view"> <span class="pe-7s-search"></span></a></li>
														<li class="wishlist"><a href="wishlist.html" title="Add to Wishlist"><span class="pe-7s-like"></span></a></li>
														<li class="compare"><a href="#" title="Add to Compare"><span class="pe-7s-edit"></span></a></li>
													</ul>
												</div>
											</div>
											<figcaption class="product_content">
												<div class="product_content_inner">
													<h4 class="product_name"><a href="product-details.html">Epicuri per lobortis eleifend eget laoreet</a></h4>
													<div class="price_box">
														<span class="old_price">$82.00</span>
														<span class="current_price">$77.00</span>
													</div>
												</div>
												<div class="add_to_cart">
													<a href="cart.html">Add to cart</a>
												</div>
											</figcaption>
										</figure>
									</article>
								</div>

								<div class="col-lg-3">
									<article class="single_product">
										<figure>
											<div class="product_thumb">
												<a class="primary_img" href="product-details.html"><img src="assets/img/product/product4.jpg" alt=""></a>
												<a class="secondary_img" href="product-details.html"><img src="assets/img/product/product3.jpg" alt=""></a>
												<div class="label_product">
													<span class="label_sale">Sale</span>
												</div>
												<div class="action_links">
													<ul>
														<li class="quick_button"><a href="#" data-bs-toggle="modal" data-bs-target="#modal_box" title="quick view"> <span class="pe-7s-search"></span></a></li>
														<li class="wishlist"><a href="wishlist.html" title="Add to Wishlist"><span class="pe-7s-like"></span></a></li>
														<li class="compare"><a href="#" title="Add to Compare"><span class="pe-7s-edit"></span></a></li>
													</ul>
												</div>
											</div>
											<figcaption class="product_content">
												<div class="product_content_inner">
													<h4 class="product_name"><a href="product-details.html">Donec eu libero ac dapibus urna placerat</a></h4>
													<div class="price_box">
														<span class="old_price">$87.00</span>
														<span class="current_price">$78.00</span>
													</div>
												</div>
												<div class="add_to_cart">
													<a href="cart.html">Add to cart</a>
												</div>
											</figcaption>
										</figure>
									</article>
								</div>
								<div class="col-lg-3">
									<article class="single_product">
										<figure>
											<div class="product_thumb">
												<a class="primary_img" href="product-details.html"><img src="assets/img/product/product8.jpg" alt=""></a>
												<a class="secondary_img" href="product-details.html"><img src="assets/img/product/product7.jpg" alt=""></a>
												<div class="action_links">
													<ul>
														<li class="quick_button"><a href="#" data-bs-toggle="modal" data-bs-target="#modal_box" title="quick view"> <span class="pe-7s-search"></span></a></li>
														<li class="wishlist"><a href="wishlist.html" title="Add to Wishlist"><span class="pe-7s-like"></span></a></li>
														<li class="compare"><a href="#" title="Add to Compare"><span class="pe-7s-edit"></span></a></li>
													</ul>
												</div>
											</div>
											<figcaption class="product_content">
												<div class="product_content_inner">
													<h4 class="product_name"><a href="product-details.html">Eodem modo vel mattis ante facilisis</a></h4>
													<div class="price_box">
														<span class="old_price">$86.00</span>
														<span class="current_price">$82.00</span>
													</div>
												</div>
												<div class="add_to_cart">
													<a href="cart.html">Add to cart</a>
												</div>
											</figcaption>
										</figure>
									</article>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

		</div>
	</div>
	<!--product area end-->

	<!--shipping area start-->
	<div class="shipping_area">
		<div class="container">
			<div class="shipping_container">
				<div class="row">
					<div class="col-lg-3 col-md-6 col-sm-6">
						<div class="single_shipping">
							<div class="shipping_icone">
								<img src="assets/img/about/shipping1.png" alt="">
							</div>
							<div class="shipping_content">
								<h3>Free Delivery</h3>
								<p>Free shipping on all order</p>
							</div>
						</div>
					</div>
					<div class="col-lg-3 col-md-6 col-sm-6">
						<div class="single_shipping">
							<div class="shipping_icone">
								<img src="assets/img/about/shipping2.png" alt="">
							</div>
							<div class="shipping_content">
								<h3>Online Support 24/7</h3>
								<p>Support online 24 hours a day</p>
							</div>
						</div>
					</div>
					<div class="col-lg-3 col-md-6 col-sm-6">
						<div class="single_shipping">
							<div class="shipping_icone">
								<img src="assets/img/about/shipping3.png" alt="">
							</div>
							<div class="shipping_content">
								<h3>Money Return</h3>
								<p>Back guarantee under 7 days</p>
							</div>
						</div>
					</div>
					<div class="col-lg-3 col-md-6 col-sm-6">
						<div class="single_shipping">
							<div class="shipping_icone">
								<img src="assets/img/about/shipping4.png" alt="">
							</div>
							<div class="shipping_content">
								<h3>Member Discount</h3>
								<p>Onevery order over $120.00</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!--shipping area end-->

	<!--brand area start-->
	<?php
	$our_brand = get_best_brand();

	// Only show brand section if there are brands
	if (!empty($our_brand)):
	?>
		<div class="brand_area">
			<div class="container">
				<div class="row">
					<div class="col-12">
						<div class="brand_container owl-carousel">
							<?php foreach ($our_brand as $brand):
								// Make sure logo path exists
								$logo_path = !empty($brand['logo_path']) ? $site . 'admin/' . $brand['logo_path'] : $site . 'assets/img/brand/default-brand.png';
							?>
								<div class="single_brand">
									<a href="<?= $brand['brand_url'] ?? '#' ?>">
										<img src="<?= $logo_path ?>" alt="<?= htmlspecialchars($brand['brand_name'] ?? 'Brand') ?>">
									</a>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>
	<!--brand area end-->

	<?php include_once "includes/footer.php"; ?>

	<?php include_once "includes/footer-link.php"; ?>


</body>

</html>