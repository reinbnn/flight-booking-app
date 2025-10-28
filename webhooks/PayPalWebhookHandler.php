            // Find refund record
            $stmt = $conn->prepare("
                SELECT id, booking_id FROM refunds
                WHERE paypal_sale_id = ?
            ");
            $stmt->bind_param('s', $sale_id);
            $stmt->execute();
            $refund = $stmt->get_result()->fetch_assoc();

            if ($refund) {
                $status = 'completed';
                $update_stmt = $conn->prepare("
                    UPDATE refunds
                    SET status = ?, refunded_at = NOW(), paypal_refund_id = ?
                    WHERE id = ?
                ");
                $update_stmt->bind_param('ssi', $status, $refund_id, $refund['id']);
                $update_stmt->execute();
            }

            $this->logger->info("PayPal refund processed", ['sale_id' => $sale_id, 'refund_id' => $refund_id]);
            return true;

        } catch (Exception $e) {
            $this->logger->exception($e);
            return false;
        }
    }

    /**
     * Handle capture refund
     */
    private function handleCaptureRefund($resource) {
        $capture_id = $resource['id'];
        $this->logger->info("PayPal capture refunded", ['capture_id' => $capture_id]);
        return true;
    }
}

?>
