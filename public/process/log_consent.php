<?php
/**
 * Log consent action for returning patient
 */
session_start();

// Only accept POST with JSON
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Store consent timestamp in session
$_SESSION['returning_patient_consent_timestamp'] = time();
$_SESSION['returning_patient_consent_date'] = date('Y-m-d H:i:s');

http_response_code(200);
echo json_encode(['success' => true]);
?>
