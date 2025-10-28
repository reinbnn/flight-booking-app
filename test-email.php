<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/email.php';
require_once __DIR__ . '/classes/EmailService.php';

echo "📧 Testing Email System...
";

$emailService = new EmailService();

// Test 1: Payment Receipt
echo "Test 1: Sending Payment Receipt...
";
$result = $emailService->sendPaymentReceipt(
    'test@example.com',
    [
        'booking_id' => 'BK123456',
        'customer_name' => 'John Doe',
        'service_type' => 'flight',
        'from_location' => 'New York (JFK)',
        'to_location' => 'London (LHR)',
        'departure_date' => '2025-11-15'
    ],
    [
        'amount' => 599.99,
        'currency' => 'USD',
        'transaction_id' => 'pi_test123',
        'payment_date' => date('Y-m-d H:i:s')
    ]
);
echo $result['success'] ? "✅ Success
" : "❌ Failed: " . $result['error'] . "
";

// Test 2: Booking Confirmation
echo "
Test 2: Sending Booking Confirmation...
";
$result = $emailService->sendBookingConfirmation(
    'test@example.com',
    [
        'booking_id' => 'BK123456',
        'customer_name' => 'John Doe',
        'service_type' => 'flight',
        'from_location' => 'New York (JFK)',
        'to_location' => 'London (LHR)',
        'departure_date' => '2025-11-15',
        'return_date' => '2025-11-22',
        'total_price' => 599.99
    ]
);
echo $result['success'] ? "✅ Success
" : "❌ Failed: " . $result['error'] . "
";

echo "
✅ All tests completed!
";
?>
