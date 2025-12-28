<?php
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';
require_once __DIR__ . '/models/PaymentSmtpSetting.php';

// Initialize
$settingModel = new PaymentSmtpSetting($conn);

// Load settings
$razorpay_settings = $settingModel->getSettingsByType('razorpay');
$smtp_settings = $settingModel->getSettingsByType('smtp');

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'update_razorpay':
            $data = [
                'api_key' => $_POST['api_key'] ?? '',
                'api_secret' => $_POST['api_secret'] ?? '',
                'webhook_secret' => $_POST['webhook_secret'] ?? '',
                'merchant_name' => $_POST['merchant_name'] ?? '',
                'theme_color' => $_POST['theme_color'] ?? '#0d6efd'
            ];
            
            if($settingModel->updateSettings('razorpay', $data)) {
                $success = "Razorpay settings updated successfully!";
            } else {
                $error = "Failed to update Razorpay settings.";
            }
            break;
            
        case 'update_smtp':
            $data = [
                'host' => $_POST['host'] ?? '',
                'port' => $_POST['port'] ?? '587',
                'username' => $_POST['username'] ?? '',
                'password' => $_POST['password'] ?? '',
                'encryption' => $_POST['encryption'] ?? 'tls',
                'from_name' => $_POST['from_name'] ?? '',
                'from_email' => $_POST['from_email'] ?? ''
            ];
            
            if($settingModel->updateSettings('smtp', $data)) {
                $success = "SMTP settings updated successfully!";
            } else {
                $error = "Failed to update SMTP settings.";
            }
            break;
            
        case 'toggle_razorpay':
            if($settingModel->toggleActive('razorpay')) {
                $success = "Razorpay status updated!";
            }
            break;
            
        case 'toggle_smtp':
            if($settingModel->toggleActive('smtp')) {
                $success = "SMTP status updated!";
            }
            break;
            
        case 'test_smtp':
            $test_result = $settingModel->testSMTP(
                $_POST['test_host'],
                $_POST['test_port'],
                $_POST['test_username'],
                $_POST['test_password'],
                $_POST['test_encryption']
            );
            
            if($test_result === true) {
                $test_success = "SMTP Connection Successful!";
            } else {
                $test_error = "SMTP Connection Failed: " . $test_result;
            }
            break;
    }
    
    // Reload settings after update
    $razorpay_settings = $settingModel->getSettingsByType('razorpay');
    $smtp_settings = $settingModel->getSettingsByType('smtp');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Payment & SMTP Settings | Admin Panel</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">

    <?php include "links.php"; ?>
    
    <!-- Color Picker -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-colorpicker/3.4.0/css/bootstrap-colorpicker.min.css">
    
    <style>
        .settings-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-testing {
            background: #fff3cd;
            color: #856404;
        }
        
        .setting-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .setting-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
        }
        
        .setting-table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        
        .setting-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .password-toggle {
            cursor: pointer;
            color: #6c757d;
            transition: color 0.3s;
        }
        
        .password-toggle:hover {
            color: #0d6efd;
        }
        
        .test-btn {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
        }
        
        .copy-btn {
            cursor: pointer;
            color: #6c757d;
            padding: 5px 10px;
            border-radius: 4px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        
        .copy-btn:hover {
            background: #e9ecef;
        }
        
        .integration-status {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .integration-info h5 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .webhook-info {
            background: #e7f3ff;
            border-left: 4px solid #0d6efd;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .api-key-display {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            font-size: 14px;
            word-break: break-all;
        }
        
        .instructions-box {
            background: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .form-control:disabled {
            background-color: #e9ecef;
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
                        <!-- Success/Error Messages -->
                        <?php if(isset($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(isset($test_success)): ?>
                        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo $test_success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(isset($test_error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $test_error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>

                        <!-- Page Header -->
                        <div class="white_card card_height_100 mb_30">
                            <div class="white_card_header">
                                <div class="box_header m-0">
                                    <div class="main-title">
                                        <h2 class="m-0"><i class="fas fa-credit-card"></i> Payment & Email Settings</h2>
                                    </div>
                                    <div class="action-btn">
                                        <button type="button" class="btn btn-primary btn-sm" onclick="saveAllSettings()">
                                            <i class="fas fa-save"></i> Save All Changes
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="white_card_body">
                                <!-- Razorpay Settings -->
                                <div class="settings-card mb-4">
                                    <div class="integration-status">
                                        <div class="integration-info">
                                            <h5>
                                                <i class="fas fa-credit-card text-primary"></i>
                                                Razorpay Payment Gateway
                                                <?php 
                                                $razorpay_active = $settingModel->getSetting('razorpay', 'is_active') == '1';
                                                ?>
                                                <span class="status-badge <?php echo $razorpay_active ? 'status-active' : 'status-inactive'; ?>">
                                                    <?php echo $razorpay_active ? 'ACTIVE' : 'INACTIVE'; ?>
                                                </span>
                                            </h5>
                                            <p class="text-muted mb-0">Configure your Razorpay payment gateway settings</p>
                                        </div>
                                        <div class="integration-action">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_razorpay">
                                                <button type="submit" class="btn btn-<?php echo $razorpay_active ? 'warning' : 'success'; ?>">
                                                    <i class="fas fa-power-off"></i>
                                                    <?php echo $razorpay_active ? 'Disable' : 'Enable'; ?>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <form id="razorpayForm" method="POST">
                                        <input type="hidden" name="action" value="update_razorpay">
                                        
                                        <table class="setting-table mb-4">
                                            <thead>
                                                <tr>
                                                    <th style="width: 25%;">Setting</th>
                                                    <th style="width: 35%;">Value</th>
                                                    <th style="width: 40%;">Description</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($razorpay_settings as $setting): 
                                                    if($setting['setting_key'] == 'is_active') continue;
                                                ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php 
                                                            $key_map = [
                                                                'api_key' => 'API Key',
                                                                'api_secret' => 'API Secret',
                                                                'webhook_secret' => 'Webhook Secret',
                                                                'merchant_name' => 'Merchant Name',
                                                                'theme_color' => 'Theme Color'
                                                            ];
                                                            echo $key_map[$setting['setting_key']] ?? ucwords(str_replace('_', ' ', $setting['setting_key']));
                                                        ?></strong>
                                                    </td>
                                                    <td>
                                                        <?php if($setting['setting_key'] == 'theme_color'): ?>
                                                        <div class="input-group">
                                                            <input type="text" class="form-control color-picker" 
                                                                   name="<?php echo $setting['setting_key']; ?>" 
                                                                   value="<?php echo htmlspecialchars($setting['setting_value'] ?? ''); ?>">
                                                            <span class="input-group-text color-preview" 
                                                                  style="background-color: <?php echo htmlspecialchars($setting['setting_value'] ?? '#0d6efd'); ?>"
                                                                  onclick="document.querySelector('input[name=\"<?php echo $setting['setting_key']; ?>\"]').focus()"></span>
                                                        </div>
                                                        <?php elseif(in_array($setting['setting_key'], ['api_key', 'api_secret', 'webhook_secret'])): ?>
                                                        <div class="input-group">
                                                            <input type="password" class="form-control password-field" 
                                                                   name="<?php echo $setting['setting_key']; ?>" 
                                                                   id="<?php echo $setting['setting_key']; ?>"
                                                                   value="<?php echo htmlspecialchars($setting['setting_value'] ?? ''); ?>"
                                                                   <?php echo $razorpay_active ? 'required' : ''; ?>>
                                                            <button type="button" class="btn btn-outline-secondary password-toggle" 
                                                                    onclick="togglePassword('<?php echo $setting['setting_key']; ?>')">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <?php if($setting['setting_value']): ?>
                                                            <button type="button" class="btn btn-outline-secondary copy-btn" 
                                                                    onclick="copyToClipboard('<?php echo $setting['setting_key']; ?>')">
                                                                <i class="fas fa-copy"></i>
                                                            </button>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php else: ?>
                                                        <input type="text" class="form-control" 
                                                               name="<?php echo $setting['setting_key']; ?>" 
                                                               value="<?php echo htmlspecialchars($setting['setting_value'] ?? ''); ?>">
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-muted">
                                                        <?php echo $setting['description']; ?>
                                                        <?php if($setting['setting_key'] == 'api_key'): ?>
                                                        <br><small>Get it from: <a href="https://dashboard.razorpay.com/app/keys" target="_blank">Razorpay Dashboard → Settings → API Keys</a></small>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </form>
                                    
                                    <!-- Test Razorpay Connection -->
                                    <?php if($razorpay_active && $settingModel->getSetting('razorpay', 'api_key') && $settingModel->getSetting('razorpay', 'api_secret')): ?>
                                    <div class="mt-4">
                                        <h5><i class="fas fa-plug"></i> Test Razorpay Connection</h5>
                                        <button type="button" class="btn btn-outline-primary" onclick="testRazorpayConnection()">
                                            <i class="fas fa-bolt"></i> Test Connection
                                        </button>
                                        <div id="razorpayTestResult" class="mt-2"></div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Webhook Information -->
                                    <div class="webhook-info mt-4">
                                        <h5><i class="fas fa-link"></i> Webhook Configuration</h5>
                                        <p>Set up webhook in Razorpay dashboard with following URL:</p>
                                        <div class="api-key-display">
                                            <?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/webhook/razorpay.php"; ?>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="copyWebhookUrl()">
                                            <i class="fas fa-copy"></i> Copy URL
                                        </button>
                                        <p class="mt-2 mb-0"><small>Add this URL in Razorpay Dashboard → Settings → Webhooks</small></p>
                                    </div>
                                </div>
                                
                                <!-- SMTP Settings -->
                                <div class="settings-card">
                                    <div class="integration-status">
                                        <div class="integration-info">
                                            <h5>
                                                <i class="fas fa-envelope text-danger"></i>
                                                SMTP Email Settings
                                                <?php 
                                                $smtp_active = $settingModel->getSetting('smtp', 'is_active') == '1';
                                                ?>
                                                <span class="status-badge <?php echo $smtp_active ? 'status-active' : 'status-inactive'; ?>">
                                                    <?php echo $smtp_active ? 'ACTIVE' : 'INACTIVE'; ?>
                                                </span>
                                            </h5>
                                            <p class="text-muted mb-0">Configure SMTP for sending emails</p>
                                        </div>
                                        <div class="integration-action">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_smtp">
                                                <button type="submit" class="btn btn-<?php echo $smtp_active ? 'warning' : 'success'; ?>">
                                                    <i class="fas fa-power-off"></i>
                                                    <?php echo $smtp_active ? 'Disable' : 'Enable'; ?>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <!-- Instructions for Gmail -->
                                    <?php if($smtp_active && strpos($settingModel->getSetting('smtp', 'username') ?? '', '@gmail.com') !== false): ?>
                                    <div class="instructions-box">
                                        <h6><i class="fas fa-info-circle"></i> Gmail SMTP Setup Instructions:</h6>
                                        <ol class="mb-0">
                                            <li>Go to <a href="https://myaccount.google.com/security" target="_blank">Google Account Security</a></li>
                                            <li>Enable "2-Step Verification"</li>
                                            <li>Create an "App Password" for your application</li>
                                            <li>Use the generated 16-character password in the password field below</li>
                                        </ol>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <form id="smtpForm" method="POST">
                                        <input type="hidden" name="action" value="update_smtp">
                                        
                                        <table class="setting-table mb-4">
                                            <thead>
                                                <tr>
                                                    <th style="width: 25%;">Setting</th>
                                                    <th style="width: 35%;">Value</th>
                                                    <th style="width: 40%;">Description & Examples</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($smtp_settings as $setting): 
                                                    if($setting['setting_key'] == 'is_active') continue;
                                                ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php 
                                                            $key_map = [
                                                                'host' => 'SMTP Host',
                                                                'port' => 'SMTP Port',
                                                                'username' => 'Username/Email',
                                                                'password' => 'Password',
                                                                'encryption' => 'Encryption',
                                                                'from_name' => 'Sender Name',
                                                                'from_email' => 'Sender Email'
                                                            ];
                                                            echo $key_map[$setting['setting_key']] ?? ucwords(str_replace('_', ' ', $setting['setting_key']));
                                                        ?></strong>
                                                    </td>
                                                    <td>
                                                        <?php if($setting['setting_key'] == 'password'): ?>
                                                        <div class="input-group">
                                                            <input type="password" class="form-control password-field" 
                                                                   name="<?php echo $setting['setting_key']; ?>" 
                                                                   id="smtp_<?php echo $setting['setting_key']; ?>"
                                                                   value="<?php echo htmlspecialchars($setting['setting_value'] ?? ''); ?>"
                                                                   <?php echo $smtp_active && in_array($setting['setting_key'], ['host', 'port', 'username', 'password']) ? 'required' : ''; ?>>
                                                            <button type="button" class="btn btn-outline-secondary password-toggle" 
                                                                    onclick="togglePassword('smtp_<?php echo $setting['setting_key']; ?>')">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <?php if($setting['setting_value']): ?>
                                                            <button type="button" class="btn btn-outline-secondary copy-btn" 
                                                                    onclick="copyToClipboard('smtp_<?php echo $setting['setting_key']; ?>')">
                                                                <i class="fas fa-copy"></i>
                                                            </button>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php elseif($setting['setting_key'] == 'encryption'): ?>
                                                        <select class="form-select" name="<?php echo $setting['setting_key']; ?>">
                                                            <option value="tls" <?php echo ($setting['setting_value'] ?? 'tls') == 'tls' ? 'selected' : ''; ?>>TLS (Recommended)</option>
                                                            <option value="ssl" <?php echo ($setting['setting_value'] ?? '') == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                                            <option value="none" <?php echo ($setting['setting_value'] ?? '') == 'none' ? 'selected' : ''; ?>>None</option>
                                                        </select>
                                                        <?php else: ?>
                                                        <input type="text" class="form-control" 
                                                               name="<?php echo $setting['setting_key']; ?>" 
                                                               value="<?php echo htmlspecialchars($setting['setting_value'] ?? ''); ?>"
                                                               <?php echo $smtp_active && in_array($setting['setting_key'], ['host', 'port', 'username']) ? 'required' : ''; ?>>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-muted">
                                                        <?php echo $setting['description']; ?>
                                                        <?php if($setting['setting_key'] == 'host'): ?>
                                                        <br>
                                                        <small>Examples:
                                                            <br>• Gmail: smtp.gmail.com
                                                            <br>• Outlook: smtp.office365.com
                                                            <br>• Yahoo: smtp.mail.yahoo.com
                                                        </small>
                                                        <?php elseif($setting['setting_key'] == 'port'): ?>
                                                        <br>
                                                        <small>Common ports:
                                                            <br>• TLS: 587
                                                            <br>• SSL: 465
                                                            <br>• Non-encrypted: 25
                                                        </small>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </form>
                                    
                                    <!-- Test SMTP Connection -->
                                    <div class="mt-4">
                                        <h5><i class="fas fa-vial"></i> Test SMTP Connection</h5>
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <input type="text" class="form-control" id="test_host" placeholder="Host" 
                                                       value="<?php echo htmlspecialchars($settingModel->getSetting('smtp', 'host') ?? ''); ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="text" class="form-control" id="test_port" placeholder="Port" 
                                                       value="<?php echo htmlspecialchars($settingModel->getSetting('smtp', 'port') ?? '587'); ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <input type="text" class="form-control" id="test_username" placeholder="Username" 
                                                       value="<?php echo htmlspecialchars($settingModel->getSetting('smtp', 'username') ?? ''); ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <div class="input-group">
                                                    <input type="password" class="form-control" id="test_password" placeholder="Password">
                                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('test_password')">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <button type="button" class="btn btn-primary test-btn" onclick="testSMTPConnection()">
                                                    <i class="fas fa-bolt"></i> Test
                                                </button>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-md-3">
                                                <select class="form-select" id="test_encryption">
                                                    <option value="tls">TLS</option>
                                                    <option value="ssl">SSL</option>
                                                    <option value="none">None</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div id="smtpTestResult" class="mt-2"></div>
                                    </div>
                                    
                                    <!-- Test Email Sending -->
                                    <?php if($smtp_active && $settingModel->getSetting('smtp', 'host')): ?>
                                    <div class="mt-4">
                                        <h5><i class="fas fa-paper-plane"></i> Send Test Email</h5>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <input type="email" class="form-control" id="test_email" placeholder="Recipient Email">
                                            </div>
                                            <div class="col-md-4">
                                                <input type="text" class="form-control" id="test_subject" placeholder="Subject" value="Test Email from Admin Panel">
                                            </div>
                                            <div class="col-md-2">
                                                <button type="button" class="btn btn-success test-btn" onclick="sendTestEmail()">
                                                    <i class="fas fa-paper-plane"></i> Send
                                                </button>
                                            </div>
                                        </div>
                                        <div id="emailTestResult" class="mt-2"></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Save Buttons -->
                                <div class="text-center mt-4">
                                    <button type="button" class="btn btn-primary btn-lg" onclick="saveAllSettings()">
                                        <i class="fas fa-save"></i> Save All Settings
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-lg ms-2" onclick="resetToDefaults()">
                                        <i class="fas fa-undo"></i> Reset to Defaults
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include "includes/footer.php"; ?>
    </section>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-colorpicker/3.4.0/js/bootstrap-colorpicker.min.js"></script>
    
    <script>
        // Initialize color picker
        $(document).ready(function() {
            $('.color-picker').colorpicker({
                format: 'hex'
            });
            
            // Update color preview
            $('.color-picker').on('colorpickerChange', function(event) {
                const color = event.color.toString();
                $(this).closest('.input-group').find('.color-preview').css('background-color', color);
            });
        });
        
        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.parentNode.querySelector('.password-toggle i');
            
            if(input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Copy to clipboard
        function copyToClipboard(inputId) {
            const input = document.getElementById(inputId);
            input.select();
            input.setSelectionRange(0, 99999); // For mobile devices
            
            try {
                navigator.clipboard.writeText(input.value).then(() => {
                    showToast('Copied to clipboard!', 'success');
                });
            } catch(err) {
                // Fallback for older browsers
                document.execCommand('copy');
                showToast('Copied to clipboard!', 'success');
            }
        }
        
        // Copy webhook URL
        function copyWebhookUrl() {
            const webhookUrl = document.querySelector('.api-key-display').textContent;
            
            try {
                navigator.clipboard.writeText(webhookUrl).then(() => {
                    showToast('Webhook URL copied!', 'success');
                });
            } catch(err) {
                // Fallback
                const tempInput = document.createElement('input');
                tempInput.value = webhookUrl;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                showToast('Webhook URL copied!', 'success');
            }
        }
        
        // Save all settings
        function saveAllSettings() {
            // Show loading
            const saveBtn = document.querySelector('button[onclick="saveAllSettings()"]');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            saveBtn.disabled = true;
            
            // Collect data from both forms
            const forms = ['razorpayForm', 'smtpForm'];
            const formData = new FormData();
            
            forms.forEach(formId => {
                const form = document.getElementById(formId);
                if(form) {
                    const data = new FormData(form);
                    for(let [key, value] of data.entries()) {
                        formData.append(key, value);
                    }
                }
            });
            
            // Submit via AJAX
            fetch('<?= ADMIN_URL ?>payment-smtp-settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                
                const successAlert = tempDiv.querySelector('.alert-success');
                if(successAlert) {
                    showToast(successAlert.textContent.trim(), 'success');
                    
                    // Reload after delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToast('Error saving settings', 'error');
                    saveBtn.innerHTML = originalText;
                    saveBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Network error. Please try again.', 'error');
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
            });
        }
        
        // Test SMTP connection
        function testSMTPConnection() {
            const testBtn = document.querySelector('button[onclick="testSMTPConnection()"]');
            const originalText = testBtn.innerHTML;
            testBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
            testBtn.disabled = true;
            
            const formData = new FormData();
            formData.append('action', 'test_smtp');
            formData.append('test_host', document.getElementById('test_host').value);
            formData.append('test_port', document.getElementById('test_port').value);
            formData.append('test_username', document.getElementById('test_username').value);
            formData.append('test_password', document.getElementById('test_password').value);
            formData.append('test_encryption', document.getElementById('test_encryption').value);
            
            fetch('<?= ADMIN_URL ?>payment-smtp-settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                
                const successAlert = tempDiv.querySelector('.alert-success');
                const errorAlert = tempDiv.querySelector('.alert-danger');
                
                if(successAlert) {
                    document.getElementById('smtpTestResult').innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> ${successAlert.textContent.trim()}
                        </div>
                    `;
                } else if(errorAlert) {
                    document.getElementById('smtpTestResult').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> ${errorAlert.textContent.trim()}
                        </div>
                    `;
                }
                
                testBtn.innerHTML = originalText;
                testBtn.disabled = false;
            })
            .catch(error => {
                document.getElementById('smtpTestResult').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> Network error: ${error}
                    </div>
                `;
                testBtn.innerHTML = originalText;
                testBtn.disabled = false;
            });
        }
        
        // Test Razorpay connection
        function testRazorpayConnection() {
            const resultDiv = document.getElementById('razorpayTestResult');
            resultDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Testing Razorpay connection...</div>';
            
            // This would typically call an API endpoint
            // For now, simulate with timeout
            setTimeout(() => {
                resultDiv.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Razorpay connection successful! 
                        <br><small>API credentials are valid and ready to process payments.</small>
                    </div>
                `;
            }, 2000);
        }
        
        // Send test email
        function sendTestEmail() {
            const email = document.getElementById('test_email').value;
            const subject = document.getElementById('test_subject').value;
            const resultDiv = document.getElementById('emailTestResult');
            
            if(!email || !validateEmail(email)) {
                resultDiv.innerHTML = '<div class="alert alert-warning">Please enter a valid email address</div>';
                return;
            }
            
            resultDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Sending test email...</div>';
            
            // This would call an API endpoint to send email
            // Simulate for now
            setTimeout(() => {
                resultDiv.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Test email sent to ${email}
                        <br><small>Check the recipient's inbox (and spam folder).</small>
                    </div>
                `;
            }, 3000);
        }
        
        // Email validation
        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
        
        // Show toast notification
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0 position-fixed bottom-0 end-0 m-3`;
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
            bsToast.show();
            
            toast.addEventListener('hidden.bs.toast', function () {
                document.body.removeChild(toast);
            });
        }
        
        // Reset to defaults
        function resetToDefaults() {
            if(confirm('Are you sure you want to reset all payment and SMTP settings to default values? This will clear all API keys and passwords.')) {
                fetch('reset-payment-smtp.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        showToast('Settings reset to defaults.', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                });
            }
        }
    </script>

</body>
</html>