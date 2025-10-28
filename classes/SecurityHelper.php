<?php
/**
 * Security Helper Class
 * Provides security utilities for the application
 */

require_once __DIR__ . '/../config/security.php';

class SecurityHelper {

    /**
     * Generate CSRF Token
     */
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF Token
     */
    public static function validateCSRFToken($token) {
        if (empty($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid CSRF token');
        }
        return true;
    }

    /**
     * Get CSRF Token Meta Tag
     */
    public static function getCSRFMetaTag() {
        $token = self::generateCSRFToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }

    /**
     * Get CSRF Input Field
     */
    public static function getCSRFInputField() {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Validate Input
     */
    public static function validateInput($input, $type = 'string', $options = []) {
        $defaults = [
            'max_length' => MAX_INPUT_LENGTH,
            'min_length' => 0,
            'required' => false,
            'pattern' => null,
            'allowed_values' => null
        ];

        $options = array_merge($defaults, $options);

        // Check required
        if ($options['required'] && empty($input)) {
            throw new Exception('This field is required');
        }

        if (empty($input)) {
            return $input;
        }

        // Check length
        if (strlen($input) > $options['max_length']) {
            throw new Exception('Input exceeds maximum length');
        }

        if (strlen($input) < $options['min_length']) {
            throw new Exception('Input below minimum length');
        }

        // Type-specific validation
        switch ($type) {
            case 'email':
                if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Invalid email format');
                }
                break;

            case 'phone':
                if (!preg_match('/^\+?[\d\s\-\(\)]{10,}$/', $input)) {
                    throw new Exception('Invalid phone format');
                }
                break;

            case 'url':
                if (!filter_var($input, FILTER_VALIDATE_URL)) {
                    throw new Exception('Invalid URL format');
                }
                break;

            case 'integer':
                if (!filter_var($input, FILTER_VALIDATE_INT)) {
                    throw new Exception('Invalid integer');
                }
                break;

            case 'float':
                if (!filter_var($input, FILTER_VALIDATE_FLOAT)) {
                    throw new Exception('Invalid decimal number');
                }
                break;

            case 'password':
                self::validatePassword($input);
                break;

            case 'date':
                if (!strtotime($input)) {
                    throw new Exception('Invalid date format');
                }
                break;
        }

        // Check against allowed values
        if ($options['allowed_values'] && !in_array($input, $options['allowed_values'])) {
            throw new Exception('Invalid value');
        }

        // Custom pattern
        if ($options['pattern'] && !preg_match($options['pattern'], $input)) {
            throw new Exception('Input does not match required format');
        }

        return $input;
    }

    /**
     * Validate Password Strength
     */
    public static function validatePassword($password) {
        $errors = [];

        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
        }

        if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain uppercase letter';
        }

