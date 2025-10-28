<?php
/**
 * Secure Login API
 * Handles authentication with rate limiting and security checks
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../middleware/SecurityMiddleware.php';
require_once __DIR__ . '/../classes/AuthenticationService.php';
require_once __DIR__ . '/../classes/SecurityHelper.php';

// Execute security middleware
SecurityMiddleware::execute();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing email or password']);
    exit;
}

try {
    $authService = new AuthenticationService($conn);
    $result = $authService->login($data['email'], $data['password']);

    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(401);
    }

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

?>
