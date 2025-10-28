<?php
/**
 * HTTPS Configuration
 * Ensures all connections use secure protocols
 */

// Force HTTPS
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $redirect);
    exit;
}

// Set security headers
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' cdnjs.cloudflare.com; style-src \'self\' \'unsafe-inline\' fonts.googleapis.com');

// Disable caching for sensitive content
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Set secure cookie options
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');

?>
