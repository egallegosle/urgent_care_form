<?php
/**
 * Refresh session timeout
 */
session_start();

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Check if valid session exists
if (!isset($_SESSION['returning_patient_id'])) {
    http_response_code(401);
    exit;
}

// Refresh session timeout (30 minutes from now)
$_SESSION['session_expires'] = time() + (30 * 60);

http_response_code(200);
echo json_encode([
    'success' => true,
    'expires_at' => $_SESSION['session_expires']
]);
?>
