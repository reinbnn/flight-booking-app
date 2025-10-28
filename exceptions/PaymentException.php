<?php
/**
 * Payment Exception
 * Thrown when payment processing fails
 */

class PaymentException extends AppException {
    protected $payment_id;

    public function __construct(
        $message = "Payment failed",
        $code = 402,
        $payment_id = null,
        $context = [],
        $user_id = null
    ) {
        $this->payment_id = $payment_id;
        parent::__construct($message, $code, null, $context, $user_id);
    }

    public function getPaymentId() {
        return $this->payment_id;
    }

    public function toArray() {
        $data = parent::toArray();
        $data['payment_id'] = $this->payment_id;
        return $data;
    }
}

?>
