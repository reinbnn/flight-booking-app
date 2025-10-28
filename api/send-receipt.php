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
    // Get booking and payment details
    $stmt = $conn->prepare("
        SELECT b.*, p.amount, p.stripe_intent_id, u.email, u.first_name, u.last_name
        FROM bookings b
        JOIN payments p ON b.id = p.booking_id
        JOIN users u ON b.user_id = u.id
        WHERE b.id = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $data['booking_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Booking not found');
    }

    $booking = $result->fetch_assoc();
    
    $emailService = new EmailService();
    
    $bookingData = [
        'booking_id' => $booking['id'],
        'customer_name' => $booking['first_name'] . ' ' . $booking['last_name'],
        'service_type' => $booking['service_type'],
        'from_location' => $booking['departure'],
        'to_location' => $booking['arrival'],
        'departure_date' => $booking['departure_date'],
        'total_price' => $booking['total_price']
    ];

    $paymentData = [
        'amount' => $booking['amount'],
        'currency' => 'USD',
        'transaction_id' => $booking['stripe_intent_id'],
        'payment_date' => date('Y-m-d H:i:s')
    ];

    $result = $emailService->sendPaymentReceipt($booking['email'], $bookingData, $paymentData);
    
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
