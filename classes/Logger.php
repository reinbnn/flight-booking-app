<?php
/**
 * Logger Class
 * Comprehensive logging system with multiple channels
 */

class Logger {
    const LEVEL_DEBUG = 0;
    const LEVEL_INFO = 1;
    const LEVEL_WARNING = 2;
    const LEVEL_ERROR = 3;
    const LEVEL_CRITICAL = 4;

    private $log_dir;
    private $channels = [];
    private $context = [];

    public function __construct($log_dir = null) {
        $this->log_dir = $log_dir ?? __DIR__ . '/../logs';
        $this->ensureLogDir();
    }

    /**
     * Ensure log directory exists
     */
    private function ensureLogDir() {
        if (!is_dir($this->log_dir)) {
            mkdir($this->log_dir, 0755, true);
        }
    }

    /**
     * Set context data
     */
    public function setContext($context = []) {
        $this->context = array_merge($this->context, $context);
        return $this;
    }

    /**
     * Clear context
     */
    public function clearContext() {
        $this->context = [];
        return $this;
    }

    /**
     * Add context data
     */
    public function addContext($key, $value) {
        $this->context[$key] = $value;
        return $this;
    }

    /**
     * Get context data
     */
    public function getContext() {
        return $this->context;
    }

    /**
     * Debug level logging
     */
    public function debug($message, $data = []) {
        return $this->log(self::LEVEL_DEBUG, 'DEBUG', $message, $data);
    }

    /**
     * Info level logging
     */
    public function info($message, $data = []) {
        return $this->log(self::LEVEL_INFO, 'INFO', $message, $data);
    }

    /**
     * Warning level logging
     */
    public function warning($message, $data = []) {
        return $this->log(self::LEVEL_WARNING, 'WARNING', $message, $data);
    }

    /**
     * Error level logging
     */
    public function error($message, $data = []) {
        return $this->log(self::LEVEL_ERROR, 'ERROR', $message, $data);
    }

    /**
     * Critical level logging
     */
    public function critical($message, $data = []) {
        return $this->log(self::LEVEL_CRITICAL, 'CRITICAL', $message, $data);
    }

    /**
     * Log exception
     */
    public function exception(Exception $exception, $level = self::LEVEL_ERROR) {
        $data = [
            'exception_class' => get_class($exception),
            'exception_code' => $exception->getCode(),
            'exception_file' => $exception->getFile(),
            'exception_line' => $exception->getLine(),
            'exception_trace' => $exception->getTraceAsString()
        ];

        if (method_exists($exception, 'toArray')) {
            $data = array_merge($data, $exception->toArray());
        }

        $level_name = $this->getLevelName($level);
        return $this->log($level, $level_name, $exception->getMessage(), $data);
    }

    /**
     * Main logging method
     */
    private function log($level, $level_name, $message, $data = []) {
        $timestamp = microtime(true);
        $date = date('Y-m-d H:i:s', (int)$timestamp);
        $microseconds = str_pad((int)(($timestamp - floor($timestamp)) * 1000000), 6, '0', STR_PAD_LEFT);

        $log_entry = [
            'timestamp' => $timestamp,
            'date' => $date,
            'microseconds' => $microseconds,
            'level' => $level_name,
            'message' => $message,
            'data' => array_merge($data, $this->context),
            'ip_address' => $this->getClientIP(),
            'request_id' => $this->getRequestId(),
            'user_id' => $_SESSION['user_id'] ?? null,
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'CLI',
        ];

        // Write to main log
        $this->writeToChannel('app', $log_entry);

        // Write to level-specific channel
        $this->writeToChannel(strtolower($level_name), $log_entry);

        // Write to database for critical errors
        if ($level >= self::LEVEL_ERROR) {
            $this->writeToDatabase($log_entry);
        }

        return true;
    }

