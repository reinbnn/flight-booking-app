<?php
/**
 * PayPal Return Handler
 * Redirects user after they approve payment on PayPal
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/paypal.php';
require_once __DIR__ . '/../classes/PayPalService.php';

if (!isset($_GET['token'])) {
    header('Location: /pages/payment.html?error=invalid_token');
    exit;
}

$orderId = sanitize_input($_GET['token']);

try {
    // Get payment details
    $stmt = $conn->prepare("SELECT booking_id FROM payments WHERE paypal_order_id = ?");
    $stmt->bind_param('s', $orderId);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();

    if (!$payment) {
        throw new Exception('Payment not found');
    }

    // Verify order on PayPal
    $paypal = new PayPalService();
    $orderDetails = $paypal->getOrderDetails($orderId);

    if ($orderDetails['status'] !== 'APPROVED') {
        throw new Exception('Order not approved');
    }

    // Redirect to success page with order ID
    header("Location: /pages/payment-success.html?booking_id=" . $payment['booking_id'] . "&order_id=" . $orderId);

} catch (Exception $e) {
    header('Location: /pages/payment.html?error=' . urlencode($e->getMessage()));
}

function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

?>
