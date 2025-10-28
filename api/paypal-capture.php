<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/paypal.php';
require_once __DIR__ . '/../classes/PayPalService.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['order_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing order_id']);
    exit;
}

try {
    $paypal = new PayPalService();
    $result = $paypal->captureOrder($data['order_id']);

    if (!$result['success']) {
        throw new Exception($result['error']);
    }

    // Get booking ID from PayPal order
    $stmt = $conn->prepare("SELECT booking_id FROM payments WHERE paypal_order_id = ?");
    $stmt->bind_param('s', $data['order_id']);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();

    if (!$payment) {
        throw new Exception('Payment record not found');
    }

    // Update payment record
    $stmt = $conn->prepare("
        UPDATE payments 
        SET status = ?, paypal_transaction_id = ?, updated_at = NOW()
        WHERE paypal_order_id = ?
    ");
    
    $status = 'succeeded';
    $stmt->bind_param('sss', $status, $result['transaction_id'], $data['order_id']);
    $stmt->execute();

    // Update booking as confirmed
    $stmt = $conn->prepare("
        UPDATE bookings 
        SET status = 'confirmed', paid = 1, paid_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param('s', $payment['booking_id']);
    $stmt->execute();

    // Send receipt email
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://skyjet.local/api/send-receipt.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['booking_id' => $payment['booking_id']]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 5
    ]);
    curl_exec($curl);
    curl_close($curl);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Payment captured successfully',
        'booking_id' => $payment['booking_id'],
        'transaction_id' => $result['transaction_id'],
        'status' => $result['status']
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

?>
