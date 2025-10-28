<?php
/**
 * Base Webhook Handler
 * Abstract base class for all webhook handlers
 */

abstract class WebhookHandler {
    protected $logger;
    protected $event;
    protected $payload;
    protected $signature;
    protected $timestamp;

    public function __construct($logger = null) {
        global $global_logger;
        $this->logger = $logger ?? $global_logger;
        $this->timestamp = microtime(true);
    }

    /**
     * Handle incoming webhook
     */
    abstract public function handle($payload);

    /**
     * Verify webhook signature
     */
    abstract public function verifySignature($signature, $payload);

    /**
     * Process webhook payload
     */
    abstract public function processPayload($payload);

    /**
     * Get webhook type
     */
    abstract public function getWebhookType();

    /**
     * Get handler name
     */
    abstract public function getHandlerName();

    /**
     * Log webhook event
     */
    protected function logWebhook($status, $event_type, $data = []) {
        $log_data = [
            'handler' => $this->getHandlerName(),
            'event_type' => $event_type,
            'status' => $status,
            'webhook_type' => $this->getWebhookType(),
            'data' => $data,
            'processing_time_ms' => round((microtime(true) - $this->timestamp) * 1000, 2),
        ];

        if ($status === 'success') {
            $this->logger->info("Webhook processed: $event_type", $log_data);
        } else {
            $this->logger->error("Webhook failed: $event_type", $log_data);
        }

        // Log to database
        $this->saveWebhookLog($event_type, $status, $data);
    }

    /**
     * Save webhook to database
     */
    protected function saveWebhookLog($event_type, $status, $data) {
        try {
            global $conn;
            if (!$conn) return;

            // Create table if needed
            $conn->query("
                CREATE TABLE IF NOT EXISTS webhook_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    handler_name VARCHAR(100),
                    webhook_type VARCHAR(100),
                    event_type VARCHAR(100),
                    payload JSON,
                    status VARCHAR(50),
                    ip_address VARCHAR(45),
                    processing_time_ms DECIMAL(10, 2),
                    error_message VARCHAR(500),
                    retry_count INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    
                    INDEX idx_handler (handler_name),
                    INDEX idx_webhook_type (webhook_type),
                    INDEX idx_event_type (event_type),
                    INDEX idx_status (status),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            $handler = $this->getHandlerName();
            $webhook_type = $this->getWebhookType();
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $processing_time = round((microtime(true) - $this->timestamp) * 1000, 2);
            $payload_json = json_encode($data);

            $stmt = $conn->prepare("
                INSERT INTO webhook_logs
                (handler_name, webhook_type, event_type, payload, status, ip_address, processing_time_ms)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                'ssssssd',
                $handler,
                $webhook_type,
                $event_type,
                $payload_json,
                $status,
                $ip,
                $processing_time
            );

            $stmt->execute();
        } catch (Exception $e) {
            $this->logger->error('Failed to save webhook log', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Queue webhook for retry
     */
    protected function queueForRetry($event_type, $payload, $error_message = null, $retry_count = 0) {
        try {
            global $conn;
            if (!$conn) return;

            // Create queue table if needed
            $conn->query("
                CREATE TABLE IF NOT EXISTS webhook_queue (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    handler_name VARCHAR(100),
                    webhook_type VARCHAR(100),
                    event_type VARCHAR(100),
                    payload JSON,
                    error_message VARCHAR(500),
                    retry_count INT DEFAULT 0,
                    next_retry_at TIMESTAMP,
                    max_retries INT DEFAULT 5,
                    status VARCHAR(50),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    
                    INDEX idx_next_retry (next_retry_at),
                    INDEX idx_status (status),
                    INDEX idx_handler (handler_name)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            $handler = $this->getHandlerName();
            $webhook_type = $this->getWebhookType();
            $max_retries = 5;
            $backoff_seconds = min(300, (2 ** $retry_count) * 60); // Exponential backoff: 60s, 120s, 240s, etc.
            $next_retry = date('Y-m-d H:i:s', time() + $backoff_seconds);
            $payload_json = json_encode($payload);
            $status = 'pending';

            $stmt = $conn->prepare("
                INSERT INTO webhook_queue
                (handler_name, webhook_type, event_type, payload, error_message, retry_count, next_retry_at, max_retries, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                'sssssisi',
                $handler,
                $webhook_type,
                $event_type,
                $payload_json,
                $error_message,
                $retry_count,
                $next_retry,
                $max_retries,
                $status
            );

            $result = $stmt->execute();

            if ($result) {
                $this->logger->info("Webhook queued for retry: $event_type (attempt " . ($retry_count + 1) . ")");
            }

            return $result;
        } catch (Exception $e) {
            $this->logger->error('Failed to queue webhook', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Move to dead letter queue
     */
    protected function moveToDeadLetter($event_type, $payload, $reason = null, $retry_count = 0) {
        try {
            global $conn;
            if (!$conn) return;

            // Create DLQ table if needed
            $conn->query("
                CREATE TABLE IF NOT EXISTS webhook_dead_letter (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    handler_name VARCHAR(100),
                    webhook_type VARCHAR(100),
                    event_type VARCHAR(100),
                    payload JSON,
                    reason VARCHAR(500),
                    retry_count INT,
                    ip_address VARCHAR(45),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    
                    INDEX idx_handler (handler_name),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            $handler = $this->getHandlerName();
            $webhook_type = $this->getWebhookType();
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $payload_json = json_encode($payload);

            $stmt = $conn->prepare("
                INSERT INTO webhook_dead_letter
                (handler_name, webhook_type, event_type, payload, reason, retry_count, ip_address)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                'ssssssi',
                $handler,
                $webhook_type,
                $event_type,
                $payload_json,
                $reason,
                $retry_count,
                $ip
            );

            $result = $stmt->execute();

            if ($result) {
                $this->logger->critical("Webhook moved to dead letter: $event_type", [
                    'reason' => $reason,
                    'retry_count' => $retry_count
                ]);

                // Send alert
                AlertManager::sendAlert(
                    'WEBHOOK_DEAD_LETTER',
                    "Webhook $event_type failed permanently after $retry_count retries",
                    ['handler' => $handler, 'reason' => $reason]
                );
            }

            return $result;
        } catch (Exception $e) {
            $this->logger->error('Failed to move webhook to DLQ', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send HTTP response
     */
    protected function sendResponse($code = 200, $message = 'OK') {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['status' => $code === 200 ? 'success' : 'error', 'message' => $message]);
        exit;
    }
}

?>
