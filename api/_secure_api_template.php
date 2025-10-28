<?php
/**
 * Secure API Template
 * Use this template for all API endpoints
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../middleware/SecurityMiddleware.php';
require_once __DIR__ . '/../classes/SecurityHelper.php';
require_once __DIR__ . '/../classes/InputValidator.php';

// Execute security middleware
SecurityMiddleware::execute();

// Check rate limiting for API
if (RATE_LIMIT_ENABLED) {
    SecurityMiddleware::checkRateLimitForAPI();
}

// Set security headers
SecurityHelper::setSecurityHeaders();

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Method not allowed');
    }

    // Parse input
    $data = json_decode(file_get_contents('php://input'), true) ?? $_REQUEST;

    // Validate CSRF token for POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($data['csrf_token'])) {
            throw new Exception('CSRF token missing');
        }
        SecurityHelper::validateCSRFToken($data['csrf_token']);
    }

    // Your API logic here
    // Example:
    // $validator = new InputValidator($data);
    // $validator->required('email');
    // $validator->email('email');
    //
    // if ($validator->hasErrors()) {
    //     throw new Exception($validator->firstError());
    // }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Operation successful'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

?>
