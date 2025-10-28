<?php
/**
 * SKYJET - Users API
 */

require_once __DIR__ . '/../config.php';

$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
} else {
    $input = $_GET;
}

switch ($action) {
    case 'list':
        listUsers();
        break;

    case 'login':
        loginUser($input);
        break;

    case 'register':
        registerUser($input);
        break;

    case 'profile':
        getUserProfile($input['id'] ?? null);
        break;

    default:
        errorResponse('Invalid action', 400);
}

function listUsers() {
    global $conn;

    try {
        $query = "SELECT id, first_name, last_name, email, phone, country, role FROM users WHERE is_active = 1";
        $stmt = $conn->prepare($query);
        $stmt->execute();

        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        successResponse([
            'users' => $users,
            'total' => count($users)
        ], 'Users retrieved successfully');

    } catch (PDOException $e) {
        errorResponse('Database error: ' . $e->getMessage(), 500);
    }
}

function loginUser($input) {
    global $conn;

    $email = $input['email'] ?? null;
    $password = $input['password'] ?? null;

    if (!$email || !$password) {
        errorResponse('Email and password required', 400);
    }

    try {
        $query = "SELECT * FROM users WHERE email = ? AND is_active = 1";
        $stmt = $conn->prepare($query);
        $stmt->execute([$email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            errorResponse('Invalid email or password', 401);
        }

        $token = bin2hex(random_bytes(32));

        successResponse([
            'user' => [
                'id' => $user['id'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'email' => $user['email'],
                'role' => $user['role']
            ],
            'token' => $token
        ], 'Login successful');

    } catch (PDOException $e) {
        errorResponse('Database error: ' . $e->getMessage(), 500);
    }
}

function registerUser($input) {
    global $conn;

    $firstName = $input['first_name'] ?? null;
    $lastName = $input['last_name'] ?? null;
    $email = $input['email'] ?? null;
    $password = $input['password'] ?? null;
    $phone = $input['phone'] ?? null;
    $country = $input['country'] ?? null;

    if (!$firstName || !$lastName || !$email || !$password) {
        errorResponse('First name, last name, email, and password required', 400);
    }

    try {
        $checkQuery = "SELECT id FROM users WHERE email = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute([$email]);

        if ($checkStmt->fetch()) {
            errorResponse('Email already exists', 409);
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $insertQuery = "INSERT INTO users (first_name, last_name, email, password, phone, country, role) 
                       VALUES (?, ?, ?, ?, ?, ?, 'user')";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->execute([$firstName, $lastName, $email, $hashedPassword, $phone, $country]);

        $userId = $conn->lastInsertId();

        successResponse([
            'user_id' => $userId,
            'email' => $email
        ], 'User registered successfully', 201);

    } catch (PDOException $e) {
        errorResponse('Database error: ' . $e->getMessage(), 500);
    }
}

function getUserProfile($id) {
    global $conn;

    if (!$id) {
        errorResponse('User ID required', 400);
    }

    try {
        $query = "SELECT id, first_name, last_name, email, phone, country, role, created_at FROM users WHERE id = ? AND is_active = 1";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            errorResponse('User not found', 404);
        }

        successResponse(['user' => $user], 'User profile retrieved');

    } catch (PDOException $e) {
        errorResponse('Database error: ' . $e->getMessage(), 500);
    }
}
?>
