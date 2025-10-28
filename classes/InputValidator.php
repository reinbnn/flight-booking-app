<?php
/**
 * Input Validator
 * Comprehensive input validation and sanitization
 */

require_once __DIR__ . '/../classes/SecurityHelper.php';

class InputValidator {

    private $errors = [];
    private $data = [];

    public function __construct($data = []) {
        $this->data = $data;
    }

    /**
     * Validate Email
     */
    public function validateEmail($key, $value = null) {
        $value = $value ?? ($this->data[$key] ?? null);

        if (empty($value)) {
            $this->addError($key, 'Email is required');
            return false;
        }

        if (!SecurityHelper::isValidEmail($value)) {
            $this->addError($key, 'Invalid email format');
            return false;
        }

        return true;
    }

    /**
     * Validate Required Field
     */
    public function required($key, $value = null) {
        $value = $value ?? ($this->data[$key] ?? null);

        if (empty($value)) {
            $this->addError($key, 'This field is required');
            return false;
        }

        return true;
    }

    /**
     * Validate Length
     */
    public function length($key, $min, $max, $value = null) {
        $value = $value ?? ($this->data[$key] ?? null);

        $len = strlen($value);

        if ($len < $min || $len > $max) {
            $this->addError($key, "Length must be between $min and $max characters");
            return false;
        }

        return true;
    }

    /**
     * Validate Phone
     */
    public function phone($key, $value = null) {
        $value = $value ?? ($this->data[$key] ?? null);

        if (empty($value)) {
            return true; // Optional
        }

        if (!preg_match('/^\+?[\d\s\-\(\)]{10,}$/', $value)) {
            $this->addError($key, 'Invalid phone number');
            return false;
        }

        return true;
    }

    /**
     * Validate Number
     */
    public function number($key, $min = 0, $max = PHP_INT_MAX, $value = null) {
        $value = $value ?? ($this->data[$key] ?? null);

        if (!is_numeric($value)) {
            $this->addError($key, 'Must be a number');
            return false;
        }

        if ($value < $min || $value > $max) {
            $this->addError($key, "Must be between $min and $max");
            return false;
        }

        return true;
    }

    /**
     * Validate Date
     */
    public function date($key, $format = 'Y-m-d', $value = null) {
        $value = $value ?? ($this->data[$key] ?? null);

        $date = \DateTime::createFromFormat($format, $value);
        if (!$date || $date->format($format) !== $value) {
            $this->addError($key, "Invalid date format. Expected: $format");
            return false;
        }

        return true;
    }

    /**
     * Validate In (Allowed Values)
     */
    public function in($key, $allowed = [], $value = null) {
        $value = $value ?? ($this->data[$key] ?? null);

        if (!in_array($value, $allowed)) {
            $this->addError($key, 'Invalid value');
            return false;
        }

        return true;
    }

    /**
     * Validate Regex Pattern
     */
    public function regex($key, $pattern, $value = null) {
        $value = $value ?? ($this->data[$key] ?? null);

        if (!preg_match($pattern, $value)) {
            $this->addError($key, 'Invalid format');
            return false;
        }

        return true;
    }

    /**
     * Validate URL
     */
    public function url($key, $value = null) {
        $value = $value ?? ($this->data[$key] ?? null);

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($key, 'Invalid URL');
            return false;
        }

        return true;
    }

    /**
     * Add Error
     */
    private function addError($key, $message) {
        if (!isset($this->errors[$key])) {
            $this->errors[$key] = [];
        }
        $this->errors[$key][] = $message;
    }

    /**
     * Get All Errors
     */
    public function errors() {
        return $this->errors;
    }

    /**
     * Get Error Message
     */
    public function getError($key) {
        return $this->errors[$key] ?? null;
    }

    /**
     * Has Errors
     */
    public function hasErrors() {
        return !empty($this->errors);
    }

    /**
     * Get First Error Message
     */
    public function firstError() {
        if (empty($this->errors)) {
            return null;
        }

        $firstErrors = reset($this->errors);
        return $firstErrors[0] ?? null;
    }
}

?>
