<?php
define('ROOT_PATH', dirname(__DIR__));

include_once ROOT_PATH . "/config/connect.php";
include_once ROOT_PATH . "/util/function.php";
include_once ROOT_PATH . "/models/Policy.php";
include_once ROOT_PATH . "/models/WebsiteSettings.php";

$slug = $_GET['alias'] ?? 'privacy-policy';
$setting = new Setting($conn);

// Get privacy policy content
$policyModel = new Policy($conn);
$policy = $policyModel->getPolicyBySlug($slug);

// If no policy found, handle accordingly
if (!$policy) {
    $policy = [
        'title' => 'Privacy Policy',
        'content' => 'Privacy policy content will be displayed here.'
    ];
}

$contact = contact_us();
?>
<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title><?= htmlspecialchars($policy['title']) ?> | Beastline</title>
    <meta name="description" content="Read our Privacy Policy to understand how we collect, use, and protect your personal information.">
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

    <!-- Add some custom styles for policy page -->
    <style>
        .policy-content {
            line-height: 1.8;
            font-size: 16px;
        }
        .policy-content h3 {
            margin-top: 30px;
            margin-bottom: 15px;
            color: #333;
        }
        .policy-content p {
            margin-bottom: 15px;
            text-align: justify;
        }
        .policy-content ul {
            margin-left: 20px;
            margin-bottom: 20px;
        }
        .policy-content li {
            margin-bottom: 8px;
        }
        .policy-section {
            background: #fff;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .last-updated {
            color: #666;
            font-style: italic;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
    </style>
</head>

<body>

    <!--header area start-->
    <?php include_once "../includes/header.php" ?>
    <!--header area end-->

    <!--breadcrumbs area start-->
    <div class="breadcrumbs_area">
        <div class="container">   
            <div class="row">
                <div class="col-12">
                    <div class="breadcrumb_content">
                        <h3><?= htmlspecialchars($policy['title']) ?></h3>
                        <ul>
                            <li><a href="<?= $site ?>">home</a></li>
                            <li><?= htmlspecialchars($policy['title']) ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>         
    </div>
    <!--breadcrumbs area end-->
    
    <!-- privacy policy content start -->
    <div class="privacy_policy">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="policy-section">
                        <?php if (isset($policy['last_updated'])): ?>
                        <div class="last-updated">
                            Last Updated: <?= date('F j, Y', strtotime($policy['last_updated'])) ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="policy-content">
                            <?php 
                            // Display the policy content
                            // If content is HTML, you can directly echo it
                            // If it's plain text, you might want to format it
                            echo nl2br(htmlspecialchars_decode($policy['content']));
                            
                            // Alternative if you want to allow HTML from database but be careful with XSS
                            // Only use this if you trust the content source
                            // echo $policy['content'];
                            ?>
                        </div>
                        
                        <!-- You can add more sections or information here -->
                        <div class="contact-info" style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee;">
                            <h4>Contact Us</h4>
                            <p>If you have any questions about this Privacy Policy, please contact us:</p>
                            <ul>
                                <li>Email: <?= htmlspecialchars($contact['email'] ?? 'contact@beastline.com') ?></li>
                                <li>Phone: <?= htmlspecialchars($contact['phone'] ?? '') ?></li>
                                <li>Address: <?= htmlspecialchars($contact['address'] ?? '') ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>    
    </div>
    <!-- privacy policy content end -->
    
   <!--footer area start-->
    <?php include_once(__DIR__. "/../includes/footer.php"); ?>
    <?php include_once(__DIR__. "/../includes/footer-link.php"); ?>

</body>
</html>