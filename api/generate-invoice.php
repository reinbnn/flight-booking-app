<?php
/**
 * Generate Invoice API
 * Creates invoice record and generates PDF
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/invoice.php';
require_once __DIR__ . '/../classes/InvoiceService.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['payment_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing payment_id']);
    exit;
}

try {
    $paymentId = intval($data['payment_id']);

    // Get payment details
    $stmt = $conn->prepare("
        SELECT p.*, b.id as booking_id, b.total_price, u.id as user_id
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ");
    $stmt->bind_param('i', $paymentId);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();

    if (!$payment) {
        throw new Exception('Payment not found');
    }

    // Check if invoice already exists
    $checkStmt = $conn->prepare("SELECT id FROM invoices WHERE payment_id = ?");
    $checkStmt->bind_param('i', $paymentId);
    $checkStmt->execute();
    $existingInvoice = $checkStmt->get_result()->fetch_assoc();

    if ($existingInvoice) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Invoice already generated',
            'invoice_id' => $existingInvoice['id']
        ]);
        exit;
    }

    // Calculate tax
    $subtotal = $payment['amount'];
    $tax = round($subtotal * INVOICE_TAX_RATE, 2);
    $discount = 0;
    $total = $subtotal + $tax - $discount;

    // Invoice items
    $items = [
        [
            'description' => 'Flight Booking - ' . $payment['booking_id'],
            'quantity' => 1,
            'unit_price' => $subtotal,
            'amount' => $subtotal
        ]
    ];

    // Create invoice
    $invoiceService = new InvoiceService($conn);
    $result = $invoiceService->createInvoice(
        $paymentId,
        $payment['booking_id'],
        $payment['user_id'],
        $items,
        $subtotal,
        $tax,
        $discount
    );

    if (!$result['success']) {
        throw new Exception($result['error']);
    }

    $invoiceId = $result['invoice_id'];

    // Generate PDF
    $pdfResult = $invoiceService->generatePDF($invoiceId);
    if (!$pdfResult['success']) {
        throw new Exception($pdfResult['error']);
    }

    // Log action
    $invoiceService->logAction($invoiceId, 'created', $payment['user_id']);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Invoice generated successfully',
        'invoice_id' => $invoiceId,
        'invoice_number' => $result['invoice_number'],
        'pdf_path' => $pdfResult['pdf_path']
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

?>
