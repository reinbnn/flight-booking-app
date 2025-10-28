<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/email.php';
require_once __DIR__ . '/../classes/EmailService.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['booking_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing booking_id']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT b.*, u.email, u.first_name, u.last_name
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        WHERE b.id = ?
    ");
    $stmt->bind_param('s', $data['booking_id']);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();

    $emailService = new EmailService();
    
    $bookingData = [
        'booking_id' => $booking['id'],
        'customer_name' => $booking['first_name'] . ' ' . $booking['last_name'],
    ];

    $reason = $data['reason'] ?? 'Your payment method was declined. Please try again.';
    
    $result = $emailService->sendPaymentFailed($booking['email'], $bookingData, $reason);
    
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
