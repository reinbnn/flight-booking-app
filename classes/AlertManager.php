<?php
/**
 * Alert Manager
 * Send alerts for critical errors and performance issues
 */

class AlertManager {
    private static $logger;
    private static $alert_threshold = 10;

    public static function initialize($logger) {
        self::$logger = $logger;
    }

    /**
     * Check for error spike
     */
    public static function checkErrorSpike() {
        try {
            global $conn;
            if (!$conn) return;

            $result = $conn->query("
                SELECT COUNT(*) as count FROM error_logs
                WHERE level IN ('ERROR', 'CRITICAL')
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");

            $count = $result->fetch_assoc()['count'];

            if ($count > self::$alert_threshold) {
                self::sendAlert(
                    'ERROR_SPIKE',
                    "Error spike detected: $count errors in last hour",
                    ['error_count' => $count]
                );
            }
        } catch (Exception $e) {
            // Silent fail
        }
    }

    /**
     * Check for performance degradation
     */
    public static function checkPerformanceDegradation() {
        try {
            global $conn;
            if (!$conn) return;

            $result = $conn->query("
                SELECT AVG(total_time_ms) as avg_time FROM performance_logs
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");

            $avg_time = $result->fetch_assoc()['avg_time'];

            if ($avg_time > 1000) { // More than 1 second average
                self::sendAlert(
                    'PERFORMANCE_DEGRADATION',
                    "Performance degradation: Avg response time is " . round($avg_time, 2) . "ms",
                    ['avg_response_time_ms' => $avg_time]
                );
            }
        } catch (Exception $e) {
            // Silent fail
        }
    }

    /**
     * Check database health
     */
    public static function checkDatabaseHealth() {
        try {
            global $conn;
            if (!$conn) {
                self::sendAlert(
                    'DATABASE_CONNECTION_FAILED',
                    'Cannot connect to database',
                    []
                );
                return;
            }

            $result = $conn->query("SELECT 1");
            if (!$result) {
                self::sendAlert(
                    'DATABASE_QUERY_FAILED',
                    'Database query failed',
                    []
                );
            }
        } catch (Exception $e) {
            self::sendAlert(
                'DATABASE_ERROR',
                'Database error: ' . $e->getMessage(),
                []
            );
        }
    }

    /**
     * Send alert
     */
    public static function sendAlert($alert_type, $message, $data = []) {
        try {
            global $conn;
            if (!$conn) return;

            // Create alerts table if needed
            $conn->query("
                CREATE TABLE IF NOT EXISTS system_alerts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    alert_type VARCHAR(100),
                    message VARCHAR(500),
                    data JSON,
                    sent BOOLEAN DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_alert_type (alert_type),
                    INDEX idx_sent (sent)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            // Check if alert already sent in last 30 minutes
            $check = $conn->prepare("
                SELECT id FROM system_alerts
                WHERE alert_type = ? AND sent = 0
                AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
                LIMIT 1
            ");

            $check->bind_param('s', $alert_type);
            $check->execute();

            if ($check->get_result()->num_rows > 0) {
                return; // Alert already sent recently
            }

            // Insert new alert
            $stmt = $conn->prepare("
                INSERT INTO system_alerts (alert_type, message, data, sent)
                VALUES (?, ?, ?, 0)
            ");

            $data_json = json_encode($data);
            $stmt->bind_param('sss', $alert_type, $message, $data_json);
            $stmt->execute();

            // Send notification to admins
            self::notifyAdmins($alert_type, $message);

            if (self::$logger) {
                self::$logger->warning("System alert: $alert_type - $message", $data);
            }

        } catch (Exception $e) {
            // Silent fail
        }
    }

    /**
     * Notify admins
     */
    private static function notifyAdmins($alert_type, $message) {
        try {
            global $conn;
            if (!$conn) return;

            // Get admin emails
            $result = $conn->query("
                SELECT email FROM users
                WHERE role = 'admin'
                LIMIT 5
            ");

            $admins = $result->fetch_all(MYSQLI_ASSOC);

            foreach ($admins as $admin) {
                // Send email notification
                $subject = "SKYJET Alert: $alert_type";
                $body = "Alert Type: $alert_type
Message: $message
Time: " . date('Y-m-d H:i:s');
                
                mail($admin['email'], $subject, $body);
            }
        } catch (Exception $e) {
            // Silent fail
        }
    }

    /**
     * Mark alert as sent
     */
    public static function markAlertSent($alert_id) {
        try {
            global $conn;
            if (!$conn) return false;

            $stmt = $conn->prepare("UPDATE system_alerts SET sent = 1 WHERE id = ?");
            $stmt->bind_param('i', $alert_id);
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get pending alerts
     */
    public static function getPendingAlerts() {
        try {
            global $conn;
            if (!$conn) return [];

            $result = $conn->query("
                SELECT * FROM system_alerts
                WHERE sent = 0
                ORDER BY created_at DESC
                LIMIT 20
            ");

            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}

?>
