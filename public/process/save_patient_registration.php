<?php
/**
 * Process Patient Registration Form
 */

session_start();
require_once '../../config/database.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method");
}

// Get database connection
$conn = getDBConnection();

// Sanitize and validate inputs
$firstName = sanitizeInput($_POST['firstName'] ?? '');
$middleName = sanitizeInput($_POST['middleName'] ?? '');
$lastName = sanitizeInput($_POST['lastName'] ?? '');
$dateOfBirth = sanitizeInput($_POST['dateOfBirth'] ?? '');
$age = intval($_POST['age'] ?? 0);
$gender = sanitizeInput($_POST['gender'] ?? '');
$ssn = sanitizeInput($_POST['ssn'] ?? '');

$address = sanitizeInput($_POST['address'] ?? '');
$city = sanitizeInput($_POST['city'] ?? '');
$state = sanitizeInput($_POST['state'] ?? '');
$zipCode = sanitizeInput($_POST['zipCode'] ?? '');
$homePhone = sanitizeInput($_POST['homePhone'] ?? '');
$cellPhone = sanitizeInput($_POST['cellPhone'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '');
$maritalStatus = sanitizeInput($_POST['maritalStatus'] ?? '');

$emergencyContactName = sanitizeInput($_POST['emergencyContactName'] ?? '');
$emergencyContactPhone = sanitizeInput($_POST['emergencyContactPhone'] ?? '');
$emergencyRelationship = sanitizeInput($_POST['emergencyRelationship'] ?? '');

$insuranceProvider = sanitizeInput($_POST['insuranceProvider'] ?? '');
$policyNumber = sanitizeInput($_POST['policyNumber'] ?? '');
$groupNumber = sanitizeInput($_POST['groupNumber'] ?? '');
$policyHolderName = sanitizeInput($_POST['policyHolderName'] ?? '');
$policyHolderDOB = sanitizeInput($_POST['policyHolderDOB'] ?? '');

$pcpName = sanitizeInput($_POST['pcpName'] ?? '');
$pcpPhone = sanitizeInput($_POST['pcpPhone'] ?? '');

$reasonForVisit = sanitizeInput($_POST['reasonForVisit'] ?? '');
$allergies = sanitizeInput($_POST['allergies'] ?? '');
$currentMedications = sanitizeInput($_POST['currentMedications'] ?? '');

// Validate required fields
if (empty($firstName) || empty($lastName) || empty($dateOfBirth) || empty($gender) ||
    empty($address) || empty($city) || empty($state) || empty($zipCode) ||
    empty($cellPhone) || empty($email) || empty($reasonForVisit) ||
    empty($emergencyContactName) || empty($emergencyContactPhone) || empty($emergencyRelationship)) {
    die("Error: All required fields must be filled out.");
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Error: Invalid email format.");
}

// Prepare SQL statement
$sql = "INSERT INTO patients (
    first_name, middle_name, last_name, date_of_birth, age, gender, ssn,
    address, city, state, zip_code, home_phone, cell_phone, email, marital_status,
    emergency_contact_name, emergency_contact_phone, emergency_relationship,
    insurance_provider, policy_number, group_number, policy_holder_name, policy_holder_dob,
    pcp_name, pcp_phone,
    reason_for_visit, allergies, current_medications,
    drchrono_sync_status
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    die("Error: Unable to save patient information. Please try again.");
}

// Bind parameters
$stmt->bind_param(
    "ssssisssssssssssssssssssssss",
    $firstName, $middleName, $lastName, $dateOfBirth, $age, $gender, $ssn,
    $address, $city, $state, $zipCode, $homePhone, $cellPhone, $email, $maritalStatus,
    $emergencyContactName, $emergencyContactPhone, $emergencyRelationship,
    $insuranceProvider, $policyNumber, $groupNumber, $policyHolderName, $policyHolderDOB,
    $pcpName, $pcpPhone,
    $reasonForVisit, $allergies, $currentMedications
);

// Execute statement
if ($stmt->execute()) {
    $patientId = $stmt->insert_id;
    $_SESSION['patient_id'] = $patientId;

    // Create form submission tracking record
    $submissionSql = "INSERT INTO form_submissions (
        patient_id, registration_completed, registration_completed_at,
        session_id, ip_address, user_agent
    ) VALUES (?, TRUE, NOW(), ?, ?, ?)";

    $submissionStmt = $conn->prepare($submissionSql);
    $sessionId = session_id();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $submissionStmt->bind_param("isss", $patientId, $sessionId, $ipAddress, $userAgent);
    $submissionStmt->execute();
    $submissionStmt->close();

    $stmt->close();
    closeDBConnection($conn);

    // Redirect to medical history form
    header("Location: ../forms/2_medical_history.php");
    exit();
} else {
    error_log("Execute failed: " . $stmt->error);
    die("Error: Unable to save patient information. Please try again.");
}
?>
