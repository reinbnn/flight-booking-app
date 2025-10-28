<?php
/**
 * Request Refund API
 * User submits refund request
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/refund.php';
require_once __DIR__ . '/../classes/RefundService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['payment_id']) || !isset($data['amount']) || !isset($data['reason'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    $paymentId = intval($data['payment_id']);
    $amount = floatval($data['amount']);
    $reason = trim($data['reason']);
    $notes = isset($data['notes']) ? trim($data['notes']) : null;

    // Get payment
    $stmt = $conn->prepare("SELECT * FROM payments WHERE id = ?");
    $stmt->bind_param('i', $paymentId);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();

    if (!$payment) {
        throw new Exception('Payment not found');
    }

    // Verify user owns this payment
    // You might want to add user_id verification here if user is logged in

    $refundService = new RefundService($conn);

    // Create refund request
    $result = $refundService->createRefundRequest(
        $paymentId,
        $payment['booking_id'],
        $payment['user_id'],
        $amount,
        $reason,
        $notes
    );

    if (!$result['success']) {
        throw new Exception($result['error']);
    }

    http_response_code(201);
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

?>
