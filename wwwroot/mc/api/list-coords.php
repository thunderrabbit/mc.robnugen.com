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
    echo json_encode(['error' => 'Unauthorized', 'message' => 'You must be logged in']);
    exit;
}

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed', 'message' => 'Only GET requests are accepted']);
    exit;
}

// Get user ID
$user_id = $is_logged_in->loggedInID();

try {
    $pdo = \Database\Base::getPDO($config);

    // Get all coordinate sets for this user
    $stmt = $pdo->prepare("
        SELECT
            coordinate_set_id,
            name,
            description,
            created_at,
            updated_at,
            (SELECT COUNT(*) FROM coordinates WHERE coordinate_set_id = cs.coordinate_set_id) as coordinate_count
        FROM coordinate_sets cs
        WHERE user_id = :user_id
        ORDER BY updated_at DESC
    ");

    $stmt->execute([':user_id' => $user_id]);
    $sets = $stmt->fetchAll();

    // Format the response
    $formatted_sets = array_map(function($set) {
        return [
            'coordinate_set_id' => (int)$set['coordinate_set_id'],
            'name' => $set['name'],
            'description' => $set['description'],
            'coordinate_count' => (int)$set['coordinate_count'],
            'created_at' => $set['created_at'],
            'updated_at' => $set['updated_at']
        ];
    }, $sets);

    echo json_encode([
        'success' => true,
        'sets' => $formatted_sets
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database Error',
        'message' => $e->getMessage()
    ]);
}
