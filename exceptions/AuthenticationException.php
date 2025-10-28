<?php
/**
 * Authentication Exception
 * Thrown when authentication fails
 */

class AuthenticationException extends AppException {
    public function __construct(
        $message = "Authentication failed",
        $code = 401,
        $context = [],
        $user_id = null
    ) {
        parent::__construct($message, $code, null, $context, $user_id);
    }
}

?>
