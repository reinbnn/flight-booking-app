<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/stripe.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['intent_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing intent_id']);
    exit;
}

try {
    $intent = \Stripe\PaymentIntent::retrieve($data['intent_id']);
    $status = $intent->status;

    // Update payment status
    $stmt = $conn->prepare("UPDATE payments SET status = ? WHERE stripe_intent_id = ?");
    $stmt->bind_param('ss', $status, $data['intent_id']);
    $stmt->execute();

    if ($intent->status === 'succeeded') {
        // Get booking ID
        $stmt = $conn->prepare("SELECT booking_id FROM payments WHERE stripe_intent_id = ?");
        $stmt->bind_param('s', $data['intent_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
            $booking_id = $row['booking_id'];
            
            // Update booking
            $stmt = $conn->prepare("UPDATE bookings SET status = 'confirmed', paid = 1 WHERE id = ?");
            $stmt->bind_param('s', $booking_id);
            $stmt->execute();

            // SEND RECEIPT EMAIL ASYNCHRONOUSLY
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://skyjet.local/api/send-receipt.php',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode(['booking_id' => $booking_id]),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 5
            ]);
            curl_exec($curl);
            curl_close($curl);
        }

        echo json_encode([
            'success' => true,
            'status' => 'Payment successful!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'status' => $intent->status
        ]);
    }

} catch (\Stripe\Exception\ApiErrorException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
