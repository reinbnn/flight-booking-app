<?php
/**
 * Refund Status API
 * Get refund status and details
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/refund.php';
require_once __DIR__ . '/../classes/RefundService.php';

$refundId = isset($_GET['id']) ? intval($_GET['id']) : null;
$bookingId = isset($_GET['booking_id']) ? trim($_GET['booking_id']) : null;

if (!$refundId && !$bookingId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing refund_id or booking_id']);
    exit;
}

try {
    $refundService = new RefundService($conn);

    if ($refundId) {
        $refund = $refundService->getRefund($refundId);
    } else {
        // Get latest refund for booking
        $stmt = $conn->prepare("
            SELECT * FROM refunds 
            WHERE booking_id = ? 
            ORDER BY requested_at DESC 
            LIMIT 1
        ");
        $stmt->bind_param('s', $bookingId);
        $stmt->execute();
        $refund = $stmt->get_result()->fetch_assoc();
    }

    if (!$refund) {
        throw new Exception('Refund not found');
    }

    // Get refund logs
    $stmt = $conn->prepare("
        SELECT * FROM refund_logs 
        WHERE refund_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->bind_param('i', $refund['id']);
    $stmt->execute();
    $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'refund' => $refund,
        'logs' => $logs
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

?>
