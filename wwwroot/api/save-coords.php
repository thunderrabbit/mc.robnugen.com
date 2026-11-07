<?php

# Must include here because DH runs FastCGI https://www.phind.com/search?cache=zfj8o8igbqvaj8cm91wp1b7k
# Extract DreamHost project root: /home/username/domain.com
preg_match('#^(/home/[^/]+/[^/]+)#', __DIR__, $matches);
include_once $matches[1] . '/prepend.php';

/**
 * Set up by prepend.php.
 *
 * @var \Config $config
 * @var \Auth\IsLoggedIn $is_logged_in
 */

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
$input = file_get_contents('php://input') ?: '';
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON', 'message' => json_last_error_msg()]);
    exit;
}

// A body that is valid JSON but not an object fails the field checks below.
if (!is_array($data)) {
    $data = [];
}
$name = $data['name'] ?? null;
$description = $data['description'] ?? null;
$coordinates = $data['coordinates'] ?? null;
$chunks = $data['chunks'] ?? null;
$requested_set_id = $data['coordinate_set_id'] ?? null;

// Validate required fields
if (!is_string($name) || empty($name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Validation Error', 'message' => 'Coordinate set name is required']);
    exit;
}

if (empty($coordinates) || !is_array($coordinates)) {
    http_response_code(400);
    echo json_encode(['error' => 'Validation Error', 'message' => 'Coordinates array is required']);
    exit;
}

// Get user ID
$user_id = $is_logged_in->loggedInID();

// Check if this is an UPDATE or INSERT
$is_update = !empty($requested_set_id);
$coordinate_set_id = null;

$pdo = \Database\Base::getPDO($config);

try {
    // Start transaction
    $pdo->beginTransaction();

    if ($is_update) {
        // UPDATE existing coordinate set
        // Anything non-numeric is id 0, which no set has, so it is refused below.
        $coordinate_set_id = is_numeric($requested_set_id) ? (int)$requested_set_id : 0;

        // Verify this set belongs to the user
        $stmt = $pdo->prepare("
            SELECT coordinate_set_id FROM coordinate_sets
            WHERE coordinate_set_id = :set_id AND user_id = :user_id
        ");
        $stmt->execute([
            ':set_id' => $coordinate_set_id,
            ':user_id' => $user_id
        ]);

        if (!$stmt->fetch()) {
            throw new Exception("Coordinate set not found or access denied");
        }

        // Delete existing coordinates
        $stmt = $pdo->prepare("
            DELETE FROM coordinates WHERE coordinate_set_id = :set_id
        ");
        $stmt->execute([':set_id' => $coordinate_set_id]);

        // Delete existing chunks
        $stmt = $pdo->prepare("
            DELETE FROM chunks WHERE coordinate_set_id = :set_id
        ");
        $stmt->execute([':set_id' => $coordinate_set_id]);

        // Update the coordinate set's updated_at timestamp
        $stmt = $pdo->prepare("
            UPDATE coordinate_sets
            SET updated_at = NOW()
            WHERE coordinate_set_id = :set_id
        ");
        $stmt->execute([':set_id' => $coordinate_set_id]);
    } else {
        // INSERT new coordinate set
        $stmt = $pdo->prepare("
            INSERT INTO coordinate_sets (user_id, name, description, created_at, updated_at)
            VALUES (:user_id, :name, :description, NOW(), NOW())
        ");

        $stmt->execute([
            ':user_id' => $user_id,
            ':name' => $name,
            ':description' => is_string($description) ? $description : null
        ]);

        $coordinate_set_id = $pdo->lastInsertId();
    }

    // Insert coordinates
    $stmt = $pdo->prepare("
        INSERT INTO coordinates
        (coordinate_set_id, x, y, z, label, color, segment_id, sort, created_at)
        VALUES
        (:coordinate_set_id, :x, :y, :z, :label, :color, :segment_id, :sort, NOW())
    ");

    foreach ($coordinates as $index => $coord) {
        // Validate coordinate structure
        if (
            !is_array($coord)
            || !is_numeric($coord['x'] ?? null)
            || !is_numeric($coord['y'] ?? null)
            || !is_numeric($coord['z'] ?? null)
        ) {
            throw new Exception("Invalid coordinate at index $index: x, y, z are required");
        }

        $stmt->execute([
            ':coordinate_set_id' => $coordinate_set_id,
            ':x' => (int)$coord['x'],
            ':y' => (int)$coord['y'],
            ':z' => (int)$coord['z'],
            ':label' => $coord['label'] ?? null,
            ':color' => $coord['color'] ?? null,
            ':segment_id' => is_numeric($coord['segmentId'] ?? null) ? (int)$coord['segmentId'] : null,
            ':sort' => $index
        ]);
    }

    // Insert chunks if provided
    if (!empty($chunks) && is_array($chunks)) {
        $stmt = $pdo->prepare("
            INSERT INTO chunks (coordinate_set_id, chunk_x, chunk_z, chunk_type)
            VALUES (:coordinate_set_id, :chunk_x, :chunk_z, :chunk_type)
        ");

        foreach ($chunks as $chunk) {
            // Validate chunk structure
            if (
                !is_array($chunk)
                || !is_numeric($chunk['chunk_x'] ?? null)
                || !is_numeric($chunk['chunk_z'] ?? null)
                || !isset($chunk['chunk_type'])
            ) {
                throw new Exception("Invalid chunk data: chunk_x, chunk_z, and chunk_type are required");
            }

            // Validate chunk_type
            if (!in_array($chunk['chunk_type'], ['mine', 'unavailable'], true)) {
                throw new Exception("Invalid chunk_type: must be 'mine' or 'unavailable'");
            }

            $stmt->execute([
                ':coordinate_set_id' => $coordinate_set_id,
                ':chunk_x' => (int)$chunk['chunk_x'],
                ':chunk_z' => (int)$chunk['chunk_z'],
                ':chunk_type' => $chunk['chunk_type']
            ]);
        }
    }

    // Commit transaction
    $pdo->commit();

    // Return success response
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Coordinate set saved successfully',
        'coordinate_set_id' => (int)$coordinate_set_id,
        'coordinates_count' => count($coordinates),
        'chunks_count' => is_array($chunks) ? count($chunks) : 0
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
