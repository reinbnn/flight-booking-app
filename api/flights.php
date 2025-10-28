<?php
/**
 * SKYJET - Flights API
 */

require_once __DIR__ . '/../config.php';

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

switch ($action) {
    case 'list':
        listFlights();
        break;

    case 'details':
        if (!$id) {
            errorResponse('Flight ID required', 400);
        }
        getFlightDetails($id);
        break;

    case 'search':
        searchFlights();
        break;

    default:
        errorResponse('Invalid action', 400);
}

function listFlights() {
    global $conn;

    try {
        $query = "SELECT * FROM flights ORDER BY departure_time ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute();

        $flights = $stmt->fetchAll(PDO::FETCH_ASSOC);

        successResponse([
            'flights' => $flights,
            'total' => count($flights)
        ], 'Flights retrieved successfully');

    } catch (PDOException $e) {
        errorResponse('Database error: ' . $e->getMessage(), 500);
    }
}

function getFlightDetails($id) {
    global $conn;

    try {
        $query = "SELECT * FROM flights WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id]);

        $flight = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$flight) {
            errorResponse('Flight not found', 404);
        }

        successResponse(['flight' => $flight], 'Flight details retrieved');

    } catch (PDOException $e) {
        errorResponse('Database error: ' . $e->getMessage(), 500);
    }
}

function searchFlights() {
    global $conn;

    $departure = $_GET['departure'] ?? null;
    $arrival = $_GET['arrival'] ?? null;
    $date = $_GET['date'] ?? null;

    if (!$departure || !$arrival) {
        errorResponse('Departure and arrival airports required', 400);
    }

    try {
        $query = "SELECT * FROM flights WHERE departure_airport = ? AND arrival_airport = ?";
        $params = [$departure, $arrival];

        if ($date) {
            $query .= " AND DATE(departure_time) = ?";
            $params[] = $date;
        }

        $query .= " ORDER BY departure_time ASC";

        $stmt = $conn->prepare($query);
        $stmt->execute($params);

        $flights = $stmt->fetchAll(PDO::FETCH_ASSOC);

        successResponse([
            'flights' => $flights,
            'total' => count($flights)
        ], 'Search completed');

    } catch (PDOException $e) {
        errorResponse('Database error: ' . $e->getMessage(), 500);
    }
}
?>
