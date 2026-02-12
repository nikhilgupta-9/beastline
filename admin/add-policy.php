<?php
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';
require_once __DIR__ . '/models/setting.php';
require_once __DIR__ . '/models/Policy.php';

// Initialize
$setting = new Setting($conn);
$policyModel = new Policy($conn);

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_policy'])) {
    // Collect form data
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $meta_keywords = trim($_POST['meta_keywords'] ?? '');
    $status = (int)($_POST['status'] ?? 1);
    $display_order = (int)($_POST['display_order'] ?? 0);
    
    // Validation
    if (empty($title)) {
        $errors[] = "Policy title is required";
    }
    
    if (empty($slug)) {
        $errors[] = "Slug URL is required";
    } else {
        // Clean slug
        $slug = $policyModel->generateSlug($slug);
    }
    
    if (empty($content)) {
        $errors[] = "Policy content is required";
    }
    
    // Create policy if no errors
    if (empty($errors)) {
        $data = [
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'meta_title' => $meta_title,
            'meta_description' => $meta_description,
            'meta_keywords' => $meta_keywords,
            'status' => $status,
            'display_order' => $display_order
        ];
        
        if ($policyModel->createPolicy($data)) {
            $_SESSION['success'] = "Policy created successfully!";
            header("Location: view-policies.php");
            exit();
        } else {
            $errors[] = "Error creating policy. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Add New Policy | Admin <?php echo htmlspecialchars($setting->get('site_name')); ?></title>
    <link rel="icon" href="<?php echo htmlspecialchars($setting->get('favicon', 'assets/img/logo.png')); ?>" type="image/png">

    <?php include "links.php"; ?>
    
    <!-- Summernote CSS -->
    <link rel="stylesheet" href="assets/vendors/text_editor/summernote-bs4.css">
    
    <style>
        .form-section {
            border-left: 4px solid #4361ee;
            background: #f8fafc;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .required-star {
            color: #dc3545;
        }
        
        .character-count {
            font-size: 0.8rem;
            color: #6c757d;
            text-align: right;
            margin-top: 0.25rem;
        }
        
        .slug-preview {
            background: #e9ecef;
            padding: 8px 12px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9rem;
        }
        
        .template-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .template-card:hover {
            border-color: #4361ee;
            background: rgba(67, 97, 238, 0.05);
        }
        
        .template-card.active {
            border-color: #4361ee;
            background: rgba(67, 97, 238, 0.1);
        }
        
        .template-icon {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        
        .preview-box {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            background: white;
            margin-top: 20px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .policy-example {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
    </style>
</head>

<body class="crm_body_bg">

    <?php include "includes/header.php"; ?>
    
    <section class="main_content dashboard_part large_header_bg">
        <div class="container-fluid g-0">
            <div class="row">
                <div class="col-lg-12 p-0">
                    <?php include "includes/top_nav.php"; ?>
                </div>
            </div>
        </div>

        <div class="main_content_iner ">
            <div class="container-fluid p-0 sm_padding_15px">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <div class="white_card card_height_100 mb_30">
                            <div class="white_card_header">
                                <div class="box_header m-0">
                                    <div class="main-title">
                                        <h2 class="m-0"><i class="fas fa-plus-circle mr-2"></i> Add New Policy</h2>
                                        <p class="mb-0 text-muted">Create new policy or legal document for your website</p>
                                    </div>
                                    <div class="action-btn">
                                        <a href="view-policies.php" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left mr-1"></i> Back to Policies
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="white_card_body">
                                <!-- Messages -->
                                <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <h6><i class="fas fa-exclamation-circle mr-2"></i> Please fix the following:</h6>
                                    <ul class="mb-0 pl-3">
                                        <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <?php endif; ?>

                                <!-- Quick Templates -->
                                <div class="form-section mb-4">
                                    <h5 class="mb-3"><i class="fas fa-clone mr-2"></i> Quick Templates</h5>
                                    <p class="text-muted mb-3">Select a template to get started quickly:</p>
                                    
                                    <div class="row" id="templateContainer">
                                        <!-- Privacy Policy Template -->
                                        <div class="col-md-4" onclick="loadTemplate('privacy')">
                                            <div class="template-card" data-template="privacy">
                                                <div class="template-icon text-primary">
                                                    <i class="fas fa-user-shield"></i>
                                                </div>
                                                <h6 class="mb-2">Privacy Policy</h6>
                                                <p class="text-muted small mb-0">Explains data collection and usage</p>
                                            </div>
                                        </div>
                                        
                                        <!-- Shipping Policy Template -->
                                        <div class="col-md-4" onclick="loadTemplate('shipping')">
                                            <div class="template-card" data-template="shipping">
                                                <div class="template-icon text-success">
                                                    <i class="fas fa-shipping-fast"></i>
                                                </div>
                                                <h6 class="mb-2">Shipping Policy</h6>
                                                <p class="text-muted small mb-0">Delivery methods, times, and costs</p>
                                            </div>
                                        </div>
                                        
                                        <!-- Return Policy Template -->
                                        <div class="col-md-4" onclick="loadTemplate('return')">
                                            <div class="template-card" data-template="return">
                                                <div class="template-icon text-warning">
                                                    <i class="fas fa-exchange-alt"></i>
                                                </div>
                                                <h6 class="mb-2">Return & Refund Policy</h6>
                                                <p class="text-muted small mb-0">Return, exchange, and refund guidelines</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <form action="" method="post" id="policyForm">
                                    <div class="row">
                                        <!-- Left Column: Basic Info -->
                                        <div class="col-lg-6">
                                            <div class="form-section mb-4">
                                                <h5 class="mb-3"><i class="fas fa-info-circle mr-2"></i> Basic Information</h5>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Policy Title <span class="required-star">*</span></label>
                                                    <input type="text" class="form-control" name="title" 
                                                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                                                           required maxlength="255" placeholder="e.g., Privacy Policy">
                                                    <div class="character-count" id="titleCount">0/255 characters</div>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Slug URL <span class="required-star">*</span></label>
                                                    <input type="text" class="form-control" name="slug" 
                                                           value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>"
                                                           required placeholder="e.g., privacy-policy">
                                                    <div class="slug-preview mt-2" id="slugPreview">
                                                        <?php 
                                                        if (isset($_POST['slug']) && !empty($_POST['slug'])) {
                                                            echo htmlspecialchars($_POST['slug']);
                                                        } else {
                                                            echo 'slug-will-appear-here';
                                                        }
                                                        ?>
                                                    </div>
                                                    <small class="form-text text-muted">Used in URL: yourwebsite.com/policy/[slug]</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Display Order</label>
                                                    <input type="number" class="form-control" name="display_order" 
                                                           value="<?php echo htmlspecialchars($_POST['display_order'] ?? 0); ?>"
                                                           min="0" max="999">
                                                    <small class="form-text text-muted">Lower numbers appear first in listings</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Status <span class="required-star">*</span></label>
                                                    <select class="form-control" name="status" required>
                                                        <option value="1" <?php echo !isset($_POST['submit']) || $_POST['status'] == '1' ? 'selected' : ''; ?>>Active</option>
                                                        <option value="0" <?php echo isset($_POST['status']) && $_POST['status'] == '0' ? 'selected' : ''; ?>>Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="form-section">
                                                <h5 class="mb-3"><i class="fas fa-search mr-2"></i> SEO Settings</h5>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Meta Title</label>
                                                    <input type="text" class="form-control" name="meta_title" 
                                                           value="<?php echo htmlspecialchars($_POST['meta_title'] ?? ''); ?>"
                                                           maxlength="255" placeholder="Title for search engines">
                                                    <div class="character-count" id="metaTitleCount">0/255 characters</div>
                                                    <small class="form-text text-muted">Optimal: 50-60 characters</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Meta Description</label>
                                                    <textarea class="form-control" name="meta_description" rows="3"
                                                        maxlength="320" placeholder="Description for search engines"><?php echo htmlspecialchars($_POST['meta_description'] ?? ''); ?></textarea>
                                                    <div class="character-count" id="metaDescCount">0/320 characters</div>
                                                    <small class="form-text text-muted">Optimal: 150-160 characters</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Meta Keywords</label>
                                                    <input type="text" class="form-control" name="meta_keywords" 
                                                           value="<?php echo htmlspecialchars($_POST['meta_keywords'] ?? ''); ?>"
                                                           placeholder="keywords, separated by commas" maxlength="500">
                                                    <div class="character-count" id="metaKeyCount">0/500 characters</div>
                                                    <small class="form-text text-muted">Separate with commas (optional)</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Right Column: Policy Content -->
                                        <div class="col-lg-6">
                                            <div class="form-section">
                                                <h5 class="mb-3"><i class="fas fa-file-alt mr-2"></i> Policy Content <span class="required-star">*</span></h5>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Policy Content</label>
                                                    <textarea class="form-control summernote" name="content" id="policyContent" rows="15"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                                                </div>
                                                
                                                <!-- Content Tips -->
                                                <div class="alert alert-info mt-3">
                                                    <h6><i class="fas fa-lightbulb mr-2"></i> Content Tips:</h6>
                                                    <ul class="mb-0 pl-3">
                                                        <li>Use clear headings (H2, H3) for sections</li>
                                                        <li>Keep paragraphs short and readable</li>
                                                        <li>Use bullet points for lists</li>
                                                        <li>Include contact information if needed</li>
                                                        <li>Specify effective dates when applicable</li>
                                                    </ul>
                                                </div>
                                            </div>
                                            
                                            <!-- Preview -->
                                            <div class="preview-box" id="previewBox">
                                                <h6 class="mb-3">Content Preview:</h6>
                                                <div id="contentPreview">
                                                    <?php if (isset($_POST['content']) && !empty($_POST['content'])): ?>
                                                    <?php echo $_POST['content']; ?>
                                                    <?php else: ?>
                                                    <p class="text-muted">Content preview will appear here...</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="mt-4 pt-3 border-top">
                                        <div class="d-flex justify-content-between">
                                            <a href="view-policies.php" class="btn btn-secondary">
                                                <i class="fas fa-times mr-2"></i> Cancel
                                            </a>
                                            <div>
                                                <button type="button" class="btn btn-outline-secondary mr-2" onclick="resetForm()">
                                                    <i class="fas fa-redo mr-1"></i> Reset
                                                </button>
                                                <button type="submit" name="add_policy" class="btn btn-primary">
                                                    <i class="fas fa-save mr-2"></i> Save Policy
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include "includes/footer.php"; ?>
    </section>

    <!-- Summernote JS -->
    <script src="assets/vendors/text_editor/summernote-bs4.js"></script>
    
    <script>
        // Initialize Summernote
        $(document).ready(function() {
            $('.summernote').summernote({
                height: 300,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['fontname', ['fontname']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['height', ['height']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'hr']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ],
                callbacks: {
                    onChange: function(contents, $editable) {
                        updatePreview(contents);
                    }
                }
            });
            
            // Character counters
            const inputs = {
                'title': 'titleCount',
                'meta_title': 'metaTitleCount',
                'meta_description': 'metaDescCount',
                'meta_keywords': 'metaKeyCount'
            };
            
            Object.keys(inputs).forEach(inputName => {
                const input = document.querySelector(`[name="${inputName}"]`);
                const counter = document.getElementById(inputs[inputName]);
                
                if (input && counter) {
                    // Initial count
                    const max = input.maxLength || 255;
                    counter.textContent = input.value.length + '/' + max;
                    
                    input.addEventListener('input', function() {
                        const max = this.maxLength || 255;
                        counter.textContent = this.value.length + '/' + max;
                        updateSlugPreview();
                    });
                }
            });
            
            // Auto-generate slug from title
            const titleInput = document.querySelector('input[name="title"]');
            const slugInput = document.querySelector('input[name="slug"]');
            const slugPreview = document.getElementById('slugPreview');
            
            titleInput.addEventListener('input', function() {
                if (!slugInput.value || slugInput.value === '<?php echo $_POST['slug'] ?? ''; ?>') {
                    const slug = this.value.toLowerCase()
                        .replace(/[^\w\s-]/g, '')
                        .replace(/[\s_-]+/g, '-')
                        .replace(/^-+|-+$/g, '');
                    slugInput.value = slug;
                    slugPreview.textContent = slug || 'slug-will-appear-here';
                }
            });
            
            slugInput.addEventListener('input', updateSlugPreview);
            
            function updateSlugPreview() {
                slugPreview.textContent = slugInput.value || 'slug-will-appear-here';
            }
            
            // Update preview function
            function updatePreview(content) {
                document.getElementById('contentPreview').innerHTML = content || '<p class="text-muted">Content preview will appear here...</p>';
            }
            
            // Template loading
            window.loadTemplate = function(templateType) {
                // Remove active class from all templates
                document.querySelectorAll('.template-card').forEach(card => {
                    card.classList.remove('active');
                });
                
                // Add active class to selected template
                const selectedCard = document.querySelector(`[data-template="${templateType}"]`);
                selectedCard.classList.add('active');
                
                // Define templates
                const templates = {
                    'privacy': {
                        title: 'Privacy Policy',
                        slug: 'privacy-policy',
                        content: `<h2>Privacy Policy</h2>
<p>Your privacy is important to us. This privacy policy explains how we collect, use, and protect your personal information when you use our website.</p>

<h3>Information We Collect</h3>
<p>We collect information you provide directly to us, such as when you create an account, make a purchase, or contact us. This may include:</p>
<ul>
<li>Name and contact information</li>
<li>Payment details</li>
<li>Shipping address</li>
<li>Communication preferences</li>
</ul>

<h3>How We Use Your Information</h3>
<p>We use the information we collect to:</p>
<ul>
<li>Process your orders and transactions</li>
<li>Provide customer support</li>
<li>Send you important updates</li>
<li>Improve our products and services</li>
</ul>

<h3>Data Security</h3>
<p>We implement appropriate security measures to protect your personal information from unauthorized access, alteration, disclosure, or destruction.</p>

<h3>Your Rights</h3>
<p>You have the right to:</p>
<ul>
<li>Access your personal data</li>
<li>Correct inaccurate data</li>
<li>Request deletion of your data</li>
<li>Opt-out of marketing communications</li>
</ul>

<p><strong>Effective Date:</strong> ${new Date().toLocaleDateString()}</p>`,
                        meta_title: 'Privacy Policy - Your Company Name',
                        meta_description: 'Read our privacy policy to understand how we collect, use, and protect your personal information.',
                        meta_keywords: 'privacy policy, data protection, personal information, GDPR, data security'
                    },
                    'shipping': {
                        title: 'Shipping Policy',
                        slug: 'shipping-policy',
                        content: `<h2>Shipping Policy</h2>
<p>This shipping policy outlines our delivery methods, shipping times, and associated costs.</p>

<h3>Shipping Methods</h3>
<p>We offer the following shipping options:</p>
<ul>
<li><strong>Standard Shipping:</strong> 5-7 business days</li>
<li><strong>Express Shipping:</strong> 2-3 business days</li>
<li><strong>Next Day Delivery:</strong> 1 business day (where available)</li>
</ul>

<h3>Shipping Costs</h3>
<table class="table table-bordered">
<thead>
<tr>
<th>Shipping Method</th>
<th>Cost</th>
<th>Delivery Time</th>
</tr>
</thead>
<tbody>
<tr>
<td>Standard Shipping</td>
<td>$5.99</td>
<td>5-7 business days</td>
</tr>
<tr>
<td>Express Shipping</td>
<td>$12.99</td>
<td>2-3 business days</td>
</tr>
<tr>
<td>Next Day Delivery</td>
<td>$24.99</td>
<td>1 business day</td>
</tr>
</tbody>
</table>

<h3>Processing Time</h3>
<p>Orders are typically processed within 1-2 business days. Processing may take longer during holidays or sales periods.</p>

<h3>Shipping Restrictions</h3>
<p>We currently ship to the following countries:</p>
<ul>
<li>United States</li>
<li>Canada</li>
<li>United Kingdom</li>
<li>Australia</li>
</ul>

<h3>Tracking Your Order</h3>
<p>Once your order ships, you will receive a tracking number via email. You can use this number to track your package on our website.</p>

<h3>Delivery Issues</h3>
<p>If you experience any delivery issues, please contact our customer service team within 7 days of the expected delivery date.</p>

<p><strong>Last Updated:</strong> ${new Date().toLocaleDateString()}</p>`,
                        meta_title: 'Shipping Policy - Your Company Name',
                        meta_description: 'Information about our shipping methods, delivery times, and shipping costs.',
                        meta_keywords: 'shipping policy, delivery times, shipping costs, shipping methods, tracking'
                    },
                    'return': {
                        title: 'Return & Refund Policy',
                        slug: 'return-refund-policy',
                        content: `<h2>Return & Refund Policy</h2>
<p>We want you to be completely satisfied with your purchase. Please review our return and refund policy below.</p>

<h3>Return Period</h3>
<p>You may return most items within 30 days of delivery for a full refund or exchange. Some items may have different return periods as specified on the product page.</p>

<h3>Return Conditions</h3>
<p>To be eligible for a return, your item must be:</p>
<ul>
<li>In the original packaging</li>
<li>Unused and in the same condition as received</li>
<li>Accompanied by the original receipt or proof of purchase</li>
<li>Not a final sale or clearance item</li>
</ul>

<h3>Return Process</h3>
<ol>
<li>Contact our customer service to initiate a return</li>
<li>You will receive a Return Merchandise Authorization (RMA) number</li>
<li>Package the item securely with the RMA number clearly visible</li>
<li>Ship the package to the address provided</li>
<li>Once received, we will process your refund within 5-7 business days</li>
</ol>

<h3>Refund Methods</h3>
<p>Refunds are issued to the original payment method. Please allow 5-10 business days for the refund to appear on your account.</p>

<h3>Exchanges</h3>
<p>We offer exchanges for items of equal value. If you wish to exchange an item, please indicate this when initiating the return.</p>

<h3>Return Shipping Costs</h3>
<p>Customers are responsible for return shipping costs unless the return is due to our error or a defective product.</p>

<h3>Non-Returnable Items</h3>
<p>The following items cannot be returned:</p>
<ul>
<li>Personalized or custom-made items</li>
<li>Gift cards</li>
<li>Downloadable software products</li>
<li>Items marked as "Final Sale"</li>
</ul>

<h3>Contact Us</h3>
<p>If you have any questions about our return policy, please contact our customer service team.</p>

<p><strong>Policy Effective:</strong> ${new Date().toLocaleDateString()}</p>`,
                        meta_title: 'Return & Refund Policy - Your Company Name',
                        meta_description: 'Learn about our return and refund procedures, timelines, and conditions.',
                        meta_keywords: 'return policy, refund policy, exchange policy, return process, refund process'
                    }
                };
                
                const template = templates[templateType];
                
                if (template) {
                    // Fill form with template data
                    document.querySelector('input[name="title"]').value = template.title;
                    document.querySelector('input[name="slug"]').value = template.slug;
                    document.querySelector('input[name="meta_title"]').value = template.meta_title;
                    document.querySelector('textarea[name="meta_description"]').value = template.meta_description;
                    document.querySelector('input[name="meta_keywords"]').value = template.meta_keywords;
                    
                    // Update summernote content
                    $('.summernote').summernote('code', template.content);
                    
                    // Update counters
                    Object.keys(inputs).forEach(inputName => {
                        const input = document.querySelector(`[name="${inputName}"]`);
                        const counter = document.getElementById(inputs[inputName]);
                        if (input && counter) {
                            const max = input.maxLength || 255;
                            counter.textContent = input.value.length + '/' + max;
                        }
                    });
                    
                    // Update slug preview
                    updateSlugPreview();
                    updatePreview(template.content);
                    
                    // Scroll to form
                    document.getElementById('policyForm').scrollIntoView({ behavior: 'smooth' });
                }
            };
            
            // Form validation
            const form = document.getElementById('policyForm');
            form.addEventListener('submit', function(e) {
                const title = document.querySelector('input[name="title"]').value.trim();
                const slug = document.querySelector('input[name="slug"]').value.trim();
                const content = $('.summernote').summernote('code').trim();
                
                if (!title) {
                    e.preventDefault();
                    alert('Please enter a policy title.');
                    return false;
                }
                
                if (!slug) {
                    e.preventDefault();
                    alert('Please enter a slug URL.');
                    return false;
                }
                
                if (!content || content === '<p><br></p>') {
                    e.preventDefault();
                    alert('Please enter policy content.');
                    return false;
                }
                
                return true;
            });
            
            // Reset form
            window.resetForm = function() {
                if (confirm('Reset all form fields?')) {
                    form.reset();
                    $('.summernote').summernote('code', '');
                    document.querySelectorAll('.template-card').forEach(card => {
                        card.classList.remove('active');
                    });
                    
                    // Reset counters
                    Object.keys(inputs).forEach(inputName => {
                        const counter = document.getElementById(inputs[inputName]);
                        if (counter) counter.textContent = '0/255 characters';
                    });
                    
                    slugPreview.textContent = 'slug-will-appear-here';
                    updatePreview('');
                }
            };
            
            // Initial preview
            const initialContent = $('.summernote').summernote('code');
            updatePreview(initialContent);
        });
    </script>
</body>
</html>