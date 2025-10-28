<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/paypal.php';
require_once __DIR__ . '/../classes/PayPalService.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['amount']) || !isset($data['booking_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    $amount = floatval($data['amount']);
    $booking_id = sanitize_input($data['booking_id']);
    $email = sanitize_input($data['email'] ?? 'customer@skyjet.local');
    $description = sanitize_input($data['description'] ?? 'SKYJET Booking');

    if ($amount <= 0) {
        throw new Exception('Invalid amount');
    }

    // Create PayPal order
    $paypal = new PayPalService();
    $result = $paypal->createOrder($booking_id, $amount, $email, $description);

    if (!$result['success']) {
        throw new Exception($result['error']);
    }

    // Save to database
    $stmt = $conn->prepare("
        INSERT INTO payments (booking_id, paypal_order_id, amount, currency, status, payment_method)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $status = 'pending';
    $method = 'paypal';
    $currency = 'USD';
    
    $stmt->bind_param('ssdsss', $booking_id, $result['order_id'], $amount, $currency, $status, $method);
    
    if (!$stmt->execute()) {
        throw new Exception('Database error: ' . $stmt->error);
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'order_id' => $result['order_id'],
        'approve_url' => $result['approve_link']
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

?>
