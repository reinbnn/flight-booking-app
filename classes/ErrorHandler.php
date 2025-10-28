<?php
/**
 * Error Handler
 * Centralized error and exception handling
 */

class ErrorHandler {
    private static $logger;
    private static $error_display = false; // Set to false in production
    private static $is_production = false;

    /**
     * Initialize error handler
     */
    public static function initialize($logger = null, $is_production = false) {
        self::$logger = $logger;
        self::$is_production = $is_production;
        self::$error_display = !$is_production;

        // Set error handlers
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);

        // Set error reporting
        error_reporting(E_ALL);
        ini_set('display_errors', self::$error_display ? 1 : 0);
    }

    /**
     * Handle PHP errors
     */
    public static function handleError($errno, $errstr, $errfile, $errline) {
        $is_fatal = in_array($errno, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]);

        $error_data = [
            'errno' => $errno,
            'errstr' => $errstr,
            'errfile' => $errfile,
            'errline' => $errline,
            'error_type' => self::getErrorType($errno),
        ];

        if (self::$logger) {
            if ($is_fatal) {
                self::$logger->critical($errstr, $error_data);
            } elseif ($errno === E_WARNING || $errno === E_CORE_WARNING) {
                self::$logger->warning($errstr, $error_data);
            } else {
                self::$logger->error($errstr, $error_data);
            }
        }

        // Return true to prevent default PHP error handling
        return !$is_fatal;
    }

    /**
     * Handle exceptions
     */
    public static function handleException(Throwable $exception) {
        if (self::$logger) {
            self::$logger->exception($exception);
        }

        // Send appropriate response
        self::sendErrorResponse($exception);
    }

    /**
     * Handle shutdown
     */
    public static function handleShutdown() {
        $error = error_get_last();

        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            if (self::$logger) {
                self::$logger->critical(
                    'Fatal error during shutdown',
                    [
                        'type' => $error['type'],
                        'message' => $error['message'],
                        'file' => $error['file'],
                        'line' => $error['line']
                    ]
                );
            }

            self::sendFatalErrorResponse($error);
        }
    }

    /**
     * Send error response
     */
    private static function sendErrorResponse($exception) {
        $code = $exception->getCode() ?: 500;
        $code = ($code < 100 || $code > 599) ? 500 : $code;

        http_response_code($code);
        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'error' => $exception->getMessage(),
            'code' => $code,
        ];

        if (!self::$is_production) {
            $response['debug'] = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];

            if (method_exists($exception, 'getContext')) {
                $response['debug']['context'] = $exception->getContext();
            }
        }

        echo json_encode($response);
        exit;
    }

    /**
     * Send fatal error response
     */
    private static function sendFatalErrorResponse($error) {
        http_response_code(500);
        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'error' => 'Internal server error',
            'code' => 500,
        ];

        if (!self::$is_production) {
            $response['debug'] = [
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
            ];
        }

        echo json_encode($response);
        exit;
    }

    /**
     * Get error type name
     */
    private static function getErrorType($errno) {
        $types = [
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated',
        ];

        return $types[$errno] ?? 'Unknown Error';
    }
}

?>
