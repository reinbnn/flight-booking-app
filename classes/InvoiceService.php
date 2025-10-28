<?php
/**
 * Invoice Generation Service
 * Generates PDF invoices using TCPDF
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/invoice.php';

class InvoiceService {
    private $conn;
    private $pdf;
    private $invoice;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    /**
     * Generate Invoice Number
     */
    private function generateInvoiceNumber() {
        $stmt = $this->conn->query("SELECT COUNT(*) as count FROM invoices");
        $result = $stmt->fetch_assoc();
        $count = $result['count'] + 1;
        
        return INVOICE_PREFIX . str_pad($count, INVOICE_PADDING, '0', STR_PAD_LEFT);
    }

    /**
     * Create Invoice Record in Database
     */
    public function createInvoice($paymentId, $bookingId, $userId, $items, $subtotal, $tax, $discount) {
        try {
            $invoiceNumber = $this->generateInvoiceNumber();
            $total = $subtotal + $tax - $discount;

            $stmt = $this->conn->prepare("
                INSERT INTO invoices (
                    payment_id, booking_id, user_id, invoice_number,
                    subtotal, tax, discount, total,
                    company_name, company_address, company_phone,
                    company_email, company_website, company_logo
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }

            $stmt->bind_param(
                'iissddddssssss',
                $paymentId, $bookingId, $userId, $invoiceNumber,
                $subtotal, $tax, $discount, $total,
                $company_name, $company_address, $company_phone,
                $company_email, $company_website, $company_logo
            );

            $company_name = INVOICE_COMPANY_NAME;
            $company_address = INVOICE_COMPANY_ADDRESS;
            $company_phone = INVOICE_COMPANY_PHONE;
            $company_email = INVOICE_COMPANY_EMAIL;
            $company_website = INVOICE_COMPANY_WEBSITE;
            $company_logo = INVOICE_LOGO_PATH;

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $invoiceId = $this->conn->insert_id;

            // Insert invoice items
            foreach ($items as $item) {
                $this->addInvoiceItem($invoiceId, $item);
            }

            return [
                'success' => true,
                'invoice_id' => $invoiceId,
                'invoice_number' => $invoiceNumber
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Add Item to Invoice
     */
    private function addInvoiceItem($invoiceId, $item) {
        $stmt = $this->conn->prepare("
            INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, amount)
            VALUES (?, ?, ?, ?, ?)
        ");

        $description = $item['description'];
        $quantity = $item['quantity'];
        $unitPrice = $item['unit_price'];
        $amount = $item['amount'];

        $stmt->bind_param('isdd', $invoiceId, $description, $quantity, $unitPrice, $amount);
        return $stmt->execute();
    }

    /**
     * Generate PDF Invoice
     */
    public function generatePDF($invoiceId) {
        try {
            // Fetch invoice data
            $invoice = $this->getInvoice($invoiceId);
            if (!$invoice) {
                throw new Exception('Invoice not found');
            }

            // Fetch invoice items
            $items = $this->getInvoiceItems($invoiceId);

            // Create PDF
            $this->pdf = new \TCPDF();
            $this->pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $this->pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
            $this->pdf->AddPage();
            $this->pdf->SetFont('helvetica', '', PDF_FONT_SIZE_MAIN);

            // Add header
            $this->addHeader($invoice);

            // Add invoice details
            $this->addInvoiceDetails($invoice);

            // Add items table
            $this->addItemsTable($invoice, $items);

            // Add totals
            $this->addTotals($invoice);

            // Add terms & notes
            $this->addTermsAndNotes();

            // Create directory if not exists
            if (!is_dir(INVOICE_STORAGE_PATH)) {
                mkdir(INVOICE_STORAGE_PATH, 0755, true);
            }

            // Save PDF
            $pdfPath = INVOICE_STORAGE_PATH . $invoice['invoice_number'] . '.pdf';
            $this->pdf->Output($pdfPath, 'F');

            // Update invoice with PDF path
            $this->updateInvoicePdfPath($invoiceId, $pdfPath);

            return [
                'success' => true,
                'pdf_path' => $pdfPath,
                'invoice_number' => $invoice['invoice_number']
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Add Header Section
     */
    private function addHeader($invoice) {
        // Company Logo
        if (file_exists(INVOICE_LOGO_PATH)) {
            $this->pdf->Image(INVOICE_LOGO_PATH, 15, 10, 30);
        }

        // Company Info
        $this->pdf->SetXY(50, 15);
        $this->pdf->SetFont('helvetica', 'B', PDF_FONT_SIZE_HEADING);
        $this->pdf->Cell(0, 10, $invoice['company_name'], 0, 1);

        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->SetXY(50, 25);
        $this->pdf->MultiCell(0, 4, 
            $invoice['company_address'] . "
" .
            $invoice['company_phone'] . "
" .
            $invoice['company_email'] . "
" .
            $invoice['company_website']
        );

        // Invoice Title
        $this->pdf->SetXY(140, 15);
        $this->pdf->SetFont('helvetica', 'B', PDF_FONT_SIZE_HEADING);
        $this->pdf->Cell(0, 10, 'INVOICE', 0, 1, 'R');

        // Invoice Number
        $this->pdf->SetXY(140, 25);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 5, 'Invoice #: ' . $invoice['invoice_number'], 0, 1, 'R');

        $this->pdf->SetXY(140, 30);
        $this->pdf->Cell(0, 5, 'Date: ' . date('M d, Y', strtotime($invoice['invoice_date'])), 0, 1, 'R');

        $this->pdf->SetXY(140, 35);
        $this->pdf->Cell(0, 5, 'Due Date: ' . date('M d, Y', strtotime($invoice['due_date'])), 0, 1, 'R');

        $this->pdf->Ln(15);
    }

    /**
     * Add Invoice Details (Bill To / Ship To)
     */
    private function addInvoiceDetails($invoice) {
        // Get customer info
        $stmt = $this->conn->prepare("
            SELECT u.first_name, u.last_name, u.email, u.phone, u.address, u.city, u.country
            FROM users u WHERE u.id = ?
        ");
        $stmt->bind_param('i', $invoice['user_id']);
        $stmt->execute();
        $customer = $stmt->get_result()->fetch_assoc();

        // Bill To Section
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->Cell(90, 6, 'BILL TO:', 0, 0);

        // Ship To Section
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->Cell(0, 6, 'BOOKING DETAILS:', 0, 1);

        // Customer Details
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->SetXY(15, $this->pdf->GetY());
        $this->pdf->MultiCell(90, 4,
            $customer['first_name'] . ' ' . $customer['last_name'] . "
" .
            $customer['address'] . "
" .
            $customer['city'] . ', ' . $customer['country'] . "
" .
            $customer['phone'] . "
" .
            $customer['email']
        );

        // Booking Details
        $stmt = $this->conn->prepare("
            SELECT departure, arrival, departure_date, return_date, passengers, total_price
            FROM bookings WHERE id = ?
        ");
        $stmt->bind_param('s', $invoice['booking_id']);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();

        $this->pdf->SetXY(110, 50);
        $this->pdf->MultiCell(90, 4,
            "From: " . $booking['departure'] . "
" .
            "To: " . $booking['arrival'] . "
" .
            "Departure: " . date('M d, Y', strtotime($booking['departure_date'])) . "
" .
            "Return: " . date('M d, Y', strtotime($booking['return_date'])) . "
" .
            "Passengers: " . $booking['passengers']
        );

        $this->pdf->Ln(5);
    }

    /**
     * Add Items Table
     */
    private function addItemsTable($invoice, $items) {
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->SetFillColor(200, 220, 255);

        // Table Header
        $this->pdf->Cell(80, 7, 'Description', 1, 0, 'L', true);
        $this->pdf->Cell(25, 7, 'Qty', 1, 0, 'C', true);
        $this->pdf->Cell(30, 7, 'Unit Price', 1, 0, 'R', true);
        $this->pdf->Cell(30, 7, 'Amount', 1, 1, 'R', true);

        // Table Rows
        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->SetFillColor(240, 245, 255);
        $fill = false;

        foreach ($items as $item) {
            $this->pdf->Cell(80, 6, $item['description'], 1, 0, 'L', $fill);
            $this->pdf->Cell(25, 6, $item['quantity'], 1, 0, 'C', $fill);
            $this->pdf->Cell(30, 6, INVOICE_CURRENCY_SYMBOL . number_format($item['unit_price'], 2), 1, 0, 'R', $fill);
            $this->pdf->Cell(30, 6, INVOICE_CURRENCY_SYMBOL . number_format($item['amount'], 2), 1, 1, 'R', $fill);
            $fill = !$fill;
        }

        $this->pdf->Ln(5);
    }

    /**
     * Add Totals Section
     */
    private function addTotals($invoice) {
        $rightX = 160;
        $width = 45;

        // Subtotal
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->SetX($rightX);
        $this->pdf->Cell(30, 6, 'Subtotal:', 0, 0, 'R');
        $this->pdf->Cell($width, 6, INVOICE_CURRENCY_SYMBOL . number_format($invoice['subtotal'], 2), 0, 1, 'R');

        // Tax
        $this->pdf->SetX($rightX);
        $this->pdf->Cell(30, 6, 'Tax:', 0, 0, 'R');
        $this->pdf->Cell($width, 6, INVOICE_CURRENCY_SYMBOL . number_format($invoice['tax'], 2), 0, 1, 'R');

        // Discount
        if ($invoice['discount'] > 0) {
            $this->pdf->SetX($rightX);
            $this->pdf->Cell(30, 6, 'Discount:', 0, 0, 'R');
            $this->pdf->Cell($width, 6, '- ' . INVOICE_CURRENCY_SYMBOL . number_format($invoice['discount'], 2), 0, 1, 'R');
        }

        // Total (Highlighted)
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->SetFillColor(200, 220, 255);
        $this->pdf->SetX($rightX);
        $this->pdf->Cell(30, 8, 'TOTAL:', 1, 0, 'R', true);
        $this->pdf->Cell($width, 8, INVOICE_CURRENCY_SYMBOL . number_format($invoice['total'], 2), 1, 1, 'R', true);

        $this->pdf->Ln(10);
    }

    /**
     * Add Terms & Notes
     */
    private function addTermsAndNotes() {
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(0, 6, 'Terms & Conditions:', 0, 1);

        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->MultiCell(0, 4, INVOICE_TERMS);

        $this->pdf->Ln(5);

        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(0, 6, 'Notes:', 0, 1);

        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->MultiCell(0, 4, INVOICE_NOTES);
    }

    /**
     * Get Invoice
     */
    public function getInvoice($invoiceId) {
        $stmt = $this->conn->prepare("SELECT * FROM invoices WHERE id = ?");
        $stmt->bind_param('i', $invoiceId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get Invoice Items
     */
    public function getInvoiceItems($invoiceId) {
        $stmt = $this->conn->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
        $stmt->bind_param('i', $invoiceId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Update Invoice PDF Path
     */
    private function updateInvoicePdfPath($invoiceId, $pdfPath) {
        $stmt = $this->conn->prepare("UPDATE invoices SET pdf_path = ? WHERE id = ?");
        $stmt->bind_param('si', $pdfPath, $invoiceId);
        return $stmt->execute();
    }

    /**
     * Mark Invoice as Sent
     */
    public function markAsSent($invoiceId) {
        $stmt = $this->conn->prepare("
            UPDATE invoices 
            SET is_sent = 1, sent_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param('i', $invoiceId);
        return $stmt->execute();
    }

    /**
     * Get All Invoices for User
     */
    public function getUserInvoices($userId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM invoices 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get Invoices for Admin
     */
    public function getAllInvoices($limit = 50, $offset = 0) {
        $stmt = $this->conn->prepare("
            SELECT i.*, u.first_name, u.last_name, b.departure, b.arrival
            FROM invoices i
            JOIN users u ON i.user_id = u.id
            JOIN bookings b ON i.booking_id = b.id
            ORDER BY i.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Log Invoice Action
     */
    public function logAction($invoiceId, $action, $userId = null, $notes = null) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $stmt = $this->conn->prepare("
            INSERT INTO invoice_logs (invoice_id, action, user_id, ip_address, user_agent, notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param('isiss', $invoiceId, $action, $userId, $ipAddress, $userAgent, $notes);
        return $stmt->execute();
    }
}

?>
