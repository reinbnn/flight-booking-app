<?php
/**
 * SKYJET - Flight Booking System Configuration
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'skyjet');
define('DB_PASS', 'skyjet123');
define('DB_NAME', 'skyjet');

// API Configuration
define('API_BASE_URL', 'http://skyjet.local/api');
define('APP_URL', 'http://skyjet.local');
define('APP_NAME', 'SKYJET - Premium Flight Booking');

// JWT Secret
define('JWT_SECRET', 'skyjet-secret-key-2025');

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
header('Content-Type: application/json');

// Handle OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Helper Functions
function jsonResponse($success, $data = null, $message = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

function errorResponse($message, $statusCode = 400, $data = null) {
    jsonResponse(false, $data, $message, $statusCode);
}

function successResponse($data, $message = 'Success', $statusCode = 200) {
    jsonResponse(true, $data, $message, $statusCode);
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

// HTTPS Configuration
require_once 'https-config.php';

// Use HTTPS for all URLs
define('SITE_URL', 'https://skyjet.local/');
define('API_URL', 'https://skyjet.local/api/');

?>
