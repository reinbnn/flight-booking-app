<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'skyjet');
define('DB_PASS', 'skyjet123');
define('DB_NAME', 'skyjet');
define('API_BASE_URL', 'http://localhost:8000/api');
define('APP_URL', 'http://localhost:8000');
define('APP_NAME', 'SKYJET - Premium Flight Booking');
define('JWT_SECRET', 'skyjet-secret-key-2025');
define('BASE_PATH', dirname(__FILE__));

try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB error', 'details' => $e->getMessage()]);
    exit();
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function jsonResponse($success, $data = null, $message = null, $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message, 'timestamp' => date('Y-m-d H:i:s')]);
    exit();
}

function errorResponse($message, $code = 400, $data = null) {
    jsonResponse(false, $data, $message, $code);
}

function successResponse($data, $message = 'Success', $code = 200) {
    jsonResponse(true, $data, $message, $code);
}
?>
