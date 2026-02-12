<?php
include "db-conn.php";
require '../vendor/autoload.php';

if (!isset($_GET['order_id']) || !preg_match('/^[a-zA-Z0-9_-]+$/', $_GET['order_id'])) {
    die("Invalid Request");
}

// Keep it as string, no casting
$order_id = $_GET['order_id'];

// Fetch order details
$order_sql = "SELECT * FROM orders_new WHERE order_id = ?";
$stmt = $conn->prepare($order_sql);
$stmt->bind_param("s", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Order not found");
}

$contact_sql = "SELECT * FROM contacts LIMIT 1";
$stmt = $conn->prepare($contact_sql);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $contact = $result->fetch_assoc();
} else {
    $contact = null; // No record found
}


class NDCI_Invoice extends FPDF
{
    const PRIMARY_COLOR = [160, 102, 66];   // Warm Brown (#a06642)
    const SECONDARY_COLOR = [216, 180, 156]; // Soft Beige (#d8b49c)
    const LIGHT_COLOR = [244, 234, 226];     // Cream (#f4eae2)
    const DARK_COLOR = [75, 46, 34];         // Deep Coffee (#4b2e22)


    private $watermarkText = "Zebulli Wellness";

    function Header()
    {
        // Watermark
        $this->_watermark();

        // Company Header
        $this->SetFillColor($this::PRIMARY_COLOR[0], $this::PRIMARY_COLOR[1], $this::PRIMARY_COLOR[2]);
        $this->SetTextColor(255);
        $this->Image('../assets/img/logo-1.png', 10, 10, 30);
        $this->SetTextColor(139, 78, 47); // #8b4e2f
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Zebulli Wellness', 0, 1, 'C');
        $this->SetTextColor(0, 0, 255);
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, 'Your Partner in Creating Timeless Beauty', 0, 1, 'C');
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 5, '10, Gajanand Complex, Nana Varachha Dhal, Varachha Main Road, Surat - 395013 | Phone: +91-8866257788', 0, 1, 'C');
        $this->SetDrawColor($this::SECONDARY_COLOR[0], $this::SECONDARY_COLOR[1], $this::SECONDARY_COLOR[2]);
        $this->SetLineWidth(0.5);
        $this->Line(10, $this->GetY() + 2, 200, $this->GetY() + 2);
        $this->Ln(6);
    }

   function _watermark()
{
    $logoPath = '../assets/img/logo3.png';
$logoWidth = 100;
$x = ($this->w - $logoWidth) / 2;
$y = ($this->h - $logoWidth) / 2;

// Apply very low opacity if supported
if (method_exists($this, 'SetAlpha')) {
    $this->SetAlpha(0.03); // 3% opacity for a subtle watermark
}

// Place the logo
$this->Image($logoPath, $x, $y, $logoWidth);

// Reset opacity
if (method_exists($this, 'SetAlpha')) {
    $this->SetAlpha(1);
}

}



    function Footer()
    {
        $this->SetY(-20);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 6, 'Thank you for choosing Zebulli Wellness!', 0, 1, 'C');
        $this->Cell(0, 6, 'For support, visit https://zebulli.com/ or call +91-8866257788', 0, 1, 'C');
        $this->Cell(0, 6, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // Custom Row method for table rows
    function Row($data)
    {
        foreach ($data as $col) {
            $this->Cell(
                $col['width'] ?? 0,
                6,
                $col['text'] ?? '',
                0,
                0,
                $col['align'] ?? 'L',
                $col['fill'] ?? false
            );
        }
        $this->Ln();
    }

    // Table header method
    function TableHeader($cols)
    {
        $this->SetFillColor($this::PRIMARY_COLOR[0], $this::PRIMARY_COLOR[1], $this::PRIMARY_COLOR[2]);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 10);

        foreach ($cols as $col) {
            $this->Cell($col['width'], 7, $col['text'], 1, 0, 'C', true);
        }
        $this->Ln();
        $this->SetFillColor(255);
        $this->SetTextColor(0);
    }

    // Color helper methods (not needed since we're using direct calls now)
    // All color methods are implemented directly using FPDF's native methods
}
// Create PDF
$pdf = new NDCI_Invoice();
$pdf->AliasNbPages();
$pdf->AddPage();