        if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain lowercase letter';
        }

        if (PASSWORD_REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain number';
        }

        if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = 'Password must contain special character';
        }

        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }

        return true;
    }

    /**
     * Hash Password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_HASH_ALGO, ['cost' => PASSWORD_HASH_COST]);
    }

    /**
     * Verify Password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Sanitize Output (XSS Prevention)
     */
    public static function sanitizeOutput($output, $type = 'html') {
        switch ($type) {
            case 'html':
                return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');

            case 'json':
                return json_encode($output, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

            case 'url':
                return urlencode($output);

            case 'sql':
                // Should use prepared statements instead!
                return addslashes($output);

            case 'javascript':
                return json_encode($output);

            default:
                return htmlspecialchars($output);
        }
    }

    /**
     * Sanitize Input (XSS & Injection Prevention)
     */
    public static function sanitizeInput($input, $type = 'string') {
        // Remove null bytes
        $input = str_replace("\0", '', $input);

        // Trim whitespace
        $input = trim($input);

        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);

            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);

            case 'integer':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);

            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

            case 'html':
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');

            case 'string':
            default:
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }
    }

    /**
     * Encrypt Data
     */
    public static function encrypt($data) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(ENCRYPTION_METHOD));
        $encrypted = openssl_encrypt($data, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt Data
     */
    public static function decrypt($data) {
        $data = base64_decode($data);
        $iv_length = openssl_cipher_iv_length(ENCRYPTION_METHOD);
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);
        return openssl_decrypt($encrypted, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
    }

    /**
     * Generate Secure Token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Verify API Signature
     */
    public static function verifyAPISignature($data, $signature, $secret) {
        $calculated = hash_hmac('sha256', $data, $secret);
        return hash_equals($calculated, $signature);
    }

    /**
     * Generate API Signature
     */
    public static function generateAPISignature($data, $secret) {
        return hash_hmac('sha256', $data, $secret);
    }

    /**
     * Validate File Upload
     */
    public static function validateFileUpload($file, $options = []) {
        $defaults = [
            'allowed_types' => ALLOWED_FILE_TYPES,
            'max_size' => MAX_FILE_SIZE,
            'required' => true
        ];

        $options = array_merge($defaults, $options);

        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            if ($options['required']) {
                throw new Exception('File is required');
            }
            return false;
        }

        // Check file size
        if ($file['size'] > $options['max_size']) {
            throw new Exception('File size exceeds maximum allowed');
        }

        // Check file type
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $options['allowed_types'])) {
            throw new Exception('File type not allowed');
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed_mimes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        if (isset($allowed_mimes[$ext]) && $mime !== $allowed_mimes[$ext]) {
            throw new Exception('File MIME type does not match extension');
        }

        return true;
    }

    /**
     * Get Client IP Address
     */
    public static function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }

        // Validate IP
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = '0.0.0.0';
        }

        return $ip;
    }

    /**
     * Check IP Against Whitelist
     */
    public static function checkIPWhitelist($ip = null) {
        global $IP_WHITELIST;

        if (empty($IP_WHITELIST)) {
            return true;
        }

        $ip = $ip ?? self::getClientIP();
        return in_array($ip, $IP_WHITELIST);
    }

    /**
     * Check IP Against Blacklist
     */
    public static function checkIPBlacklist($ip = null) {
        global $IP_BLACKLIST;

        if (empty($IP_BLACKLIST)) {
            return false;
        }

        $ip = $ip ?? self::getClientIP();
        return in_array($ip, $IP_BLACKLIST);
    }

    /**
     * Set Security Headers
     */
    public static function setSecurityHeaders() {
        global $SECURITY_HEADERS;

        foreach ($SECURITY_HEADERS as $header => $value) {
            header($header . ': ' . $value);
        }

        // HTTPS only
        if (FORCE_HTTPS) {
            header('Strict-Transport-Security: max-age=' . HSTS_MAX_AGE . '; includeSubDomains; preload');
        }
    }

    /**
     * Regenerate Session ID
     */
    public static function regenerateSessionID() {
        if (REGENERATE_SESSION_ID) {
            session_regenerate_id(true);
        }
    }

    /**
     * Configure Session Security
     */
    public static function configureSessionSecurity() {
        session_name(SESSION_NAME);

        $options = [
            'lifetime' => SESSION_TIMEOUT,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'],
            'secure' => SECURE_COOKIE,
            'httponly' => HTTPONLY_COOKIE,
            'samesite' => SAMESITE_COOKIE
        ];

        session_set_cookie_params($options);
    }

    /**
     * Mask Credit Card Number
     */
    public static function maskCardNumber($cardNumber) {
        if (!MASK_CARD_NUMBERS) {
            return $cardNumber;
        }

        $cardNumber = str_replace(' ', '', $cardNumber);
        return substr_replace($cardNumber, str_repeat('*', 12), 0, -4);
    }

    /**
     * Log Security Event
     */
    public static function logSecurityEvent($event, $details = []) {
        $message = date('[Y-m-d H:i:s] ') . $event;
        $message .= ' | IP: ' . self::getClientIP();

        if (!empty($details)) {
            $message .= ' | ' . json_encode($details);
        }

        $message .= "
";

        if (!is_dir(dirname(SECURITY_LOG_FILE))) {
            mkdir(dirname(SECURITY_LOG_FILE), 0755, true);
        }

        error_log($message, 3, SECURITY_LOG_FILE);
    }

    /**
     * Validate Email
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Generate OTP
     */
    public static function generateOTP($length = 6) {
        return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    /**
     * Rate Limit Check
     */
    public static function checkRateLimit($key, $limit, $window = 60) {
        $cache_key = 'rate_limit_' . md5($key);
        
        // In production, use Redis or Memcached
        // For now, using file-based simple approach
        $file = sys_get_temp_dir() . '/' . $cache_key;

        $attempts = 0;
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if (time() - $data['timestamp'] < $window) {
                $attempts = $data['count'];
            }
        }

        if ($attempts >= $limit) {
            return false;
        }

        // Increment
        $attempts++;
        file_put_contents($file, json_encode([
            'count' => $attempts,
            'timestamp' => time()
        ]));

        return true;
    }
}

?>
