<?php
require_once __DIR__ . '/../config/connect.php';
require_once __DIR__ . '/../admin/models/PaymentSmtpSetting.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private $conn;
    private $smtp;
    private $site;
    private $mailer;

    public function __construct($conn, $site = '')
    {
        $this->conn = $conn;
        $this->site = $site;
        $this->smtpSetting = new PaymentSmtpSetting($conn);
        $this->loadSMTPSettings();
        $this->initializeMailer();
    }

    private function loadSMTPSettings()
    {
        $this->smtp = [
            'host'       => $this->smtpSetting->getSetting('smtp', 'host'),
            'port'       => $this->smtpSetting->getSetting('smtp', 'port'),
            'username'   => $this->smtpSetting->getSetting('smtp', 'username'),
            'password'   => $this->smtpSetting->getSetting('smtp', 'password'),
            'encryption' => $this->smtpSetting->getSetting('smtp', 'encryption'),
            'from_name'  => $this->smtpSetting->getSetting('smtp', 'from_name'),
            'from_email' => $this->smtpSetting->getSetting('smtp', 'from_email'),
        ];
    }

    private function initializeMailer()
    {
        $this->mailer = new PHPMailer(true);
        
        // Configure SMTP
        $this->mailer->isSMTP();
        $this->mailer->Host       = $this->smtp['host'];
        $this->mailer->SMTPAuth   = true;
        $this->mailer->Username   = $this->smtp['username'];
        $this->mailer->Password   = $this->smtp['password'];
        $this->mailer->Port       = (int)$this->smtp['port'];

        if ($this->smtp['encryption'] === 'ssl') {
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($this->smtp['encryption'] === 'tls') {
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $this->mailer->setFrom($this->smtp['from_email'], $this->smtp['from_name']);
    }

    // ==================== REGISTRATION EMAILS ====================

    public function sendRegistrationEmail($email, $name, $verification_link = '')
    {
        $subject = 'Welcome to Beastline - Complete Your Registration';
        
        $html = $this->getEmailTemplate('registration', [
            'name' => $name,
            'verification_link' => $verification_link,
            'site' => $this->site
        ]);

        return $this->sendEmail($email, $name, $subject, $html);
    }

    public function sendRegistrationOTP($email, $name, $otp)
    {
        $subject = 'Verify Your Email - Beastline';
        
        $html = $this->getEmailTemplate('registration_otp', [
            'name' => $name,
            'otp' => $otp,
            'site' => $this->site
        ]);

        return $this->sendEmail($email, $name, $subject, $html);
    }

    public function sendWelcomeEmail($email, $name)
    {
        $subject = 'Welcome to Beastline! Start Your Style Journey';
        
        $html = $this->getEmailTemplate('welcome', [
            'name' => $name,
            'site' => $this->site
        ]);

        return $this->sendEmail($email, $name, $subject, $html);
    }

    // ==================== PASSWORD & SECURITY EMAILS ====================

    public function sendPasswordResetOTP($email, $name, $otp)
    {
        $subject = 'Password Reset OTP - Beastline';
        
        $html = $this->getEmailTemplate('password_reset_otp', [
            'name' => $name,
            'otp' => $otp,
            'site' => $this->site
        ]);

        return $this->sendEmail($email, $name, $subject, $html);
    }

    public function sendPasswordResetConfirmation($email, $name)
    {
        $subject = 'Password Reset Successful - Beastline';
        
        $html = $this->getEmailTemplate('password_reset_success', [
            'name' => $name,
            'site' => $this->site
        ]);

        return $this->sendEmail($email, $name, $subject, $html);
    }

    public function sendAccountLockedEmail($email, $name, $unlock_link)
    {
        $subject = 'Account Security Alert - Beastline';
        
        $html = $this->getEmailTemplate('account_locked', [
            'name' => $name,
            'unlock_link' => $unlock_link,
            'site' => $this->site
        ]);

        return $this->sendEmail($email, $name, $subject, $html);
    }

    // ==================== ORDER EMAILS ====================

    public function sendOrderConfirmation($email, $name, $order_data)
    {
        $subject = 'Order Confirmed - #' . $order_data['order_number'] . ' - Beastline';
        
        $html = $this->getEmailTemplate('order_confirmation', [
            'name' => $name,
            'order_data' => $order_data,
            'site' => $this->site
        ]);

        return $this->sendEmail($email, $name, $subject, $html);
    }

    public function sendOrderProcessing($email, $name, $order_data)
    {
        $subject = 'Your Order is Being Processed - #' . $order_data['order_number'] . ' - Beastline';
        
        $html = $this->getEmailTemplate('order_processing', [
            'name' => $name,
            'order_data' => $order_data,
            'site' => $this->site
        ]);

        return $this->sendEmail($email, $name, $subject, $html);
    }

    public function sendOrderShipped($email, $name, $order_data, $tracking_info)
    {
        $subject = 'Your Order Has Shipped! - #' . $order_data['order_number'] . ' - Beastline';
        
        $html = $this->getEmailTemplate('order_shipped', [
            'name' => $name,
            'order_data' => $order_data,
            'tracking_info' => $tracking_info,
            'site' => $this->site
        ]);

        return $this->sendEmail($email, $name, $subject, $html);
    }

    public function sendOrderDelivered($email, $name, $order_data)
    {
        $subject = 'Your Order Has Been Delivered - #' . $order_data['order_number'] . ' - Beastline';
        
        $html = $this->getEmailTemplate('order_delivered', [
            'name' => $name,
            'order_data' => $order_data,
            'site' => $this->site
        ]);

        return $this->sendEmail($email, $name, $subject, $html);
    }

    public function sendOrderCancelled($email, $name, $order_data, $reason = '')
    {
        $subject = 'Order Cancelled - #' . $order_data['order_number'] . ' - Beastline';
        
        $html = $this->getEmailTemplate('order_cancelled', [
            'name' => $name,
            'order_data' => $order_data,
            'reason' => $reason,
            'site' => $this->site
        ]);

        return $this->sendEmail($email, $name, $subject, $html);
    }

    // ==================== PAYMENT EMAILS ====================

    public function sendPaymentReceived($email, $name, $payment_data)
    {
        $subject = 'Payment Received - #' . $payment_data['order_number'] . ' - Beastline';
        
        $html = $this->getEmailTemplate('payment_received', [
            'name' => $name,
            'payment_data' => $payment_data,
            'site' => $this->site
        ]);

        return $this->sendEmail($email, $name, $subject, $html);
    }

    public function sendPaymentFailed($email, $name, $order_data, $reason = '')
    {
        $subject = 'Payment Failed - #' . $order_data['order_number'] . ' - Beastline';
        
        $html = $this->getEmailTemplate('payment_failed', [
            'name' => $name,
            'order_data' => $order_data,
            'reason' => $reason,
            'site' => $this->site
        ]);

        return $this->sendEmail($email, $name, $subject, $html);
    }

    public function sendInvoice($email, $name, $invoice_data)
    {
        $subject = 'Invoice - #' . $invoice_data['invoice_number'] . ' - Beastline';
        
        $html = $this->getEmailTemplate('invoice', [
            'name' => $name,
            'invoice_data' => $invoice_data,
            'site' => $this->site
        ]);

        // Attach PDF if available
        if (isset($invoice_data['pdf_path']) && file_exists($invoice_data['pdf_path'])) {
            $this->mailer->addAttachment($invoice_data['pdf_path']);
        }

        return $this->sendEmail($email, $name, $subject, $html);
    }

    // ==================== SHIPPING & DELIVERY EMAILS ====================

    public function sendShippingUpdate($email, $name, $shipping_data)
    {
        $subject = 'Shipping Update - #' . $shipping_data['order_number'] . ' - Beastline';
        
        $html = $this->getEmailTemplate('shipping_update', [
            'name' => $name,
            'shipping_data' => $shipping_data,
            'site' => $this->site
        ]);

        return $this->sendEmail($email, $name, $subject, $html);
    }

    public function sendOutForDelivery($email, $name, $delivery_data)
    {
        $subject = 'Your Order is Out for Delivery! - #' . $delivery_data['order_number'] . ' - Beastline';
        
        $html = $this->getEmailTemplate('out_for_delivery', [
            'name' => $name,
            'delivery_data' => $delivery_data,
            'site' => $this->site
        ]);

        return $this->sendEmail($email, $name, $subject, $html);
    }

    public function sendDeliveryAttemptFailed($email, $name, $delivery_data)
    {
        $subject = 'Delivery Attempt Failed - #' . $delivery_data['order_number'] . ' - Beastline';
        
        $html = $this->getEmailTemplate('delivery_attempt_failed', [
            'name' => $name,
            'delivery_data' => $delivery_data,
            'site' => $this->site
        ]);

        return $this->sendEmail($email, $name, $subject, $html);
    }

    // ==================== RETURNS & REFUNDS EMAILS ====================

    public function sendReturnRequestReceived($email, $name, $return_data)
    {
        $subject = 'Return Request Received - #' . $return_data['return_number'] . ' - Beastline';
        
        $html = $this->getEmailTemplate('return_request_received', [
            'name' => $name,
            'return_data' => $return_data,
            'site' => $this->site
        ]);

        return $this->sendEmail($email, $name, $subject, $html);
    }

    public function sendReturnApproved($email, $name, $return_data)
    {
        $subject = 'Return Approved - #' . $return_data['return_number'] . ' - Beastline';
        
        $html = $this->getEmailTemplate('return_approved', [
            'name' => $name,
            'return_data' => $return_data,
            'site' => $this->site
        ]);

        return $this->sendEmail($email, $name, $subject, $html);
    }

    public function sendRefundProcessed($email, $name, $refund_data)
    {
        $subject = 'Refund Processed - #' . $refund_data['refund_number'] . ' - Beastline';
        
        $html = $this->getEmailTemplate('refund_processed', [
            'name' => $name,
            'refund_data' => $refund_data,
            'site' => $this->site
        ]);

        return $this->sendEmail($email, $name, $subject, $html);
    }

    // ==================== CUSTOMER SERVICE EMAILS ====================

    public function sendCustomerServiceResponse($email, $name, $ticket_data)
    {
        $subject = 'Re: Support Ticket #' . $ticket_data['ticket_number'] . ' - Beastline';
        
        $html = $this->getEmailTemplate('customer_service_response', [
            'name' => $name,
            'ticket_data' => $ticket_data,
            'site' => $this->site
        ]);

        return $this->sendEmail($email, $name, $subject, $html);
    }

    public function sendFeedbackRequest($email, $name, $order_data)
    {
        $subject = 'How was your experience? - #' . $order_data['order_number'] . ' - Beastline';
        
        $html = $this->getEmailTemplate('feedback_request', [
            'name' => $name,
            'order_data' => $order_data,
            'site' => $this->site
        ]);

        return $this->sendEmail($email, $name, $subject, $html);
    }

    // ==================== MARKETING & PROMOTIONAL EMAILS ====================

    public function sendNewsletter($email, $name, $newsletter_content)
    {
        $subject = $newsletter_content['subject'] ?? 'Latest Updates from Beastline';
        
        $html = $this->getEmailTemplate('newsletter', [
            'name' => $name,
            'content' => $newsletter_content,
            'site' => $this->site
        ]);

        return $this->sendEmail($email, $name, $subject, $html);
    }

    public function sendPromotionalOffer($email, $name, $offer_data)
    {
        $subject = $offer_data['subject'] ?? 'Special Offer Just For You! - Beastline';
        
        $html = $this->getEmailTemplate('promotional_offer', [
            'name' => $name,
            'offer_data' => $offer_data,
            'site' => $this->site
        ]);

        return $this->sendEmail($email, $name, $subject, $html);
    }

    public function sendAbandonedCartReminder($email, $name, $cart_data)
    {
        $subject = 'Complete Your Purchase - Items Waiting in Your Cart - Beastline';
        
        $html = $this->getEmailTemplate('abandoned_cart_reminder', [
            'name' => $name,
            'cart_data' => $cart_data,
            'site' => $this->site
        ]);

        return $this->sendEmail($email, $name, $subject, $html);
    }

    // ==================== ADMIN NOTIFICATION EMAILS ====================

    public function sendNewOrderNotification($admin_email, $order_data)
    {
        $subject = 'New Order Received - #' . $order_data['order_number'] . ' - Beastline';
        
        $html = $this->getEmailTemplate('admin_new_order', [
            'order_data' => $order_data,
            'site' => $this->site
        ]);

        return $this->sendEmail($admin_email, 'Admin', $subject, $html);
    }

    public function sendLowStockNotification($admin_email, $product_data)
    {
        $subject = 'Low Stock Alert - ' . $product_data['product_name'] . ' - Beastline';
        
        $html = $this->getEmailTemplate('admin_low_stock', [
            'product_data' => $product_data,
            'site' => $this->site
        ]);

        return $this->sendEmail($admin_email, 'Admin', $subject, $html);
    }

    // ==================== HELPER METHODS ====================

    private function sendEmail($to_email, $to_name, $subject, $html)
    {
        try {
            // Reset mailer for each email
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            $this->mailer->addAddress($to_email, $to_name);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $html;
            
            // Add plain text version
            $plain_text = strip_tags($html);
            $this->mailer->AltBody = $plain_text;
            
            return $this->mailer->send();
            
        } catch (Exception $e) {
            error_log("Email send failed: " . $e->getMessage());
            return false;
        }
    }

    private function getEmailTemplate($template_name, $data)
    {
        $template_file = __DIR__ . "/email_templates/{$template_name}.html";
        
        if (file_exists($template_file)) {
            $html = file_get_contents($template_file);
        } else {
            // Fallback to basic template
            $html = $this->getBasicTemplate($template_name, $data);
        }
        
        // Replace variables in template
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Handle array data (like order items)
                if ($key === 'order_items') {
                    $items_html = '';
                    foreach ($value as $item) {
                        $items_html .= $this->getOrderItemHTML($item);
                    }
                    $html = str_replace('{{order_items}}', $items_html, $html);
                }
                continue;
            }
            $html = str_replace("{{{$key}}}", htmlspecialchars($value), $html);
        }
        
        return $html;
    }

    private function getBasicTemplate($type, $data)
    {
        $name = $data['name'] ?? 'Customer';
        $site = $this->site;
        
        switch ($type) {
            case 'password_reset_otp':
                return "
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Password Reset OTP</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #c7a17a 0%, #8b6b4d 100%); color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
                        .otp-box { background: #fff; border: 2px dashed #c7a17a; padding: 20px; text-align: center; font-size: 32px; font-weight: bold; color: #c7a17a; margin: 20px 0; border-radius: 5px; }
                        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Beastline Password Reset</h2>
                        </div>
                        <div class='content'>
                            <h3>Hello {$name},</h3>
                            <p>We received a request to reset your password for your Beastline account.</p>
                            <p>Please use the following OTP to verify your identity:</p>
                            
                            <div class='otp-box'>{$data['otp']}</div>
                            
                            <p><strong>This OTP is valid for 10 minutes.</strong></p>
                            <p>If you didn't request this password reset, please ignore this email or contact our support team immediately.</p>
                            
                            <p>Best regards,<br>The Beastline Team</p>
                        </div>
                        <div class='footer'>
                            <p>This is an automated message. Please do not reply to this email.</p>
                            <p>&copy; " . date('Y') . " Beastline. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
            case 'order_confirmation':
                $order = $data['order_data'];
                return "
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Order Confirmation</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #c7a17a 0%, #8b6b4d 100%); color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
                        .order-details { background: white; padding: 20px; border-radius: 5px; margin: 20px 0; }
                        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Order Confirmed!</h2>
                        </div>
                        <div class='content'>
                            <h3>Hello {$name},</h3>
                            <p>Thank you for your order! We're excited to let you know that we've received your order and it is now being processed.</p>
                            
                            <div class='order-details'>
                                <h4>Order Details</h4>
                                <p><strong>Order Number:</strong> #{$order['order_number']}</p>
                                <p><strong>Order Date:</strong> {$order['order_date']}</p>
                                <p><strong>Order Total:</strong> ₹{$order['order_total']}</p>
                                <p><strong>Shipping Address:</strong> {$order['shipping_address']}</p>
                            </div>
                            
                            <p>You can track your order status by visiting <a href='{$site}track-order/'>Order Tracking</a>.</p>
                            <p>If you have any questions, please contact our customer support.</p>
                            
                            <p>Best regards,<br>The Beastline Team</p>
                        </div>
                        <div class='footer'>
                            <p>This is an automated message. Please do not reply to this email.</p>
                            <p>&copy; " . date('Y') . " Beastline. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
            // Add more basic templates as needed...
            
            default:
                return "
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Beastline Email</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #c7a17a 0%, #8b6b4d 100%); color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
                        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Beastline</h2>
                        </div>
                        <div class='content'>
                            <h3>Hello {$name},</h3>
                            <p>This is a message from Beastline.</p>
                            <p>Best regards,<br>The Beastline Team</p>
                        </div>
                        <div class='footer'>
                            <p>This is an automated message. Please do not reply to this email.</p>
                            <p>&copy; " . date('Y') . " Beastline. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
        }
    }

    private function getOrderItemHTML($item)
    {
        return "
        <tr>
            <td style='padding: 10px; border-bottom: 1px solid #eee;'>
                <img src='{$item['image']}' alt='{$item['name']}' style='width: 80px; height: 80px; object-fit: cover; border-radius: 5px;'>
            </td>
            <td style='padding: 10px; border-bottom: 1px solid #eee;'>
                <strong>{$item['name']}</strong><br>
                <small>{$item['variant']}</small>
            </td>
            <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>
                {$item['quantity']}
            </td>
            <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>
                ₹{$item['price']}
            </td>
            <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>
                ₹{$item['total']}
            </td>
        </tr>
        ";
    }

    // ==================== BULK EMAIL METHODS ====================

    public function sendBulkEmails($recipients, $subject, $template, $data = [])
    {
        $results = [];
        
        foreach ($recipients as $recipient) {
            $data['name'] = $recipient['name'];
            $html = $this->getEmailTemplate($template, $data);
            
            $result = $this->sendEmail($recipient['email'], $recipient['name'], $subject, $html);
            $results[] = [
                'email' => $recipient['email'],
                'success' => $result,
                'error' => $result ? '' : $this->mailer->ErrorInfo
            ];
            
            // Small delay to prevent rate limiting
            usleep(100000); // 0.1 second
        }
        
        return $results;
    }

    // ==================== TEST METHOD ====================

    public function testConnection()
    {
        try {
            $this->mailer->smtpConnect();
            return [
                'success' => true,
                'message' => 'SMTP connection successful'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'SMTP connection failed: ' . $e->getMessage()
            ];
        }
    }

    // ==================== STATIC METHODS FOR EASY ACCESS ====================

    public static function sendQuickEmail($to_email, $to_name, $subject, $message)
    {
        global $conn, $site;
        
        $emailService = new self($conn, $site);
        return $emailService->sendEmail($to_email, $to_name, $subject, $message);
    }
}
?>