<?php
// File: send_mail.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session for CSRF token validation
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../config/connect.php";

// Composer autoloader
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set JSON response header
header('Content-Type: application/json');

// Function to sanitize input
function clean_input($data)
{
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

// Function to log errors
function log_error($message) {
    error_log("Contact Form Error: " . $message);
}

try {
    // Check if form was submitted via POST
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method.");
    }

    // Validate CSRF token
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        throw new Exception("Security token validation failed.");
    }

    // Validate required fields - NOTE: Changed EMAIL to email
    $required = ['fname', 'phone', 'email', 'subject', 'message'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Please fill all required fields. Missing: " . $field);
        }
    }

    // Validate email format
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Please enter a valid email address.");
    }

    // Validate phone number (basic validation)
    if (!preg_match('/^[0-9]{10}$/', $_POST['phone'])) {
        throw new Exception("Please enter a valid 10-digit phone number.");
    }

    // Sanitize inputs - NOTE: Changed EMAIL to email
    $name = clean_input($_POST['fname']);
    $email = clean_input($_POST['email']); // Fixed: was EMAIL
    $phone = clean_input($_POST['phone']);
    $subject = clean_input($_POST['subject']);
    $message = clean_input($_POST['message']);

    // Fetch contact information from database
    $contactInfo = [];
    $contactQuery = "SELECT * FROM contacts LIMIT 1";
    $contactResult = mysqli_query($conn, $contactQuery);
    if ($contactResult && mysqli_num_rows($contactResult) > 0) {
        $contactInfo = mysqli_fetch_assoc($contactResult);
    } else {
        log_error("No contact information found in database");
    }

    // Fetch logo details
    $logo_path = "";
    $logoQuery = "SELECT * FROM logos WHERE is_active = 1 AND location = 'email' LIMIT 1";
    $logoResult = mysqli_query($conn, $logoQuery);
    if ($logoResult && mysqli_num_rows($logoResult) > 0) {
        $logo = mysqli_fetch_assoc($logoResult);
        $logo_path = __DIR__ . "/../admin/backend/uploads/" . $logo['logo_path'];
        
        // Check if logo file exists
        if (!file_exists($logo_path)) {
            log_error("Logo file not found: " . $logo_path);
            $logo_path = ""; // Reset if file doesn't exist
        }
    } else {
        log_error("No active email logo found");
    }

    // Store inquiry in database
    $sql = "INSERT INTO inquiries (name, email, phone, subject, message, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $phone, $subject, $message);
        if (mysqli_stmt_execute($stmt)) {
            $inquiryId = mysqli_insert_id($conn);
        } else {
            log_error("Database insert failed: " . mysqli_error($conn));
            $inquiryId = "N/A";
        }
        mysqli_stmt_close($stmt);
    } else {
        log_error("Prepare statement failed: " . mysqli_error($conn));
        $inquiryId = "N/A";
    }

    // Initialize PHPMailer
    $mail = new PHPMailer(true);

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'no-reply@zebulli.com'; // your email
        $mail->Password = 'e[3K0HD~qOe2'; // your app password
        $mail->Port = 587;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->CharSet = 'UTF-8';
        $mail->SMTPDebug = 0; // Set to 2 for debugging

        // Sender & Recipient
        $mail->setFrom('no-reply@zebulli.com', 'Zebulli Wellness');
        $mail->addReplyTo($email, $name);
        
        // Add recipients safely
        $adminEmail = 'iamnikhilgupta9@gmail.com';
        if (!empty($contactInfo['email'])) {
            $adminEmail = $contactInfo['email'];
        }
        $mail->addAddress($adminEmail);
        
        if (!empty($contactInfo['contact_email'])) {
            $mail->addAddress($contactInfo['contact_email']);
        }

        // Email Subject & Body
        $mail->Subject = "New Contact Form Submission: " . (strlen($subject) > 50 ? substr($subject, 0, 47) . '...' : $subject);
        $mail->isHTML(true);

        $mail->Body = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>New Contact Form Submission</title>
            <style>
                body { font-family: 'Arial', sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #2F358A; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .header img { max-height: 60px; }
                .content { padding: 20px; background-color: #f9f9f9; border-radius: 0 0 8px 8px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th { background-color: #f0f0f0; text-align: left; padding: 10px; }
                td { padding: 10px; border-bottom: 1px solid #ddd; }
                .footer { margin-top: 20px; font-size: 12px; color: #666; text-align: center; }
                .button { display: inline-block; padding: 10px 20px; background-color: #E2AD44; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>New Contact Form Submission</h2>
                </div>
                <div class='content'>
                    <table>
                        <tr>
                            <th>Name</th>
                            <td>" . htmlspecialchars($name) . "</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a></td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td><a href='tel:" . htmlspecialchars($phone) . "'>" . htmlspecialchars($phone) . "</a></td>
                        </tr>
                        <tr>
                            <th>Subject</th>
                            <td>" . htmlspecialchars($subject) . "</td>
                        </tr>
                        <tr>
                            <th>Message</th>
                            <td>" . nl2br(htmlspecialchars($message)) . "</td>
                        </tr>
                    </table>
                    
                    <div style='text-align: center; margin-top: 20px;'>
                        <a href='mailto:" . htmlspecialchars($email) . "' class='button'>Reply to Customer</a>
                        <a href='tel:" . htmlspecialchars($phone) . "' class='button'>Call Customer</a>
                    </div>
                </div>
                <div class='footer'>
                    <p>Inquiry ID: #" . htmlspecialchars($inquiryId) . "</p>
                    <p>&copy; " . date('Y') . " Freto Enterprises. All rights reserved.</p>
                    <p>" . (!empty($contactInfo['address']) ? htmlspecialchars($contactInfo['address']) : '') . "</p>
                </div>
            </div>
        </body>
        </html>";

        // Add logo if available
        if (!empty($logo_path) && file_exists($logo_path)) {
            $mail->addEmbeddedImage($logo_path, 'logo', 'logo.png');
            // Add logo to email body
            $mail->Body = str_replace(
                '<h2>New Contact Form Submission</h2>',
                '<img src="cid:logo" alt="Freto Enterprises Logo" style="max-height: 60px; margin-bottom: 10px;"><h2>New Contact Form Submission</h2>',
                $mail->Body
            );
        }

        // Send Email
        if ($mail->send()) {
            // Return success response
            echo json_encode([
                'status' => 'success',
                'message' => 'Thank you for your message! We will contact you soon.'
            ]);
        } else {
            throw new Exception("Failed to send email. Mailer Error: " . $mail->ErrorInfo);
        }

    } catch (Exception $e) {
        throw new Exception("Email sending failed: " . $e->getMessage());
    }

} catch (Exception $e) {
    // Log the error
    log_error($e->getMessage());
    
    // Return error response
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

// Close database connection
if (isset($conn)) {
    mysqli_close($conn);
}
?>