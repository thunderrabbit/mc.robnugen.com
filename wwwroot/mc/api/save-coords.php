<?php

# Must include here because DH runs FastCGI https://www.phind.com/search?cache=zfj8o8igbqvaj8cm91wp1b7k
# Extract DreamHost project root: /home/username/domain.com
preg_match('#^(/home/[^/]+/[^/]+)#', __DIR__, $matches);
include_once $matches[1] . '/prepend.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!$is_logged_in->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'message' => 'You must be logged in to save coordinates']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed', 'message' => 'Only POST requests are accepted']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON', 'message' => json_last_error_msg()]);
    exit;
}

// Validate required fields
if (empty($data['name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Validation Error', 'message' => 'Coordinate set name is required']);
    exit;
}

if (empty($data['coordinates']) || !is_array($data['coordinates'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Validation Error', 'message' => 'Coordinates array is required']);
    exit;
}

// Get user ID
$user_id = $is_logged_in->loggedInID();

try {
    $db = new \Database\Base($config);
    $pdo = $db->getPDO();

    // Start transaction
    $pdo->beginTransaction();

    // Insert coordinate set
    $stmt = $pdo->prepare("
        INSERT INTO coordinate_sets (user_id, name, description, created_at, updated_at)
        VALUES (:user_id, :name, :description, NOW(), NOW())
    ");

    $stmt->execute([
        ':user_id' => $user_id,
        ':name' => $data['name'],
        ':description' => $data['description'] ?? null
    ]);

    $coordinate_set_id = $pdo->lastInsertId();

    // Insert coordinates
    $stmt = $pdo->prepare("
        INSERT INTO coordinates
        (coordinate_set_id, x, y, z, label, color, segment_id, sort, created_at)
        VALUES
        (:coordinate_set_id, :x, :y, :z, :label, :color, :segment_id, :sort, NOW())
    ");

    foreach ($data['coordinates'] as $index => $coord) {
        // Validate coordinate structure
        if (!isset($coord['x']) || !isset($coord['y']) || !isset($coord['z'])) {
            throw new Exception("Invalid coordinate at index $index: x, y, z are required");
        }

        $stmt->execute([
            ':coordinate_set_id' => $coordinate_set_id,
            ':x' => (int)$coord['x'],
            ':y' => (int)$coord['y'],
            ':z' => (int)$coord['z'],
            ':label' => $coord['label'] ?? null,
            ':color' => $coord['color'] ?? null,
            ':segment_id' => isset($coord['segmentId']) ? (int)$coord['segmentId'] : null,
            ':sort' => $index
        ]);
    }

    // Commit transaction
    $pdo->commit();

    // Return success response
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Coordinate set saved successfully',
        'coordinate_set_id' => (int)$coordinate_set_id,
        'coordinates_count' => count($data['coordinates'])
    ]);

} catch (Exception $e) {
    // Rollback on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'error' => 'Database Error',
        'message' => $e->getMessage()
    ]);
}
