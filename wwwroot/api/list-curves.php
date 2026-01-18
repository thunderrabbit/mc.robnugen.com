<?php
/**
 * List all curve files from the curves/ directory
 * Returns JSON array of curve options with filename and display name
 */

header('Content-Type: application/json');

// Path to curves directory (relative to project root)
$curvesDir = dirname(__DIR__, 2) . '/curves';

$response = [
    'success' => false,
    'curves' => [],
    'error' => null
];

try {
    // Check if curves directory exists
    if (!is_dir($curvesDir)) {
        throw new Exception("Curves directory not found: $curvesDir");
    }

    // Scan directory for .txt files
    $files = scandir($curvesDir);

    foreach ($files as $file) {
        // Skip . and .. and non-.txt files
        if ($file === '.' || $file === '..' || !str_ends_with($file, '.txt')) {
            continue;
        }

        // Remove "curve_" prefix and ".txt" suffix for display
        $display = $file;
        if (str_starts_with($display, 'curve_')) {
            $display = substr($display, 6); // Remove "curve_"
        }
        if (str_ends_with($display, '.txt')) {
            $display = substr($display, 0, -4); // Remove ".txt"
        }

        $response['curves'][] = [
            'filename' => $file,
            'display' => $display
        ];
    }

    // Sort by display name for logical ordering
    usort($response['curves'], function($a, $b) {
        return strcmp($a['display'], $b['display']);
    });

    $response['success'] = true;

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
