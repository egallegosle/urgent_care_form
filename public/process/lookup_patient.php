<?php
/**
 * Process Patient Lookup Request
 * Handles returning patient authentication with rate limiting and security
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/returning_patient_functions.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../returning_patient.php?error=' . urlencode('Invalid request method'));
    exit;
}

// Get database connection
$conn = getDBConnection();

// Get client information for security
$ip_address = getUserIP();
$user_agent = getUserAgent();
$session_id = session_id();

// Check rate limiting BEFORE processing lookup
$rate_limit = checkRateLimit($conn, $ip_address, 5, 15);

if (!$rate_limit['allowed']) {
    $minutes_remaining = 15;
    if ($rate_limit['blocked_until']) {
        $blocked_timestamp = strtotime($rate_limit['blocked_until']);
        $minutes_remaining = ceil(($blocked_timestamp - time()) / 60);
    }

    $error_msg = "Too many lookup attempts. Please try again in {$minutes_remaining} minutes.";
    header('Location: ../returning_patient.php?error=' . urlencode($error_msg));
    exit;
}

// Sanitize and validate inputs
$email = trim($_POST['email'] ?? '');
$dob = trim($_POST['dateOfBirth'] ?? '');

// Validate required fields
if (empty($email) || empty($dob)) {
    header('Location: ../returning_patient.php?error=' . urlencode('Please fill in all required fields'));
    exit;
}

// Validate email format
if (!isValidEmail($email)) {
    header('Location: ../returning_patient.php?error=' . urlencode('Please enter a valid email address'));
    exit;
}

// Validate date format
if (!isValidDate($dob)) {
    header('Location: ../returning_patient.php?error=' . urlencode('Please enter a valid date of birth'));
    exit;
}

// Validate date is not in future
$dob_timestamp = strtotime($dob);
if ($dob_timestamp > time()) {
    header('Location: ../returning_patient.php?error=' . urlencode('Date of birth cannot be in the future'));
    exit;
}

// Validate date is reasonable (not more than 120 years ago)
$min_date = strtotime('-120 years');
if ($dob_timestamp < $min_date) {
    header('Location: ../returning_patient.php?error=' . urlencode('Please enter a valid date of birth'));
    exit;
}

// Attempt to look up patient
$patient = lookupPatient($conn, $email, $dob);

if ($patient === null) {
    // Patient not found
    logLookupAttempt($conn, $email, $dob, false, null, null, $ip_address, $user_agent, $session_id);

    $error_msg = "We couldn't find your records. Please check your email and date of birth, or register as a new patient.";
    header('Location: ../returning_patient.php?error=' . urlencode($error_msg) . '&email=' . urlencode($email));
    exit;
}

// Patient found! Log successful lookup
$patient_name = $patient['first_name'] . ' ' . $patient['last_name'];
logLookupAttempt($conn, $email, $dob, true, $patient['patient_id'], $patient_name, $ip_address, $user_agent, $session_id);

// Create new visit record
$visit_id = createVisitRecord($conn, $patient['patient_id'], 'returning', $ip_address, $user_agent, $session_id);

if (!$visit_id) {
    error_log("Failed to create visit record for patient " . $patient['patient_id']);
    header('Location: ../returning_patient.php?error=' . urlencode('An error occurred. Please try again.'));
    exit;
}

// Store patient information in session
$_SESSION['returning_patient_id'] = $patient['patient_id'];
$_SESSION['patient_id'] = $patient['patient_id']; // For compatibility with existing forms
$_SESSION['visit_id'] = $visit_id;
$_SESSION['is_returning_patient'] = true;
$_SESSION['patient_name'] = $patient_name;
$_SESSION['last_lookup_time'] = time();

// Set session timeout (30 minutes)
$_SESSION['session_expires'] = time() + (30 * 60);

// Redirect to confirmation page
header('Location: ../patient_found.php');
exit;
?>
