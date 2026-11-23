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
    echo json_encode(['error' => 'Unauthorized', 'message' => 'You must be logged in']);
    exit;
}

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed', 'message' => 'Only GET requests are accepted']);
    exit;
}

// Get coordinate set ID from query parameter
$set_id = $_GET['set_id'] ?? null;

if (!$set_id || !is_numeric($set_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Validation Error', 'message' => 'set_id parameter is required']);
    exit;
}

// Get user ID
$user_id = $is_logged_in->loggedInID();

try {
    $pdo = \Database\Base::getPDO($config);

    // First verify this set belongs to the user
    $set = new \Database\CoordinateSetRepository($pdo)->findSetForUser((int)$set_id, $user_id);

    if (!$set) {
        http_response_code(404);
        echo json_encode(['error' => 'Not Found', 'message' => 'Coordinate set not found']);
        exit;
    }

    // Get all coordinates for this set
    $coordinates = new \Database\CoordinateRepository($pdo)->listCoordinatesForSet((int)$set_id);

    // Format coordinates
    $formatted_coords = array_map(fn(array $coord): array => [
        'x' => (int)$coord['x'],
        'y' => (int)$coord['y'],
        'z' => (int)$coord['z'],
        'label' => $coord['label'],
        'color' => $coord['color'],
        'segmentId' => $coord['segment_id'] !== null ? (int)$coord['segment_id'] : null
    ], $coordinates);

    // Get all chunks for this set
    $chunks = new \Database\ChunkRepository($pdo)->listChunksForSet((int)$set_id);

    // Format chunks
    $formatted_chunks = array_map(fn(array $chunk): array => [
        'chunk_x' => (int)$chunk['chunk_x'],
        'chunk_z' => (int)$chunk['chunk_z'],
        'chunk_type' => $chunk['chunk_type']
    ], $chunks);

    echo json_encode([
        'success' => true,
        'set' => [
            'coordinate_set_id' => (int)$set['coordinate_set_id'],
            'name' => $set['name'],
            'description' => $set['description'],
            'created_at' => $set['created_at'],
            'updated_at' => $set['updated_at']
        ],
        'coordinates' => $formatted_coords,
        'chunks' => $formatted_chunks
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database Error',
        'message' => $e->getMessage()
    ]);
}
