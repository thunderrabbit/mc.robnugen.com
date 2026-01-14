<?php

# Must include here because DH runs FastCGI https://www.phind.com/search?cache=zfj8o8igbqvaj8cm91wp1b7k
# Extract DreamHost project root: /home/username/domain.com
preg_match('#^(/home/[^/]+/[^/]+)#', __DIR__, $matches);
include_once $matches[1] . '/prepend.php';

// SECURITY: Whitelist of demo coordinate sets that can be loaded
// TODO: allow any coordinate set owned by librarian (user_id = 4)
$DEMO_SET_WHITELIST = [12]; // coordinate_set_id = 12 owned by librarian

// Set JSON response header
header('Content-Type: application/json');

// Demo data is publicly accessible - no login required

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

if (!in_array((int)$set_id, $DEMO_SET_WHITELIST)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden', 'message' => 'This coordinate set is not available as a demo']);
    exit;
}

try {
    $pdo = \Database\Base::getPDO($config);

    // Fetch the coordinate set (NO user_id check - it's a demo)
    $stmt = $pdo->prepare("
        SELECT coordinate_set_id, name, description, created_at, updated_at
        FROM coordinate_sets
        WHERE coordinate_set_id = :set_id
    ");

    $stmt->execute([':set_id' => $set_id]);
    $set = $stmt->fetch();

    if (!$set) {
        http_response_code(404);
        echo json_encode(['error' => 'Not Found', 'message' => 'Demo coordinate set not found']);
        exit;
    }

    // Get all coordinates for this set
    $stmt = $pdo->prepare("
        SELECT
            coordinate_id,
            x, y, z,
            label,
            color,
            segment_id,
            sort
        FROM coordinates
        WHERE coordinate_set_id = :set_id
        ORDER BY sort ASC
    ");

    $stmt->execute([':set_id' => $set_id]);
    $coordinates = $stmt->fetchAll();

    // Format coordinates
    $formatted_coords = array_map(function($coord) {
        return [
            'x' => (int)$coord['x'],
            'y' => (int)$coord['y'],
            'z' => (int)$coord['z'],
            'label' => $coord['label'],
            'color' => $coord['color'],
            'segmentId' => $coord['segment_id'] !== null ? (int)$coord['segment_id'] : null
        ];
    }, $coordinates);

    // Get all chunks for this set
    $stmt = $pdo->prepare("
        SELECT chunk_x, chunk_z, chunk_type
        FROM chunks
        WHERE coordinate_set_id = :set_id
        ORDER BY chunk_type, chunk_x, chunk_z
    ");

    $stmt->execute([':set_id' => $set_id]);
    $chunks = $stmt->fetchAll();

    // Format chunks
    $formatted_chunks = array_map(function($chunk) {
        return [
            'chunk_x' => (int)$chunk['chunk_x'],
            'chunk_z' => (int)$chunk['chunk_z'],
            'chunk_type' => $chunk['chunk_type']
        ];
    }, $chunks);

    echo json_encode([
        'success' => true,
        'is_demo' => true, // Flag to indicate this is demo data
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
