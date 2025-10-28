<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($method) {
    case 'GET':
        if ($action === 'list') {
            listBookings();
        } else if ($action === 'details') {
            getBookingDetails();
        }
        break;
    case 'POST':
        if ($action === 'create') {
            createBooking();
        } else if ($action === 'pay') {
            processPayment();
        }
        break;
    case 'PUT':
        if ($action === 'update') {
            updateBooking();
        }
        break;
    case 'DELETE':
        if ($action === 'cancel') {
            cancelBooking();
        }
        break;
    case 'OPTIONS':
        http_response_code(200);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function listBookings() {
    global $conn;
    
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    
    if (!$userId) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID required']);
        return;
    }
    
    try {
        $query = "SELECT 
                  b.*,
                  f.flight_number,
                  f.departure_airport,
                  f.arrival_airport,
                  f.departure_time,
                  f.arrival_time,
                  a.airline_name,
                  p.total_price,
                  p.payment_status
                FROM bookings b
                LEFT JOIN flights f ON b.flight_id = f.id
                LEFT JOIN airlines a ON f.airline_id = a.id
                LEFT JOIN payments p ON b.id = p.booking_id
                WHERE b.user_id = :user_id
                ORDER BY b.created_at DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $bookings,
            'total' => count($bookings)
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getBookingDetails() {
    global $conn;
    
    $bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
    
    if (!$bookingId) {
        http_response_code(400);
        echo json_encode(['error' => 'Booking ID required']);
        return;
    }
    
    try {
        $query = "SELECT 
                  b.*,
                  f.*,
                  a.airline_name,
                  p.*,
                  u.first_name,
                  u.last_name,
                  u.email
                FROM bookings b
                LEFT JOIN flights f ON b.flight_id = f.id
                LEFT JOIN airlines a ON f.airline_id = a.id
                LEFT JOIN payments p ON b.id = p.booking_id
                LEFT JOIN users u ON b.user_id = u.id
                WHERE b.id = :booking_id";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([':booking_id' => $bookingId]);
        
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking) {
            http_response_code(404);
            echo json_encode(['error' => 'Booking not found']);
            return;
        }
        
        // Get passengers
        $passengerQuery = "SELECT * FROM passengers WHERE booking_id = :booking_id";
        $passengerStmt = $conn->prepare($passengerQuery);
        $passengerStmt->execute([':booking_id' => $bookingId]);
        $booking['passengers'] = $passengerStmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $booking
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function createBooking() {
    global $conn;
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    $required = ['user_id', 'flight_id', 'passengers', 'cabin_class'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            return;
        }
    }
    
    try {
        $conn->beginTransaction();
        
        // Create booking
        $bookingQuery = "INSERT INTO bookings (
                        user_id,
                        flight_id,
                        booking_reference,
                        cabin_class,
                        number_of_passengers,
                        total_price,
                        booking_status,
                        created_at
                    ) VALUES (
                        :user_id,
                        :flight_id,
                        :booking_reference,
                        :cabin_class,
                        :number_of_passengers,
                        :total_price,
                        :booking_status,
                        NOW()
                    )";
        
        $bookingRef = 'SK' . date('YmdHis') . rand(1000, 9999);
        
        $bookingStmt = $conn->prepare($bookingQuery);
        $bookingStmt->execute([
            ':user_id' => $data['user_id'],
            ':flight_id' => $data['flight_id'],
            ':booking_reference' => $bookingRef,
            ':cabin_class' => $data['cabin_class'],
            ':number_of_passengers' => count($data['passengers']),
            ':total_price' => $data['total_price'] ?? 0,
            ':booking_status' => 'pending'
        ]);
        
        $bookingId = $conn->lastInsertId();
        
        // Add passengers
        $passengerQuery = "INSERT INTO passengers (
                          booking_id,
                          first_name,
                          last_name,
                          email,
                          phone,
                          passport_number,
                          seat_number,
                          created_at
                      ) VALUES (
                          :booking_id,
                          :first_name,
                          :last_name,
                          :email,
                          :phone,
                          :passport_number,
                          :seat_number,
                          NOW()
                      )";
        
        $passengerStmt = $conn->prepare($passengerQuery);
        
        foreach ($data['passengers'] as $passenger) {
            $passengerStmt->execute([
                ':booking_id' => $bookingId,
                ':first_name' => $passenger['first_name'],
                ':last_name' => $passenger['last_name'],
                ':email' => $passenger['email'] ?? '',
                ':phone' => $passenger['phone'] ?? '',
                ':passport_number' => $passenger['passport_number'] ?? '',
                ':seat_number' => $passenger['seat_number'] ?? ''
            ]);
        }
        
        // Update flight available seats
        $updateFlightQuery = "UPDATE flights SET available_seats = available_seats - :count WHERE id = :flight_id";
        $updateFlightStmt = $conn->prepare($updateFlightQuery);
        $updateFlightStmt->execute([
            ':count' => count($data['passengers']),
            ':flight_id' => $data['flight_id']
        ]);
        
        $conn->commit();
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Booking created successfully',
            'booking_id' => $bookingId,
            'booking_reference' => $bookingRef
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
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
        
        // Create payment record
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
        $updateBookingQuery = "UPDATE bookings SET booking_status = 'confirmed' WHERE id = :booking_id";
        $updateBookingStmt = $conn->prepare($updateBookingQuery);
        $updateBookingStmt->execute([':booking_id' => $data['booking_id']]);
        
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

function updateBooking() {
    global $conn;
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['booking_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Booking ID required']);
        return;
    }
    
    try {
        $updateFields = [];
        $params = [':booking_id' => $data['booking_id']];
        
        if (isset($data['cabin_class'])) {
            $updateFields[] = "cabin_class = :cabin_class";
            $params[':cabin_class'] = $data['cabin_class'];
        }
        
        if (isset($data['special_requests'])) {
            $updateFields[] = "special_requests = :special_requests";
            $params[':special_requests'] = $data['special_requests'];
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            return;
        }
        
        $query = "UPDATE bookings SET " . implode(', ', $updateFields) . " WHERE id = :booking_id";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Booking updated successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function cancelBooking() {
    global $conn;
    
    $bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
    
    if (!$bookingId) {
        http_response_code(400);
        echo json_encode(['error' => 'Booking ID required']);
        return;
    }
    
    try {
        $conn->beginTransaction();
        
        // Get booking details to restore seats
        $getBookingQuery = "SELECT flight_id, number_of_passengers FROM bookings WHERE id = :booking_id";
        $getBookingStmt = $conn->prepare($getBookingQuery);
        $getBookingStmt->execute([':booking_id' => $bookingId]);
        $booking = $getBookingStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking) {
            http_response_code(404);
            echo json_encode(['error' => 'Booking not found']);
            return;
        }
        
        // Update booking status
        $cancelQuery = "UPDATE bookings SET booking_status = 'cancelled' WHERE id = :booking_id";
        $cancelStmt = $conn->prepare($cancelQuery);
        $cancelStmt->execute([':booking_id' => $bookingId]);
        
        // Restore flight seats
        $restoreSeatsQuery = "UPDATE flights SET available_seats = available_seats + :count WHERE id = :flight_id";
        $restoreSeatsStmt = $conn->prepare($restoreSeatsQuery);
        $restoreSeatsStmt->execute([
            ':count' => $booking['number_of_passengers'],
            ':flight_id' => $booking['flight_id']
        ]);
        
        // Process refund
        $refundQuery = "UPDATE payments SET payment_status = 'refunded' WHERE booking_id = :booking_id";
        $refundStmt = $conn->prepare($refundQuery);
        $refundStmt->execute([':booking_id' => $bookingId]);
        
        $conn->commit();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Booking cancelled successfully. Refund will be processed within 5-7 business days.'
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>