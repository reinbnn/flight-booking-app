<?php
/**
 * Validation Exception
 * Thrown when input validation fails
 */

class ValidationException extends AppException {
    protected $errors = [];

    public function __construct($message, $errors = [], $code = 400, $context = []) {
        $this->errors = $errors;
        parent::__construct($message, $code, null, $context);
    }

    public function getErrors() {
        return $this->errors;
    }

    public function toArray() {
        $data = parent::toArray();
        $data['errors'] = $this->errors;
        return $data;
    }
}

?>
