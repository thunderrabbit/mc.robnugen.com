<?php
/**
 * Delete (soft delete) a curve file by moving it to curves/deleted/
 * Accepts: filename (POST)
 * Returns: JSON with success status
 */

header('Content-Type: application/json');

// Path to curves directory (relative to project root)
$curvesDir = dirname(__DIR__, 2) . '/curves';
$deletedDir = $curvesDir . '/deleted';

$response = [
    'success' => false,
    'error' => null
];

try {
    // Get filename from request
    $filename = $_POST['filename'] ?? null;

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

    $sourcePath = $curvesDir . '/' . $filename;

    // Check if file exists
    if (!file_exists($sourcePath)) {
        throw new Exception("Curve file not found: $filename");
    }

    // Create deleted directory if it doesn't exist
    if (!is_dir($deletedDir)) {
        mkdir($deletedDir, 0755, true);
    }

    $destPath = $deletedDir . '/' . $filename;

    // If destination already exists, add timestamp
    if (file_exists($destPath)) {
        $pathInfo = pathinfo($filename);
        $timestamp = date('YmdHis');
        $destPath = $deletedDir . '/' . $pathInfo['filename'] . '_' . $timestamp . '.' . $pathInfo['extension'];
    }

    // Move file
    if (!rename($sourcePath, $destPath)) {
        throw new Exception("Failed to move file to deleted directory");
    }

    $response['success'] = true;

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
