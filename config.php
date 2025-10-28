<?php
/**
 * SKYJET - Flight Booking System Configuration
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'skyjet');
define('DB_PASS', 'skyjet123');  // <-- ADD PASSWORD HERE
define('DB_NAME', 'skyjet');

// API Configuration
define('API_BASE_URL', 'http://localhost:8000/api');
define('APP_URL', 'http://localhost:8000');
define('APP_NAME', 'SKYJET - Premium Flight Booking');

// JWT Secret
define('JWT_SECRET', 'skyjet-secret-key-2025-change-in-production');

// File paths
define('BASE_PATH', dirname(__FILE__));
define('CLASSES_PATH', BASE_PATH . '/classes');
define('API_PATH', BASE_PATH . '/api');

// PDO Connection
try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        )
    );
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed',
        'details' => $e->getMessage()
    ]);
    exit();
}

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// Handle OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Helper functions
function jsonResponse($success, $data = null, $message = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

function errorResponse($message, $code = 400, $data = null) {
    jsonResponse(false, $data, $message, $code);
}

function successResponse($data, $message = 'Success', $code = 200) {
    jsonResponse(true, $data, $message, $code);
}

function getAuthToken() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $matches = [];
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    }
    return null;
}
?>
