<?php
/**
 * Password Reset Request API
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/AuthenticationService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['email'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing email']);
    exit;
}

try {
    $authService = new AuthenticationService($conn);
    $result = $authService->resetPasswordRequest($data['email']);

    http_response_code(200);
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

?>
