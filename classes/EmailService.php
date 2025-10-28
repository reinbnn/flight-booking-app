<?php
/**
 * Email Service using PHPMailer
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mailer;
    private $templatePath;

    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->templatePath = TEMPLATE_PATH;
        $this->setupSMTP();
    }

    /**
     * Setup SMTP Configuration
     */
    private function setupSMTP() {
        try {
            $this->mailer->isSMTP();
            $this->mailer->Host = MAIL_HOST;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = MAIL_USERNAME;
            $this->mailer->Password = MAIL_PASSWORD;
            $this->mailer->SMTPSecure = 'tls';
            $this->mailer->Port = MAIL_PORT;
            $this->mailer->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        } catch (Exception $e) {
            error_log("SMTP Setup Error: " . $e->getMessage());
        }
    }

    /**
     * Send Payment Receipt
     */
    public function sendPaymentReceipt($recipientEmail, $bookingData, $paymentData) {
        try {
            $this->mailer->addAddress($recipientEmail);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Payment Receipt - SKYJET Booking #' . $bookingData['booking_id'];

            // Load template
            $html = $this->loadTemplate(EMAIL_PAYMENT_RECEIPT, [
                'booking_id' => $bookingData['booking_id'],
                'customer_name' => $bookingData['customer_name'],
                'service_type' => $bookingData['service_type'],
                'from_location' => $bookingData['from_location'],
                'to_location' => $bookingData['to_location'],
                'departure_date' => $bookingData['departure_date'],
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'],
                'transaction_id' => $paymentData['transaction_id'],
                'payment_date' => $paymentData['payment_date']
            ]);

            $this->mailer->Body = $html;
            $this->mailer->send();

            // Log email
            $this->logEmail($recipientEmail, 'payment_receipt', $bookingData['booking_id'], 'sent');

            return ['success' => true, 'message' => 'Receipt sent successfully'];

        } catch (Exception $e) {
            error_log("Email Error: " . $e->getMessage());
            $this->logEmail($recipientEmail, 'payment_receipt', $bookingData['booking_id'], 'failed', $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send Booking Confirmation
     */
    public function sendBookingConfirmation($recipientEmail, $bookingData) {
        try {
            $this->mailer->addAddress($recipientEmail);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Booking Confirmed - SKYJET #' . $bookingData['booking_id'];

            $html = $this->loadTemplate(EMAIL_BOOKING_CONFIRMATION, [
                'booking_id' => $bookingData['booking_id'],
                'customer_name' => $bookingData['customer_name'],
                'service_type' => $bookingData['service_type'],
                'from_location' => $bookingData['from_location'],
                'to_location' => $bookingData['to_location'],
                'departure_date' => $bookingData['departure_date'],
                'return_date' => $bookingData['return_date'] ?? null,
                'total_price' => $bookingData['total_price']
            ]);

            $this->mailer->Body = $html;
            $this->mailer->send();

            $this->logEmail($recipientEmail, 'booking_confirmation', $bookingData['booking_id'], 'sent');
            return ['success' => true, 'message' => 'Confirmation sent'];

        } catch (Exception $e) {
            error_log("Email Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send Payment Failed Notification
     */
    public function sendPaymentFailed($recipientEmail, $bookingData, $reason) {
        try {
            $this->mailer->addAddress($recipientEmail);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Payment Failed - SKYJET Booking #' . $bookingData['booking_id'];

            $html = $this->loadTemplate(EMAIL_PAYMENT_FAILED, [
                'booking_id' => $bookingData['booking_id'],
                'customer_name' => $bookingData['customer_name'],
                'reason' => $reason,
                'retry_url' => 'https://skyjet.local/pages/payment.html?booking_id=' . $bookingData['booking_id']
            ]);

            $this->mailer->Body = $html;
            $this->mailer->send();

            $this->logEmail($recipientEmail, 'payment_failed', $bookingData['booking_id'], 'sent');
            return ['success' => true];

        } catch (Exception $e) {
            error_log("Email Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send Booking Cancellation
     */
    public function sendCancellationConfirmation($recipientEmail, $bookingData, $refundAmount) {
        try {
            $this->mailer->addAddress($recipientEmail);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Booking Cancelled - SKYJET #' . $bookingData['booking_id'];

            $html = $this->loadTemplate(EMAIL_BOOKING_CANCELLED, [
                'booking_id' => $bookingData['booking_id'],
                'customer_name' => $bookingData['customer_name'],
                'refund_amount' => $refundAmount,
                'cancellation_date' => date('Y-m-d H:i:s')
            ]);

            $this->mailer->Body = $html;
            $this->mailer->send();

            $this->logEmail($recipientEmail, 'booking_cancelled', $bookingData['booking_id'], 'sent');
            return ['success' => true];

        } catch (Exception $e) {
            error_log("Email Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send Pre-Travel Reminder
     */
    public function sendTravelReminder($recipientEmail, $bookingData) {
        try {
            $this->mailer->addAddress($recipientEmail);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Travel Reminder - SKYJET Booking #' . $bookingData['booking_id'];

            $html = $this->loadTemplate(EMAIL_REMINDER, [
                'booking_id' => $bookingData['booking_id'],
                'customer_name' => $bookingData['customer_name'],
                'departure_date' => $bookingData['departure_date'],
                'from_location' => $bookingData['from_location'],
                'to_location' => $bookingData['to_location'],
                'my_bookings_url' => 'https://skyjet.local/pages/my-bookings.html'
            ]);

            $this->mailer->Body = $html;
            $this->mailer->send();

            $this->logEmail($recipientEmail, 'reminder', $bookingData['booking_id'], 'sent');
            return ['success' => true];

        } catch (Exception $e) {
            error_log("Email Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Load Email Template
     */
    private function loadTemplate($templateName, $data = []) {
        $templateFile = $this->templatePath . $templateName . '.html';
        
        if (!file_exists($templateFile)) {
            return '<p>Email template not found</p>';
        }

        ob_start();
        include $templateFile;
        return ob_get_clean();
    }

    /**
     * Log Email
     */
    private function logEmail($recipient, $type, $bookingId, $status, $error = null) {
        global $conn;
        
        $stmt = $conn->prepare("
            INSERT INTO email_logs (recipient, email_type, booking_id, status, error_message, sent_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param('sssss', $recipient, $type, $bookingId, $status, $error);
        $stmt->execute();
    }
}

?>

    /**
     * Send Email with Attachment
     */
    public function sendWithAttachment($recipientEmail, $subject, $body, $attachmentPath) {
        try {
            $this->mailer->clearAllRecipients();
            $this->mailer->addAddress($recipientEmail);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->isHTML(true);

            // Add attachment
            if (file_exists($attachmentPath)) {
                $this->mailer->addAttachment($attachmentPath);
            } else {
                throw new Exception('Attachment file not found: ' . $attachmentPath);
            }

            if ($this->mailer->send()) {
                // Log email
                $this->logEmail($recipientEmail, $subject, 'sent');
                return true;
            } else {
                $this->logEmail($recipientEmail, $subject, 'failed', $this->mailer->ErrorInfo);
                return false;
            }
        } catch (Exception $e) {
            $this->logEmail($recipientEmail, $subject, 'failed', $e->getMessage());
            return false;
        }
    }

