<?php
/**
 * Database Exception
 * Thrown when database operations fail
 */

class DatabaseException extends AppException {
    public function __construct(
        $message = "Database error",
        $code = 500,
        $context = [],
        $user_id = null
    ) {
        parent::__construct($message, $code, null, $context, $user_id);
    }
}

?>
