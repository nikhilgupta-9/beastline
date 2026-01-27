<?php
// File: util/send_mail_user.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './vendor/autoload.php';

function sendOrderEmail($userEmail, $userName, $orderId, $amount, $status) {
    $mail = new PHPMailer(true);

    try {
        // SMTP Settings
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'no-reply@web2techsolutions.com';
        $mail->Password = '9gWZ:1k^';
        $mail->Port = 465;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;

        $mail->setFrom('no-reply@web2techsolutions.com', 'Earnova');
        $mail->addAddress($userEmail, $userName);

        $mail->isHTML(true);
        $mail->Subject = "Your Order #$orderId is Successful";

        $mail->Body = "
        <h2>Hi $userName,</h2>
        <p>Thank you for your order! Here are your order details:</p>
        <table style='border:1px solid #ccc; padding:10px;'>
            <tr><td><strong>Order ID:</strong></td><td>$orderId</td></tr>
            <tr><td><strong>Amount:</strong></td><td>₹$amount</td></tr>
            <tr><td><strong>Status:</strong></td><td>Paid</td></tr>
        </table>
        <p>We appreciate your trust in us!</p>
        <p><b>Team Earnova</b></p>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail Error: " . $mail->ErrorInfo);
        return false;
    }
}
