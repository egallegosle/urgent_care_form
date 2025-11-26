<?php
/**
 * Process Patient Consent Form
 */

session_start();
require_once '../../config/database.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method");
}

// Check if patient_id exists in session
if (!isset($_SESSION['patient_id'])) {
    die("Error: Patient information not found. Please start from the beginning.");
}

$patientId = $_SESSION['patient_id'];

// Get database connection
$conn = getDBConnection();

// Sanitize and validate inputs
$readAndUnderstood = sanitizeInput($_POST['readAndUnderstood'] ?? '');
$questionsAnswered = sanitizeInput($_POST['questionsAnswered'] ?? '');
$voluntaryConsent = sanitizeInput($_POST['voluntaryConsent'] ?? '');

$patientSignatureName = sanitizeInput($_POST['patientSignatureName'] ?? '');
$patientSignature = sanitizeInput($_POST['patientSignature'] ?? '');
$signatureDate = sanitizeInput($_POST['signatureDate'] ?? '');
$signatureTime = sanitizeInput($_POST['signatureTime'] ?? '');

$guardianName = sanitizeInput($_POST['guardianName'] ?? '');
$guardianRelationship = sanitizeInput($_POST['guardianRelationship'] ?? '');
$guardianSignature = sanitizeInput($_POST['guardianSignature'] ?? '');
$guardianDate = sanitizeInput($_POST['guardianDate'] ?? '');

// Validate required fields
if (empty($readAndUnderstood) || empty($questionsAnswered) || empty($voluntaryConsent) ||
    empty($patientSignatureName) || empty($patientSignature) || empty($signatureDate) || empty($signatureTime)) {
    die("Error: All required fields must be filled out.");
}

// Prepare SQL statement
$sql = "INSERT INTO patient_consents (
    patient_id,
    read_and_understood, questions_answered, voluntary_consent,
    patient_signature_name, patient_signature, signature_date, signature_time,
    guardian_name, guardian_relationship, guardian_signature, guardian_date
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    die("Error: Unable to save consent form. Please try again.");
}

// Bind parameters
$stmt->bind_param(
    "isssssssssss",
    $patientId,
    $readAndUnderstood, $questionsAnswered, $voluntaryConsent,
    $patientSignatureName, $patientSignature, $signatureDate, $signatureTime,
    $guardianName, $guardianRelationship, $guardianSignature, $guardianDate
);

// Execute statement
if ($stmt->execute()) {
    // Update form submission tracking
    $updateSql = "UPDATE form_submissions
                  SET consent_completed = TRUE,
                      consent_completed_at = NOW()
                  WHERE patient_id = ?";

    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("i", $patientId);
    $updateStmt->execute();
    $updateStmt->close();

    $stmt->close();
    closeDBConnection($conn);

    // Redirect to financial agreement form
    header("Location: ../forms/4_financial_agreement.php");
    exit();
} else {
    error_log("Execute failed: " . $stmt->error);
    die("Error: Unable to save consent form. Please try again.");
}
?>
