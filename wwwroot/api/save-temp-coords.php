<?php

# Extract DreamHost project root: /home/username/domain.com
preg_match('#^(/home/[^/]+/[^/]+)#', __DIR__, $matches);
include_once $matches[1] . '/prepend.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$coords = $_POST['temp_coords'] ?? '';
$redirect = $_POST['redirect'] ?? '/login/register.php';

// Store in session
if (!empty($coords)) {
    $_SESSION['temp_coords'] = $coords;
    $_SESSION['temp_coords_timestamp'] = time();
}

// Redirect to registration/login
header("Location: $redirect");
exit;
