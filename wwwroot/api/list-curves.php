<?php
/**
 * List all curve files from curves/ and curves_north/ directories
 * Returns JSON array of curve options with filename and display name
 */

header('Content-Type: application/json');

// Paths to curves directories (relative to project root)
$projectRoot = dirname(__DIR__, 2);
$curvesDir = $projectRoot . '/curves';
$curvesNorthDir = $projectRoot . '/curves_north';

$response = [
    'success' => false,
    'curves' => [],
    'error' => null
];

try {
    $allCurves = [];

    // Scan south-first curves directory
    if (is_dir($curvesDir)) {
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

            $allCurves[] = [
                'filename' => $file,
                'display' => 'SOUTH: ' . $display,
                'directory' => 'curves'
            ];
        }
    }

    // Scan north-first curves directory
    if (is_dir($curvesNorthDir)) {
        $files = scandir($curvesNorthDir);

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

            $allCurves[] = [
                'filename' => $file,
                'display' => 'NORTH: ' . $display,
                'directory' => 'curves_north'
            ];
        }
    }

    if (empty($allCurves)) {
        throw new Exception("No curve files found in curves/ or curves_north/ directories");
    }

    // Sort by display name for logical ordering
    usort($allCurves, function($a, $b) {
        return strcmp($a['display'], $b['display']);
    });

    $response['curves'] = $allCurves;
    $response['success'] = true;

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);

