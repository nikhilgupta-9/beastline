<?php
// Start session at the very top
session_start();

include_once "config/connect.php";
include_once "util/function.php";
include_once(__DIR__ . "/models/WebsiteSettings.php");

$setting = new Setting($conn);

// Handle form submission
if (isset($_POST['submit_contact'])) {
    // Process form and store result in session
    $result = handleContactForm($conn);
    echo "form submitted";
    if ($result === true) {
        // Success - redirect to prevent form resubmission
        header('Location: ' . $site . 'contact/?success=1');
        exit;
    }
}

// Check for success parameter
$success_message = '';
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = 'Thank you for your inquiry! We will get back to you soon.';
}
?>
<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Contact Us | Beastline</title>
    <meta name="description" content="">
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
                        <h3>contact</h3>
                        <ul>
                            <li><a href="<?= $site ?>">home</a></li>
                            <li>contact us</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--breadcrumbs area end-->

    <!--contact map start-->
    <div class="contact_map">
        <div class="map-area">
            <?php echo $setting->get('google_maps'); ?>
        </div>
    </div>
    <!--contact map end-->

    <!--contact area start-->
    <div class="contact_area">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 col-md-6">
                    <div class="contact_message content">
                        <h3 class="fw-bold">contact us</h3>
                        <p>At Beastline, we're not just building a brand; we're building a community of men who refuse to settle. We've cut out the middlemen to bring you premium products directly, and we're just as direct when it comes to hearing from you.
                            <br>
                            Whether you're curious about a specific fit or want to track your latest upgrade, we're here to ensure your experience stays Above Average.
                        </p>
                        <ul>
                            <li><i class="fa fa-fax"></i> Address : <?php echo htmlspecialchars($setting->get('business_address')); ?></li>
                            <li><i class="fa fa-phone"></i> <a href="mailto:<?php echo htmlspecialchars($setting->get('business_email')); ?>"><?php echo htmlspecialchars($setting->get('business_email')); ?></a></li>
                            <li><i class="fa fa-envelope-o"></i><a href="tel:91<?php echo htmlspecialchars($setting->get('support_phone')); ?>">+91 <?php echo htmlspecialchars($setting->get('support_phone')); ?></a> </li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6">
                    <div class="contact_message form">
                        <h3 class="fw-bold">Ask Query</h3>

                        <!-- Success/Error Messages -->
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['error_msg'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php
                                echo $_SESSION['error_msg'];
                                unset($_SESSION['error_msg']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="<?= $site ?>contact/">
                            <div class="row">
                                <div class="col-md-6">
                                    <p>
                                        <label>Your Name (required)</label>
                                        <input name="name" placeholder="Name *" type="text" required minlength="2" maxlength="100"
                                            value="<?php echo isset($_SESSION['form_data']['name']) ? htmlspecialchars($_SESSION['form_data']['name']) : ''; ?>">
                                        <span class="text-danger small"><?php echo isset($_SESSION['errors']['name']) ? $_SESSION['errors']['name'] : ''; ?></span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p>
                                        <label>Your Email (required)</label>
                                        <input name="email" placeholder="Email *" type="email" required maxlength="255"
                                            value="<?php echo isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : ''; ?>">
                                        <span class="text-danger small"><?php echo isset($_SESSION['errors']['email']) ? $_SESSION['errors']['email'] : ''; ?></span>
                                    </p>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <p>
                                        <label>Phone Number</label>
                                        <input name="phone" placeholder="Phone (optional)" type="tel" maxlength="20"
                                            value="<?php echo isset($_SESSION['form_data']['phone']) ? htmlspecialchars($_SESSION['form_data']['phone']) : ''; ?>">
                                        <span class="text-danger small"><?php echo isset($_SESSION['errors']['phone']) ? $_SESSION['errors']['phone'] : ''; ?></span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p>
                                        <label>Subject</label>
                                        <input name="subject" placeholder="Subject *" type="text" required maxlength="255"
                                            value="<?php echo isset($_SESSION['form_data']['subject']) ? htmlspecialchars($_SESSION['form_data']['subject']) : ''; ?>">
                                        <span class="text-danger small"><?php echo isset($_SESSION['errors']['subject']) ? $_SESSION['errors']['subject'] : ''; ?></span>
                                    </p>
                                </div>
                            </div>

                            <div class="contact_textarea">
                                <label>Your Message (required)</label>
                                <textarea placeholder="Message *" name="message" class="form-control2" required minlength="10" maxlength="2000"><?php echo isset($_SESSION['form_data']['message']) ? htmlspecialchars($_SESSION['form_data']['message']) : ''; ?></textarea>
                                <span class="text-danger small"><?php echo isset($_SESSION['errors']['message']) ? $_SESSION['errors']['message'] : ''; ?></span>
                            </div>

                            <button type="submit" name="submit_contact" class="btn btn-dark">Send Message</button>
                            <p class="form-messege mt-2"></p>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--contact area end-->

    <!--footer area start-->
    <?php include_once "includes/footer.php"; ?>
    <?php include_once "includes/footer-link.php"; ?>

    <!-- Add SweetAlert for better notifications -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Show success message if exists
        <?php if (!empty($success_message)): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '<?php echo addslashes($success_message); ?>',
                timer: 3000,
                showConfirmButton: false
            }).then(() => {
                // Clear form after successful submission
                document.getElementById('contact-form').reset();
            });
        <?php endif; ?>

        // Show error message if exists
        <?php if (isset($_SESSION['error_msg']) && !empty($_SESSION['error_msg'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '<?php echo addslashes($_SESSION["error_msg"]); ?>',
                timer: 3000,
                showConfirmButton: false
            });
        <?php endif; ?>

        // Clear session data after displaying
        <?php
        unset($_SESSION['form_data']);
        unset($_SESSION['errors']);
        unset($_SESSION['error_msg']);
        ?>

        // Form validation
        $(document).ready(function() {
            $('#contact-form').on('submit', function(e) {
                // Basic validation
                var valid = true;
                $(this).find('input[required], textarea[required]').each(function() {
                    if ($(this).val().trim() === '') {
                        $(this).addClass('is-invalid');
                        valid = false;
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });

                if (!valid) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Required Fields',
                        text: 'Please fill in all required fields.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });

            // Remove error class on input
            $('#contact-form input, #contact-form textarea').on('input', function() {
                if ($(this).val().trim() !== '') {
                    $(this).removeClass('is-invalid');
                }
            });
        });
    </script>

</body>

</html>