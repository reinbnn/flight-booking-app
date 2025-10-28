<?php
/**
 * Authorization Exception
 * Thrown when user lacks required permissions
 */

class AuthorizationException extends AppException {
    public function __construct(
        $message = "Access denied",
        $code = 403,
        $context = [],
        $user_id = null
    ) {
        parent::__construct($message, $code, null, $context, $user_id);
    }
}

?>
