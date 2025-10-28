<?php
/**
 * SKYJET - Cars API
 */

require_once __DIR__ . '/../config.php';

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

switch ($action) {
    case 'list':
        listCars();
        break;

    case 'details':
        if (!$id) {
            errorResponse('Car ID required', 400);
        }
        getCarDetails($id);
        break;

    case 'search':
        searchCars();
        break;

    default:
        errorResponse('Invalid action', 400);
}

function listCars() {
    global $conn;

    try {
        $query = "SELECT * FROM cars WHERE available_count > 0 ORDER BY daily_rate ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute();

        $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

        successResponse([
            'cars' => $cars,
            'total' => count($cars)
        ], 'Cars retrieved successfully');

    } catch (PDOException $e) {
        errorResponse('Database error: ' . $e->getMessage(), 500);
    }
}

function getCarDetails($id) {
    global $conn;

    try {
        $query = "SELECT * FROM cars WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id]);

        $car = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$car) {
            errorResponse('Car not found', 404);
        }

        successResponse(['car' => $car], 'Car details retrieved');

    } catch (PDOException $e) {
        errorResponse('Database error: ' . $e->getMessage(), 500);
    }
}

function searchCars() {
    global $conn;

    $type = $_GET['type'] ?? null;
    $minPrice = $_GET['min_price'] ?? 0;
    $maxPrice = $_GET['max_price'] ?? 9999;

    try {
        $query = "SELECT * FROM cars WHERE available_count > 0 AND daily_rate BETWEEN ? AND ?";
        $params = [$minPrice, $maxPrice];

        if ($type) {
            $query .= " AND car_type = ?";
            $params[] = $type;
        }

        $query .= " ORDER BY daily_rate ASC";

        $stmt = $conn->prepare($query);
        $stmt->execute($params);

        $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

        successResponse([
            'cars' => $cars,
            'total' => count($cars)
        ], 'Search completed');

    } catch (PDOException $e) {
        errorResponse('Database error: ' . $e->getMessage(), 500);
    }
}
?>
