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
    <title>FAQ | Beastline</title>
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
                        <ul>
                            <li><a href="<?= $site ?>">home</a></li>
                            <li>Frequently Questions</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>         
    </div>
    <!--breadcrumbs area end-->
    
    <!--faq area start-->
    <div class="faq_content_area">
        <div class="container">   
            <div class="row">
                <div class="col-12">
                    <div class="faq_content_wrapper">
                        <h4>Below are frequently asked questions, you may find the answer for yourself</h4>
                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec id erat sagittis, faucibus metus malesuada, eleifend turpis. Mauris semper augue id nisl aliquet, a porta lectus mattis. Nulla at tortor augue. In eget enim diam. Donec gravida tortor sem, ac fermentum nibh rutrum sit amet. Nulla convallis mauris vitae congue consequat. Donec interdum nunc purus, vitae vulputate arcu fringilla quis. Vivamus iaculis euismod dui.</p>

                    </div>
                </div>
            </div> 
        </div>    
    </div>
     <!--Accordion area-->
    <div class="accordion_area">
        <div class="container">
            <div class="row">
            <div class="col-12"> 
                <div id="accordion" class="card__accordion">
                  <div class="card card_dipult">
                    <div class="card-header card_accor" id="headingOne">
                        <button class="btn btn-link" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                          Mauris congue euismod purus at semper. Morbi et vulputate massa?

                          <i class="fa fa-plus"></i>
                          <i class="fa fa-minus"></i>

                        </button>

                    </div>

                    <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
                      <div class="card-body">
                           <p>Donec mattis finibus elit ut tristique. Nullam tempus nunc eget arcu vulputate, eu porttitor tellus commodo. Aliquam erat volutpat. Aliquam consectetur lorem eu viverra lobortis. Morbi gravida, nisi id fringilla ultricies, elit lorem eleifend lorem</p>
                      </div>
                    </div>
                  </div>
                  <div class="card  card_dipult">
                    <div class="card-header card_accor" id="headingTwo">
                        <button class="btn btn-link collapsed" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                          Donec mattis finibus elit ut tristique?
                           <i class="fa fa-plus"></i>
                           <i class="fa fa-minus"></i>

                        </button>
                    </div>
                    <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordion">
                      <div class="card-body">
                        <p>Donec mattis finibus elit ut tristique. Nullam tempus nunc eget arcu vulputate, eu porttitor tellus commodo. Aliquam erat volutpat. Aliquam consectetur lorem eu viverra lobortis. Morbi gravida, nisi id fringilla ultricies, elit lorem eleifend lorem</p>
                      </div>
                    </div>
                  </div>
                  <div class="card  card_dipult">
                    <div class="card-header card_accor" id="headingThree">
                        <button class="btn btn-link collapsed" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                         Aenean elit orci, efficitur quis nisl at, accumsan?
                           <i class="fa fa-plus"></i>
                           <i class="fa fa-minus"></i>
                        </button>
                    </div>
                    <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordion">
                      <div class="card-body">
                        <p>Donec mattis finibus elit ut tristique. Nullam tempus nunc eget arcu vulputate, eu porttitor tellus commodo. Aliquam erat volutpat. Aliquam consectetur lorem eu viverra lobortis. Morbi gravida, nisi id fringilla ultricies, elit lorem eleifend lorem</p>
                      </div>
                    </div>
                  </div>
                  <div class="card  card_dipult">
                    <div class="card-header card_accor" id="headingfour">
                        <button class="btn btn-link collapsed" data-bs-toggle="collapse" data-bs-target="#collapseeight" aria-expanded="false" aria-controls="collapseeight">
                         Pellentesque habitant morbi tristique senectus et netus?
                           <i class="fa fa-plus"></i>
                           <i class="fa fa-minus"></i>
                        </button>
                    </div>
                    <div id="collapseeight" class="collapse" aria-labelledby="headingfour" data-parent="#accordion">
                      <div class="card-body">
                        <p>Donec mattis finibus elit ut tristique. Nullam tempus nunc eget arcu vulputate, eu porttitor tellus commodo. Aliquam erat volutpat. Aliquam consectetur lorem eu viverra lobortis. Morbi gravida, nisi id fringilla ultricies, elit lorem eleifend lorem</p>
                      </div>
                    </div>
                  </div>
                  <div class="card  card_dipult">
                    <div class="card-header card_accor" id="headingfive">
                        <button class="btn btn-link collapsed" data-bs-toggle="collapse" data-bs-target="#collapseseven" aria-expanded="false" aria-controls="collapseseven">
                         Nam pellentesque aliquam metus?
                           <i class="fa fa-plus"></i>
                           <i class="fa fa-minus"></i>
                        </button>
                    </div>
                    <div id="collapseseven" class="collapse" aria-labelledby="headingfive" data-parent="#accordion">
                      <div class="card-body">
                        <p>Donec mattis finibus elit ut tristique. Nullam tempus nunc eget arcu vulputate, eu porttitor tellus commodo. Aliquam erat volutpat. Aliquam consectetur lorem eu viverra lobortis. Morbi gravida, nisi id fringilla ultricies, elit lorem eleifend lorem</p>
                      </div>
                    </div>
                  </div>

                  <div class="card  card_dipult">
                    <div class="card-header card_accor" id="headingsix">
                        <button class="btn btn-link collapsed" data-bs-toggle="collapse" data-bs-target="#collapsefour" aria-expanded="false" aria-controls="collapsefour">
                        Aenean elit orci, efficitur quis nisl at?
                           <i class="fa fa-plus"></i>
                           <i class="fa fa-minus"></i>
                        </button>
                    </div>
                    <div id="collapsefour" class="collapse" aria-labelledby="headingsix" data-parent="#accordion">
                      <div class="card-body">
                        <p>Donec mattis finibus elit ut tristique. Nullam tempus nunc eget arcu vulputate, eu porttitor tellus commodo. Aliquam erat volutpat. Aliquam consectetur lorem eu viverra lobortis. Morbi gravida, nisi id fringilla ultricies, elit lorem eleifend lorem</p>
                      </div>
                    </div>
                  </div>
                  <div class="card  card_dipult">
                    <div class="card-header card_accor" id="headingseven">
                        <button class="btn btn-link collapsed" data-bs-toggle="collapse" data-bs-target="#collapsefive" aria-expanded="false" aria-controls="collapsefive">
                         Morbi gravida, nisi id fringilla ultricies, elit lorem?
                           <i class="fa fa-plus"></i>
                           <i class="fa fa-minus"></i>
                        </button>
                    </div>
                    <div id="collapsefive" class="collapse" aria-labelledby="headingseven" data-parent="#accordion">
                      <div class="card-body">
                        <p>Donec mattis finibus elit ut tristique. Nullam tempus nunc eget arcu vulputate, eu porttitor tellus commodo. Aliquam erat volutpat. Aliquam consectetur lorem eu viverra lobortis. Morbi gravida, nisi id fringilla ultricies, elit lorem eleifend lorem</p>
                      </div>
                    </div>
                  </div>
                  <div class="card  card_dipult">
                    <div class="card-header card_accor" id="headingeight">
                        <button class="btn btn-link collapsed" data-bs-toggle="collapse" data-bs-target="#collapsesix" aria-expanded="false" aria-controls="collapsesix">
                         Aenean elit orci, efficitur quis nisl at, accumsan?
                           <i class="fa fa-plus"></i>
                           <i class="fa fa-minus"></i>
                        </button>
                    </div>
                    <div id="collapsesix" class="collapse" aria-labelledby="headingeight" data-parent="#accordion">
                      <div class="card-body">
                        <p>Donec mattis finibus elit ut tristique. Nullam tempus nunc eget arcu vulputate, eu porttitor tellus commodo. Aliquam erat volutpat. Aliquam consectetur lorem eu viverra lobortis. Morbi gravida, nisi id fringilla ultricies, elit lorem eleifend lorem</p>
                      </div>
                    </div>
                  </div>
                </div>
            </div>
        </div>
        </div>
    </div>
    <!--Accordion area end-->
    <!--faq area end-->
    
<!--footer area start-->
    <?php include_once "includes/footer.php"; ?>
    <?php include_once "includes/footer-link.php"; ?>

</body>
</html>