    /**
     * Write to log channel
     */
    private function writeToChannel($channel, $log_entry) {
        $file = $this->log_dir . '/' . $channel . '.log';
        $line = $this->formatLogEntry($log_entry);
        error_log($line . "
", 3, $file);
    }

    /**
     * Format log entry
     */
    private function formatLogEntry($entry) {
        $formatted = sprintf(
            "[%s.%s] [%s] %s | IP: %s | User: %s | %s %s | Request ID: %s",
            $entry['date'],
            $entry['microseconds'],
            $entry['level'],
            $entry['message'],
            $entry['ip_address'],
            $entry['user_id'] ?? 'anonymous',
            $entry['method'],
            $entry['uri'],
            $entry['request_id']
        );

        if (!empty($entry['data'])) {
            $formatted .= " | " . json_encode($entry['data']);
        }

        return $formatted;
    }

    /**
     * Write to database
     */
    private function writeToDatabase($log_entry) {
        try {
            global $conn;
            if (!$conn) {
                return false;
            }

            // Create error_logs table if needed
            $conn->query("
                CREATE TABLE IF NOT EXISTS error_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    level VARCHAR(50),
                    message VARCHAR(500),
                    data JSON,
                    ip_address VARCHAR(45),
                    user_id INT,
                    request_id VARCHAR(100),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_level (level),
                    INDEX idx_user_id (user_id),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            $stmt = $conn->prepare("
                INSERT INTO error_logs (level, message, data, ip_address, user_id, request_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $data_json = json_encode($log_entry['data']);
            $stmt->bind_param(
                'ssssis',
                $log_entry['level'],
                $log_entry['message'],
                $data_json,
                $log_entry['ip_address'],
                $log_entry['user_id'],
                $log_entry['request_id']
            );

            return $stmt->execute();

        } catch (Exception $e) {
            // Silently fail - don't cause errors in logging
            return false;
        }
    }

    /**
     * Get client IP
     */
    private function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
        return trim($ip);
    }

    /**
     * Get or create request ID
     */
    private function getRequestId() {
        if (!isset($_SERVER['HTTP_X_REQUEST_ID'])) {
            $_SERVER['HTTP_X_REQUEST_ID'] = uniqid('req_', true);
        }
        return $_SERVER['HTTP_X_REQUEST_ID'];
    }

    /**
     * Get level name
     */
    private function getLevelName($level) {
        $levels = [
            self::LEVEL_DEBUG => 'DEBUG',
            self::LEVEL_INFO => 'INFO',
            self::LEVEL_WARNING => 'WARNING',
            self::LEVEL_ERROR => 'ERROR',
            self::LEVEL_CRITICAL => 'CRITICAL'
        ];
        return $levels[$level] ?? 'UNKNOWN';
    }

    /**
     * Get logs by level
     */
    public function getLogsByLevel($level, $limit = 100) {
        try {
            global $conn;
            if (!$conn) {
                return [];
            }

            $stmt = $conn->prepare("
                SELECT * FROM error_logs
                WHERE level = ?
                ORDER BY created_at DESC
                LIMIT ?
            ");

            $level_name = $this->getLevelName($level);
            $stmt->bind_param('si', $level_name, $limit);
            $stmt->execute();

            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get logs for user
     */
    public function getLogsForUser($user_id, $limit = 50) {
        try {
            global $conn;
            if (!$conn) {
                return [];
            }

            $stmt = $conn->prepare("
                SELECT * FROM error_logs
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ?
            ");

            $stmt->bind_param('ii', $user_id, $limit);
            $stmt->execute();

            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get recent errors
     */
    public function getRecentErrors($minutes = 60, $limit = 100) {
        try {
            global $conn;
            if (!$conn) {
                return [];
            }

            $stmt = $conn->prepare("
                SELECT * FROM error_logs
                WHERE level IN ('ERROR', 'CRITICAL')
                AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
                ORDER BY created_at DESC
                LIMIT ?
            ");

            $stmt->bind_param('ii', $minutes, $limit);
            $stmt->execute();

            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get log statistics
     */
    public function getStatistics($hours = 24) {
        try {
            global $conn;
            if (!$conn) {
                return [];
            }

            $result = $conn->query("
                SELECT 
                    level,
                    COUNT(*) as count,
                    MIN(created_at) as first_occurrence,
                    MAX(created_at) as last_occurrence
                FROM error_logs
                WHERE created_at > DATE_SUB(NOW(), INTERVAL $hours HOUR)
                GROUP BY level
                ORDER BY count DESC
            ");

            return $result->fetch_all(MYSQLI_ASSOC);

        } catch (Exception $e) {
            return [];
        }
    }
}

?>
