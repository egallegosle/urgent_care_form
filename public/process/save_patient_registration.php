<?php
/**
 * Process Patient Registration Form
 * Handles both NEW patients (INSERT) and RETURNING patients (UPDATE)
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/returning_patient_functions.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method");
}

// Get database connection
$conn = getDBConnection();

// Check if this is an update (returning patient) or new insert
$is_update = isset($_POST['is_update']) && $_POST['is_update'] == '1';
$existing_patient_id = isset($_POST['existing_patient_id']) ? intval($_POST['existing_patient_id']) : null;

// Verify returning patient session
if ($is_update) {
    if (!isset($_SESSION['returning_patient_id']) || $_SESSION['returning_patient_id'] != $existing_patient_id) {
        die("Error: Invalid session. Please start over.");
    }
}

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

if ($is_update) {
    // ========================================
    // UPDATE EXISTING PATIENT RECORD
    // ========================================

    // First, get old data for change tracking
    $old_data_result = $conn->query("SELECT * FROM patients WHERE patient_id = " . $existing_patient_id);
    $old_data = $old_data_result->fetch_assoc();

    // Update patient record
    $sql = "UPDATE patients SET
        first_name = ?, middle_name = ?, last_name = ?, date_of_birth = ?, age = ?, gender = ?, ssn = ?,
        address = ?, city = ?, state = ?, zip_code = ?, home_phone = ?, cell_phone = ?, email = ?, marital_status = ?,
        emergency_contact_name = ?, emergency_contact_phone = ?, emergency_relationship = ?,
        insurance_provider = ?, policy_number = ?, group_number = ?, policy_holder_name = ?, policy_holder_dob = ?,
        pcp_name = ?, pcp_phone = ?,
        reason_for_visit = ?, allergies = ?, current_medications = ?,
        updated_at = NOW()
        WHERE patient_id = ?";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        die("Error: Unable to update patient information. Please try again.");
    }

    $stmt->bind_param(
        "ssssisssssssssssssssssssssssi",
        $firstName, $middleName, $lastName, $dateOfBirth, $age, $gender, $ssn,
        $address, $city, $state, $zipCode, $homePhone, $cellPhone, $email, $maritalStatus,
        $emergencyContactName, $emergencyContactPhone, $emergencyRelationship,
        $insuranceProvider, $policyNumber, $groupNumber, $policyHolderName, $policyHolderDOB,
        $pcpName, $pcpPhone,
        $reasonForVisit, $allergies, $currentMedications,
        $existing_patient_id
    );

    if ($stmt->execute()) {
        $_SESSION['patient_id'] = $existing_patient_id;

        // Track changes
        $new_data = [
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'date_of_birth' => $dateOfBirth,
            'age' => $age,
            'gender' => $gender,
            'ssn' => $ssn,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'zip_code' => $zipCode,
            'home_phone' => $homePhone,
            'cell_phone' => $cellPhone,
            'email' => $email,
            'marital_status' => $maritalStatus,
            'emergency_contact_name' => $emergencyContactName,
            'emergency_contact_phone' => $emergencyContactPhone,
            'emergency_relationship' => $emergencyRelationship,
            'insurance_provider' => $insuranceProvider,
            'policy_number' => $policyNumber,
            'group_number' => $groupNumber,
            'policy_holder_name' => $policyHolderName,
            'policy_holder_dob' => $policyHolderDOB,
            'pcp_name' => $pcpName,
            'pcp_phone' => $pcpPhone,
            'allergies' => $allergies,
            'current_medications' => $currentMedications
        ];

        $changes = trackDataChanges($old_data, $new_data);

        // Update visit record with changes and reason for visit
        if (isset($_SESSION['visit_id'])) {
            updateVisitChanges($conn, $_SESSION['visit_id'], $changes, $reasonForVisit);
        }

        // Update form submission tracking
        $submissionSql = "UPDATE form_submissions SET
            registration_completed = TRUE,
            registration_completed_at = NOW(),
            is_update = TRUE
            WHERE patient_id = ?";

        $submissionStmt = $conn->prepare($submissionSql);
        $submissionStmt->bind_param("i", $existing_patient_id);
        $submissionStmt->execute();
        $submissionStmt->close();

        $stmt->close();
        closeDBConnection($conn);

        // Redirect to medical history form
        header("Location: ../forms/2_medical_history.php");
        exit();
    } else {
        error_log("Update failed: " . $stmt->error);
        die("Error: Unable to update patient information. Please try again.");
    }

} else {
    // ========================================
    // INSERT NEW PATIENT RECORD
    // ========================================

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

    $stmt->bind_param(
        "ssssisssssssssssssssssssssss",
        $firstName, $middleName, $lastName, $dateOfBirth, $age, $gender, $ssn,
        $address, $city, $state, $zipCode, $homePhone, $cellPhone, $email, $maritalStatus,
        $emergencyContactName, $emergencyContactPhone, $emergencyRelationship,
        $insuranceProvider, $policyNumber, $groupNumber, $policyHolderName, $policyHolderDOB,
        $pcpName, $pcpPhone,
        $reasonForVisit, $allergies, $currentMedications
    );

    if ($stmt->execute()) {
        $patientId = $stmt->insert_id;
        $_SESSION['patient_id'] = $patientId;

        // Create initial visit record for new patient
        $visit_id = createVisitRecord(
            $conn,
            $patientId,
            'new',
            getUserIP(),
            getUserAgent(),
            session_id()
        );

        if ($visit_id) {
            $_SESSION['visit_id'] = $visit_id;

            // Update visit with reason
            updateVisitChanges($conn, $visit_id, ['count' => 0, 'json' => '{}'], $reasonForVisit);
        }

        // Create form submission tracking record
        $submissionSql = "INSERT INTO form_submissions (
            patient_id, visit_id, registration_completed, registration_completed_at,
            session_id, ip_address, user_agent, is_update
        ) VALUES (?, ?, TRUE, NOW(), ?, ?, ?, FALSE)";

        $submissionStmt = $conn->prepare($submissionSql);
        $sessionId = session_id();
        $ipAddress = getUserIP();
        $userAgent = getUserAgent();

        $submissionStmt->bind_param("iisss", $patientId, $visit_id, $sessionId, $ipAddress, $userAgent);
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
}
?>
