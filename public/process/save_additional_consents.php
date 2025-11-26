<?php
/**
 * Process Additional Consents Form
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
$hipaaAcknowledged = sanitizeInput($_POST['hipaaAcknowledged'] ?? '');

// Communication preferences - array
$commPrefs = $_POST['communicationPrefs'] ?? [];
$communicationPreferences = implode(', ', array_map('sanitizeInput', $commPrefs));

// Contact methods - array
$contactMethods = $_POST['contactMethods'] ?? [];
$contactMethodsStr = implode(', ', array_map('sanitizeInput', $contactMethods));

$voicemailAuthorization = sanitizeInput($_POST['voicemailAuthorization'] ?? '');

$portalAccess = isset($_POST['portalAccess']) ? 'Yes' : 'No';
$portalEmail = sanitizeInput($_POST['portalEmail'] ?? '');

$authorizedPersonName = sanitizeInput($_POST['authorizedPersonName'] ?? '');
$authorizedPersonRelation = sanitizeInput($_POST['authorizedPersonRelation'] ?? '');
$authorizedPersonPhone = sanitizeInput($_POST['authorizedPersonPhone'] ?? '');
$authorizeDiscussion = isset($_POST['authorizeDiscussion']) ? 'Yes' : 'No';

$allFormsComplete = sanitizeInput($_POST['allFormsComplete'] ?? '');
$consentToAll = sanitizeInput($_POST['consentToAll'] ?? '');

$finalSignatureName = sanitizeInput($_POST['finalSignatureName'] ?? '');
$finalSignature = sanitizeInput($_POST['finalSignature'] ?? '');
$finalSignatureDate = sanitizeInput($_POST['finalSignatureDate'] ?? '');

// Validate required fields
if (empty($hipaaAcknowledged) || empty($allFormsComplete) || empty($consentToAll) ||
    empty($finalSignatureName) || empty($finalSignature) || empty($finalSignatureDate)) {
    die("Error: All required fields must be filled out.");
}

// Validate at least one contact method is selected
if (empty($contactMethodsStr)) {
    die("Error: Please select at least one contact method.");
}

// Prepare SQL statement
$sql = "INSERT INTO additional_consents (
    patient_id,
    hipaa_acknowledged,
    communication_preferences, contact_methods,
    voicemail_authorization,
    portal_access, portal_email,
    authorized_person_name, authorized_person_relation, authorized_person_phone, authorize_discussion,
    all_forms_complete, consent_to_all,
    signature_name, signature, signature_date
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    die("Error: Unable to save additional consents. Please try again.");
}

// Bind parameters
$stmt->bind_param(
    "isssssssssssssss",
    $patientId,
    $hipaaAcknowledged,
    $communicationPreferences, $contactMethodsStr,
    $voicemailAuthorization,
    $portalAccess, $portalEmail,
    $authorizedPersonName, $authorizedPersonRelation, $authorizedPersonPhone, $authorizeDiscussion,
    $allFormsComplete, $consentToAll,
    $finalSignatureName, $finalSignature, $finalSignatureDate
);

// Execute statement
if ($stmt->execute()) {
    // Update form submission tracking - mark all forms as complete
    $updateSql = "UPDATE form_submissions
                  SET additional_consents_completed = TRUE,
                      additional_consents_completed_at = NOW(),
                      all_forms_completed = TRUE,
                      completed_at = NOW()
                  WHERE patient_id = ?";

    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("i", $patientId);
    $updateStmt->execute();
    $updateStmt->close();

    $stmt->close();
    closeDBConnection($conn);

    // Redirect to success page
    header("Location: ../success.php");
    exit();
} else {
    error_log("Execute failed: " . $stmt->error);
    die("Error: Unable to save additional consents. Please try again.");
}
?>
