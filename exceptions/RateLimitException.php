<?php
/**
 * Rate Limit Exception
 * Thrown when rate limit is exceeded
 */

class RateLimitException extends AppException {
    protected $retry_after;

    public function __construct(
        $retry_after = 60,
        $message = "Rate limit exceeded",
        $code = 429,
        $context = []
    ) {
        $this->retry_after = $retry_after;
        parent::__construct($message, $code, null, $context);
    }

    public function getRetryAfter() {
        return $this->retry_after;
    }

    public function toArray() {
        $data = parent::toArray();
        $data['retry_after'] = $this->retry_after;
        return $data;
    }
}

?>
