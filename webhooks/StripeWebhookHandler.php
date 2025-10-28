<?php
/**
 * Stripe Webhook Handler
 * Handles all Stripe payment events
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../classes/WebhookHandler.php';

class StripeWebhookHandler extends WebhookHandler {
    private $stripe_secret;
    private $stripe_webhook_secret;

    public function __construct($logger = null) {
        parent::__construct($logger);
        $this->stripe_secret = getenv('STRIPE_SECRET_KEY');
        $this->stripe_webhook_secret = getenv('STRIPE_WEBHOOK_SECRET');
    }

    public function getHandlerName() {
        return 'Stripe';
    }

    public function getWebhookType() {
        return 'payment';
    }

    /**
     * Handle Stripe webhook
     */
    public function handle($payload) {
        $input = file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null;

        if (!$sig_header) {
            $this->logger->warning('Stripe webhook missing signature header');
            $this->sendResponse(400, 'Missing signature header');
        }

        // Verify signature
        if (!$this->verifySignature($sig_header, $input)) {
            $this->logger->warning('Stripe webhook signature verification failed');
            $this->sendResponse(403, 'Signature verification failed');
        }

        $event = json_decode($input, true);

        try {
            // Process event
            $result = $this->processPayload($event);

            if ($result) {
                $this->logWebhook('success', $event['type'], $event['data']);
                $this->sendResponse(200, 'Webhook processed');
            } else {
                $this->logWebhook('failed', $event['type'], $event['data']);
                $this->queueForRetry($event['type'], $event['data'], 'Processing failed');
                $this->sendResponse(500, 'Processing failed');
            }
        } catch (Exception $e) {
            $this->logger->exception($e);
            $this->logWebhook('error', $event['type'] ?? 'unknown', $event['data'] ?? []);
            $this->queueForRetry($event['type'] ?? 'unknown', $event['data'] ?? [], $e->getMessage());
            $this->sendResponse(500, 'Server error');
        }
    }

    /**
     * Verify Stripe signature
     */
    public function verifySignature($signature, $payload) {
        try {
            $signed_content = $payload;
            $timestamp = explode(',', $signature)[0];
            $sig = explode(',', $signature)[1];

            $signed_content = $timestamp . '.' . $payload;
            $expected_sig = hash_hmac('sha256', $signed_content, $this->stripe_webhook_secret);

            return hash_equals($expected_sig, substr($sig, 5));
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Process Stripe events
     */
    public function processPayload($event) {
        $event_type = $event['type'] ?? null;
        $event_data = $event['data']['object'] ?? [];

        switch ($event_type) {
            case 'payment_intent.succeeded':
                return $this->handlePaymentSucceeded($event_data);

            case 'payment_intent.payment_failed':
                return $this->handlePaymentFailed($event_data);

            case 'charge.refunded':
                return $this->handleRefund($event_data);

            case 'charge.dispute.created':
                return $this->handleDispute($event_data);

            case 'customer.subscription.updated':
                return $this->handleSubscriptionUpdate($event_data);

            case 'invoice.payment_succeeded':
                return $this->handleInvoicePaid($event_data);

            default:
                $this->logger->info("Stripe event received: $event_type");
                return true;
        }
    }

    /**
     * Handle successful payment
     */
    private function handlePaymentSucceeded($payment_intent) {
        try {
            global $conn;

            $stripe_payment_id = $payment_intent['id'];
            $amount = $payment_intent['amount'] / 100; // Convert from cents
            $currency = $payment_intent['currency'];

            // Find booking by payment intent
            $stmt = $conn->prepare("
                SELECT id, user_id, booking_reference FROM bookings
                WHERE stripe_payment_intent_id = ?
            ");
            $stmt->bind_param('s', $stripe_payment_id);
            $stmt->execute();
            $booking = $stmt->get_result()->fetch_assoc();

            if (!$booking) {
                $this->logger->warning("Booking not found for payment intent: $stripe_payment_id");
                return false;
            }

            // Update booking status
            $status = 'confirmed';
            $update_stmt = $conn->prepare("
                UPDATE bookings 
                SET status = ?, payment_status = 'completed', stripe_charge_id = ?, paid_at = NOW()
                WHERE id = ?
            ");
            $update_stmt->bind_param('ssi', $status, $stripe_payment_id, $booking['id']);
            $update_stmt->execute();

            // Send confirmation email
            $this->sendConfirmationEmail($booking);

            $this->logger->info("Payment succeeded", [
                'booking_id' => $booking['id'],
                'amount' => $amount,
                'currency' => $currency,
                'stripe_payment_id' => $stripe_payment_id
            ]);

            return true;

        } catch (Exception $e) {
            $this->logger->exception($e);
            return false;
        }
    }

    /**
     * Handle payment failure
     */
    private function handlePaymentFailed($payment_intent) {
        try {
            global $conn;

            $stripe_payment_id = $payment_intent['id'];
            $last_payment_error = $payment_intent['last_payment_error']['message'] ?? 'Unknown error';

            // Find booking
            $stmt = $conn->prepare("
                SELECT id, user_id FROM bookings
                WHERE stripe_payment_intent_id = ?
            ");
            $stmt->bind_param('s', $stripe_payment_id);
            $stmt->execute();
            $booking = $stmt->get_result()->fetch_assoc();

            if (!$booking) {
                return false;
            }

            // Update booking status
            $status = 'payment_failed';
            $update_stmt = $conn->prepare("
                UPDATE bookings
                SET status = ?, payment_status = 'failed', payment_error = ?
                WHERE id = ?
            ");
            $update_stmt->bind_param('ssi', $status, $last_payment_error, $booking['id']);
            $update_stmt->execute();

            // Send failure email
            $this->sendFailureEmail($booking, $last_payment_error);

            $this->logger->warning("Payment failed", [
                'booking_id' => $booking['id'],
                'error' => $last_payment_error
            ]);

            return true;

        } catch (Exception $e) {
            $this->logger->exception($e);
            return false;
        }
    }

    /**
     * Handle refund
     */
    private function handleRefund($charge) {
        try {
            global $conn;

            $stripe_charge_id = $charge['id'];
            $refund_amount = $charge['amount_refunded'] / 100;

            // Find booking
            $stmt = $conn->prepare("
                SELECT id, booking_id FROM refunds
                WHERE stripe_charge_id = ?
            ");
            $stmt->bind_param('s', $stripe_charge_id);
            $stmt->execute();
            $refund = $stmt->get_result()->fetch_assoc();

            if (!$refund) {
                return false;
            }

            // Update refund status
            $status = 'completed';
            $update_stmt = $conn->prepare("
                UPDATE refunds
                SET status = ?, refunded_at = NOW(), stripe_refund_id = ?
                WHERE id = ?
            ");
            $update_stmt->bind_param('ssi', $status, $stripe_charge_id, $refund['id']);
            $update_stmt->execute();

            $this->logger->info("Refund processed", [
                'refund_id' => $refund['id'],
                'amount' => $refund_amount,
                'stripe_charge_id' => $stripe_charge_id
            ]);

            return true;

        } catch (Exception $e) {
            $this->logger->exception($e);
            return false;
        }
    }

    /**
     * Handle dispute
     */
    private function handleDispute($dispute) {
        try {
            global $conn;

            $stripe_charge_id = $dispute['charge'];
            $dispute_reason = $dispute['reason'];
            $dispute_amount = $dispute['amount'] / 100;

            $this->logger->critical("Stripe dispute created", [
                'charge_id' => $stripe_charge_id,
                'reason' => $dispute_reason,
                'amount' => $dispute_amount
            ]);

            // Alert admins
            AlertManager::sendAlert(
                'STRIPE_DISPUTE',
                "Dispute on charge $stripe_charge_id: $dispute_reason",
                ['charge_id' => $stripe_charge_id, 'reason' => $dispute_reason, 'amount' => $dispute_amount]
            );

            return true;

        } catch (Exception $e) {
            $this->logger->exception($e);
            return false;
        }
    }

    /**
     * Handle subscription update
     */
    private function handleSubscriptionUpdate($subscription) {
        $this->logger->info("Subscription updated", [
            'subscription_id' => $subscription['id'],
            'status' => $subscription['status']
        ]);
        return true;
    }

    /**
     * Handle invoice paid
     */
    private function handleInvoicePaid($invoice) {
        $this->logger->info("Invoice paid", [
            'invoice_id' => $invoice['id'],
            'amount' => $invoice['total'] / 100
        ]);
        return true;
    }

    /**
     * Send confirmation email
     */
    private function sendConfirmationEmail($booking) {
        // Implementation for sending email
        $this->logger->info("Confirmation email sent", ['booking_id' => $booking['id']]);
    }

    /**
     * Send failure email
     */
    private function sendFailureEmail($booking, $error) {
        // Implementation for sending email
        $this->logger->info("Failure email sent", ['booking_id' => $booking['id'], 'error' => $error]);
    }
}

?>
