<?php
/**
 * Process Medical History Form
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
$smoke = sanitizeInput($_POST['smoke'] ?? '');
$smokingFrequency = sanitizeInput($_POST['smokingFrequency'] ?? '');
$alcohol = sanitizeInput($_POST['alcohol'] ?? '');
$alcoholFrequency = sanitizeInput($_POST['alcoholFrequency'] ?? '');

// Medical conditions - array
$conditions = $_POST['conditions'] ?? [];
$medicalConditions = implode(', ', array_map('sanitizeInput', $conditions));

$otherConditions = sanitizeInput($_POST['otherConditions'] ?? '');
$previousSurgeries = sanitizeInput($_POST['previousSurgeries'] ?? '');
$surgeryDetails = sanitizeInput($_POST['surgeryDetails'] ?? '');
$currentMedications = sanitizeInput($_POST['currentMedications'] ?? '');
$hasAllergies = sanitizeInput($_POST['hasAllergies'] ?? '');
$allergyDetails = sanitizeInput($_POST['allergyDetails'] ?? '');
$familyHistory = sanitizeInput($_POST['familyHistory'] ?? '');

// Validate required fields
if (empty($smoke) || empty($alcohol)) {
    die("Error: All required fields must be filled out.");
}

// Prepare SQL statement
$sql = "INSERT INTO medical_history (
    patient_id, smokes, smoking_frequency, drinks_alcohol, alcohol_frequency,
    medical_conditions, other_conditions,
    previous_surgeries, surgery_details,
    current_medications,
    has_allergies, allergy_details,
    family_history
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    die("Error: Unable to save medical history. Please try again.");
}

// Bind parameters
$stmt->bind_param(
    "issssssssssss",
    $patientId, $smoke, $smokingFrequency, $alcohol, $alcoholFrequency,
    $medicalConditions, $otherConditions,
    $previousSurgeries, $surgeryDetails,
    $currentMedications,
    $hasAllergies, $allergyDetails,
    $familyHistory
);

// Execute statement
if ($stmt->execute()) {
    // Update form submission tracking
    $updateSql = "UPDATE form_submissions
                  SET medical_history_completed = TRUE,
                      medical_history_completed_at = NOW()
                  WHERE patient_id = ?";

    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("i", $patientId);
    $updateStmt->execute();
    $updateStmt->close();

    $stmt->close();
    closeDBConnection($conn);

    // Redirect to consent form
    header("Location: ../forms/3_patient_consent.php");
    exit();
} else {
    error_log("Execute failed: " . $stmt->error);
    die("Error: Unable to save medical history. Please try again.");
}
?>
