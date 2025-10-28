<?php
/**
 * 2FA Verification API
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/AuthenticationService.php';
require_once __DIR__ . '/../classes/SecurityHelper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id']) || !isset($data['code'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing user_id or code']);
    exit;
}

try {
    $authService = new AuthenticationService($conn);
    $result = $authService->verify2FACode($data['user_id'], $data['code']);

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
