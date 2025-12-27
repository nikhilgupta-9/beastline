<?php include('auth_check.php'); ?>
<?php
include "db-conn.php";

// Check if current user has permission to create admins
// if ($_SESSION['role'] !== 'super_admin') {
//     $_SESSION['error'] = "You don't have permission to create admin users!";
//     header("Location: dashboard.php");
//     exit();
// }

if (isset($_POST['createLogin'])) {
    // Get and sanitize input
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $email = trim($_POST['email']);
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $role = trim($_POST['role']);
    $notes = trim($_POST['notes']);

    // Input validation
    $errors = [];
    
    if (empty($username) || empty($password) || empty($email)) {
        $errors[] = "Username, password, and email are required!";
    }

    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long!";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address!";
    }

    // Check if the username already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_user WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $errors[] = "Username or email already exists!";
    }

    // If there are errors, show them
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: admin-create.php");
        exit();
    }

    // Secure password hashing
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $currentTime = date('Y-m-d H:i:s');
    $createdBy = $_SESSION['username'];

    // Insert new admin user securely
    $stmt = $conn->prepare("INSERT INTO admin_user 
        (username, password, email, first_name, last_name, role, status, created_at, updated_at, created_by, notes) 
        VALUES (?, ?, ?, ?, ?, ?, 'active', ?, ?, ?, ?)");
    
    $stmt->bind_param("ssssssssss", 
        $username, 
        $hashedPassword, 
        $email, 
        $firstName, 
        $lastName, 
        $role, 
        $currentTime, 
        $currentTime, 
        $createdBy, 
        $notes
    );

    if ($stmt->execute()) {
        $_SESSION['success'] = "Admin user created successfully!";
        header("Location: all-admin.php");
        exit();
    } else {
        error_log("Database Error: " . $stmt->error);
        $_SESSION['error'] = "Failed to create admin user.";
        header("Location: admin-create.php");
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Create Admin User | Admin Panel</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">

    <?php include "links.php"; ?>
    
    <style>
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            margin: 2px;
        }
        
        .badge-super-admin { background: #dc3545; color: white; }
        .badge-admin { background: #007bff; color: white; }
        .badge-editor { background: #6f42c1; color: white; }
        .badge-viewer { background: #6c757d; color: white; }
        
        .password-strength {
            height: 5px;
            border-radius: 5px;
            margin-top: 5px;
            transition: all 0.3s ease;
            display: none;
        }
        
        .strength-weak { background: #dc3545; width: 25%; }
        .strength-medium { background: #ffc107; width: 50%; }
        .strength-strong { background: #28a745; width: 75%; }
        .strength-very-strong { background: #20c997; width: 100%; }
        
        .info-icon {
            color: #6c757d;
            cursor: help;
            margin-left: 5px;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            padding: 5px 0;
            display: flex;
            align-items: center;
        }
        
        .feature-list li i {
            color: #28a745;
            margin-right: 10px;
            font-size: 14px;
        }
    </style>
</head>

<body class="crm_body_bg">

    <?php include "header.php"; ?>
    
    <section class="main_content dashboard_part large_header_bg">
        <div class="container-fluid g-0">
            <div class="row">
                <div class="col-lg-12 p-0">
                    <?php include "top_nav.php"; ?>
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
                                        <h2 class="m-0">Create Admin User</h2>
                                    </div>
                                   
                                </div>
                            </div>
                            <div class="white_card_body">
                                <div class="row">
                                    <div class="col-lg-8">
                                        <div class="admin-form-container">
                                            <h4 class="card-title mb-4">Admin User Information</h4>
                                            
                                            <!-- Display success or error messages -->
                                            <?php if (isset($_SESSION['success'])): ?>
                                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                                    <i class="fas fa-check-circle me-2"></i>
                                                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (isset($_SESSION['error'])): ?>
                                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <form action="" method="POST" id="adminCreateForm">
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="first_name" class="form-label">First Name</label>
                                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                                               placeholder="Enter first name" value="<?= $_POST['first_name'] ?? '' ?>">
                                                    </div>
                                                    
                                                    <div class="col-md-6 mb-3">
                                                        <label for="last_name" class="form-label">Last Name</label>
                                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                                               placeholder="Enter last name" value="<?= $_POST['last_name'] ?? '' ?>">
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="username" class="form-label">
                                                            Username 
                                                            <i class="fas fa-info-circle info-icon" 
                                                               title="Unique username for login"></i>
                                                        </label>
                                                        <input type="text" class="form-control" id="username" name="username" 
                                                               placeholder="Enter username" required 
                                                               value="<?= $_POST['username'] ?? '' ?>">
                                                        <small class="form-text text-muted">Must be unique and at least 3 characters</small>
                                                    </div>
                                                    
                                                    <div class="col-md-6 mb-3">
                                                        <label for="email" class="form-label">
                                                            Email Address
                                                            <i class="fas fa-info-circle info-icon" 
                                                               title="Used for notifications and password recovery"></i>
                                                        </label>
                                                        <input type="email" class="form-control" id="email" name="email" 
                                                               placeholder="Enter email address" required 
                                                               value="<?= $_POST['email'] ?? '' ?>">
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="password" class="form-label">
                                                        Password 
                                                        <i class="fas fa-info-circle info-icon" 
                                                           title="Minimum 8 characters with mix of letters, numbers, and symbols"></i>
                                                    </label>
                                                    <input type="password" class="form-control" id="password" name="password" 
                                                           placeholder="Choose a strong password" required 
                                                           minlength="8">
                                                    <div class="password-strength" id="passwordStrength"></div>
                                                    <small class="form-text text-muted" id="passwordFeedback"></small>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="role" class="form-label">
                                                        User Role
                                                        <i class="fas fa-info-circle info-icon" 
                                                           title="Define the level of access for this user"></i>
                                                    </label>
                                                    <select class="form-select" id="role" name="role" required>
                                                        <option value="">Select a role</option>
                                                        <option value="super_admin" <?= ($_POST['role'] ?? '') == 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                                                        <option value="admin" <?= ($_POST['role'] ?? '') == 'admin' ? 'selected' : '' ?>>Admin</option>
                                                        <option value="editor" <?= ($_POST['role'] ?? '') == 'editor' ? 'selected' : '' ?>>Editor</option>
                                                        <option value="viewer" <?= ($_POST['role'] ?? '') == 'viewer' ? 'selected' : '' ?>>Viewer</option>
                                                    </select>
                                                    
                                                    <div class="mt-2">
                                                        <small class="text-muted">
                                                            <strong>Role Permissions:</strong>
                                                            <span class="role-badge badge-super-admin">Full Access</span>
                                                            <span class="role-badge badge-admin">Manage Content</span>
                                                            <span class="role-badge badge-editor">Edit Content</span>
                                                            <span class="role-badge badge-viewer">View Only</span>
                                                        </small>
                                                    </div>
                                                </div>

                                                <div class="mb-4">
                                                    <label for="notes" class="form-label">
                                                        Additional Notes
                                                        <i class="fas fa-info-circle info-icon" 
                                                           title="Optional notes about this user"></i>
                                                    </label>
                                                    <textarea class="form-control" id="notes" name="notes" 
                                                              rows="3" placeholder="Enter any additional notes or information"><?= $_POST['notes'] ?? '' ?></textarea>
                                                </div>

                                                <div class="d-flex gap-2">
                                                    <a href="all-admin.php" class="btn btn-secondary">
                                                        <i class="fas fa-arrow-left me-2"></i>Back to Users
                                                    </a>
                                                    <button type="submit" class="btn btn-primary" name="createLogin">
                                                        <i class="fas fa-user-plus me-2"></i>Create Admin User
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <div class="col-lg-4">
                                        <div class="info-sidebar">
                                            <h4 class="card-title mb-4">Security Features</h4>
                                            <div class="card">
                                                <div class="card-body">
                                                    <ul class="feature-list">
                                                        <li>
                                                            <i class="fas fa-check"></i>
                                                            <span>Secure password hashing with bcrypt</span>
                                                        </li>
                                                        <li>
                                                            <i class="fas fa-check"></i>
                                                            <span>Role-based access control</span>
                                                        </li>
                                                        <li>
                                                            <i class="fas fa-check"></i>
                                                            <span>Login attempt tracking</span>
                                                        </li>
                                                        <li>
                                                            <i class="fas fa-check"></i>
                                                            <span>Account activity monitoring</span>
                                                        </li>
                                                        <li>
                                                            <i class="fas fa-check"></i>
                                                            <span>Email verification ready</span>
                                                        </li>
                                                        <li>
                                                            <i class="fas fa-check"></i>
                                                            <span>Password strength enforcement</span>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                            
                                            <div class="card mt-4">
                                                <div class="card-body">
                                                    <h6 class="card-title">Role Descriptions</h6>
                                                    <div class="mb-2">
                                                        <span class="role-badge badge-super-admin">Super Admin</span>
                                                        <small class="text-muted d-block">Full system access, can manage all users</small>
                                                    </div>
                                                    <div class="mb-2">
                                                        <span class="role-badge badge-admin">Admin</span>
                                                        <small class="text-muted d-block">Manage content and settings</small>
                                                    </div>
                                                    <div class="mb-2">
                                                        <span class="role-badge badge-editor">Editor</span>
                                                        <small class="text-muted d-block">Edit existing content only</small>
                                                    </div>
                                                    <div class="mb-2">
                                                        <span class="role-badge badge-viewer">Viewer</span>
                                                        <small class="text-muted d-block">Read-only access to dashboard</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include "footer.php"; ?>
    </section>

    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            var password = this.value;
            var strengthBar = document.getElementById('passwordStrength');
            var feedback = document.getElementById('passwordFeedback');
            
            var strength = 0;
            var feedbackText = '';
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++;
            
            switch(strength) {
                case 0:
                    strengthBar.className = 'password-strength';
                    feedbackText = 'Enter a password';
                    break;
                case 1:
                    strengthBar.className = 'password-strength strength-weak';
                    feedbackText = 'Weak password';
                    break;
                case 2:
                    strengthBar.className = 'password-strength strength-medium';
                    feedbackText = 'Medium strength password';
                    break;
                case 3:
                    strengthBar.className = 'password-strength strength-strong';
                    feedbackText = 'Strong password';
                    break;
                case 4:
                    strengthBar.className = 'password-strength strength-very-strong';
                    feedbackText = 'Very strong password';
                    break;
            }
            
            strengthBar.style.display = password ? 'block' : 'none';
            feedback.textContent = feedbackText;
        });

        // Form validation
        document.getElementById('adminCreateForm').addEventListener('submit', function(e) {
            var password = document.getElementById('password').value;
            var username = document.getElementById('username').value;
            var role = document.getElementById('role').value;
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return false;
            }
            
            if (username.length < 3) {
                e.preventDefault();
                alert('Username must be at least 3 characters long!');
                return false;
            }
            
            if (!role) {
                e.preventDefault();
                alert('Please select a role for the user!');
                return false;
            }
        });

        // Add tooltip functionality
        document.querySelectorAll('.info-icon').forEach(function(icon) {
            icon.addEventListener('mouseover', function() {
                var title = this.getAttribute('title');
                // You can enhance this with a custom tooltip if needed
            });
        });
    </script>

</body>
</html>