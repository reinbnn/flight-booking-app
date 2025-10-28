<?php
/**
 * Download Invoice API
 * Streams PDF file for download
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/invoice.php';
require_once __DIR__ . '/../classes/InvoiceService.php';

$invoiceId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$invoiceId) {
    http_response_code(400);
    echo 'Invalid invoice ID';
    exit;
}

try {
    // Get invoice
    $stmt = $conn->prepare("SELECT * FROM invoices WHERE id = ?");
    $stmt->bind_param('i', $invoiceId);
    $stmt->execute();
    $invoice = $stmt->get_result()->fetch_assoc();

    if (!$invoice) {
        throw new Exception('Invoice not found');
    }

    if (!file_exists($invoice['pdf_path'])) {
        // Generate PDF if not exists
        $invoiceService = new InvoiceService($conn);
        $result = $invoiceService->generatePDF($invoiceId);
        if (!$result['success']) {
            throw new Exception('Failed to generate invoice PDF');
        }
        $pdfPath = $result['pdf_path'];
    } else {
        $pdfPath = $invoice['pdf_path'];
    }

    // Log download
    $invoiceService = new InvoiceService($conn);
    $invoiceService->logAction($invoiceId, 'downloaded');

    // Stream PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $invoice['invoice_number'] . '.pdf"');
    header('Content-Length: ' . filesize($pdfPath));
    header('Pragma: no-cache');
    header('Expires: 0');

    readfile($pdfPath);
    exit;

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

?>
