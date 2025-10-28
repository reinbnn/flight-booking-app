<?php
/**
 * Client-side Error Reporting API
 * Allows frontend to report JavaScript errors
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['message'])) {
        throw new ValidationException('Missing error message', ['message' => 'Required']);
    }

    // Log client-side error
    global $global_logger;
    $global_logger->error('Client-side error: ' . $data['message'], [
        'type' => $data['type'] ?? 'unknown',
        'stack' => $data['stack'] ?? null,
        'url' => $data['url'] ?? null,
        'line' => $data['line'] ?? null,
        'column' => $data['column'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Error reported'
    ]);

} catch (Exception $e) {
    global $global_logger;
    $global_logger->exception($e);
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

?>
