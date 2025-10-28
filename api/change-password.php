<?php
/**
 * Change Password API
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/AuthenticationService.php';
require_once __DIR__ . '/../classes/SecurityHelper.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['current_password']) || !isset($data['new_password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    $authService = new AuthenticationService($conn);
    $result = $authService->changePassword(
        $_SESSION['user_id'],
        $data['current_password'],
        $data['new_password']
    );

    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
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
