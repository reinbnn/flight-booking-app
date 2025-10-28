<?php
require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    getDashboardData();
} else if ($method === 'OPTIONS') {
    http_response_code(200);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

function getDashboardData() {
    global $conn;
    
    try {
        // Get flight statistics
        $flightStats = getFlightStats();
        
        // Get booking statistics
        $bookingStats = getBookingStats();
        
        // Get revenue
        $revenueStats = getRevenueStats();
        
        // Get user statistics
        $userStats = getUserStats();
        
        // Get recent bookings
        $recentBookings = getRecentBookings();
        
        // Get top routes
        $topRoutes = getTopRoutes();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => [
                'flights' => $flightStats,
                'bookings' => $bookingStats,
                'revenue' => $revenueStats,
                'users' => $userStats,
                'recent_bookings' => $recentBookings,
                'top_routes' => $topRoutes
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getFlightStats() {
    global $conn;
    
    $query = "SELECT 
              COUNT(*) as total_flights,
              SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_flights,
              SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_flights,
              SUM(CASE WHEN status = 'delayed' THEN 1 ELSE 0 END) as delayed_flights
              FROM flights";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getBookingStats() {
    global $conn;
    
    $query = "SELECT 
              COUNT(*) as total_bookings,
              SUM(CASE WHEN booking_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
              SUM(CASE WHEN booking_status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
              SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings
              FROM bookings";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getRevenueStats() {
    global $conn;
    
    $query = "SELECT 
              SUM(amount) as total_revenue,
              COUNT(CASE WHEN payment_status = 'completed' THEN 1 END) as completed_payments,
              COUNT(CASE WHEN payment_status = 'refunded' THEN 1 END) as refunded_payments,
              SUM(CASE WHEN payment_status = 'refunded' THEN amount ELSE 0 END) as refunded_amount
              FROM payments";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getUserStats() {
    global $conn;
    
    $query = "SELECT 
              COUNT(*) as total_users,
              SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
              SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_users,
              SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as regular_users
              FROM users";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getRecentBookings() {
    global $conn;
    
    $query = "SELECT 
              b.id,
              b.booking_reference,
              u.first_name,
              u.last_name,
              f.flight_number,
              b.total_price,
              b.booking_status,
              b.created_at
              FROM bookings b
              JOIN users u ON b.user_id = u.id
              JOIN flights f ON b.flight_id = f.id
              ORDER BY b.created_at DESC
              LIMIT 10";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTopRoutes() {
    global $conn;
    
    $query = "SELECT 
              CONCAT(f.departure_airport, ' → ', f.arrival_airport) as route,
              COUNT(b.id) as booking_count,
              SUM(b.total_price) as total_revenue
              FROM bookings b
              JOIN flights f ON b.flight_id = f.id
              WHERE b.booking_status = 'confirmed'
              GROUP BY f.departure_airport, f.arrival_airport
              ORDER BY booking_count DESC
              LIMIT 5";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>