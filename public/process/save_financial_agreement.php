<?php
/**
 * Process Financial Agreement Form
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
$paymentMethod = sanitizeInput($_POST['paymentMethod'] ?? '');

$financeReadUnderstood = sanitizeInput($_POST['financeReadUnderstood'] ?? '');
$agreeToTerms = sanitizeInput($_POST['agreeToTerms'] ?? '');
$authorizeInsurance = sanitizeInput($_POST['authorizeInsurance'] ?? '');
$responsibleForBalance = sanitizeInput($_POST['responsibleForBalance'] ?? '');

$financialSignatureName = sanitizeInput($_POST['financialSignatureName'] ?? '');
$financialSignature = sanitizeInput($_POST['financialSignature'] ?? '');
$financialSignatureDate = sanitizeInput($_POST['financialSignatureDate'] ?? '');
$relationshipToPatient = sanitizeInput($_POST['relationshipToPatient'] ?? '');

// Validate required fields
if (empty($paymentMethod) || empty($financeReadUnderstood) || empty($agreeToTerms) ||
    empty($authorizeInsurance) || empty($responsibleForBalance) ||
    empty($financialSignatureName) || empty($financialSignature) ||
    empty($financialSignatureDate) || empty($relationshipToPatient)) {
    die("Error: All required fields must be filled out.");
}

// Prepare SQL statement
$sql = "INSERT INTO financial_agreements (
    patient_id,
    payment_method,
    read_understood, agree_to_terms, authorize_insurance, responsible_for_balance,
    signature_name, signature, signature_date, relationship_to_patient
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    die("Error: Unable to save financial agreement. Please try again.");
}

// Bind parameters
$stmt->bind_param(
    "isssssssss",
    $patientId,
    $paymentMethod,
    $financeReadUnderstood, $agreeToTerms, $authorizeInsurance, $responsibleForBalance,
    $financialSignatureName, $financialSignature, $financialSignatureDate, $relationshipToPatient
);

// Execute statement
if ($stmt->execute()) {
    // Update form submission tracking
    $updateSql = "UPDATE form_submissions
                  SET financial_completed = TRUE,
                      financial_completed_at = NOW()
                  WHERE patient_id = ?";

    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("i", $patientId);
    $updateStmt->execute();
    $updateStmt->close();

    $stmt->close();
    closeDBConnection($conn);

    // Redirect to additional consents form
    header("Location: ../forms/5_additional_consents.php");
    exit();
} else {
    error_log("Execute failed: " . $stmt->error);
    die("Error: Unable to save financial agreement. Please try again.");
}
?>
