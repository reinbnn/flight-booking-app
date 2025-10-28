<?php
/**
 * SKYJET - Hotels API
 */

require_once __DIR__ . '/../config.php';

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

switch ($action) {
    case 'list':
        listHotels();
        break;

    case 'details':
        if (!$id) {
            errorResponse('Hotel ID required', 400);
        }
        getHotelDetails($id);
        break;

    case 'search':
        searchHotels();
        break;

    default:
        errorResponse('Invalid action', 400);
}

function listHotels() {
    global $conn;

    try {
        $query = "SELECT * FROM hotels ORDER BY rating DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();

        $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

        successResponse([
            'hotels' => $hotels,
            'total' => count($hotels)
        ], 'Hotels retrieved successfully');

    } catch (PDOException $e) {
        errorResponse('Database error: ' . $e->getMessage(), 500);
    }
}

function getHotelDetails($id) {
    global $conn;

    try {
        $query = "SELECT * FROM hotels WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id]);

        $hotel = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$hotel) {
            errorResponse('Hotel not found', 404);
        }

        successResponse(['hotel' => $hotel], 'Hotel details retrieved');

    } catch (PDOException $e) {
        errorResponse('Database error: ' . $e->getMessage(), 500);
    }
}

function searchHotels() {
    global $conn;

    $city = $_GET['city'] ?? null;
    $country = $_GET['country'] ?? null;

    if (!$city) {
        errorResponse('City parameter required', 400);
    }

    try {
        $query = "SELECT * FROM hotels WHERE city LIKE ?";
        $params = ["%$city%"];

        if ($country) {
            $query .= " AND country LIKE ?";
            $params[] = "%$country%";
        }

        $query .= " ORDER BY rating DESC";

        $stmt = $conn->prepare($query);
        $stmt->execute($params);

        $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

        successResponse([
            'hotels' => $hotels,
            'total' => count($hotels)
        ], 'Search completed');

    } catch (PDOException $e) {
        errorResponse('Database error: ' . $e->getMessage(), 500);
    }
}
?>
