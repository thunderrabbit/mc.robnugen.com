<?php
/**
 * Load a specific curve file and return its coordinates
 * Accepts: filename (GET or POST)
 * Returns: JSON with coordinates array
 */

header('Content-Type: application/json');

// Path to curves directory (relative to project root)
$curvesDir = dirname(__DIR__, 2) . '/curves';

$response = [
    'success' => false,
    'coordinates' => [],
    'metadata' => [],
    'error' => null
];

try {
    // Get filename from request
    $filename = $_GET['filename'] ?? $_POST['filename'] ?? null;

    if (!$filename) {
        throw new Exception("No filename provided");
    }

    // Security: prevent directory traversal
    if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
        throw new Exception("Invalid filename");
    }

    // Ensure .txt extension
    if (!str_ends_with($filename, '.txt')) {
        $filename .= '.txt';
    }

    $filepath = $curvesDir . '/' . $filename;

    // Check if file exists
    if (!file_exists($filepath)) {
        throw new Exception("Curve file not found: $filename");
    }

    // Read and parse file
    $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comment lines
        if (empty($line) || $line[0] === '#') {
            // Extract metadata from comments
            if (preg_match('/^#\s*(\w+):\s*(.+)$/', $line, $matches)) {
                $response['metadata'][$matches[1]] = $matches[2];
            }
            continue;
        }

        // Parse coordinate line: [x, y, z]
        if (preg_match('/^\[(-?\d+),\s*(-?\d+),\s*(-?\d+)\]$/', $line, $matches)) {
            $response['coordinates'][] = [
                'x' => (int)$matches[1],
                'y' => (int)$matches[2],
                'z' => (int)$matches[3]
            ];
        }
    }

    if (empty($response['coordinates'])) {
        throw new Exception("No valid coordinates found in file");
    }

    $response['success'] = true;

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
