<?php
/**
 * Authentication Service
 * Handles secure user authentication with 2FA
 */

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../classes/SecurityHelper.php';
require_once __DIR__ . '/../classes/EmailService.php';

class AuthenticationService {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    /**
     * Login User with Rate Limiting
     */
    public function login($email, $password) {
        try {
            // Rate limit login attempts
            $ip = SecurityHelper::getClientIP();
            if (!SecurityHelper::checkRateLimit($ip . '_login', LOGIN_ATTEMPTS_MAX, LOGIN_ATTEMPTS_TIMEOUT)) {
                SecurityHelper::logSecurityEvent('LOGIN_RATE_LIMIT_EXCEEDED', ['email' => $email]);
                throw new Exception('Too many login attempts. Please try again later.');
            }

            // Validate input
            $email = SecurityHelper::sanitizeInput($email, 'email');
            if (!SecurityHelper::isValidEmail($email)) {
                throw new Exception('Invalid email');
            }

            // Get user
            $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if (!$user) {
                SecurityHelper::logSecurityEvent('LOGIN_FAILED', ['email' => $email, 'reason' => 'user_not_found']);
                throw new Exception('Invalid credentials');
            }

            // Check if account is locked
            if ($this->isAccountLocked($user['id'])) {
                SecurityHelper::logSecurityEvent('LOGIN_ACCOUNT_LOCKED', ['user_id' => $user['id']]);
                throw new Exception('Account is locked. Please contact support.');
            }

            // Verify password
            if (!SecurityHelper::verifyPassword($password, $user['password'])) {
                $this->recordFailedLoginAttempt($user['id']);
                SecurityHelper::logSecurityEvent('LOGIN_FAILED', ['email' => $email, 'reason' => 'invalid_password']);
                throw new Exception('Invalid credentials');
            }

            // Reset failed attempts
            $this->clearFailedLoginAttempts($user['id']);

            // Check if 2FA is enabled
            if ($user['two_factor_enabled']) {
                // Send 2FA code
                $this->send2FACode($user['id']);

                return [
                    'success' => true,
                    'requires_2fa' => true,
                    'user_id' => $user['id'],
                    'message' => '2FA code sent to your email'
                ];
            }

            // Set session
            $this->createSession($user);

            SecurityHelper::logSecurityEvent('LOGIN_SUCCESS', ['user_id' => $user['id']]);

            return [
                'success' => true,
                'requires_2fa' => false,
                'user_id' => $user['id'],
                'message' => 'Login successful'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify 2FA Code
     */
    public function verify2FACode($userId, $code) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM two_factor_codes 
                WHERE user_id = ? AND code = ? AND expires_at > NOW()
                ORDER BY created_at DESC LIMIT 1
            ");
            $stmt->bind_param('is', $userId, $code);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();

            if (!$result) {
                SecurityHelper::logSecurityEvent('2FA_FAILED', ['user_id' => $userId]);
                throw new Exception
\<Streaming stoppped because the conversation grew too long for this model\>
sudo tee -a /var/www/html/flight-booking-app/classes/AuthenticationService.php > /dev/null << 'EOF'

            if (!$result) {
                SecurityHelper::logSecurityEvent('2FA_FAILED', ['user_id' => $userId]);
                throw new Exception('Invalid 2FA code');
            }

            // Mark code as used
            $stmt = $this->conn->prepare("UPDATE two_factor_codes SET used = 1 WHERE id = ?");
            $stmt->bind_param('i', $result['id']);
            $stmt->execute();

            // Get user and create session
            $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            $this->createSession($user);

            SecurityHelper::logSecurityEvent('2FA_SUCCESS', ['user_id' => $userId]);

            return [
                'success' => true,
                'message' => '2FA verified successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send 2FA Code
     */
    private function send2FACode($userId) {
        try {
            $code = SecurityHelper::generateOTP(6);
            $expiresAt = date('Y-m-d H:i:s', time() + 300); // 5 minutes

            // Save code
            $stmt = $this->conn->prepare("
                INSERT INTO two_factor_codes (user_id, code, expires_at)
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param('iss', $userId, $code, $expiresAt);
            $stmt->execute();

            // Get user email
            $stmt = $this->conn->prepare("SELECT email, first_name FROM users WHERE id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            // Send email
            $emailService = new EmailService();
            $subject = 'Your 2FA Code - SKYJET';
            $body = "
                <h2>Two-Factor Authentication</h2>
                <p>Your 2FA code is: <strong>$code</strong></p>
                <p>This code expires in 5 minutes.</p>
                <p>Do not share this code with anyone.</p>
            ";

            return $emailService->send($user['email'], $subject, $body);

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Create Session
     */
    private function createSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['ip_address'] = SecurityHelper::getClientIP();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Regenerate session ID
        SecurityHelper::regenerateSessionID();
    }

    /**
     * Record Failed Login Attempt
     */
    private function recordFailedLoginAttempt($userId) {
        $stmt = $this->conn->prepare("
            INSERT INTO login_attempts (user_id, attempt_type, ip_address, user_agent)
            VALUES (?, 'failed', ?, ?)
        ");

        $ip = SecurityHelper::getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $stmt->bind_param('iss', $userId, $ip, $userAgent);
        $stmt->execute();

        // Check if account should be locked
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count FROM login_attempts
            WHERE user_id = ? AND attempt_type = 'failed'
            AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        ");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result['count'] >= ACCOUNT_LOCKOUT_THRESHOLD) {
            $this->lockAccount($userId);
        }
    }

    /**
     * Clear Failed Login Attempts
     */
    private function clearFailedLoginAttempts($userId) {
        $stmt = $this->conn->prepare("
            DELETE FROM login_attempts 
            WHERE user_id = ? AND attempt_type = 'failed'
        ");
        $stmt->bind_param('i', $userId);
        return $stmt->execute();
    }

    /**
     * Lock Account
     */
    private function lockAccount($userId) {
        $unlockTime = date('Y-m-d H:i:s', time() + ACCOUNT_LOCKOUT_DURATION);

        $stmt = $this->conn->prepare("
            UPDATE users SET locked_until = ? WHERE id = ?
        ");
        $stmt->bind_param('si', $unlockTime, $userId);
        return $stmt->execute();
    }

    /**
     * Check if Account is Locked
     */
    private function isAccountLocked($userId) {
        $stmt = $this->conn->prepare("
            SELECT locked_until FROM users WHERE id = ? AND locked_until > NOW()
        ");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    /**
     * Logout User
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            SecurityHelper::logSecurityEvent('LOGOUT', ['user_id' => $_SESSION['user_id']]);
        }

        // Destroy session
        $_SESSION = [];
        session_destroy();

        return [
            'success' => true,
            'message' => 'Logout successful'
        ];
    }

    /**
     * Change Password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get user
            $stmt = $this->conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if (!$user) {
                throw new Exception('User not found');
            }

            // Verify current password
            if (!SecurityHelper::verifyPassword($currentPassword, $user['password'])) {
                SecurityHelper::logSecurityEvent('PASSWORD_CHANGE_FAILED', ['user_id' => $userId, 'reason' => 'invalid_current_password']);
                throw new Exception('Current password is incorrect');
            }

            // Validate new password
            SecurityHelper::validatePassword($newPassword);

            // Check password history
            $this->checkPasswordHistory($userId, $newPassword);

            // Hash new password
            $hashedPassword = SecurityHelper::hashPassword($newPassword);

            // Update password
            $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param('si', $hashedPassword, $userId);
            $stmt->execute();

            // Record password change
            $this->recordPasswordChange($userId);

            SecurityHelper::logSecurityEvent('PASSWORD_CHANGED', ['user_id' => $userId]);

            return [
                'success' => true,
                'message' => 'Password changed successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check Password History
     */
    private function checkPasswordHistory($userId, $newPassword) {
        $stmt = $this->conn->prepare("
            SELECT password FROM password_history
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");

        $limit = PASSWORD_HISTORY_COUNT;
        $stmt->bind_param('ii', $userId, $limit);
        $stmt->execute();
        $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($results as $record) {
            if (SecurityHelper::verifyPassword($newPassword, $record['password'])) {
                throw new Exception('Cannot reuse recent passwords');
            }
        }
    }

    /**
     * Record Password Change
     */
    private function recordPasswordChange($userId) {
        $currentPassword = $this->conn->query("SELECT password FROM users WHERE id = $userId")->fetch_assoc()['password'];

        $stmt = $this->conn->prepare("
            INSERT INTO password_history (user_id, password)
            VALUES (?, ?)
        ");
        $stmt->bind_param('is', $userId, $currentPassword);
        return $stmt->execute();
    }

    /**
     * Reset Password Request
     */
    public function resetPasswordRequest($email) {
        try {
            $stmt = $this->conn->prepare("SELECT id, first_name FROM users WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if (!$user) {
                // Don't reveal if email exists
                return [
                    'success' => true,
                    'message' => 'If email exists, reset link sent'
                ];
            }

            // Generate token
            $token = SecurityHelper::generateToken(32);
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            // Save token
            $stmt = $this->conn->prepare("
                INSERT INTO password_reset_tokens (user_id, token, expires_at)
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param('iss', $user['id'], $token, $expiresAt);
            $stmt->execute();

            // Send email
            $resetLink = 'https://' . $_SERVER['HTTP_HOST'] . '/pages/reset-password.html?token=' . $token;
            $emailService = new EmailService();
            $subject = 'Password Reset - SKYJET';
            $body = "
                <h2>Password Reset Request</h2>
                <p>Click the link below to reset your password:</p>
                <p><a href='$resetLink'>Reset Password</a></p>
                <p>This link expires in 1 hour.</p>
                <p>If you didn't request this, ignore this email.</p>
            ";

            $emailService->send($email, $subject, $body);

            SecurityHelper::logSecurityEvent('PASSWORD_RESET_REQUESTED', ['user_id' => $user['id']]);

            return [
                'success' => true,
                'message' => 'If email exists, reset link sent'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Reset Password
     */
    public function resetPassword($token, $newPassword) {
        try {
            // Validate token
            $stmt = $this->conn->prepare("
                SELECT user_id FROM password_reset_tokens
                WHERE token = ? AND used = 0 AND expires_at > NOW()
            ");
            $stmt->bind_param('s', $token);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();

            if (!$result) {
                throw new Exception('Invalid or expired reset token');
            }

            $userId = $result['user_id'];

            // Validate password
            SecurityHelper::validatePassword($newPassword);

            // Hash password
            $hashedPassword = SecurityHelper::hashPassword($newPassword);

            // Update password
            $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param('si', $hashedPassword, $userId);
            $stmt->execute();

            // Mark token as used
            $stmt = $this->conn->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
            $stmt->bind_param('s', $token);
            $stmt->execute();

            SecurityHelper::logSecurityEvent('PASSWORD_RESET', ['user_id' => $userId]);

            return [
                'success' => true,
                'message' => 'Password reset successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

?>
