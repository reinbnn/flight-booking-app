<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

// Get search parameters
$type = isset($_GET['type']) ? $_GET['type'] : 'flights';
$action = isset($_GET['action']) ? $_GET['action'] : 'search';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

try {
    switch ($type) {
        case 'flights':
            handleFlightSearch($pdo, $offset, $limit);
            break;
        case 'hotels':
            handleHotelSearch($pdo, $offset, $limit);
            break;
        case 'cars':
            handleCarSearch($pdo, $offset, $limit);
            break;
        default:
            sendError('Invalid search type', 400);
    }
} catch (Exception $e) {
    sendError($e->getMessage(), 500);
}

/**
 * Flight Search Handler
 */
function handleFlightSearch($pdo, $offset, $limit) {
    // Get filter parameters
    $departure = isset($_GET['departure']) ? $_GET['departure'] : '';
    $arrival = isset($_GET['arrival']) ? $_GET['arrival'] : '';
    $departure_date = isset($_GET['departure_date']) ? $_GET['departure_date'] : '';
    $return_date = isset($_GET['return_date']) ? $_GET['return_date'] : '';
    $min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
    $max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 999999;
    $min_seats = isset($_GET['min_seats']) ? (int)$_GET['min_seats'] : 1;
    $sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'departure_time';
    $sort_order = isset($_GET['sort_order']) ? strtoupper($_GET['sort_order']) : 'ASC';
    
    // Validate sort order
    if (!in_array($sort_order, ['ASC', 'DESC'])) {
        $sort_order = 'ASC';
    }
    
    // Validate sort columns
    $allowed_sorts = ['departure_time', 'price', 'available_seats', 'duration'];
    if (!in_array($sort_by, $allowed_sorts)) {
        $sort_by = 'departure_time';
    }

    // Build base query
    $where = [
        'status = "scheduled"',
        'available_seats >= ?'
    ];
    $params = [$min_seats];

    // Add filters
    if (!empty($departure)) {
        $where[] = 'departure_airport LIKE ?';
        $params[] = "%{$departure}%";
    }

    if (!empty($arrival)) {
        $where[] = 'arrival_airport LIKE ?';
        $params[] = "%{$arrival}%";
    }

    if (!empty($departure_date)) {
        $where[] = 'DATE(departure_time) = ?';
        $params[] = $departure_date;
    }

    if (!empty($return_date)) {
        $where[] = 'DATE(departure_time) <= ?';
        $params[] = $return_date;
    }

    $where[] = 'price BETWEEN ? AND ?';
    $params[] = $min_price;
    $params[] = $max_price;

    // Build query
    $where_clause = implode(' AND ', $where);
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM flights WHERE {$where_clause}";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get filtered results
    $sql = "
        SELECT 
            id,
            flight_number,
            departure_airport,
            arrival_airport,
            departure_time,
            arrival_time,
            total_seats,
            available_seats,
            price,
            status,
            TIMESTAMPDIFF(MINUTE, departure_time, arrival_time) as duration_minutes,
            created_at
        FROM flights 
        WHERE {$where_clause}
        ORDER BY {$sort_by} {$sort_order}
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $flights = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate duration in hours:minutes format
    foreach ($flights as &$flight) {
        $minutes = $flight['duration_minutes'];
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        $flight['duration'] = "{$hours}h {$mins}m";
    }

    // Send response
    $response = [
        'success' => true,
        'type' => 'flights',
        'data' => $flights,
        'pagination' => [
            'current_page' => ceil(($offset / 10) + 1),
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => ceil($total / $limit)
        ],
        'filters' => [
            'departure' => $departure,
            'arrival' => $arrival,
            'departure_date' => $departure_date,
            'return_date' => $return_date,
            'price_range' => [
                'min' => $min_price,
                'max' => $max_price
            ],
            'min_seats' => $min_seats
        ],
        'sorting' => [
            'by' => $sort_by,
            'order' => $sort_order
        ],
        'message' => "Found {$total} flights matching your criteria",
        'timestamp' => date('Y-m-d H:i:s')
    ];

    sendResponse($response);
}

/**
 * Hotel Search Handler
 */
