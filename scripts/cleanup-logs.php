<?php
/**
 * Log Cleanup Script
 * Remove old log files and database entries
 * Run via cron: 0 2 * * * php /var/www/html/flight-booking-app/scripts/cleanup-logs.php
 */

require_once __DIR__ . '/../config/bootstrap.php';

global $global_logger;
$global_logger->info('Starting log cleanup');

// Configuration
$log_retention_days = 90; // Keep logs for 90 days
$log_dir = __DIR__ . '/../logs';

try {
    // Cleanup old log files
    $files = glob($log_dir . '/*.log');
    $deleted_files = 0;

    foreach ($files as $file) {
        $file_age = (time() - filemtime($file)) / 86400; // Convert to days

        if ($file_age > $log_retention_days) {
            unlink($file);
            $deleted_files++;
        }
    }

    // Cleanup old database entries
    global $conn;
    if ($conn) {
        // Clean error logs (keep 90 days)
        $result = $conn->query("
            DELETE FROM error_logs
            WHERE created_at < DATE_SUB(NOW(), INTERVAL $log_retention_days DAY)
        ");
        $deleted_errors = $conn->affected_rows;

        // Clean performance logs (keep 60 days)
        $result = $conn->query("
            DELETE FROM performance_logs
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 60 DAY)
        ");
        $deleted_performance = $conn->affected_rows;

        // Clean security audit logs (keep 1 year for compliance)
        $result = $conn->query("
            DELETE FROM security_audit_log
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 365 DAY)
        ");
        $deleted_audit = $conn->affected_rows;

        $global_logger->info('Log cleanup completed', [
            'deleted_files' => $deleted_files,
            'deleted_error_logs' => $deleted_errors,
            'deleted_performance_logs' => $deleted_performance,
            'deleted_audit_logs' => $deleted_audit,
        ]);

        echo "Cleanup completed successfully
";
        echo "Deleted files: $deleted_files
";
        echo "Deleted error logs: $deleted_errors
";
        echo "Deleted performance logs: $deleted_performance
";
        echo "Deleted audit logs: $deleted_audit
";
    }

} catch (Exception $e) {
    $global_logger->critical('Log cleanup failed: ' . $e->getMessage());
    echo "Error: " . $e->getMessage() . "
";
    exit(1);
}

?>
