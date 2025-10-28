<?php
/**
 * Create Stripe Payment Intent
 * POST: /api/create-payment-intent.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../config/stripe-config.php';
require_once __DIR__ . '/../config/db.php';

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['amount']) || !isset($data['booking_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$amount = floatval($data['amount']);
$booking_id = sanitize_input($data['booking_id']);
$description = sanitize_input($data['description'] ?? 'SKYJET Booking');
$customer_email = sanitize_input($data['email'] ?? '');

// Validate amount (must be > 0)
if ($amount <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid amount']);
    exit;
}

// Convert to cents for Stripe
$amount_cents = intval($amount * 100);

try {
    // Create Payment Intent
    $intent = \Stripe\PaymentIntent::create([
        'amount' => $amount_cents,
        'currency' => CURRENCY,
        'description' => $description,
        'metadata' => [
            'booking_id' => $booking_id,
            'customer_email' => $customer_email
        ],
        'receipt_email' => $customer_email,
    ]);

    // Save to database
    $stmt = $conn->prepare("
        INSERT INTO payments (booking_id, stripe_intent_id, amount, currency, status, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $status = $intent->status;
    $stmt->bind_param('ssdss', $booking_id, $intent->id, $amount, CURRENCY, $status);
    
    if (!$stmt->execute()) {
        throw new Exception('Database error: ' . $stmt->error);
    }

    // Return client secret
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'client_secret' => $intent->client_secret,
        'intent_id' => $intent->id,
        'amount' => $amount,
        'currency' => CURRENCY
    ]);

} catch (\Stripe\Exception\ApiErrorException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

?>