function handleHotelSearch($pdo, $offset, $limit) {
    $city = isset($_GET['city']) ? $_GET['city'] : '';
    $country = isset($_GET['country']) ? $_GET['country'] : '';
    $check_in = isset($_GET['check_in']) ? $_GET['check_in'] : '';
    $check_out = isset($_GET['check_out']) ? $_GET['check_out'] : '';
    $min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
    $max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 999999;
    $min_rating = isset($_GET['min_rating']) ? (float)$_GET['min_rating'] : 0;
    $min_rooms = isset($_GET['min_rooms']) ? (int)$_GET['min_rooms'] : 1;
    $sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'rating';
    $sort_order = isset($_GET['sort_order']) ? strtoupper($_GET['sort_order']) : 'DESC';

    $where = ['available_rooms >= ?'];
    $params = [$min_rooms];

    if (!empty($city)) {
        $where[] = 'city LIKE ?';
        $params[] = "%{$city}%";
    }

    if (!empty($country)) {
        $where[] = 'country LIKE ?';
        $params[] = "%{$country}%";
    }

    $where[] = 'price_per_night BETWEEN ? AND ?';
    $params[] = $min_price;
    $params[] = $max_price;

    $where[] = 'CAST(rating AS DECIMAL(3,1)) >= ?';
    $params[] = $min_rating;

    $where_clause = implode(' AND ', $where);

    // Count query
    $count_query = "SELECT COUNT(*) as total FROM hotels WHERE {$where_clause}";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Allowed sorts
    $allowed_sorts = ['price_per_night', 'rating', 'available_rooms', 'hotel_name'];
    if (!in_array($sort_by, $allowed_sorts)) {
        $sort_by = 'rating';
    }

    $sql = "
        SELECT 
            id,
            hotel_name,
            city,
            country,
            rating,
            total_rooms,
            available_rooms,
            price_per_night,
            description,
            created_at
        FROM hotels 
        WHERE {$where_clause}
        ORDER BY {$sort_by} {$sort_order}
        LIMIT ? OFFSET ?
    ";

    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response = [
        'success' => true,
        'type' => 'hotels',
        'data' => $hotels,
        'pagination' => [
            'current_page' => ceil(($offset / 10) + 1),
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => ceil($total / $limit)
        ],
        'filters' => [
            'city' => $city,
            'country' => $country,
            'check_in' => $check_in,
            'check_out' => $check_out,
            'price_range' => ['min' => $min_price, 'max' => $max_price],
            'min_rating' => $min_rating,
            'min_rooms' => $min_rooms
        ],
        'sorting' => ['by' => $sort_by, 'order' => $sort_order],
        'message' => "Found {$total} hotels matching your criteria",
        'timestamp' => date('Y-m-d H:i:s')
    ];

    sendResponse($response);
}

/**
 * Car Search Handler
 */
function handleCarSearch($pdo, $offset, $limit) {
    $location = isset($_GET['location']) ? $_GET['location'] : '';
    $car_type = isset($_GET['car_type']) ? $_GET['car_type'] : '';
    $min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
    $max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 999999;
    $min_capacity = isset($_GET['min_capacity']) ? (int)$_GET['min_capacity'] : 1;
    $transmission = isset($_GET['transmission']) ? $_GET['transmission'] : '';
    $sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'daily_rate';
    $sort_order = isset($_GET['sort_order']) ? strtoupper($_GET['sort_order']) : 'ASC';

    $where = ['available_vehicles > 0'];
    $params = [];

    if (!empty($location)) {
        $where[] = 'location LIKE ?';
        $params[] = "%{$location}%";
    }

    if (!empty($car_type)) {
        $where[] = 'car_type = ?';
        $params[] = $car_type;
    }

    $where[] = 'daily_rate BETWEEN ? AND ?';
    $params[] = $min_price;
    $params[] = $max_price;

    if (!empty($transmission)) {
        $where[] = 'transmission = ?';
        $params[] = $transmission;
    }

    $where_clause = implode(' AND ', $where);

    // Count
    $count_query = "SELECT COUNT(*) as total FROM cars WHERE {$where_clause}";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $allowed_sorts = ['daily_rate', 'car_type', 'seating_capacity'];
    if (!in_array($sort_by, $allowed_sorts)) {
        $sort_by = 'daily_rate';
    }

    $sql = "
        SELECT 
            id,
            model,
            car_type,
            location,
            daily_rate,
            available_vehicles,
            total_vehicles,
            seating_capacity,
            transmission,
            fuel_type,
            features,
            created_at
        FROM cars 
        WHERE {$where_clause}
        ORDER BY {$sort_by} {$sort_order}
        LIMIT ? OFFSET ?
    ";

    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response = [
        'success' => true,
        'type' => 'cars',
        'data' => $cars,
        'pagination' => [
            'current_page' => ceil(($offset / 10) + 1),
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => ceil($total / $limit)
        ],
        'filters' => [
            'location' => $location,
            'car_type' => $car_type,
            'price_range' => ['min' => $min_price, 'max' => $max_price],
            'min_capacity' => $min_capacity,
            'transmission' => $transmission
        ],
        'sorting' => ['by' => $sort_by, 'order' => $sort_order],
        'message' => "Found {$total} cars matching your criteria",
        'timestamp' => date('Y-m-d H:i:s')
    ];

    sendResponse($response);
}

/**
 * Helper functions
 */
function sendResponse($data) {
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'code' => $code,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    exit;
}
?>
