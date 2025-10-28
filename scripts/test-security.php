<?php
/**
 * Security Testing Script
 * Run security checks and display results
 */

require_once __DIR__ . '/../config/security.php';

class SecurityTester {

    public static function runTests() {
        echo "╔════════════════════════════════════════════════════════════════╗
";
        echo "║           🔐 SECURITY TESTING SUITE                           ║
";
        echo "╚════════════════════════════════════════════════════════════════╝
";

        $results = [];

        // Test 1: PHP Configuration
        echo "Testing PHP Configuration...
";
        $results['php_config'] = self::testPHPConfig();

        // Test 2: File Permissions
        echo "Testing File Permissions...
";
        $results['permissions'] = self::testFilePermissions();

        // Test 3: Security Configuration
        echo "Testing Security Configuration...
";
        $results['security_config'] = self::testSecurityConfig();

        // Test 4: Password Hashing
        echo "Testing Password Hashing...
";
        $results['password'] = self::testPasswordHashing();

        // Test 5: Encryption
        echo "Testing Encryption...
";
        $results['encryption'] = self::testEncryption();

        // Test 6: Token Generation
        echo "Testing Token Generation...
";
        $results['tokens'] = self::testTokenGeneration();

        // Test 7: Session Configuration
        echo "Testing Session Configuration...
";
        $results['session'] = self::testSessionConfiguration();

        // Test 8: API Files
        echo "Testing API Files...
";
        $results['api_files'] = self::testAPIFiles();

        // Summary
        echo "
╔════════════════════════════════════════════════════════════════╗
";
        echo "║                    TEST SUMMARY                               ║
";
        echo "╚════════════════════════════════════════════════════════════════╝
";

        $passed = 0;
        $failed = 0;

        foreach ($results as $test => $result) {
            $status = $result['passed'] ? '✅ PASS' : '❌ FAIL';
            $testName = str_replace('_', ' ', ucfirst($test));
            echo "$status: " . str_pad($testName, 30) . " - {$result['message']}
";

            if ($result['passed']) {
                $passed++;
            } else {
                $failed++;
            }
        }

        echo "
═══════════════════════════════════════════════════════════════════
";
        echo "Results: $passed passed, $failed failed
";
        echo "═══════════════════════════════════════════════════════════════════
";

        return $failed === 0;
    }

    private static function testPHPConfig() {
        $issues = [];

        if (ini_get('display_errors')) {
            $issues[] = 'display_errors should be off';
        }

        if (!function_exists('openssl_encrypt')) {
            $issues[] = 'OpenSSL extension not available';
        }

        $passed = empty($issues);
        $message = $passed ? 'PHP config secure' : implode(', ', $issues);
        
        return ['passed' => $passed, 'message' => $message];
    }

    private static function testFilePermissions() {
        $checks = [
            '/var/www/html/flight-booking-app/config' => true,
            '/var/www/html/flight-booking-app/logs' => true,
            '/var/www/html/flight-booking-app/uploads' => true,
            '/var/www/html/flight-booking-app/scripts' => true,
        ];

        $issues = [];
        foreach ($checks as $path => $required) {
            if (!is_dir($path)) {
                $issues[] = basename($path) . " missing";
            }
        }

        $passed = empty($issues);
        $message = $passed ? 'All directories exist' : implode(', ', $issues);
        
        return ['passed' => $passed, 'message' => $message];
    }

    private static function testSecurityConfig() {
        $checks = [
            'FORCE_HTTPS' => defined('FORCE_HTTPS'),
            'SESSION_TIMEOUT' => defined('SESSION_TIMEOUT'),
            'SECURE_COOKIE' => defined('SECURE_COOKIE'),
            'HTTPONLY_COOKIE' => defined('HTTPONLY_COOKIE'),
            'PASSWORD_MIN_LENGTH' => defined('PASSWORD_MIN_LENGTH'),
        ];

        $failed = array_filter($checks, fn($v) => !$v);
        $passed = empty($failed);
        $message = $passed ? 'All security constants defined' : count($failed) . ' missing';
        
        return ['passed' => $passed, 'message' => $message];
    }

    private static function testPasswordHashing() {
        try {
            $testPassword = 'TestPassword123!';
            $hashed = password_hash($testPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            $verified = password_verify($testPassword, $hashed);

            $passed = $verified && strlen($hashed) > 50;
            $message = $passed ? 'Password hashing working' : 'Password hashing failed';
            
            return ['passed' => $passed, 'message' => $message];
        } catch (Exception $e) {
            return ['passed' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    private static function testEncryption() {
        try {
            $testData = 'Sensitive Data 12345';
            $method = 'AES-256-CBC';
            $key = hash('sha256', 'test_encryption_key', true);
            
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
            $encrypted = openssl_encrypt($testData, $method, $key, 0, $iv);
            $decrypted = openssl_decrypt($encrypted, $method, $key, 0, $iv);

            $passed = $testData === $decrypted && !empty($encrypted);
            $message = $passed ? 'Encryption working' : 'Encryption failed';
            
            return ['passed' => $passed, 'message' => $message];
        } catch (Exception $e) {
            return ['passed' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    private static function testTokenGeneration() {
        try {
            $token1 = bin2hex(random_bytes(16));
            $token2 = bin2hex(random_bytes(16));

            $passed = !empty($token1) && !empty($token2) && $token1 !== $token2 && strlen($token1) === 32;
            $message = $passed ? 'Token generation working' : 'Token generation failed';
            
            return ['passed' => $passed, 'message' => $message];
        } catch (Exception $e) {
            return ['passed' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    private static function testSessionConfiguration() {
        $checks = [
            'SESSION_TIMEOUT' => defined('SESSION_TIMEOUT') && SESSION_TIMEOUT > 0,
            'SECURE_COOKIE' => defined('SECURE_COOKIE'),
            'HTTPONLY_COOKIE' => defined('HTTPONLY_COOKIE'),
            'SAMESITE_COOKIE' => defined('SAMESITE_COOKIE'),
        ];

        $passed = !in_array(false, $checks);
        $message = $passed ? 'All session options configured' : 'Some options missing';
        
        return ['passed' => $passed, 'message' => $message];
    }

    private static function testAPIFiles() {
        $apiFiles = [
            '/var/www/html/flight-booking-app/api/login.php',
            '/var/www/html/flight-booking-app/api/verify-2fa.php',
            '/var/www/html/flight-booking-app/api/change-password.php',
            '/var/www/html/flight-booking-app/api/reset-password-request.php',
            '/var/www/html/flight-booking-app/api/reset-password.php',
        ];

        $missing = [];
        foreach ($apiFiles as $file) {
            if (!file_exists($file)) {
                $missing[] = basename($file);
            }
        }

        $passed = empty($missing);
        $message = $passed ? 'All API files present' : count($missing) . ' missing';
        
        return ['passed' => $passed, 'message' => $message];
    }
}

// Run tests
$success = SecurityTester::runTests();
exit($success ? 0 : 1);

?>
