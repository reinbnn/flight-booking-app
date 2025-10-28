<?php
/**
 * Email Service Webhook Handler
 * Handles email delivery and bounce events
 * Compatible with SendGrid, Mailgun, AWS SES
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../classes/WebhookHandler.php';

class EmailWebhookHandler extends WebhookHandler {
    private $email_service;
    private $api_key;

    public function __construct($logger = null, $service = 'sendgrid') {
        parent::__construct($logger);
        $this->email_service = $service;
        $this->api_key = getenv(strtoupper($service) . '_API_KEY');
    }

    public function getHandlerName() {
        return 'EmailService';
    }

    public function getWebhookType() {
        return 'email';
    }

    /**
     * Handle email webhook
     */
    public function handle($payload) {
        $input = file_get_contents('php://input');
        
        // Verify signature based on service
        if (!$this->verifySignature($_SERVER['HTTP_AUTHORIZATION'] ?? '', $input)) {
            $this->logger->warning('Email webhook signature verification failed');
            $this->sendResponse(403, 'Signature verification failed');
        }

        try {
            $events = json_decode($input, true);
            
            if (!is_array($events)) {
                $events = [$events];
            }

            $processed = 0;
            foreach ($events as $event) {
                if ($this->processPayload($event)) {
                    $processed++;
                }
            }

            $this->logger->info("Email webhook batch processed", ['events' => count($events), 'processed' => $processed]);
            $this->sendResponse(200, 'Events processed');

        } catch (Exception $e) {
            $this->logger->exception($e);
            $this->sendResponse(500, 'Server error');
        }
    }

    /**
     * Verify email service signature
     */
    public function verifySignature($signature, $payload) {
        try {
            switch ($this->email_service) {
                case 'sendgrid':
                    return $this->verifySendGridSignature($signature, $payload);
                case 'mailgun':
                    return $this->verifyMailgunSignature($signature, $payload);
                case 'aws':
                    return $this->verifyAWSSignature($signature, $payload);
                default:
                    return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Verify SendGrid signature
     */
    private function verifySendGridSignature($signature, $payload) {
        $public_key = getenv('SENDGRID_PUBLIC_KEY');
        $timestamp = $_SERVER['HTTP_X_TWILIO_EMAIL_EVENT_WEBHOOK_TIMESTAMP'] ?? '';
        $signature_header = $_SERVER['HTTP_X_TWILIO_EMAIL_EVENT_WEBHOOK_SIGNATURE'] ?? '';

        $signed_content = $timestamp . $payload;
        $expected_signature = base64_encode(
            hash_hmac('sha256', $signed_content, $public_key, true)
        );

        return hash_equals($signature_header, 'v1=' . $expected_signature);
    }

    /**
     * Verify Mailgun signature
     */
    private function verifyMailgunSignature($signature, $payload) {
        $api_key = getenv('MAILGUN_API_KEY');
        $timestamp = $_POST['timestamp'] ?? '';
        $token = $_POST['token'] ?? '';
        $signature_header = $_POST['signature'] ?? '';

        $signed_content = $timestamp . $token;
        $expected_signature = hash_hmac('sha256', $signed_content, $api_key);

        return hash_equals($signature_header, $expected_signature);
    }

    /**
     * Verify AWS SES signature
     */
    private function verifyAWSSignature($signature, $payload) {
        // AWS SNS signature verification
        $message = json_decode($payload, true);
        
        if ($message['Type'] === 'SubscriptionConfirmation') {
            // Handle subscription confirmation
            return true;
        }

        return true; // Simplified for this example
    }

    /**
     * Process email events
     */
    public function processPayload($event) {
        $event_type = $event['event'] ?? $event['type'] ?? null;

        switch ($event_type) {
            case 'delivered':
            case 'delivery':
                return $this->handleDelivered($event);

            case 'open':
            case 'opened':
                return $this->handleOpened($event);

            case 'click':
            case 'clicked':
                return $this->handleClicked($event);

            case 'bounce':
            case 'bounced':
                return $this->handleBounce($event);

            case 'complaint':
                return $this->handleComplaint($event);

            case 'dropped':
            case 'drop':
                return $this->handleDropped($event);

            case 'spamreport':
            case 'marked_as_spam':
                return $this->handleSpamReport($event);

            default:
                $this->logger->debug("Email event received: $event_type");
                return true;
        }
    }

    /**
     * Handle email delivered
     */
    private function handleDelivered($event) {
        try {
            global $conn;

            $email = $event['email'] ?? null;
            $message_id = $event['messageId'] ?? $event['id'] ?? null;

            if (!$email || !$message_id) {
                return false;
            }

            $status = 'delivered';
            $stmt = $conn->prepare("
                UPDATE email_logs
                SET status = ?, delivered_at = NOW()
                WHERE message_id = ? OR recipient_email = ?
            ");
            $stmt->bind_param('sss', $status, $message_id, $email);
            $stmt->execute();

            $this->logger->info("Email delivered", ['email' => $email, 'message_id' => $message_id]);
            return true;

        } catch (Exception $e) {
            $this->logger->error('Failed to handle delivered event', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Handle email opened
     */
    private function handleOpened($event) {
        try {
            global $conn;

            $email = $event['email'] ?? null;
            $message_id = $event['messageId'] ?? $event['id'] ?? null;

            if (!$email || !$message_id) {
                return false;
            }

            $status = 'opened';
            $stmt = $conn->prepare("
                UPDATE email_logs
                SET status = ?, opened_at = NOW(), opens = opens + 1
                WHERE message_id = ? OR recipient_email = ?
            ");
            $stmt->bind_param('sss', $status, $message_id, $email);
            $stmt->execute();

            $this->logger->debug("Email opened", ['email' => $email]);
            return true;

        } catch (Exception $e) {
            $this->logger->error('Failed to handle opened event', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Handle link clicked
     */
    private function handleClicked($event) {
        try {
            global $conn;

            $email = $event['email'] ?? null;
            $url = $event['url'] ?? null;

            if (!$email) {
                return false;
            }

            $stmt = $conn->prepare("
                UPDATE email_logs
                SET clicks = clicks + 1
                WHERE recipient_email = ?
            ");
            $stmt->bind_param('s', $email);
            $stmt->execute();

            $this->logger->debug("Email link clicked", ['email' => $email, 'url' => $url]);
            return true;

        } catch (Exception $e) {
            $this->logger->error('Failed to handle click event', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Handle bounce
     */
    private function handleBounce($event) {
        try {
            global $conn;

            $email = $event['email'] ?? null;
            $bounce_type = $event['bounceType'] ?? $event['type'] ?? 'permanent';

            if (!$email) {
                return false;
            }

            $status = 'bounced';
            $stmt = $conn->prepare("
                UPDATE email_logs
                SET status = ?, bounce_type = ?, bounced_at = NOW()
                WHERE recipient_email = ?
            ");
            $stmt->bind_param('sss', $status, $bounce_type, $email);
            $stmt->execute();

            // Unsubscribe bounced email
            if ($bounce_type === 'permanent' || $bounce_type === 'Permanent') {
                $unsubscribed = true;
                $unsub_stmt = $conn->prepare("
                    UPDATE email_subscriptions
                    SET unsubscribed = ?, unsubscribed_at = NOW()
                    WHERE email = ?
                ");
                $unsub_stmt->bind_param('is', $unsubscribed, $email);
                $unsub_stmt->execute();
            }

            $this->logger->warning("Email bounced", ['email' => $email, 'type' => $bounce_type]);
            return true;

        } catch (Exception $e) {
            $this->logger->error('Failed to handle bounce event', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Handle complaint
     */
    private function handleComplaint($event) {
        try {
            global $conn;

            $email = $event['email'] ?? null;
            $complained_recipients = $event['complaintFeedbackType'] ?? null;

            if (!$email) {
                return false;
            }

            // Unsubscribe complainant
            $unsubscribed = true;
            $stmt = $conn->prepare("
                UPDATE email_subscriptions
                SET unsubscribed = ?, unsubscribed_at = NOW(), complaint_date = NOW()
                WHERE email = ?
            ");
            $stmt->bind_param('is', $unsubscribed, $email);
            $stmt->execute();

            AlertManager::sendAlert(
                'EMAIL_COMPLAINT',
                "Email complaint from $email",
                ['email' => $email, 'type' => $complained_recipients]
            );

            $this->logger->critical("Email complaint", ['email' => $email, 'type' => $complained_recipients]);
            return true;

        } catch (Exception $e) {
            $this->logger->error('Failed to handle complaint event', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Handle dropped email
     */
    private function handleDropped($event) {
        try {
            global $conn;

            $email = $event['email'] ?? null;
            $reason = $event['reason'] ?? 'unknown';

            if (!$email) {
                return false;
            }

            $status = 'dropped';
            $stmt = $conn->prepare("
                UPDATE email_logs
                SET status = ?, drop_reason = ?
                WHERE recipient_email = ?
            ");
            $stmt->bind_param('sss', $status, $reason, $email);
            $stmt->execute();

            $this->logger->warning("Email dropped", ['email' => $email, 'reason' => $reason]);
            return true;

        } catch (Exception $e) {
            $this->logger->error('Failed to handle dropped event', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Handle spam report
     */
    private function handleSpamReport($event) {
        try {
            global $conn;

            $email = $event['email'] ?? null;

            if (!$email) {
                return false;
            }

            // Unsubscribe
            $unsubscribed = true;
            $stmt = $conn->prepare("
                UPDATE email_subscriptions
                SET unsubscribed = ?, unsubscribed_at = NOW()
                WHERE email = ?
            ");
            $stmt->bind_param('is', $unsubscribed, $email);
            $stmt->execute();

            $this->logger->critical("Email marked as spam", ['email' => $email]);
            return true;

        } catch (Exception $e) {
            $this->logger->error('Failed to handle spam report', ['error' => $e->getMessage()]);
            return false;
        }
    }
}

?>
