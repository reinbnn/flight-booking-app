<?php
/**
 * Base Application Exception
 */

class AppException extends Exception {
    protected $context = [];
    protected $user_id = null;
    protected $timestamp;

    public function __construct(
        $message = "",
        $code = 0,
        Exception $previous = null,
        $context = [],
        $user_id = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
        $this->user_id = $user_id;
        $this->timestamp = microtime(true);
    }

    public function getContext() {
        return $this->context;
    }

    public function getUserId() {
        return $this->user_id;
    }

    public function getTimestamp() {
        return $this->timestamp;
    }

    public function toArray() {
        return [
            'type' => get_class($this),
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->context,
            'user_id' => $this->user_id,
            'timestamp' => $this->timestamp,
            'trace' => $this->getTraceAsString()
        ];
    }
}

?>
