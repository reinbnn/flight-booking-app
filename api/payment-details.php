<?php
/**
 * Payment Details API
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$bookingId = isset($_GET['booking_id']) ? trim($_GET['booking_id']) : null;
$paymentId = isset($_GET['payment_id']) ? intval($_GET['payment_id']) : null;

if (!$bookingId && !$paymentId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing booking_id or payment_id']);
    exit;
}

try {
    if ($bookingId) {
        $stmt = $conn->prepare("
            SELECT * FROM payments 
            WHERE booking_id = ? 
            ORDER BY payment_date DESC 
            LIMIT 1
        ");
        $stmt->bind_param('s', $bookingId);
    } else {
        $stmt = $conn->prepare("SELECT * FROM payments WHERE id = ?");
        $stmt->bind_param('i', $paymentId);
    }

    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();

    if (!$payment) {
        throw new Exception('Payment not found');
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'payment' => $payment
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

?>
