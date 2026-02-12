<?php
include_once "config/connect.php";
include_once "util/function.php";

// Get size information if needed
// $size_info = get_size_info(); // You can create this function
?>
<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Size Guide | Beastline - Fashion & Lifestyle</title>
    <meta name="description" content="Find your perfect fit with Beastline's comprehensive size guide for shirts, pants, shoes, and perfumes.">
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
    
    <!-- Custom Size Guide CSS -->
    <style>
        .size-tabs {
            margin-bottom: 30px;
        }
        .size-tabs .nav-tabs {
            border-bottom: 2px solid #f1f1f1;
        }
        .size-tabs .nav-tabs .nav-link {
            border: none;
            border-bottom: 2px solid transparent;
            color: #666;
            font-weight: 600;
            padding: 12px 25px;
            margin-right: 5px;
            transition: all 0.3s ease;
        }
        .size-tabs .nav-tabs .nav-link.active {
            color: #c7a17a;
            border-bottom: 2px solid #c7a17a;
            background: transparent;
        }
        .size-tabs .nav-tabs .nav-link:hover {
            color: #c7a17a;
        }
        .size-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .size-table th {
            background: #f9f9f9;
            padding: 15px;
            text-align: center;
            font-weight: 600;
            border: 1px solid #eee;
        }
        .size-table td {
            padding: 12px 15px;
            border: 1px solid #eee;
            text-align: center;
        }
        .size-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .size-table tr:hover {
            background: #f5f5f5;
        }
        .recommended-size {
            background: #fff8e1 !important;
        }
        .size-guide-image {
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .measurement-guide {
            background: #f9f9f9;
            padding: 25px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .measurement-guide h4 {
            color: #c7a17a;
            margin-bottom: 15px;
        }
        .size-tip-box {
            background: #fff;
            border-left: 4px solid #c7a17a;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 0 5px 5px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .size-icon {
            font-size: 24px;
            color: #c7a17a;
            margin-bottom: 15px;
        }
        .size-section {
            margin-bottom: 50px;
        }
        .fit-guide-image {
            width: 100%;
            height: auto;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .size-category-title {
            position: relative;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .size-category-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 2px;
            background: #c7a17a;
        }
        .size-conversion-table {
            overflow-x: auto;
        }
        @media (max-width: 768px) {
            .size-tabs .nav-tabs .nav-link {
                padding: 10px 15px;
                font-size: 14px;
            }
            .size-table {
                font-size: 14px;
            }
            .size-table th,
            .size-table td {
                padding: 10px 8px;
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
                        <h3>Size Guide</h3>
                        <ul>
                            <li><a href="<?= $site ?>">home</a></li>
                            <li>size guide</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--breadcrumbs area end-->

    <!--size guide area start-->
    <div class="size_guide_area">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="size-guide-intro mb-50">
                        <h2 class="text-center mb-3">Find Your Perfect Fit</h2>
                        <p class="text-center">At Beastline, we understand that the perfect fit makes all the difference. Use our comprehensive size guide to find your ideal size for shirts, pants, shoes, and perfumes.</p>
                    </div>
                    
                    <!-- Size Tabs -->
                    <div class="size-tabs">
                        <ul class="nav nav-tabs justify-content-center" id="sizeTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="shirts-tab" data-bs-toggle="tab" data-bs-target="#shirts" type="button" role="tab" aria-controls="shirts" aria-selected="true">
                                    <i class="fa fa-tshirt me-2"></i> Shirts
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="pants-tab" data-bs-toggle="tab" data-bs-target="#pants" type="button" role="tab" aria-controls="pants" aria-selected="false">
                                    <i class="fa fa-user me-2"></i> Pants
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="shoes-tab" data-bs-toggle="tab" data-bs-target="#shoes" type="button" role="tab" aria-controls="shoes" aria-selected="false">
                                    <i class="fa fa-shoe-prints me-2"></i> Shoes
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="perfume-tab" data-bs-toggle="tab" data-bs-target="#perfume" type="button" role="tab" aria-controls="perfume" aria-selected="false">
                                    <i class="fa fa-wine-bottle me-2"></i> Perfumes
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content pt-4" id="sizeTabContent">
                            <!-- Shirts Size Guide -->
                            <div class="tab-pane fade show active" id="shirts" role="tabpanel" aria-labelledby="shirts-tab">
                                <div class="size-section">
                                    <h3 class="size-category-title">Shirt Size Guide</h3>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="measurement-guide">
                                                <h4><i class="fa fa-ruler me-2"></i> How to Measure</h4>
                                                <ol>
                                                    <li><strong>Chest:</strong> Measure around the fullest part of your chest</li>
                                                    <li><strong>Waist:</strong> Measure around your natural waistline</li>
                                                    <li><strong>Shoulder:</strong> Measure from shoulder seam to shoulder seam</li>
                                                    <li><strong>Sleeve:</strong> Measure from center back to wrist bone</li>
                                                </ol>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <img src="<?= $site ?>assets/img/size/shirt-measurement.jpg" alt="Shirt Measurement Guide" class="fit-guide-image">
                                        </div>
                                    </div>
                                    
                                    <div class="size-conversion-table">
                                        <table class="size-table">
                                            <thead>
                                                <tr>
                                                    <th>Size</th>
                                                    <th>Chest (inches)</th>
                                                    <th>Chest (cm)</th>
                                                    <th>Waist (inches)</th>
                                                    <th>Waist (cm)</th>
                                                    <th>Shoulder (inches)</th>
                                                    <th>Sleeve (inches)</th>
                                                    <th>Recommended Height</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>S</td>
                                                    <td>36-38</td>
                                                    <td>91-96</td>
                                                    <td>30-32</td>
                                                    <td>76-81</td>
                                                    <td>16.5</td>
                                                    <td>32-33</td>
                                                    <td>5'5" - 5'8"</td>
                                                </tr>
                                                <tr class="recommended-size">
                                                    <td>M</td>
                                                    <td>38-40</td>
                                                    <td>96-101</td>
                                                    <td>32-34</td>
                                                    <td>81-86</td>
                                                    <td>17</td>
                                                    <td>33-34</td>
                                                    <td>5'8" - 5'11"</td>
                                                </tr>
                                                <tr>
                                                    <td>L</td>
                                                    <td>40-42</td>
                                                    <td>101-106</td>
                                                    <td>34-36</td>
                                                    <td>86-91</td>
                                                    <td>17.5</td>
                                                    <td>34-35</td>
                                                    <td>5'11" - 6'2"</td>
                                                </tr>
                                                <tr>
                                                    <td>XL</td>
                                                    <td>42-44</td>
                                                    <td>106-111</td>
                                                    <td>36-38</td>
                                                    <td>91-96</td>
                                                    <td>18</td>
                                                    <td>35-36</td>
                                                    <td>6'2" - 6'4"</td>
                                                </tr>
                                                <tr>
                                                    <td>XXL</td>
                                                    <td>44-46</td>
                                                    <td>111-116</td>
                                                    <td>38-40</td>
                                                    <td>96-101</td>
                                                    <td>18.5</td>
                                                    <td>36-37</td>
                                                    <td>6'4" - 6'6"</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="size-tip-box">
                                        <div class="size-icon">
                                            <i class="fa fa-lightbulb"></i>
                                        </div>
                                        <h5>Pro Tip for Shirts</h5>
                                        <p>If you're between sizes, we recommend going with the larger size for a more comfortable fit. All Beastline shirts are designed with premium fabrics that offer slight stretch for optimal comfort.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pants Size Guide -->
                            <div class="tab-pane fade" id="pants" role="tabpanel" aria-labelledby="pants-tab">
                                <div class="size-section">
                                    <h3 class="size-category-title">Pant Size Guide</h3>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="measurement-guide">
                                                <h4><i class="fa fa-ruler-combined me-2"></i> How to Measure</h4>
                                                <ol>
                                                    <li><strong>Waist:</strong> Measure around your natural waistline</li>
                                                    <li><strong>Hip:</strong> Measure around the fullest part of your hips</li>
                                                    <li><strong>Inseam:</strong> Measure from crotch to ankle bone</li>
                                                    <li><strong>Outseam:</strong> Measure from waist to ankle bone</li>
                                                </ol>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <img src="<?= $site ?>assets/img/size/pant-measurement.jpg" alt="Pant Measurement Guide" class="fit-guide-image">
                                        </div>
                                    </div>
                                    
                                    <div class="size-conversion-table">
                                        <table class="size-table">
                                            <thead>
                                                <tr>
                                                    <th>Size</th>
                                                    <th>Waist (inches)</th>
                                                    <th>Waist (cm)</th>
                                                    <th>Hip (inches)</th>
                                                    <th>Hip (cm)</th>
                                                    <th>Inseam (inches)</th>
                                                    <th>Fit Type</th>
                                                    <th>EU Size</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>28</td>
                                                    <td>28-29</td>
                                                    <td>71-73</td>
                                                    <td>36-37</td>
                                                    <td>91-94</td>
                                                    <td>30-32</td>
                                                    <td>Slim</td>
                                                    <td>44</td>
                                                </tr>
                                                <tr>
                                                    <td>30</td>
                                                    <td>30-31</td>
                                                    <td>76-78</td>
                                                    <td>38-39</td>
                                                    <td>96-99</td>
                                                    <td>30-32</td>
                                                    <td>Slim</td>
                                                    <td>46</td>
                                                </tr>
                                                <tr class="recommended-size">
                                                    <td>32</td>
                                                    <td>32-33</td>
                                                    <td>81-83</td>
                                                    <td>40-41</td>
                                                    <td>101-104</td>
                                                    <td>32-34</td>
                                                    <td>Regular</td>
                                                    <td>48</td>
                                                </tr>
                                                <tr>
                                                    <td>34</td>
                                                    <td>34-35</td>
                                                    <td>86-88</td>
                                                    <td>42-43</td>
                                                    <td>106-109</td>
                                                    <td>32-34</td>
                                                    <td>Regular</td>
                                                    <td>50</td>
                                                </tr>
                                                <tr>
                                                    <td>36</td>
                                                    <td>36-37</td>
                                                    <td>91-94</td>
                                                    <td>44-45</td>
                                                    <td>111-114</td>
                                                    <td>34-36</td>
                                                    <td>Relaxed</td>
                                                    <td>52</td>
                                                </tr>
                                                <tr>
                                                    <td>38</td>
                                                    <td>38-40</td>
                                                    <td>96-101</td>
                                                    <td>46-47</td>
                                                    <td>116-119</td>
                                                    <td>34-36</td>
                                                    <td>Relaxed</td>
                                                    <td>54</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="size-tip-box">
                                        <div class="size-icon">
                                            <i class="fa fa-lightbulb"></i>
                                        </div>
                                        <h5>Pro Tip for Pants</h5>
                                        <p>Consider the fit type: Slim fit pants have a narrower cut through the thigh and leg, while regular fit offers more room. All Beastline pants feature premium stretch fabric for maximum comfort.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Shoes Size Guide -->
                            <div class="tab-pane fade" id="shoes" role="tabpanel" aria-labelledby="shoes-tab">
                                <div class="size-section">
                                    <h3 class="size-category-title">Shoe Size Guide</h3>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="measurement-guide">
                                                <h4><i class="fa fa-ruler-vertical me-2"></i> How to Measure</h4>
                                                <ol>
                                                    <li>Place your foot on a piece of paper</li>
                                                    <li>Mark the longest point of your foot (heel to toe)</li>
                                                    <li>Measure the distance in centimeters</li>
                                                    <li>Refer to the chart below for your size</li>
                                                </ol>
                                                <p class="mt-3"><strong>Note:</strong> Measure both feet and use the larger measurement.</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <img src="<?= $site ?>assets/img/size/shoe-measurement.jpg" alt="Shoe Measurement Guide" class="fit-guide-image">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h5 class="mb-3">Men's Shoe Size Conversion</h5>
                                            <table class="size-table">
                                                <thead>
                                                    <tr>
                                                        <th>US Size</th>
                                                        <th>UK Size</th>
                                                        <th>EU Size</th>
                                                        <th>Foot Length (cm)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr><td>7</td><td>6</td><td>40</td><td>25.4</td></tr>
                                                    <tr><td>7.5</td><td>6.5</td><td>40.5</td><td>25.8</td></tr>
                                                    <tr><td>8</td><td>7</td><td>41</td><td>26.2</td></tr>
                                                    <tr class="recommended-size"><td>8.5</td><td>7.5</td><td>42</td><td>26.7</td></tr>
                                                    <tr><td>9</td><td>8</td><td>42.5</td><td>27.1</td></tr>
                                                    <tr><td>9.5</td><td>8.5</td><td>43</td><td>27.5</td></tr>
                                                    <tr><td>10</td><td>9</td><td>44</td><td>27.9</td></tr>
                                                    <tr><td>10.5</td><td>9.5</td><td>44.5</td><td>28.3</td></tr>
                                                    <tr><td>11</td><td>10</td><td>45</td><td>28.8</td></tr>
                                                    <tr><td>12</td><td>11</td><td>46</td><td>29.4</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="size-tip-box">
                                                <div class="size-icon">
                                                    <i class="fa fa-shoe-prints"></i>
                                                </div>
                                                <h5>Shoe Fitting Tips</h5>
                                                <ul>
                                                    <li>Measure your feet at the end of the day when they're largest</li>
                                                    <li>Wear the socks you plan to wear with the shoes</li>
                                                    <li>Leave about a thumb's width of space at the toe</li>
                                                    <li>Consider width: Beastline offers Regular (D) and Wide (E) options</li>
                                                </ul>
                                            </div>
                                            
                                            <div class="size-tip-box mt-3">
                                                <div class="size-icon">
                                                    <i class="fa fa-info-circle"></i>
                                                </div>
                                                <h5>Returns & Exchanges</h5>
                                                <p>If the fit isn't perfect, we offer hassle-free returns and exchanges within 30 days. Make sure shoes are unworn and in original packaging.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Perfume Size Guide -->
                            <div class="tab-pane fade" id="perfume" role="tabpanel" aria-labelledby="perfume-tab">
                                <div class="size-section">
                                    <h3 class="size-category-title">Perfume Size Guide</h3>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="measurement-guide">
                                                <h4><i class="fa fa-wine-bottle me-2"></i> Understanding Fragrance Sizes</h4>
                                                <p>Perfumes come in various sizes to suit different needs and preferences. Here's what each size typically offers:</p>
                                                <ul>
                                                    <li><strong>Sample/Travel Size (5-15ml):</strong> Perfect for trying new scents</li>
                                                    <li><strong>Standard Size (30-50ml):</strong> Ideal for regular use</li>
                                                    <li><strong>Large Size (75-100ml):</strong> Best value for favorite fragrances</li>
                                                    <li><strong>Premium Size (150-200ml):</strong> Ultimate luxury experience</li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <img src="<?= $site ?>assets/img/size/perfume-sizes.jpg" alt="Perfume Sizes Guide" class="fit-guide-image">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h5 class="mb-3">Perfume Concentration Guide</h5>
                                            <table class="size-table">
                                                <thead>
                                                    <tr>
                                                        <th>Type</th>
                                                        <th>Concentration</th>
                                                        <th>Longevity</th>
                                                        <th>Best For</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Eau de Cologne</td>
                                                        <td>2-5%</td>
                                                        <td>2-3 hours</td>
                                                        <td>Daytime, Summer</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Eau de Toilette</td>
                                                        <td>5-15%</td>
                                                        <td>3-4 hours</td>
                                                        <td>Daily Wear</td>
                                                    </tr>
                                                    <tr class="recommended-size">
                                                        <td>Eau de Parfum</td>
                                                        <td>15-20%</td>
                                                        <td>4-6 hours</td>
                                                        <td>Evening, Special Occasions</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Parfum/Extrait</td>
                                                        <td>20-40%</td>
                                                        <td>6-8+ hours</td>
                                                        <td>Signature Scents</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <h5 class="mb-3">Beastline Fragrance Collections</h5>
                                            <div class="size-tip-box">
                                                <div class="size-icon">
                                                    <i class="fa fa-spray-can"></i>
                                                </div>
                                                <h5>Application Tips</h5>
                                                <ul>
                                                    <li><strong>Pulse Points:</strong> Apply to wrists, neck, and behind ears</li>
                                                    <li><strong>Distance:</strong> Hold bottle 6-8 inches from skin</li>
                                                    <li><strong>Quantity:</strong> 2-3 sprays typically sufficient</li>
                                                    <li><strong>Storage:</strong> Keep away from heat and sunlight</li>
                                                </ul>
                                            </div>
                                            
                                            <div class="size-tip-box mt-3">
                                                <div class="size-icon">
                                                    <i class="fa fa-calendar-check"></i>
                                                </div>
                                                <h5>Shelf Life & Storage</h5>
                                                <p>Properly stored perfumes can last 3-5 years. Keep bottles tightly closed and stored in cool, dark places to preserve fragrance integrity.</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-4">
                                        <div class="col-12">
                                            <div class="alert alert-info">
                                                <h6><i class="fa fa-exclamation-circle me-2"></i> Important Note</h6>
                                                <p class="mb-0">All Beastline fragrances are available in multiple sizes. We recommend starting with smaller sizes to test longevity and scent development on your skin before committing to larger bottles.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- General Size Tips -->
                    <div class="general-size-tips my-5">
                        <h3 class="size-category-title">General Size Tips</h3>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="size-tip-box text-center h-100">
                                    <div class="size-icon">
                                        <i class="fa fa-ruler"></i>
                                    </div>
                                    <h5>Measure Accurately</h5>
                                    <p>Always measure yourself before ordering. Use a soft measuring tape and follow our measurement guides carefully.</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="size-tip-box text-center h-100">
                                    <div class="size-icon">
                                        <i class="fa fa-exchange-alt"></i>
                                    </div>
                                    <h5>Easy Returns</h5>
                                    <p>Not satisfied with the fit? We offer 30-day returns and exchanges on all unworn items in original condition.</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="size-tip-box text-center h-100">
                                    <div class="size-icon">
                                        <i class="fa fa-headset"></i>
                                    </div>
                                    <h5>Need Help?</h5>
                                    <p>Contact our style experts for personalized size recommendations. We're here to help you find your perfect fit.</p>
                                    <a href="<?= $site ?>contact/" class="btn btn-outline-primary mt-2">Contact Us</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--size guide area end-->

    <!--footer area start-->
    <?php include_once "includes/footer.php"; ?>
    <?php include_once "includes/footer-link.php"; ?>

    <!-- Bootstrap JS for Tabs -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Size Guide Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Highlight current tab
            const currentTab = localStorage.getItem('currentSizeTab');
            if (currentTab) {
                const tab = document.querySelector(`[data-bs-target="${currentTab}"]`);
                if (tab) {
                    const tabInstance = new bootstrap.Tab(tab);
                    tabInstance.show();
                }
            }
            
            // Save current tab on change
            document.querySelectorAll('#sizeTab button').forEach(tab => {
                tab.addEventListener('shown.bs.tab', function(event) {
                    localStorage.setItem('currentSizeTab', event.target.getAttribute('data-bs-target'));
                });
            });
            
            // Print size guide functionality
            const printButton = document.getElementById('printSizeGuide');
            if (printButton) {
                printButton.addEventListener('click', function() {
                    window.print();
                });
            }
            
            // Size calculator (if needed in future)
            function calculateSize() {
                // Add size calculation logic here if needed
            }
        });
    </script>

</body>

</html>