<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';
require_once __DIR__ . '/models/setting.php';
require_once __DIR__ . '/models/Policy.php';

// Initialize
$setting = new Setting($conn);
$policyModel = new Policy($conn);

// Get policy ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view-policies.php");
    exit();
}

$policy_id = (int)$_GET['id'];
$policy = $policyModel->getPolicyById($policy_id);

if (!$policy) {
    $_SESSION['error'] = "Policy not found";
    header("Location: view-policies.php");
    exit();
}

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_policy'])) {
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
        // Clean slug - Check if slug has changed
        if ($slug !== $policy['slug']) {
            $slug = $policyModel->generateSlug($slug);
        }
    }

    if (empty($content)) {
        $errors[] = "Policy content is required";
    }

    // Update policy if no errors
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

        if ($policyModel->updatePolicy($policy_id, $data)) {
            $_SESSION['success'] = "Policy updated successfully!";
            header("Location: view-policies.php");
            exit();
        } else {
            $errors[] = "Error updating policy. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Edit Policy | Admin <?php echo htmlspecialchars($setting->get('site_name')); ?></title>
    <link rel="icon" href="<?php echo htmlspecialchars($setting->get('favicon', 'assets/img/logo.png')); ?>" type="image/png">

    <?php include "links.php"; ?>

    <!-- Summernote CSS -->
    <!-- <link rel="stylesheet" href="assets/vendors/text_editor/summernote-bs4.css"> -->
         <script src="https://cdn.ckeditor.com/4.21.0/standard/ckeditor.js"></script>


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

        .policy-info {
            background: #f8f9fa;
            border-left: 4px solid #6c757d;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .policy-info h6 {
            margin-bottom: 10px;
        }

        .slug-warning {
            color: #856404;
            background-color: #fff3cd;
            border-color: #ffeaa7;
            padding: 0.5rem;
            border-radius: 4px;
            margin-top: 5px;
            font-size: 0.875rem;
            display: none;
        }

        .last-saved {
            font-size: 0.875rem;
            color: #28a745;
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
                                        <h2 class="m-0"><i class="fas fa-edit mr-2"></i> Edit Policy</h2>
                                        <p class="mb-0 text-muted">Editing: <strong><?php echo htmlspecialchars($policy['title']); ?></strong></p>
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

                                <!-- Success Message from Session -->
                                <?php if (isset($_SESSION['success'])): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($_SESSION['success']); ?>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <?php unset($_SESSION['success']); ?>
                                <?php endif; ?>

                                <!-- Policy Information -->
                                <div class="policy-info">
                                    <div class="row">
                                        <div class="col-md-3 mb-2">
                                            <strong><i class="fas fa-hashtag mr-1"></i> Policy ID:</strong> <?php echo htmlspecialchars($policy['policy_id']); ?>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <strong><i class="fas fa-calendar-plus mr-1"></i> Created:</strong> <?php echo date('d M Y, h:i A', strtotime($policy['created_at'])); ?>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <strong><i class="fas fa-calendar-check mr-1"></i> Last Updated:</strong> <?php echo date('d M Y, h:i A', strtotime($policy['last_updated'])); ?>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <strong><i class="fas fa-circle mr-1"></i> Status:</strong>
                                            <span class="badge badge-<?php echo $policy['status'] ? 'success' : 'danger'; ?>">
                                                <?php echo $policy['status'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Templates (for reference) -->
                                <div class="form-section mb-4">
                                    <h5 class="mb-3"><i class="fas fa-clone mr-2"></i> Template Reference</h5>
                                    <p class="text-muted mb-3">Use these templates as reference or copy sections:</p>

                                    <div class="row" id="templateContainer">
                                        <!-- Privacy Policy Template -->
                                        <div class="col-md-4" onclick="loadTemplateSection('privacy')">
                                            <div class="template-card" data-template="privacy">
                                                <div class="template-icon text-primary">
                                                    <i class="fas fa-user-shield"></i>
                                                </div>
                                                <h6 class="mb-2">Privacy Policy</h6>
                                                <p class="text-muted small mb-0">Data collection and usage sections</p>
                                            </div>
                                        </div>

                                        <!-- Shipping Policy Template -->
                                        <div class="col-md-4" onclick="loadTemplateSection('shipping')">
                                            <div class="template-card" data-template="shipping">
                                                <div class="template-icon text-success">
                                                    <i class="fas fa-shipping-fast"></i>
                                                </div>
                                                <h6 class="mb-2">Shipping Policy</h6>
                                                <p class="text-muted small mb-0">Delivery methods and times</p>
                                            </div>
                                        </div>

                                        <!-- Return Policy Template -->
                                        <div class="col-md-4" onclick="loadTemplateSection('return')">
                                            <div class="template-card" data-template="return">
                                                <div class="template-icon text-warning">
                                                    <i class="fas fa-exchange-alt"></i>
                                                </div>
                                                <h6 class="mb-2">Return & Refund</h6>
                                                <p class="text-muted small mb-0">Return and refund guidelines</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <form action="" method="post" id="editPolicyForm">
                                    <div class="row">
                                        <!-- Left Column: Basic Info -->
                                        <div class="col-lg-6">
                                            <div class="form-section mb-4">
                                                <h5 class="mb-3"><i class="fas fa-info-circle mr-2"></i> Basic Information</h5>

                                                <div class="form-group">
                                                    <label class="form-label">Policy Title <span class="required-star">*</span></label>
                                                    <input type="text" class="form-control" name="title"
                                                        value="<?php echo htmlspecialchars($policy['title']); ?>"
                                                        required maxlength="255" placeholder="e.g., Privacy Policy" id="policyTitle">
                                                    <div class="character-count" id="titleCount"><?php echo strlen($policy['title']); ?>/255 characters</div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">Slug URL <span class="required-star">*</span></label>
                                                    <input type="text" class="form-control" name="slug"
                                                        value="<?php echo htmlspecialchars($policy['slug']); ?>"
                                                        required placeholder="e.g., privacy-policy" id="slugInput">
                                                    <div class="slug-warning" id="slugWarning">
                                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                                        Changing the slug may break existing links. Make sure to set up redirects if needed.
                                                    </div>
                                                    <div class="slug-preview mt-2" id="slugPreview">
                                                        <?php echo htmlspecialchars($policy['slug']); ?>
                                                    </div>
                                                    <small class="form-text text-muted">Used in URL: yourwebsite.com/policy/<?php echo htmlspecialchars($policy['slug']); ?></small>
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">Display Order</label>
                                                    <input type="number" class="form-control" name="display_order"
                                                        value="<?php echo htmlspecialchars($policy['display_order']); ?>"
                                                        min="0" max="999">
                                                    <small class="form-text text-muted">Lower numbers appear first in listings</small>
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">Status <span class="required-star">*</span></label>
                                                    <select class="form-control" name="status" required>
                                                        <option value="1" <?php echo $policy['status'] == 1 ? 'selected' : ''; ?>>Active</option>
                                                        <option value="0" <?php echo $policy['status'] == 0 ? 'selected' : ''; ?>>Inactive</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-section">
                                                <h5 class="mb-3"><i class="fas fa-search mr-2"></i> SEO Settings</h5>

                                                <div class="form-group">
                                                    <label class="form-label">Meta Title</label>
                                                    <input type="text" class="form-control" name="meta_title"
                                                        value="<?php echo htmlspecialchars($policy['meta_title']); ?>"
                                                        maxlength="255" placeholder="Title for search engines" id="metaTitleInput">
                                                    <div class="character-count" id="metaTitleCount"><?php echo strlen($policy['meta_title']); ?>/255 characters</div>
                                                    <small class="form-text text-muted">Optimal: 50-60 characters</small>
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">Meta Description</label>
                                                    <textarea class="form-control" name="meta_description" rows="3"
                                                        maxlength="320" placeholder="Description for search engines" id="metaDescInput"><?php echo htmlspecialchars($policy['meta_description']); ?></textarea>
                                                    <div class="character-count" id="metaDescCount"><?php echo strlen($policy['meta_description']); ?>/320 characters</div>
                                                    <small class="form-text text-muted">Optimal: 150-160 characters</small>
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">Meta Keywords</label>
                                                    <input type="text" class="form-control" name="meta_keywords"
                                                        value="<?php echo htmlspecialchars($policy['meta_keywords'] ?? ''); ?>"
                                                        placeholder="keywords, separated by commas" maxlength="500" id="metaKeyInput">
                                                    <div class="character-count" id="metaKeyCount"><?php echo strlen($policy['meta_keywords'] ?? ''); ?>/500 characters</div>
                                                    <small class="form-text text-muted">Separate with commas (optional)</small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Right Column: Policy Content -->
                                        <div class="col-lg-6">
                                            <div class="form-section">
                                                <h5 class="mb-3"><i class="fas fa-file-alt mr-2"></i> Policy Content <span class="required-star">*</span></h5>

                                                <div class="alert alert-warning mb-3">
                                                    <i class="fas fa-save mr-2"></i>
                                                    <span class="last-saved" id="lastSavedTime">
                                                        Last saved: <?php echo date('d M Y, h:i A', strtotime($policy['last_updated'])); ?>
                                                    </span>
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">Policy Content</label>
                                                    <textarea class="form-control summernote" name="content" id="short_desc" rows="15"><?php echo $policy['content']; ?></textarea>
                                                </div>

                                                <!-- Content Tips -->
                                                <div class="alert alert-info mt-3">
                                                    <h6><i class="fas fa-lightbulb mr-2"></i> Content Tips:</h6>
                                                    <ul class="mb-0 pl-3">
                                                        <li>Use clear headings (H2, H3) for sections</li>
                                                        <li>Keep paragraphs short and readable</li>
                                                        <li>Use bullet points for lists</li>
                                                        <li>Include contact information if needed</li>
                                                        <li>Update the effective date if needed</li>
                                                    </ul>
                                                </div>
                                            </div>

                                            <!-- Preview -->
                                            <div class="preview-box" id="previewBox">
                                                <h6 class="mb-3">Content Preview:</h6>
                                                <div id="contentPreview">
                                                    <?php echo $policy['content']; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Hidden Fields -->
                                    <input type="hidden" name="policy_id" value="<?php echo $policy['id']; ?>">

                                    <!-- Action Buttons -->
                                    <div class="mt-4 pt-3 border-top">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <a href="view-policies.php" class="btn btn-secondary">
                                                    <i class="fas fa-times mr-2"></i> Cancel
                                                </a>
                                                <a href="preview-policy.php?slug=<?php echo urlencode($policy['slug']); ?>" target="_blank" class="btn btn-info ml-2">
                                                    <i class="fas fa-eye mr-2"></i> Preview
                                                </a>
                                            </div>
                                            <div>
                                                <button type="button" class="btn btn-outline-secondary mr-2" onclick="resetToOriginal()">
                                                    <i class="fas fa-history mr-1"></i> Reset Changes
                                                </button>
                                                <button type="submit" name="update_policy" class="btn btn-primary">
                                                    <i class="fas fa-save mr-2"></i> Update Policy
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
        // Store original values
        const originalValues = {
            title: "<?php echo addslashes($policy['title']); ?>",
            slug: "<?php echo addslashes($policy['slug']); ?>",
            content: `<?php echo addslashes($policy['content']); ?>`,
            meta_title: "<?php echo addslashes($policy['meta_title']); ?>",
            meta_description: "<?php echo addslashes($policy['meta_description']); ?>",
            meta_keywords: "<?php echo addslashes($policy['meta_keywords']); ?>",
            status: <?php echo $policy['status']; ?>,
            display_order: <?php echo $policy['display_order']; ?>
        };

        // Initialize Summernote
        $(document).ready(function() {
            CKEDITOR.replace('short_desc');


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
                    const max = input.maxLength || 255;
                    counter.textContent = input.value.length + '/' + max;

                    input.addEventListener('input', function() {
                        const max = this.maxLength || 255;
                        counter.textContent = this.value.length + '/' + max;
                    });
                }
            });

            // Slug handling
            const titleInput = document.getElementById('policyTitle');
            const slugInput = document.getElementById('slugInput');
            const slugPreview = document.getElementById('slugPreview');
            const slugWarning = document.getElementById('slugWarning');

            // Show warning if slug differs from original
            slugInput.addEventListener('input', function() {
                slugPreview.textContent = this.value || 'slug-will-appear-here';

                if (this.value !== originalValues.slug) {
                    slugWarning.style.display = 'block';
                } else {
                    slugWarning.style.display = 'none';
                }
            });

            // Auto-generate slug from title only if slug hasn't been manually modified
            let slugManuallyChanged = false;
            titleInput.addEventListener('input', function() {
                if (!slugManuallyChanged && slugInput.value === originalValues.slug) {
                    const slug = this.value.toLowerCase()
                        .replace(/[^\w\s-]/g, '')
                        .replace(/[\s_-]+/g, '-')
                        .replace(/^-+|-+$/g, '');
                    slugInput.value = slug;
                    slugPreview.textContent = slug || 'slug-will-appear-here';

                    if (slug !== originalValues.slug) {
                        slugWarning.style.display = 'block';
                    }
                }
            });

            slugInput.addEventListener('change', function() {
                slugManuallyChanged = true;
            });

            // Update preview function
            function updatePreview(content) {
                document.getElementById('contentPreview').innerHTML = content;
            }



            // Form validation
            const form = document.getElementById('editPolicyForm');
            form.addEventListener('submit', function(e) {
                const title = document.querySelector('input[name="title"]').value.trim();
                const slug = document.querySelector('input[name="slug"]').value.trim();
                const content = $('.summernote').summernote('code').trim();

                if (!title) {
                    e.preventDefault();
                    showNotification('Please enter a policy title.', 'error');
                    return false;
                }

                if (!slug) {
                    e.preventDefault();
                    showNotification('Please enter a slug URL.', 'error');
                    return false;
                }

                if (!content || content === '<p><br></p>') {
                    e.preventDefault();
                    showNotification('Please enter policy content.', 'error');
                    return false;
                }

                // Confirm slug change warning
                if (slug !== originalValues.slug && !confirm('You are changing the slug URL. This may break existing links. Are you sure you want to continue?')) {
                    e.preventDefault();
                    return false;
                }

                return true;
            });

            // Reset to original values
            window.resetToOriginal = function() {
                if (confirm('Reset all changes to original values?')) {
                    document.querySelector('input[name="title"]').value = originalValues.title;
                    document.querySelector('input[name="slug"]').value = originalValues.slug;
                    $('.summernote').summernote('code', originalValues.content);
                    document.querySelector('input[name="meta_title"]').value = originalValues.meta_title;
                    document.querySelector('textarea[name="meta_description"]').value = originalValues.meta_description;
                    document.querySelector('input[name="meta_keywords"]').value = originalValues.meta_keywords;
                    document.querySelector('select[name="status"]').value = originalValues.status;
                    document.querySelector('input[name="display_order"]').value = originalValues.display_order;

                    // Reset counters
                    Object.keys(inputs).forEach(inputName => {
                        const input = document.querySelector(`[name="${inputName}"]`);
                        const counter = document.getElementById(inputs[inputName]);
                        if (input && counter) {
                            const max = input.maxLength || 255;
                            counter.textContent = input.value.length + '/' + max;
                        }
                    });

                    // Reset slug preview and warning
                    slugPreview.textContent = originalValues.slug;
                    slugWarning.style.display = 'none';
                    slugManuallyChanged = false;

                    updatePreview(originalValues.content);
                    showNotification('Reset to original values', 'success');
                }
            };

            // Auto-save functionality (optional)
            let autoSaveTimer;

            function setupAutoSave() {
                const formElements = form.querySelectorAll('input, textarea, select');

                formElements.forEach(element => {
                    element.addEventListener('change', function() {
                        if (autoSaveTimer) clearTimeout(autoSaveTimer);

                        autoSaveTimer = setTimeout(() => {
                            // Optional: Implement AJAX auto-save here
                            // showNotification('Changes saved automatically', 'info');
                        }, 3000);
                    });
                });
            }

            // Notification function
            function showNotification(message, type = 'info') {
                // Create notification element
                const notification = document.createElement('div');
                notification.className = `alert alert-${type} alert-dismissible fade show`;
                notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
                notification.innerHTML = `
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} mr-2"></i>
                    ${message}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                `;

                document.body.appendChild(notification);

                // Auto-remove after 3 seconds
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 3000);
            }

            // Initialize auto-save
            setupAutoSave();
        });
    </script>
</body>

</html>