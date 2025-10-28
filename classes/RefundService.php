<?php
/**
 * Refund Management Service
 * Handles refund requests, approvals, and processing
 */

require_once __DIR__ . '/../config/refund.php';
require_once __DIR__ . '/../config/stripe.php';
require_once __DIR__ . '/../config/paypal.php';

class RefundService {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    /**
     * Create Refund Request
     */
    public function createRefundRequest($paymentId, $bookingId, $userId, $amount, $reason, $notes = null) {
        try {
            // Get payment details
            $stmt = $this->conn->prepare("
                SELECT * FROM payments WHERE id = ?
            ");
            $stmt->bind_param('i', $paymentId);
            $stmt->execute();
            $payment = $stmt->get_result()->fetch_assoc();

            if (!$payment) {
                throw new Exception('Payment not found');
            }

            // Check if refund already exists for this payment
            $checkStmt = $this->conn->prepare("
                SELECT id FROM refunds 
                WHERE payment_id = ? AND status NOT IN ('rejected')
                LIMIT 1
            ");
            $checkStmt->bind_param('i', $paymentId);
            $checkStmt->execute();
            $existing = $checkStmt->get_result()->fetch_assoc();

            if ($existing) {
                throw new Exception('A refund request already exists for this payment');
            }

            // Validate amount
            if ($amount > $payment['amount']) {
                throw new Exception('Refund amount cannot exceed payment amount');
            }

            if ($amount <= 0) {
                throw new Exception('Refund amount must be greater than 0');
            }

            // Get refund policy
            $policy = $this->getApplicableRefundPolicy($bookingId);
            $applicablePercentage = $policy ? $policy['refund_percentage'] : 0;

            // Calculate refund
            $maxRefund = ($payment['amount'] * $applicablePercentage) / 100;
            if ($amount > $maxRefund && $applicablePercentage > 0) {
                // Can request more but will require manual approval
            }

            // Calculate processing fee
            $processingFee = ($amount * REFUND_PROCESSING_FEE_PERCENTAGE) / 100;
            $netRefund = $amount - $processingFee;

            // Insert refund request
            $stmt = $this->conn->prepare("
                INSERT INTO refunds (
                    payment_id, booking_id, user_id, amount, reason,
                    admin_notes, processing_fee, net_refund, 
                    refund_method, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");

            $stmt->bind_param(
                'iisdsdsss',
                $paymentId, $bookingId, $userId, $amount, $reason,
                $notes, $processingFee, $netRefund, $payment['payment_method']
            );

            if (!$stmt->execute()) {
                throw new Exception('Failed to create refund request: ' . $stmt->error);
            }

            $refundId = $this->conn->insert_id;

            // Log action
            $this->logRefundAction($refundId, 'requested', $userId, 'Refund request created');

            return [
                'success' => true,
                'refund_id' => $refundId,
                'message' => 'Refund request submitted successfully',
                'applicable_percentage' => $applicablePercentage,
                'amount' => $amount,
                'processing_fee' => $processingFee,
                'net_refund' => $netRefund
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get Applicable Refund Policy
     */
    private function getApplicableRefundPolicy($bookingId) {
        try {
            // Get booking departure date
            $stmt = $this->conn->prepare("
                SELECT departure_date FROM bookings WHERE id = ?
            ");
            $stmt->bind_param('s', $bookingId);
            $stmt->execute();
            $booking = $stmt->get_result()->fetch_assoc();

            if (!$booking) {
                return null;
            }

            $departureDate = strtotime($booking['departure_date']);
            $now = time();
            $daysDiff = floor(($departureDate - $now) / (60 * 60 * 24));

            // Get matching policy
            $stmt = $this->conn->prepare("
                SELECT * FROM refund_policies 
                WHERE is_active = 1 
                AND days_before_departure <= ? 
                ORDER BY days_before_departure DESC 
                LIMIT 1
            ");
            $stmt->bind_param('i', $daysDiff);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Approve Refund Request (Admin)
     */
    public function approveRefund($refundId, $adminId, $notes = null) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE refunds 
                SET status = 'approved', admin_id = ?, admin_notes = ?, approved_at = NOW()
                WHERE id = ?
            ");

            $stmt->bind_param('isi', $adminId, $notes, $refundId);

            if (!$stmt->execute()) {
                throw new Exception('Failed to approve refund');
            }

            $this->logRefundAction($refundId, 'approved', $adminId, $notes);

            // Send approval email
            $this->sendRefundApprovalEmail($refundId);

            return [
                'success' => true,
                'message' => 'Refund approved successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Reject Refund Request (Admin)
     */
    public function rejectRefund($refundId, $adminId, $reason) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE refunds 
                SET status = 'rejected', admin_id = ?, rejected_reason = ?
                WHERE id = ?
            ");

            $stmt->bind_param('isi', $adminId, $reason, $refundId);

            if (!$stmt->execute()) {
                throw new Exception('Failed to reject refund');
            }

            $this->logRefundAction($refundId, 'rejected', $adminId, $reason);

            // Send rejection email
            $this->sendRefundRejectionEmail($refundId, $reason);

            return [
                'success' => true,
                'message' => 'Refund rejected successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process Refund (Charge to payment gateway)
     */
    public function processRefund($refundId) {
        try {
            $refund = $this->getRefund($refundId);
            if (!$refund) {
                throw new Exception('Refund not found');
            }

            if ($refund['status'] !== 'approved') {
                throw new Exception('Refund must be approved before processing');
            }

            // Get payment details
            $stmt = $this->conn->prepare("
                SELECT * FROM payments WHERE id = ?
            ");
            $stmt->bind_param('i', $refund['payment_id']);
            $stmt->execute();
            $payment = $stmt->get_result()->fetch_assoc();

            if (!$payment) {
                throw new Exception('Payment not found');
            }

            $refundTxnId = null;

            // Process based on payment method
            if ($refund['refund_method'] === 'stripe') {
                $refundTxnId = $this->processStripeRefund($payment, $refund);
            } elseif ($refund['refund_method'] === 'paypal') {
                $refundTxnId = $this->processPayPalRefund($payment, $refund);
            } else {
                throw new Exception('Unknown refund method');
            }

            if (!$refundTxnId) {
                throw new Exception('Failed to process refund with payment gateway');
            }

            // Update refund status
            $stmt = $this->conn->prepare("
                UPDATE refunds 
                SET status = 'processed', 
                    refund_transaction_id = ?,
                    processed_at = NOW()
                WHERE id = ?
            ");

            $stmt->bind_param('si', $refundTxnId, $refundId);
            $stmt->execute();

            $this->logRefundAction($refundId, 'processed', null, 'Refund processed: ' . $refundTxnId);

            // Send confirmation email
            $this->sendRefundProcessedEmail($refundId);

            return [
                'success' => true,
                'message' => 'Refund processed successfully',
                'transaction_id' => $refundTxnId
            ];

        } catch (Exception $e) {
            $this->updateRefundStatus($refundId, 'failed');
            $this->logRefundAction($refundId, 'failed', null, 'Error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process Stripe Refund
     */
    private function processStripeRefund($payment, $refund) {
        try {
            require_once __DIR__ . '/../vendor/autoload.php';

            \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

            $refundObj = \Stripe\Refund::create([
                'charge' => $payment['transaction_id'],
                'amount' => intval($refund['amount'] * 100), // Convert to cents
                'reason' => 'requested_by_customer',
                'metadata' => [
                    'refund_id' => $refund['id'],
                    'booking_id' => $refund['booking_id']
                ]
            ]);

            return $refundObj->id;

        } catch (Exception $e) {
            throw new Exception('Stripe refund failed: ' . $e->getMessage());
        }
    }

    /**
     * Process PayPal Refund
     */
    private function processPayPalRefund($payment, $refund) {
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            require_once __DIR__ . '/../classes/PayPalService.php';

            $paypalService = new PayPalService();
            return $paypalService->refundPayment(
                $payment['transaction_id'],
                $refund['amount'],
                'Refund for booking: ' . $refund['booking_id']
            );

        } catch (Exception $e) {
            throw new Exception('PayPal refund failed: ' . $e->getMessage());
        }
    }

    /**
     * Get Refund
     */
    public function getRefund($refundId) {
        $stmt = $this->conn->prepare("
            SELECT r.*, u.first_name, u.last_name, u.email, b.departure, b.arrival
            FROM refunds r
            JOIN users u ON r.user_id = u.id
            JOIN bookings b ON r.booking_id = b.id
            WHERE r.id = ?
        ");
        $stmt->bind_param('i', $refundId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get All Refunds (Admin)
     */
    public function getAllRefunds($limit = 50, $offset = 0, $status = null) {
        $query = "
            SELECT r.*, u.first_name, u.last_name, u.email, b.departure, b.arrival
            FROM refunds r
            JOIN users u ON r.user_id = u.id
            JOIN bookings b ON r.booking_id = b.id
        ";

        if ($status) {
            $query .= " WHERE r.status = '" . $this->conn->real_escape_string($status) . "'";
        }

        $query .= " ORDER BY r.requested_at DESC LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get User Refunds
     */
    public function getUserRefunds($userId) {
        $stmt = $this->conn->prepare("
            SELECT r.*, b.departure, b.arrival
            FROM refunds r
            JOIN bookings b ON r.booking_id = b.id
            WHERE r.user_id = ?
            ORDER BY r.requested_at DESC
        ");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get Refund Statistics
     */
    public function getRefundStats() {
        $result = $this->conn->query("
            SELECT 
                COUNT(*) as total_refunds,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                SUM(CASE WHEN status = 'processed' THEN 1 ELSE 0 END) as processed_count,
                SUM(CASE WHEN status = 'processed' THEN amount ELSE 0 END) as total_refunded,
                AVG(amount) as average_refund
            FROM refunds
        ");

        return $result->fetch_assoc();
    }

    /**
     * Log Refund Action
     */
    private function logRefundAction($refundId, $action, $userId = null, $notes = null) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';

        $stmt = $this->conn->prepare("
            INSERT INTO refund_logs (refund_id, action, user_id, ip_address, notes)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->bind_param('isiss', $refundId, $action, $userId, $ipAddress, $notes);
        return $stmt->execute();
    }

    /**
     * Update Refund Status
     */
    private function updateRefundStatus($refundId, $status) {
        $stmt = $this->conn->prepare("
            UPDATE refunds SET status = ? WHERE id = ?
        ");
        $stmt->bind_param('si', $status, $refundId);
        return $stmt->execute();
    }

    /**
     * Send Refund Approval Email
     */
    private function sendRefundApprovalEmail($refundId) {
        $refund = $this->getRefund($refundId);
        if (!$refund) return false;

        require_once __DIR__ . '/../classes/EmailService.php';
        $emailService = new EmailService();

        $subject = 'Refund Approved - SKYJET';
        $body = "
            <h2>Your Refund Has Been Approved</h2>
            <p>Dear {$refund['first_name']},</p>
            <p>Good news! Your refund request has been approved.</p>
            
            <h3>Refund Details:</h3>
            <ul>
                <li><strong>Refund Amount:</strong> \${$refund['amount']}</li>
                <li><strong>Booking:</strong> {$refund['departure']} → {$refund['arrival']}</li>
                <li><strong>Status:</strong> Approved</li>
            </ul>
            
            <p>Your refund will be processed within 24 business hours and credited back to your original payment method.</p>
            <p>Thank you for choosing SKYJET!</p>
        ";

        return $emailService->send($refund['email'], $subject, $body);
    }

    /**
     * Send Refund Rejection Email
     */
    private function sendRefundRejectionEmail($refundId, $reason) {
        $refund = $this->getRefund($refundId);
        if (!$refund) return false;

        require_once __DIR__ . '/../classes/EmailService.php';
        $emailService = new EmailService();

        $subject = 'Refund Request - Decision';
        $body = "
            <h2>Refund Request Decision</h2>
            <p>Dear {$refund['first_name']},</p>
            <p>Unfortunately, your refund request has been declined.</p>
            
            <h3>Reason:</h3>
            <p>{$reason}</p>
            
            <h3>Request Details:</h3>
            <ul>
                <li><strong>Booking:</strong> {$refund['departure']} → {$refund['arrival']}</li>
                <li><strong>Amount Requested:</strong> \${$refund['amount']}</li>
            </ul>
            
            <p>If you believe this is an error, please contact our support team.</p>
        ";

        return $emailService->send($refund['email'], $subject, $body);
    }

    /**
     * Send Refund Processed Email
     */
    private function sendRefundProcessedEmail($refundId) {
        $refund = $this->getRefund($refundId);
        if (!$refund) return false;

        require_once __DIR__ . '/../classes/EmailService.php';
        $emailService = new EmailService();

        $subject = 'Your Refund Has Been Processed - SKYJET';
        $body = "
            <h2>Refund Processed Successfully</h2>
            <p>Dear {$refund['first_name']},</p>
            <p>Your refund has been processed successfully!</p>
            
            <h3>Refund Details:</h3>
            <ul>
                <li><strong>Refund Amount:</strong> \${$refund['amount']}</li>
                <li><strong>Net Amount:</strong> \${$refund['net_refund']}</li>
                <li><strong>Transaction ID:</strong> {$refund['refund_transaction_id']}</li>
                <li><strong>Booking:</strong> {$refund['departure']} → {$refund['arrival']}</li>
            </ul>
            
            <p>The refund should appear in your account within 5-7 business days, depending on your bank.</p>
            <p>Thank you for your understanding!</p>
        ";

        return $emailService->send($refund['email'], $subject, $body);
    }
}

?>