// Invoice title
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(NDCI_Invoice::PRIMARY_COLOR[0], NDCI_Invoice::PRIMARY_COLOR[1], NDCI_Invoice::PRIMARY_COLOR[2]);
$pdf->Cell(0, 3, 'PROFORMA INVOICE', 0, 1, 'C');
$pdf->Ln(3);

// Invoice info
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0, 0, 0); // Set text color to black (RGB: 0,0,0)
$pdf->Cell(95, 8, 'Invoice Number: INV-' . str_pad($order['id'], 5, '0', STR_PAD_LEFT), 1, 0);
$pdf->Cell(95, 8, 'Invoice Date: ' . date("F j, Y", strtotime($order['created_at'])), 1, 1);
$pdf->Cell(95, 8, 'Order Number: ' . str_pad($order['order_id'], 5, '0', STR_PAD_LEFT), 1, 0);
$pdf->Cell(95, 8, 'Due Date: ' . date("F j, Y", strtotime($order['created_at'] . ' + 7 days')), 1, 1);
$pdf->Ln(1);

// Customer info
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 8, 'BILLING DETAILS', 0, 1, 'L');
$pdf->SetDrawColor(200, 200, 200);
$pdf->Cell(0, 0.5, '', 'B', 1);
$pdf->Ln(4);

$pdf->SetFont('Arial', '', 10);
$customer_name = trim($order['first_name'] . ' ' . ($order['last_name'] ?? ''));
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(35, 6, 'Customer Name:', 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, $customer_name, 0, 1);

if (!empty($order['comp_name'])) {
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(35, 6, 'Company:', 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, $order['comp_name'], 0, 1);
}

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(35, 6, 'Email:', 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, $order['email'], 0, 1);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(35, 6, 'Phone:', 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, $order['phone'], 0, 1);

if (!empty($order['address'])) {
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(35, 6, 'Address:', 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->MultiCell(0, 6, $order['address'], 0, 1);
}

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(35, 6, 'Location:', 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, $order['city'] . ', ' . $order['state'] . ' - ' . $order['postal_code'], 0, 1);

$pdf->Ln(6);
$pdf->SetDrawColor(200, 200, 200);
$pdf->Cell(0, 0.5, '', 'B', 1);
$pdf->Ln(4);

// Services Table
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(240, 240, 240);
$pdf->TableHeader([
    ['width' => 80, 'text' => 'SERVICE PACKAGE INCLUDES'],
    ['width' => 40, 'text' => 'TOKEN AMOUNT', 'align' => 'R'],
    ['width' => 40, 'text' => 'PENDING AMOUNT', 'align' => 'R'],
    ['width' => 30, 'text' => 'TOTAL', 'align' => 'R']
]);

// Process services
$services = is_array($order['products']) ? $order['products'] : json_decode($order['products'], true) ?? [];
if (empty($services) && is_string($order['products']) && !empty(trim($order['products']))) {
    $services = array_map('trim', explode(',', $order['products']));
}

// Package totals
$total_token = $order['token_money'] ?? 0;
$total_pending = $order['pending_money'] ?? 0;
$package_total = $order['order_total'] ?? 0;
$gst_rate = 5;
$discount = 0;

// Display package
$pdf->SetFont('Arial', 'B', 9);
$pdf->Row([
    ['width' => 80, 'text' => 'COMPLETE DIGITAL MARKETING PACKAGE'],
    // Use either all "Rs." or all "₹" for consistency
    ['width' => 40, 'text' => 'Rs. ' . number_format($total_token, 2), 'align' => 'R'],
    ['width' => 40, 'text' => 'Rs. ' . number_format($total_pending, 2), 'align' => 'R'],
    ['width' => 30, 'text' => 'Rs. ' . number_format($package_total, 2), 'align' => 'R']
]);
// List services
$pdf->SetFont('Arial', '', 9);
foreach ($services as $service) {
    $service_name = is_array($service) ? ($service['name'] ?? 'Service') : $service;
    $pdf->Row([
        ['width' => 100, 'text' => chr(149) . ' ' . $service_name],
        ['width' => 30, 'text' => '', 'align' => 'R'],
        ['width' => 30, 'text' => '', 'align' => 'R'],
        ['width' => 30, 'text' => '', 'align' => 'R']
    ]);
}

// Financial summary
$pdf->Cell(190, 0, '', 'T');
$pdf->Ln(3);

$pdf->SetFont('Arial', '', 9);
$pdf->Cell(130, 6, '', 0, 0);
$pdf->Cell(30, 6, 'Package Total:', 0, 0, 'R');
$pdf->Cell(30, 6, 'Rs. ' . number_format($package_total, 2), 0, 1, 'R');

$gst_amount = $package_total * ($gst_rate / 100);
$pdf->Cell(130, 6, '', 0, 0);
$pdf->Cell(30, 6, 'GST (' . $gst_rate . '%):', 0, 0, 'R');
$pdf->Cell(30, 6, 'Rs. ' . number_format($gst_amount, 2), 0, 1, 'R');

$pdf->Cell(130, 6, '', 0, 0);
$pdf->Cell(30, 6, 'Total Token Paid:', 0, 0, 'R');
$pdf->Cell(30, 6, 'Rs. ' . number_format($total_token, 2), 0, 1, 'R');

$pdf->Cell(130, 6, '', 0, 0);
$pdf->Cell(30, 6, 'Balance Due:', 0, 0, 'R');
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(30, 6, 'Rs. ' . number_format(($package_total + $gst_amount) - $total_token, 2), 0, 1, 'R');
$pdf->Ln(4);

// Payment info
$payment_info = "Payment Method: " . ($order['payment_method'] ?? 'Not specified');
$payment_info .= "\n\nPlease make payment to:  Leo Square Enterprise\n";
// $payment_info .= "Leo Square Enterprise\n";
$payment_info .= "Bank Name: Surat National Co-operative Bank Ltd.\n";
$payment_info .= "Account Number: 016120100001293\n";
$payment_info .= "IFSC Code: SUNB0000016\n";
$payment_info .= "UPI ID: milanrakholiya1996-2@okicici";

$pdf->MultiCell(0, 6, $payment_info, 0, 'L');
$pdf->Ln(5);

// Terms and Conditions Section
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 8, 'TERMS & CONDITIONS', 0, 1);
$pdf->SetFont('Arial', '', 9);

