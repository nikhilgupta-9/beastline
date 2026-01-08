<?php
include_once "config/connect.php";
include_once "util/function.php";
?>
<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Customer Service | Beastline - We're Here to Help</title>
    <meta name="description" content="Get help with your Beastline orders, products, returns, and more. Our customer service team is ready to assist you.">
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
    
    <!-- Customer Service Custom CSS -->
    <style>
        .service-hero {
            background: linear-gradient(135deg, #c7a17a 0%, #8b6b4d 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
            border-radius: 0 0 20px 20px;
            margin-bottom: 40px;
        }
        .service-option-card {
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 30px 20px;
            text-align: center;
            height: 100%;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        .service-option-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-color: #c7a17a;
        }
        .service-icon {
            width: 70px;
            height: 70px;
            background: rgba(199, 161, 122, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: #c7a17a;
            font-size: 30px;
        }
        .service-cta-btn {
            background: #c7a17a;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }
        .service-cta-btn:hover {
            background: #8b6b4d;
            color: white;
            transform: translateY(-2px);
        }
        .service-hours {
            background: #f9f9f9;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .service-hours h5 {
            color: #c7a17a;
            margin-bottom: 15px;
        }
        .quick-help-box {
            background: #fff;
            border-left: 4px solid #c7a17a;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 0 5px 5px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .contact-form-box {
            background: #f9f9f9;
            border-radius: 10px;
            padding: 30px;
        }
        .social-contact {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }
        .social-contact a {
            width: 45px;
            height: 45px;
            background: #c7a17a;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .social-contact a:hover {
            background: #8b6b4d;
            transform: scale(1.1);
        }
        .service-faq-link {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 10px;
        }
        .service-section-title {
            position: relative;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .service-section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 2px;
            background: #c7a17a;
        }
        .response-time {
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }
        @media (max-width: 768px) {
            .service-hero {
                padding: 40px 0;
            }
            .service-option-card {
                padding: 20px 15px;
            }
            .social-contact {
                flex-wrap: wrap;
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
                        <h3>Customer Service</h3>
                        <ul>
                            <li><a href="<?= $site ?>">Home</a></li>
                            <li>Customer Service</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>         
    </div>
    <!--breadcrumbs area end-->
    
    <!-- Service Hero Section -->
    <div class="service-hero">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1 class="mb-3">We're Here to Help!</h1>
                    <p class="mb-4">Get assistance with orders, products, returns, and more. Our team is ready to support you.</p>
                    <a href="#contact-form" class="service-cta-btn">Send Us a Message</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Help Options -->
    <div class="quick_help_area">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h3 class="service-section-title">Quick Help Options</h3>
                    <p class="mb-4">Choose the most convenient way to get help with your query.</p>
                </div>
            </div>
            
            <div class="row">
                <!-- Live Chat -->
                <div class="col-md-4">
                    <div class="service-option-card">
                        <div class="service-icon">
                            <i class="fa fa-comments"></i>
                        </div>
                        <h5>Live Chat</h5>
                        <p>Chat instantly with our support team. Available 24/7 for quick answers.</p>
                        <button class="service-cta-btn start-chat-btn">
                            <i class="fa fa-comment me-2"></i>Start Chat
                        </button>
                        <div class="response-time">Instant Response</div>
                    </div>
                </div>
                
                <!-- Phone Support -->
                <div class="col-md-4">
                    <div class="service-option-card">
                        <div class="service-icon">
                            <i class="fa fa-phone"></i>
                        </div>
                        <h5>Phone Support</h5>
                        <p>Speak directly with our customer service representatives.</p>
                        <a href="tel:+911800123456" class="service-cta-btn">
                            <i class="fa fa-phone me-2"></i>Call Now
                        </a>
                        <div class="response-time">Mon-Sun: 9 AM - 9 PM IST</div>
                    </div>
                </div>
                
                <!-- Email Support -->
                <div class="col-md-4">
                    <div class="service-option-card">
                        <div class="service-icon">
                            <i class="fa fa-envelope"></i>
                        </div>
                        <h5>Email Support</h5>
                        <p>Send us an email for detailed inquiries. We'll respond promptly.</p>
                        <a href="mailto:support@beastline.com" class="service-cta-btn">
                            <i class="fa fa-envelope me-2"></i>Send Email
                        </a>
                        <div class="response-time">Response within 24 hours</div>
                    </div>
                </div>
            </div>
            
            <!-- Service Hours & Info -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="service-hours">
                        <h5><i class="fa fa-clock me-2"></i>Service Hours</h5>
                        <div class="row">
                            <div class="col-6">
                                <p><strong>Phone Support:</strong><br>Mon - Sun: 9 AM - 9 PM IST</p>
                            </div>
                            <div class="col-6">
                                <p><strong>Email Support:</strong><br>24/7 - Response within 24 hours</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <p><strong>Live Chat:</strong><br>Available 24/7</p>
                            </div>
                            <div class="col-6">
                                <p><strong>Social Media:</strong><br>Mon - Sun: 10 AM - 8 PM IST</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="quick-help-box">
                        <h5><i class="fa fa-lightbulb me-2"></i>Quick Self-Help</h5>
                        <p>Many questions can be answered instantly through our resources:</p>
                        <ul>
                            <li><a href="faq.php">Check our FAQ page</a> for common questions</li>
                            <li><a href="size.php">View our Size Guide</a> for fitting help</li>
                            <li><a href="<?= $site ?>track-order.php">Track your order</a> status online</li>
                            <li><a href="<?= $site ?>returns.php">Start a return</a> or exchange</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contact Form Section -->
    <div class="contact_form_area mt-5" id="contact-form">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h3 class="service-section-title">Send Us a Message</h3>
                    <p class="mb-4">Fill out the form below and we'll get back to you as soon as possible.</p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="contact-form-box">
                        <form id="customer-service-form" method="POST" action="submit_service_request.php">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Your Name *</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="order_number" class="form-label">Order Number (if applicable)</label>
                                        <input type="text" class="form-control" id="order_number" name="order_number">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="issue_type" class="form-label">What can we help you with? *</label>
                                <select class="form-select" id="issue_type" name="issue_type" required>
                                    <option value="">Select an option</option>
                                    <option value="order">Order Status & Tracking</option>
                                    <option value="returns">Returns & Exchanges</option>
                                    <option value="product">Product Information</option>
                                    <option value="sizing">Sizing & Fit Help</option>
                                    <option value="shipping">Shipping & Delivery</option>
                                    <option value="payment">Payment Issues</option>
                                    <option value="account">Account Issues</option>
                                    <option value="other">Other Inquiry</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="message" class="form-label">Your Message *</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required placeholder="Please provide details about your inquiry..."></textarea>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="subscribe" name="subscribe" checked>
                                <label class="form-check-label" for="subscribe">Send me updates about my inquiry and Beastline news</label>
                            </div>
                            
                            <button type="submit" class="service-cta-btn w-100">
                                <i class="fa fa-paper-plane me-2"></i>Send Message
                            </button>
                            
                            <div id="form-message" class="mt-3"></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Social & Additional Contact -->
    <div class="social_contact_area mt-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h3 class="service-section-title text-center">Connect With Us</h3>
                    <p class="mb-4">Follow us on social media for updates, style tips, and exclusive offers.</p>
                    
                    <div class="social-contact">
                        <a href="https://facebook.com/beastline" title="Facebook" target="_blank">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://instagram.com/beastline" title="Instagram" target="_blank">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://twitter.com/beastline" title="Twitter" target="_blank">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://wa.me/911800123456" title="WhatsApp" target="_blank">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <a href="https://linkedin.com/company/beastline" title="LinkedIn" target="_blank">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                    
                    <div class="service-faq-link">
                        <h5 class="mb-3">Quick Answers in Our FAQ</h5>
                        <p>Many common questions are already answered in our comprehensive FAQ section.</p>
                        <a href="faq.php" class="service-cta-btn">
                            <i class="fa fa-question-circle me-2"></i>Visit FAQ Page
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!--footer area start-->
    <?php include_once "includes/footer.php"; ?>
    <?php include_once "includes/footer-link.php"; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Customer Service Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Live Chat Simulation
            const chatBtn = document.querySelector('.start-chat-btn');
            if (chatBtn) {
                chatBtn.addEventListener('click', function() {
                    const chatModal = `
                        <div class="modal fade" id="chatModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Live Chat Support</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="text-center py-4">
                                            <div class="service-icon mb-3">
                                                <i class="fa fa-comments"></i>
                                            </div>
                                            <h5>Connecting to Support...</h5>
                                            <p class="mb-4">Please wait while we connect you with our next available customer service representative.</p>
                                            <div class="spinner-border text-primary mb-3" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p><small>Average wait time: <strong>2 minutes</strong></small></p>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="button" class="btn btn-primary">Continue Waiting</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Remove any existing modal
                    const existingModal = document.getElementById('chatModal');
                    if (existingModal) {
                        existingModal.remove();
                    }
                    
                    // Add new modal to body
                    document.body.insertAdjacentHTML('beforeend', chatModal);
                    
                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('chatModal'));
                    modal.show();
                    
                    // Auto connect after 3 seconds (simulation)
                    setTimeout(() => {
                        const modalBody = document.querySelector('#chatModal .modal-body');
                        if (modalBody) {
                            modalBody.innerHTML = `
                                <div class="text-center py-4">
                                    <div class="service-icon mb-3" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                                        <i class="fa fa-check"></i>
                                    </div>
                                    <h5>Connected!</h5>
                                    <p class="mb-4">You are now chatting with <strong>Priya</strong> from Customer Support.</p>
                                    <p><small>How can we help you today?</small></p>
                                    
                                    <div class="chat-box mt-4">
                                        <div class="card">
                                            <div class="card-body" style="height: 200px; overflow-y: auto;">
                                                <div class="message support mb-2">
                                                    <div class="alert alert-light">
                                                        <strong>Priya:</strong> Hello! How can I help you today?
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-footer">
                                                <div class="input-group">
                                                    <input type="text" class="form-control" placeholder="Type your message...">
                                                    <button class="btn btn-primary">Send</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }
                    }, 3000);
                });
            }
            
            // Form Submission
            const serviceForm = document.getElementById('customer-service-form');
            if (serviceForm) {
                serviceForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Simple validation
                    const name = document.getElementById('name').value;
                    const email = document.getElementById('email').value;
                    const message = document.getElementById('message').value;
                    
                    if (!name || !email || !message) {
                        showMessage('Please fill in all required fields.', 'danger');
                        return;
                    }
                    
                    // Simulate form submission
                    showMessage('Sending your message...', 'info');
                    
                    // Simulate API call
                    setTimeout(() => {
                        showMessage('Thank you! Your message has been sent. We\'ll respond within 24 hours.', 'success');
                        serviceForm.reset();
                        
                        // Scroll to message
                        document.getElementById('form-message').scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }, 1500);
                });
            }
            
            function showMessage(text, type) {
                const messageDiv = document.getElementById('form-message');
                if (messageDiv) {
                    messageDiv.innerHTML = `
                        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                            ${text}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `;
                }
            }
            
            // Auto-fill order number if in URL
            const urlParams = new URLSearchParams(window.location.search);
            const orderNumber = urlParams.get('order');
            if (orderNumber && document.getElementById('order_number')) {
                document.getElementById('order_number').value = orderNumber;
            }
            
            // Issue type auto-suggest
            const issueType = document.getElementById('issue_type');
            const messageField = document.getElementById('message');
            
            if (issueType && messageField) {
                issueType.addEventListener('change', function() {
                    const suggestions = {
                        'order': 'Please include your order number and specific questions about delivery status or tracking.',
                        'returns': 'Please mention the item(s) you want to return, reason for return, and your preferred resolution.',
                        'product': 'Please specify the product name, size/color, and what information you need.',
                        'sizing': 'Please mention the product, your measurements (if known), and fit concerns.',
                        'shipping': 'Please include your order number and specific shipping concerns.',
                        'payment': 'Please describe the payment issue and include transaction details if available.',
                        'account': 'Please describe the issue with your account and include your registered email.',
                        'other': 'Please provide detailed information about your inquiry.'
                    };
                    
                    const suggestion = suggestions[this.value];
                    if (suggestion && !messageField.value) {
                        messageField.placeholder = suggestion;
                    }
                });
            }
        });
    </script>

</body>
</html>