<?php
/**
 * SMS Service Webhook Handler
 * Handles SMS delivery events (Twilio, AWS SNS, etc)
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../classes/WebhookHandler.php';

class SMSWebhookHandler extends WebhookHandler {
    private $sms_service;
    private $auth_token;

    public function __construct($logger = null, $service = 'twilio') {
        parent::__construct($logger);
        $this->sms_service = $service;
        $this->auth_token = getenv(strtoupper($service) . '_AUTH_TOKEN');
    }

    public function getHandlerName() {
        return 'SMSService';
    }

    public function getWebhookType() {
        return 'sms';
    }

    /**
     * Handle SMS webhook
     */
    public function handle($payload) {
        $input = file_get_contents('php://input');
        
        // Parse based on service
        if ($this->sms_service === 'twilio') {
            $event = $_POST; // Twilio sends form-encoded
        } else {
            $event = json_decode($input, true);
        }

        // Verify signature
        if (!$this->verifySignature($_SERVER['HTTP_X_TWILIO_SIGNATURE'] ?? '', $input)) {
            $this->logger->warning('SMS webhook signature verification failed');
            $this->sendResponse(403, 'Signature verification failed');
        }

        try {
            $result = $this->processPayload($event);

            if ($result) {
                $this->logWebhook('success', $event['MessageStatus'] ?? 'unknown', $event);
                $this->sendResponse(200, 'Webhook processed');
            } else {
                $this->logWebhook('failed', $event['MessageStatus'] ?? 'unknown', $event);
                $this->sendResponse(500, 'Processing failed');
            }
        } catch (Exception $e) {
            $this->logger->exception($e);
            $this->logWebhook('error', 'unknown', []);
            $this->sendResponse(500, 'Server error');
        }
    }

    /**
     * Verify SMS service signature
     */
    public function verifySignature($signature, $payload) {
        try {
            if ($this->sms_service === 'twilio') {
                return $this->verifyTwilioSignature($signature, $payload);
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Verify Twilio signature
     */
    private function verifyTwilioSignature($signature, $payload) {
        $url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $params = $_POST;

        $data = $url;
        foreach ($params as $key => $value) {
            $data .= $key . $value;
        }

        $expected = base64_encode(hash_hmac('sha1', $data, $this->auth_token, true));
        return hash_equals($signature, $expected);
    }

    /**
     * Process SMS events
     */
    public function processPayload($event) {
        $message_status = $event['MessageStatus'] ?? $event['status'] ?? null;
        $message_sid = $event['MessageSid'] ?? $event['id'] ?? null;

        switch ($message_status) {
            case 'delivered':
                return $this->handleDelivered($event);

            case 'failed':
                return $this->handleFailed($event);

            case 'undelivered':
                return $this->handleUndelivered($event);

            case 'sent':
                return $this->handleSent($event);

            case 'queued':
            case 'sending':
                return true; // Just log

            default:
                $this->logger->debug("SMS event received: $message_status");
                return true;
        }
    }

    /**
     * Handle SMS delivered
     */
    private function handleDelivered($event) {
        try {
            global $conn;

            $message_sid = $event['MessageSid'] ?? null;
            $phone = $event['To'] ?? null;

            if (!$message_sid || !$phone) {
                return false;
            }

            $status = 'delivered';
            $stmt = $conn->prepare("
                UPDATE sms_logs
                SET status = ?, delivered_at = NOW()
                WHERE message_sid = ? OR recipient_phone = ?
            ");
            $stmt->bind_param('sss', $status, $message_sid, $phone);
            $stmt->execute();

            $this->logger->info("SMS delivered", ['phone' => $phone, 'message_sid' => $message_sid]);
            return true;

        } catch (Exception $e) {
            $this->logger->error('Failed to handle SMS delivery', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Handle SMS failed
     */
    private function handleFailed($event) {
        try {
            global $conn;

            $message_sid = $event['MessageSid'] ?? null;
            $phone = $event['To'] ?? null;
            $error_code = $event['ErrorCode'] ?? null;
            $error_message = $event['ErrorMessage'] ?? 'Unknown error';

            if (!$message_sid || !$phone) {
                return false;
            }

            $status = 'failed';
            $stmt = $conn->prepare("
                UPDATE sms_logs
                SET status = ?, error_code = ?, error_message = ?, failed_at = NOW()
                WHERE message_sid = ? OR recipient_phone = ?
            ");
            $stmt->bind_param('sisss', $status, $error_code, $error_message, $message_sid, $phone);
            $stmt->execute();

            AlertManager::sendAlert(
                'SMS_DELIVERY_FAILED',
                "SMS to $phone failed: $error_message",
                ['phone' => $phone, 'error_code' => $error_code, 'error_message' => $error_message]
            );

            $this->logger->warning("SMS delivery failed", [
                'phone' => $phone,
                'error_code' => $error_code,
                'error_message' => $error_message
            ]);
            return true;

        } catch (Exception $e) {
            $this->logger->error('Failed to handle SMS failure', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Handle SMS undelivered
     */
    private function handleUndelivered($event) {
        try {
            global $conn;

            $message_sid = $event['MessageSid'] ?? null;
            $phone = $event['To'] ?? null;

            if (!$message_sid || !$phone) {
                return false;
            }

            $status = 'undelivered';
            $stmt = $conn->prepare("
                UPDATE sms_logs
                SET status = ?
                WHERE message_sid = ? OR recipient_phone = ?
            ");
            $stmt->bind_param('sss', $status, $message_sid, $phone);
            $stmt->execute();

            $this->logger->warning("SMS undelivered", ['phone' => $phone]);
            return true;

        } catch (Exception $e) {
            $this->logger->error('Failed to handle SMS undelivered', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Handle SMS sent
     */
    private function handleSent($event) {
        try {
            global $conn;

            $message_sid = $event['MessageSid'] ?? null;
            $phone = $event['To'] ?? null;

            if (!$message_sid || !$phone) {
                return false;
            }

            $status = 'sent';
            $stmt = $conn->prepare("
                UPDATE sms_logs
                SET status = ?, sent_at = NOW()
                WHERE message_sid = ? OR recipient_phone = ?
            ");
            $stmt->bind_param('sss', $status, $message_sid, $phone);
            $stmt->execute();

            $this->logger->debug("SMS sent", ['phone' => $phone]);
            return true;

        } catch (Exception $e) {
            $this->logger->error('Failed to handle SMS sent', ['error' => $e->getMessage()]);
            return false;
        }
    }
}

?>
