<?php
/**
 * Reject Refund API
 * Admin rejects refund request
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/refund.php';
require_once __DIR__ . '/../classes/RefundService.php';

session_start();
if (!isset($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['refund_id']) || !isset($data['reason'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    $refundId = intval($data['refund_id']);
    $adminId = $_SESSION['admin_id'];
    $reason = trim($data['reason']);

    $refundService = new RefundService($conn);
    $result = $refundService->rejectRefund($refundId, $adminId, $reason);

    if (!$result['success']) {
        throw new Exception($result['error']);
    }

    http_response_code(200);
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

?>
