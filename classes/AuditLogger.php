<?php
/**
 * Audit Logger
 * Logs all important security and business events
 */

require_once __DIR__ . '/../classes/SecurityHelper.php';

class AuditLogger {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    /**
     * Log Action
     */
    public function log($action, $resource = null, $userId = null, $changes = null, $status = 'success') {
        try {
            $userId = $userId ?? ($_SESSION['user_id'] ?? null);
            $ipAddress = SecurityHelper::getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $changesJSON = $changes ? json_encode($changes) : null;

            $stmt = $this->conn->prepare("
                INSERT INTO security_audit_log 
                (user_id, action, resource, changes, ip_address, user_agent, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                'issssss',
                $userId,
                $action,
                $resource,
                $changesJSON,
                $ipAddress,
                $userAgent,
                $status
            );

            return $stmt->execute();

        } catch (Exception $e) {
            error_log('Audit log failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log User Creation
     */
    public function logUserCreation($userId, $userData) {
        return $this->log('USER_CREATED', 'users', null, [
            'user_id' => $userId,
            'email' => $userData['email'],
            'role' => $userData['role']
        ]);
    }

    /**
     * Log User Update
     */
    public function logUserUpdate($userId, $changes) {
        return $this->log('USER_UPDATED', 'users', null, [
            'user_id' => $userId,
            'changes' => $changes
        ]);
    }

    /**
     * Log Data Access
     */
    public function logDataAccess($resource, $recordId, $userId = null) {
        return $this->log('DATA_ACCESSED', $resource, $userId, [
            'record_id' => $recordId
        ]);
    }

    /**
     * Log Data Modification
     */
    public function logDataModification($action, $resource, $recordId, $changes = null, $userId = null) {
        return $this->log($action, $resource, $userId, [
            'record_id' => $recordId,
            'changes' => $changes
        ]);
    }

    /**
     * Log Permission Change
     */
    public function logPermissionChange($userId, $oldRole, $newRole) {
        return $this->log('PERMISSION_CHANGED', 'users', null, [
            'user_id' => $userId,
            'old_role' => $oldRole,
            'new_role' => $newRole
        ]);
    }

    /**
     * Log Payment
     */
    public function logPayment($paymentId, $amount, $status) {
        return $this->log('PAYMENT_PROCESSED', 'payments', null, [
            'payment_id' => $paymentId,
            'amount' => $amount,
            'status' => $status
        ]);
    }

    /**
     * Log Refund
     */
    public function logRefund($refundId, $amount, $status) {
        return $this->log('REFUND_PROCESSED', 'refunds', null, [
            'refund_id' => $refundId,
            'amount' => $amount,
            'status' => $status
        ]);
    }

    /**
     * Get Audit Trail
     */
    public function getAuditTrail($userId = null, $limit = 100) {
        $query = "SELECT * FROM security_audit_log";
        
        if ($userId) {
            $query .= " WHERE user_id = $userId";
        }

        $query .= " ORDER BY created_at DESC LIMIT $limit";

        return $this->conn->query($query)->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get Suspicious Activity
     */
    public function getSuspiciousActivity($hours = 24) {
        $query = "
            SELECT * FROM security_audit_log
            WHERE (
                action LIKE '%FAILED%' OR
                action LIKE '%DENIED%' OR
                status = 'failed'
            )
            AND created_at > DATE_SUB(NOW(), INTERVAL $hours HOUR)
            ORDER BY created_at DESC
        ";

        return $this->conn->query($query)->fetch_all(MYSQLI_ASSOC);
    }
}

?>
