<?php
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';
require_once __DIR__ . '/models/setting.php';

// Initialize
$setting = new Setting($conn);

// CSRF Protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle profile updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }

    $admin_id = $_SESSION['admin_id'];
    $new_username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $notes = isset($_POST['notes']) ? mysqli_real_escape_string($conn, $_POST['notes']) : '';
    
    // Fetch current admin data
    $stmt = $conn->prepare("SELECT password FROM admin_user WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();
    
    // Verify current password
    if (password_verify($current_password, $hashed_password)) {
        // Update profile information
        $update_stmt = $conn->prepare("UPDATE admin_user SET 
                                     username = ?, email = ?, first_name = ?, last_name = ?, notes = ?, updated_at = NOW() 
                                     WHERE id = ?");
        $update_stmt->bind_param("sssssi", $new_username, $email, $first_name, $last_name, $notes, $admin_id);
        $update_stmt->execute();
        
        // Update password if new password provided
        if (!empty($new_password)) {
            if (strlen($new_password) < 8) {
                $error_message = "Password must be at least 8 characters";
            } else {
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_pass_stmt = $conn->prepare("UPDATE admin_user SET 
                                                  password = ?, password_changed_at = NOW() 
                                                  WHERE id = ?");
                $update_pass_stmt->bind_param("si", $new_hashed_password, $admin_id);
                $update_pass_stmt->execute();
                $update_pass_stmt->close();
                
                if ($update_stmt->affected_rows > 0) {
                    $success_message = "Profile and password updated successfully!";
                }
            }
        } else {
            if ($update_stmt->affected_rows > 0) {
                $success_message = "Profile updated successfully!";
            }
        }
        
        $update_stmt->close();
        
        // Update session data
        $_SESSION['admin_user'] = $new_username;
        $_SESSION['admin_email'] = $email;
        $_SESSION['admin_name'] = $first_name . ' ' . $last_name;
        
    } else {
        $error_message = "Current password is incorrect!";
    }
}

// Fetch current admin data
$admin_id = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT 
    username, 
    email, 
    first_name, 
    last_name, 
    role, 
    status, 
    last_login, 
    created_at
    FROM admin_user WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result(
    $username, 
    $email, 
    $first_name, 
    $last_name, 
    $role, 
    $status, 
    $last_login, 
    $created_at
);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Admin Profile | Beastline</title>
    <link rel="icon" href="<?php echo htmlspecialchars($setting->get('favicon', 'assets/img/logo.png')); ?>" type="image/png">

    <?php include "links.php"; ?>
    
    <style>
        :root {
            --primary-red: #dc3545;
            --dark-red: #c82333;
            --light-red: #f8d7da;
            --white: #ffffff;
            --black: #212529;
            --light-gray: #f8f9fa;
            --gray: #6c757d;
            --border-color: #dee2e6;
        }
        
        body {
            background-color: var(--white);
            color: var(--black);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Header */
        .profile-header {
            background-color: var(--white);
            border-bottom: 3px solid var(--primary-red);
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-red), var(--dark-red));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: var(--white);
            font-weight: bold;
            border: 3px solid var(--white);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        /* Cards */
        .simple-card {
            background-color: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .card-header {
            background-color: var(--white);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 1.25rem;
        }
        
        .card-header h5 {
            color: var(--black);
            font-weight: 600;
            margin: 0;
        }
        
        .card-body {
            padding: 1.25rem;
        }
        
        /* Form */
        .form-label {
            font-weight: 600;
            color: var(--black);
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border: 1px solid var(--border-color);
            border-radius: 5px;
            padding: 0.75rem;
            color: var(--black);
            background-color: var(--white);
        }
        
        .form-control:focus {
            border-color: var(--primary-red);
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.15);
        }
        
        /* Buttons */
        .btn-red {
            background-color: var(--primary-red);
            border: none;
            color: var(--white);
            padding: 0.75rem 2rem;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-red:hover {
            background-color: var(--dark-red);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
        }
        
        .btn-outline-red {
            background-color: transparent;
            border: 1px solid var(--primary-red);
            color: var(--primary-red);
            padding: 0.5rem 1.5rem;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-outline-red:hover {
            background-color: var(--primary-red);
            color: var(--white);
        }
        
        /* Badges */
        .badge-red {
            background-color: var(--light-red);
            color: var(--primary-red);
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .badge-success {
            background-color: #d4edda;
            color: #155724;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        /* Info Display */
        .info-row {
            display: flex;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--black);
            width: 150px;
            min-width: 150px;
        }
        
        .info-value {
            color: var(--gray);
            flex: 1;
        }
        
        /* Alerts */
        .alert {
            border-radius: 5px;
            border: 1px solid transparent;
        }
        
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .alert-danger {
            background-color: var(--light-red);
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .info-row {
                flex-direction: column;
            }
            
            .info-label {
                width: 100%;
                margin-bottom: 0.25rem;
            }
            
            .profile-avatar {
                width: 80px;
                height: 80px;
                font-size: 28px;
            }
        }
        
        /* Password Toggle */
        .password-toggle {
            cursor: pointer;
            background-color: var(--white);
            border: 1px solid var(--border-color);
            border-left: none;
        }
        
        .password-toggle:hover {
            background-color: var(--light-gray);
        }
        
        /* Divider */
        .divider {
            height: 1px;
            background-color: var(--border-color);
            margin: 2rem 0;
        }
    </style>
</head>

<body class="crm_body_bg">

    <?php include "includes/header.php"; ?>
    
    <section class="main_content dashboard_part">
        <div class="container-fluid g-0">
            <div class="row">
                <div class="col-lg-12 p-0">
                    <?php include "includes/top_nav.php"; ?>
                </div>
            </div>
        </div>

        <div class="main_content_iner">
            <div class="container-fluid p-0">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <!-- Header -->
                        <div class="profile-header text-center">
                            <div class="profile-avatar mx-auto mb-3">
                                <?= strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)) ?>
                            </div>
                            <h2 class="mb-2"><?= htmlspecialchars($first_name . ' ' . $last_name) ?></h2>
                            <p class="mb-1">
                                <span class="badge-<?= $role === 'super_admin' ? 'red' : 'success' ?> me-2">
                                    <?= ucfirst(str_replace('_', ' ', $role)) ?>
                                </span>
                                <span class="badge-<?= $status === 'active' ? 'success' : 'red' ?>">
                                    <?= ucfirst($status) ?>
                                </span>
                            </p>
                            <p class="text-muted">Member since <?= date('M Y', strtotime($created_at)) ?></p>
                        </div>
                        
                        <!-- Alerts -->
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error_message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                                <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($success_message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <!-- Left Column - Basic Info -->
                            <div class="col-lg-6 mb-4">
                                <div class="simple-card">
                                    <div class="card-header">
                                        <h5><i class="fas fa-user me-2"></i>Basic Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="info-row">
                                            <div class="info-label">Username</div>
                                            <div class="info-value"><?= htmlspecialchars($username) ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Email</div>
                                            <div class="info-value"><?= htmlspecialchars($email) ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Full Name</div>
                                            <div class="info-value"><?= htmlspecialchars($first_name . ' ' . $last_name) ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Role</div>
                                            <div class="info-value">
                                                <span class="badge-<?= $role === 'super_admin' ? 'red' : 'success' ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $role)) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Status</div>
                                            <div class="info-value">
                                                <span class="badge-<?= $status === 'active' ? 'success' : 'red' ?>">
                                                    <?= ucfirst($status) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Last Login</div>
                                            <div class="info-value">
                                                <?= $last_login ? date('M j, Y H:i', strtotime($last_login)) : 'Never' ?>
                                            </div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Account Created</div>
                                            <div class="info-value"><?= date('M j, Y', strtotime($created_at)) ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right Column - Update Form -->
                            <div class="col-lg-6 mb-4">
                                <div class="simple-card">
                                    <div class="card-header">
                                        <h5><i class="fas fa-edit me-2"></i>Edit Profile</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="post" action="">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">First Name</label>
                                                    <input type="text" class="form-control" 
                                                        name="first_name" value="<?= htmlspecialchars($first_name) ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Last Name</label>
                                                    <input type="text" class="form-control" 
                                                        name="last_name" value="<?= htmlspecialchars($last_name) ?>" required>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Username</label>
                                                <input type="text" class="form-control" 
                                                    name="username" value="<?= htmlspecialchars($username) ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Email Address</label>
                                                <input type="email" class="form-control" 
                                                    name="email" value="<?= htmlspecialchars($email) ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Notes (Optional)</label>
                                                <textarea class="form-control" 
                                                    name="notes" rows="2" placeholder="Add any notes about your account"><?= htmlspecialchars($notes ?? '') ?></textarea>
                                            </div>
                                            
                                            <div class="divider"></div>
                                            
                                            <h6 class="mb-3">Change Password (Optional)</h6>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Current Password</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" 
                                                        id="current_password" name="current_password">
                                                    <span class="input-group-text password-toggle" onclick="togglePassword('current_password')">
                                                        <i class="fas fa-eye"></i>
                                                    </span>
                                                </div>
                                                <small class="text-muted">Required to save changes</small>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">New Password</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" 
                                                        id="new_password" name="new_password">
                                                    <span class="input-group-text password-toggle" onclick="togglePassword('new_password')">
                                                        <i class="fas fa-eye"></i>
                                                    </span>
                                                </div>
                                                <small class="text-muted">Leave blank to keep current password</small>
                                            </div>
                                            
                                            <div class="mb-4">
                                                <label class="form-label">Confirm New Password</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" 
                                                        id="confirm_password" name="confirm_password">
                                                    <span class="input-group-text password-toggle" onclick="togglePassword('confirm_password')">
                                                        <i class="fas fa-eye"></i>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between">
                                                <button type="button" class="btn btn-outline-red" onclick="window.location.reload()">
                                                    <i class="fas fa-times me-2"></i>Cancel
                                                </button>
                                                <button type="submit" name="update_profile" class="btn btn-red">
                                                    <i class="fas fa-save me-2"></i>Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include "includes/footer.php"; ?>
    </section>

    <script>
        // Toggle password visibility
        function togglePassword(id) {
            const password = document.getElementById(id);
            const icon = password.nextElementSibling.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            // Check password match
            function checkPasswordMatch() {
                if (newPassword.value && confirmPassword.value) {
                    if (newPassword.value !== confirmPassword.value) {
                        alert("New passwords don't match!");
                        return false;
                    }
                    if (newPassword.value.length < 8) {
                        alert("New password must be at least 8 characters!");
                        return false;
                    }
                }
                return true;
            }
            
            form.addEventListener('submit', function(e) {
                // Check current password
                const currentPassword = document.getElementById('current_password');
                if (!currentPassword.value) {
                    e.preventDefault();
                    alert("Please enter your current password to save changes");
                    return;
                }
                
                // Check new password
                if (!checkPasswordMatch()) {
                    e.preventDefault();
                }
            });
            
            // Live password match check
            if (newPassword && confirmPassword) {
                newPassword.addEventListener('input', function() {
                    if (confirmPassword.value && this.value !== confirmPassword.value) {
                        confirmPassword.style.borderColor = 'var(--primary-red)';
                    } else {
                        confirmPassword.style.borderColor = '';
                    }
                });
                
                confirmPassword.addEventListener('input', function() {
                    if (newPassword.value && this.value !== newPassword.value) {
                        this.style.borderColor = 'var(--primary-red)';
                    } else {
                        this.style.borderColor = '';
                    }
                });
            }
        });
    </script>

</body>
</html>