// Initialize $terms variable
$terms = "";

// Add terms with proper spacing
$terms = "1. PAYMENT TERMS: Full payment must be made at the time of purchase through the payment options available on zebulli.com.\n";
$terms .= "2. ORDER PROCESSING: Orders are processed only after successful payment confirmation. Estimated delivery timelines may vary based on location and product availability.\n";
$terms .= "3. RETURNS & REFUNDS: Due to hygiene and safety reasons, cosmetic and skincare products are not eligible for return or exchange once opened or used. Refunds are only applicable for damaged, defective, or wrongly delivered items.\n";
$terms .= "4. PRODUCT AUTHENTICITY: Zebulli Wellness guarantees 100% genuine and original products. In case of any concerns regarding product authenticity, customers must report within 48 hours of delivery.\n";
$terms .= "5. SHIPPING & DELIVERY: Zebulli Wellness is not liable for delays caused by courier partners, customs, or natural circumstances beyond our control.\n";
$terms .= "6. LIABILITY: Zebulli Wellness is not responsible for allergic reactions or misuse of any product. Customers are advised to check ingredient lists before use.\n";
$terms .= "7. DATA PRIVACY: Customer data is collected solely for order processing and service improvement. Zebulli Wellness does not share personal data with third parties without consent.\n";
$terms .= "8. GOVERNING LAW: All transactions are governed by the laws of India. Any disputes shall be subject to the jurisdiction of Surat, Gujarat courts.\n";
$terms .= "9. FORCE MAJEURE: Zebulli Wellness shall not be held liable for delays or failure to perform obligations due to events beyond reasonable control, including but not limited to natural disasters, strikes, or system outages.\n\n";
$terms .= "Note: This is a computer-generated invoice. No signature is required.\n\n";

$terms .= "Questions? Email support@zebulli.com";

// Set line height and draw terms
$pdf->MultiCell(0, 5, $terms, 0, 'L');
$pdf->Ln(5);


// Thank you note
$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(0, 8, 'Thank you for your business! We appreciate your trust in Zebulli Wellness services.', 0, 1, 'C');

// Output PDF
$pdf->Output('INV-' . str_pad($order['id'], 5, '0', STR_PAD_LEFT) . '.pdf', 'I');
?>