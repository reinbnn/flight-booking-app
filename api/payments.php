<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($method) {
    case 'GET':
        if ($action === 'list') {
            listPayments();
        } else if ($action === 'details') {
            getPaymentDetails();
        }
        break;
    case 'POST':
        if ($action === 'process') {
            processPayment();
        } else if ($action === 'refund') {
            refundPayment();
        }
        break;
    case 'OPTIONS':
        http_response_code(200);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function listPayments() {
    global $conn;
    
    $status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
    $method = isset($_GET['method']) ? sanitize($_GET['method']) : '';
    
    try {
        $query = "SELECT p.*, b.booking_reference, u.email 
                  FROM payments p 
                  JOIN bookings b ON p.booking_id = b.id 
                  JOIN users u ON b.user_id = u.id 
                  WHERE 1=1";
        
        if ($status) {
            $query .= " AND p.payment_status = :status";
        }
        
        if ($method) {
            $query .= " AND p.payment_method = :method";
        }
        
        $query .= " ORDER BY p.created_at DESC";
        
        $stmt = $conn->prepare($query);
        
        $params = [];
        if ($status) $params[':status'] = $status;
        if ($method) $params[':method'] = $method;
        
        $stmt->execute($params);
        
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $payments,
            'total' => count($payments)
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getPaymentDetails() {
    global $conn;
    
    $paymentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$paymentId) {
        http_response_code(400);
        echo json_encode(['error' => 'Payment ID required']);
        return;
    }
    
    try {
        $query = "SELECT p.*, b.booking_reference, u.first_name, u.last_name, u.email 
                  FROM payments p 
                  JOIN bookings b ON p.booking_id = b.id 
                  JOIN users u ON b.user_id = u.id 
                  WHERE p.id = :id";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([':id' => $paymentId]);
        
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            http_response_code(404);
            echo json_encode(['error' => 'Payment not found']);
            return;
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $payment
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function processPayment() {
    global $conn;
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    $required = ['booking_id', 'amount', 'payment_method'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            return;
        }
    }
    
    try {
        $conn->beginTransaction();
        
        // Create payment
        $paymentQuery = "INSERT INTO payments (
                        booking_id,
                        amount,
                        payment_method,
                        transaction_id,
                        payment_status,
                        created_at
                    ) VALUES (
                        :booking_id,
                        :amount,
                        :payment_method,
                        :transaction_id,
                        :payment_status,
                        NOW()
                    )";
        
        $transactionId = 'TXN' . date('YmdHis') . rand(100000, 999999);
        
        $paymentStmt = $conn->prepare($paymentQuery);
        $paymentStmt->execute([
            ':booking_id' => $data['booking_id'],
            ':amount' => $data['amount'],
            ':payment_method' => $data['payment_method'],
            ':transaction_id' => $transactionId,
            ':payment_status' => 'completed'
        ]);
        
        // Update booking status
        $bookingQuery = "UPDATE bookings SET booking_status = 'confirmed' WHERE id = :booking_id";
        $bookingStmt = $conn->prepare($bookingQuery);
        $bookingStmt->execute([':booking_id' => $data['booking_id']]);
        
        $conn->commit();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Payment processed successfully',
            'transaction_id' => $transactionId
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function refundPayment() {
    global $conn;
    
    $paymentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$paymentId) {
        http_response_code(400);
        echo json_encode(['error' => 'Payment ID required']);
        return;
    }
    
    try {
        $conn->beginTransaction();
        
        // Get booking ID
        $getQuery = "SELECT booking_id FROM payments WHERE id = :id";
        $getStmt = $conn->prepare($getQuery);
        $getStmt->execute([':id' => $paymentId]);
        $result = $getStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            http_response_code(404);
            echo json_encode(['error' => 'Payment not found']);
            return;
        }
        
        // Update payment status
        $updatePaymentQuery = "UPDATE payments SET payment_status = 'refunded' WHERE id = :id";
        $updatePaymentStmt = $conn->prepare($updatePaymentQuery);
        $updatePaymentStmt->execute([':id' => $paymentId]);
        
        // Update booking status
        $updateBookingQuery = "UPDATE bookings SET booking_status = 'cancelled' WHERE id = :booking_id";
        $updateBookingStmt = $conn->prepare($updateBookingQuery);
        $updateBookingStmt->execute([':booking_id' => $result['booking_id']]);
        
        $conn->commit();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Payment refunded successfully'
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function sanitize($input) {
    return htmlspecialchars(trim($input));
}
?>